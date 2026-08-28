<?php
/*+***********************************************************************************
 * Zalo OA -> Leads (test scope).
 * Supports:
 * 1) ZaloDemo registration message:
 *    Người đăng ký: ...
 *    Số điện thoại: ...
 *    Email: ...
 *    Đã đăng ký chương trình ZaloDemo.
 * 2) Scripted Q&A answers (business model / channel / email / phone).
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';

class Leads_ZaloOaLeadIngestService {

	const STATE_TABLE = 'bace_zalo_oa_state';

	public static function ensureSchema() {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS ' . self::STATE_TABLE . ' (
				oa_user_id VARCHAR(128) NOT NULL,
				oa_id VARCHAR(128) DEFAULT NULL,
				full_name VARCHAR(255) DEFAULT NULL,
				phone VARCHAR(64) DEFAULT NULL,
				email VARCHAR(255) DEFAULT NULL,
				business_model VARCHAR(255) DEFAULT NULL,
				sales_channel VARCHAR(64) DEFAULT NULL,
				answers_json MEDIUMTEXT,
				last_payload MEDIUMTEXT,
				leadid INT(19) DEFAULT NULL,
				is_completed TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME DEFAULT NULL,
				updated_at DATETIME DEFAULT NULL,
				PRIMARY KEY (oa_user_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	public static function ingestWebhook(array $payload) {
		self::ensureSchema();
		$ev = self::extractEvent($payload);
		if (!$ev['oa_user_id']) {
			return array('success' => true, 'ignored' => true, 'reason' => 'missing_oa_user_id');
		}
		$state = self::loadState($ev['oa_user_id']);
		$merged = self::mergeState($state, $ev, $payload);
		self::saveState($ev['oa_user_id'], $merged);

		if (!$merged['is_completed']) {
			return array(
				'success' => true,
				'pending' => true,
				'oa_user_id' => $ev['oa_user_id'],
				'missing' => self::missingFields($merged),
			);
		}

		$existingLeadId = !empty($merged['leadid']) ? (int) $merged['leadid'] : 0;
		$lead = self::upsertLeadFromState($merged, $existingLeadId > 0 ? $existingLeadId : null);
		$newLeadId = isset($lead['crmid']) ? (int) $lead['crmid'] : (isset($lead['id']) ? (int) $lead['id'] : 0);
		if ($newLeadId > 0) {
			$merged['leadid'] = $newLeadId;
		}
		$merged['is_completed'] = 1;
		self::saveState($ev['oa_user_id'], $merged);

		return array(
			'success' => true,
			'created' => $existingLeadId <= 0,
			'updated' => $existingLeadId > 0,
			'lead' => $lead,
			'oa_user_id' => $ev['oa_user_id'],
		);
	}

	protected static function upsertLeadFromState(array $state, $existingLeadId = null) {
		self::ensureCurrentUser();
		$name = trim((string) $state['full_name']);
		if (self::isPlaceholderName($name) && !empty($state['answers_json'])) {
			$decoded = json_decode($state['answers_json'], true);
			if (is_array($decoded) && !empty($decoded['registration_raw'])) {
				$reg = self::extractZaloRegistration((string) $decoded['registration_raw']);
				if (!empty($reg['full_name'])) {
					$name = $reg['full_name'];
				}
			}
		}
		if ($name === '') {
			$name = 'Khách Zalo OA';
		}
		$tags = array('zalo');
		$chan = self::normalizeSalesChannel($state['sales_channel']);
		if ($chan === 'online') {
			$tags[] = 'online';
		} elseif ($chan === 'offline') {
			$tags[] = 'offline';
		} elseif ($chan === 'ket_hop') {
			$tags[] = 'ket_hop';
		}

		$payload = array(
			'name' => $name,
			'phone' => (string) $state['phone'],
			'email' => (string) $state['email'],
			'companyName' => '-',
			'tags' => array_values(array_unique($tags)),
			'business_model' => (string) $state['business_model'],
			'qa_raw' => isset($state['answers_json']) ? $state['answers_json'] : '',
			'skip_potential' => 1, // test scope: only Leads
			'sheet_source' => 0,
			'force_create' => 0, // allow merge by phone/email
		);
		$recordId = ($existingLeadId !== null && (int) $existingLeadId > 0) ? (int) $existingLeadId : null;
		return Leads_ModernService::saveLead($payload, $recordId);
	}

	protected static function isPlaceholderName($name) {
		$n = trim((string) $name);
		if ($n === '') {
			return true;
		}
		$f = self::fold($n);
		return ($f === 'khach zalo oa' || $f === 'khach zalo');
	}

	/**
	 * Webhook runs without CRM login — seed admin user for saveLead().
	 */
	protected static function ensureCurrentUser() {
		global $current_user;
		if ($current_user && !empty($current_user->id)) {
			return;
		}
		$admin = Users::getActiveAdminUser();
		if (!$admin || empty($admin->id)) {
			throw new Exception('Không tìm thấy admin user để tạo Lead từ Zalo OA.');
		}
		$current_user = $admin;
		vglobal('current_user', $admin);
	}

	/**
	 * Complete when we have a valid VN mobile + email.
	 */
	protected static function missingFields(array $state) {
		$missing = array();
		$phone = trim((string) $state['phone']);
		if ($phone === '' || !self::isValidVnMobile($phone)) {
			$missing[] = 'phone';
		}
		if (trim((string) $state['email']) === '') {
			$missing[] = 'email';
		}
		return $missing;
	}

	protected static function mergeState(array $state, array $ev, array $payload) {
		$out = $state;
		if (!$out) {
			$out = self::emptyState();
		}
		// Drop previously stored garbage phones (ids / non-VN).
		if (!empty($out['phone']) && !self::isValidVnMobile($out['phone'])) {
			$out['phone'] = '';
		}

		$fromReg = !empty($ev['registration_raw']);
		if (!empty($ev['oa_id'])) {
			$out['oa_id'] = $ev['oa_id'];
		}

		$incomingName = trim((string) (isset($ev['full_name']) ? $ev['full_name'] : ''));
		$curName = trim((string) $out['full_name']);
		if ($incomingName !== '' && !self::isPlaceholderName($incomingName)) {
			if ($fromReg || $curName === '' || self::isPlaceholderName($curName)) {
				$out['full_name'] = $incomingName;
			}
		}
		// Last chance: registration matched but name regex missed — re-parse raw block.
		if ($fromReg && self::isPlaceholderName($out['full_name'])) {
			$regAgain = self::extractZaloRegistration((string) $ev['registration_raw']);
			if (!empty($regAgain['full_name'])) {
				$out['full_name'] = $regAgain['full_name'];
			}
		}

		$incomingPhone = trim((string) (isset($ev['phone']) ? $ev['phone'] : ''));
		if ($incomingPhone !== '' && self::isValidVnMobile($incomingPhone)) {
			$curPhone = trim((string) $out['phone']);
			if ($fromReg || $curPhone === '' || !self::isValidVnMobile($curPhone)) {
				$out['phone'] = $incomingPhone;
			}
		}

		if (!empty($ev['email'])) {
			if ($fromReg || empty($out['email'])) {
				$out['email'] = $ev['email'];
			}
		}
		if (!empty($ev['business_model'])) {
			$out['business_model'] = $ev['business_model'];
		}
		if (!empty($ev['sales_channel'])) {
			$out['sales_channel'] = $ev['sales_channel'];
		}

		$answers = array();
		if (!empty($out['answers_json'])) {
			$decoded = json_decode($out['answers_json'], true);
			if (is_array($decoded)) {
				$answers = $decoded;
			}
		}
		foreach (array('business_model', 'sales_channel', 'email', 'phone', 'full_name') as $k) {
			if (!empty($out[$k])) {
				$answers[$k] = $out[$k];
			}
		}
		if ($fromReg) {
			$answers['registration_raw'] = $ev['registration_raw'];
		}
		$out['answers_json'] = json_encode($answers, JSON_UNESCAPED_UNICODE);
		$out['last_payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
		$out['is_completed'] = empty(self::missingFields($out)) ? 1 : 0;
		return $out;
	}

	protected static function emptyState() {
		return array(
			'oa_id' => '',
			'full_name' => '',
			'phone' => '',
			'email' => '',
			'business_model' => '',
			'sales_channel' => '',
			'answers_json' => '',
			'last_payload' => '',
			'leadid' => 0,
			'is_completed' => 0,
		);
	}

	protected static function extractEvent(array $payload) {
		$ev = array(
			'oa_user_id' => '',
			'oa_id' => '',
			'full_name' => '',
			'phone' => '',
			'email' => '',
			'business_model' => '',
			'sales_channel' => '',
			'registration_raw' => '',
		);

		$flat = self::flatten($payload);
		$eventName = '';
		foreach (array('event_name', 'event', 'eventName') as $ek) {
			if (!empty($flat[$ek])) {
				$eventName = strtolower(trim((string) $flat[$ek]));
				break;
			}
		}

		$senderId = !empty($flat['sender.id']) ? trim((string) $flat['sender.id']) : '';
		$recipientId = !empty($flat['recipient.id']) ? trim((string) $flat['recipient.id']) : '';

		// OA → user (confirmation): user is recipient. User → OA: user is sender.
		$isOaOutbound = ($eventName !== '' && strpos($eventName, 'oa_send') === 0);
		if ($isOaOutbound && $recipientId !== '') {
			$ev['oa_user_id'] = $recipientId;
			if ($senderId !== '') {
				$ev['oa_id'] = $senderId;
			}
		} else {
			foreach (array('sender.id', 'source.uid', 'from.id', 'user_id_by_app', 'user_id', 'uid') as $key) {
				if (!empty($flat[$key])) {
					$ev['oa_user_id'] = trim((string) $flat[$key]);
					break;
				}
			}
			if ($ev['oa_user_id'] === '' && $recipientId !== '') {
				$ev['oa_user_id'] = $recipientId;
			}
			if ($senderId !== '' && $recipientId !== '' && $ev['oa_user_id'] === $senderId) {
				$ev['oa_id'] = $recipientId;
			}
		}

		foreach (array('oa_id', 'oa.id', 'app.oa_id', 'recipient.oa_id') as $key) {
			if ($ev['oa_id'] === '' && !empty($flat[$key])) {
				$ev['oa_id'] = trim((string) $flat[$key]);
			}
		}

		$messageText = '';
		if (!empty($flat['message.text'])) {
			$messageText = trim((string) $flat['message.text']);
		} elseif (!empty($flat['text'])) {
			$messageText = trim((string) $flat['text']);
		}
		// JSON sometimes stores literal "\n" sequences.
		$messageText = str_replace(array("\\r\\n", "\\n", "\\r"), array("\n", "\n", "\n"), $messageText);

		// Q&A free-text: only use primary message body (avoid msg_id / ids looking like phones).
		$qaText = $messageText;

		$reg = self::extractZaloRegistration($messageText);
		if (!empty($reg['full_name'])) {
			$ev['full_name'] = $reg['full_name'];
		}
		if (!empty($reg['phone'])) {
			$ev['phone'] = $reg['phone'];
		}
		if (!empty($reg['email'])) {
			$ev['email'] = $reg['email'];
		}
		if (!empty($reg['matched'])) {
			$ev['registration_raw'] = $messageText;
			// Registration template is sent by OA → use recipient as lead key.
			if ($recipientId !== '' && $senderId !== '' && $ev['oa_user_id'] === $senderId) {
				$ev['oa_user_id'] = $recipientId;
				$ev['oa_id'] = $senderId;
			}
		}

		if ($ev['full_name'] === '') {
			foreach (array('sender.name', 'sender.display_name', 'contact.name', 'shared_info.name', 'user.name') as $key) {
				if (!empty($flat[$key])) {
					$ev['full_name'] = trim((string) $flat[$key]);
					break;
				}
			}
		}

		if ($ev['email'] === '' && $qaText !== '') {
			$email = self::extractEmail($qaText);
			if ($email !== '') {
				$ev['email'] = $email;
			}
		}
		if ($ev['phone'] === '' && $qaText !== '') {
			$phone = self::extractPhone($qaText);
			if ($phone !== '') {
				$ev['phone'] = $phone;
			}
		}

		$bm = self::extractBusinessModel($qaText);
		if ($bm !== '') {
			$ev['business_model'] = $bm;
		}
		$ch = self::extractSalesChannel($qaText);
		if ($ch !== '') {
			$ev['sales_channel'] = $ch;
		}

		return $ev;
	}

	/**
	 * Parse ZaloDemo / OA registration confirmation block.
	 * Example:
	 * Người đăng ký: Huy Đặng
	 * Số điện thoại: +84906345554
	 * Email: a1@abc.com
	 * Đã đăng ký chương trình ZaloDemo.
	 */
	protected static function extractZaloRegistration($text) {
		$out = array(
			'matched' => false,
			'full_name' => '',
			'phone' => '',
			'email' => '',
		);
		$text = trim((string) $text);
		if ($text === '') {
			return $out;
		}
		$text = str_replace(array("\\r\\n", "\\n", "\\r"), array("\n", "\n", "\n"), $text);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$fold = self::fold($text);
		$looksLikeReg = (strpos($fold, 'nguoi dang ky') !== false)
			|| (strpos($fold, 'nguoi dang ki') !== false)
			|| (strpos($fold, 'da dang ky chuong trinh') !== false)
			|| (strpos($fold, 'da dang ki chuong trinh') !== false)
			|| (strpos($fold, 'so dien thoai') !== false && strpos($fold, 'email') !== false);
		if (!$looksLikeReg) {
			return $out;
		}
		$out['matched'] = true;
		$out['full_name'] = self::extractRegistrantName($text);

		if (preg_match('/số\s*điện\s*thoại\s*[:：]\s*([+\d][\d\s.\-]{7,}\d)/iu', $text, $m)) {
			$out['phone'] = self::extractPhone($m[1]);
		} elseif (preg_match('/so\s*dien\s*thoai\s*[:：]\s*([+\d][\d\s.\-]{7,}\d)/iu', $fold, $m2)) {
			$out['phone'] = self::extractPhone($m2[1]);
		}
		if ($out['phone'] === '') {
			$out['phone'] = self::extractPhone($text);
		}

		if (preg_match('/e-?mail\s*[:：]\s*([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/iu', $text, $m)) {
			$out['email'] = strtolower(trim($m[1]));
		}
		if ($out['email'] === '') {
			$out['email'] = self::extractEmail($text);
		}

		return $out;
	}

	/**
	 * Extract name after "Người đăng ký:" — line-based + folded label match (Unicode-safe).
	 */
	protected static function extractRegistrantName($text) {
		$text = trim((string) $text);
		if ($text === '') {
			return '';
		}
		$lines = preg_split('/\R/u', $text);
		if (!is_array($lines)) {
			$lines = array($text);
		}
		foreach ($lines as $line) {
			$line = trim((string) $line);
			if ($line === '') {
				continue;
			}
			$lineFold = self::fold($line);
			if (!preg_match('/nguoi\s*dang\s*k[iy]\b/u', $lineFold)) {
				continue;
			}
			// Take original text after first colon on this line.
			if (preg_match('/[:：]\s*(.+)$/u', $line, $m)) {
				$name = trim($m[1], " \t\"'“”");
				$name = preg_replace('/\s+/u', ' ', $name);
				$nameFold = self::fold($name);
				if ($name === '' || self::isPlaceholderName($name)) {
					continue;
				}
				if (strpos($nameFold, 'so dien thoai') === 0 || strpos($nameFold, 'email') === 0) {
					continue;
				}
				if (strpos($nameFold, 'da dang ky') === 0 || strpos($nameFold, 'da dang ki') === 0) {
					continue;
				}
				return $name;
			}
		}

		// Fallback: whole-block regex (accented + folded).
		if (preg_match('/người\s*đăng\s*k[ýíìỉị]\s*[:：]\s*([^\r\n]+)/iu', $text, $m)) {
			$name = trim($m[1], " \t\"'“”");
			if ($name !== '' && !self::isPlaceholderName($name)) {
				return $name;
			}
		}
		if (preg_match('/nguoi\s*dang\s*k[iy]\s*[:：]\s*([^\r\n]+)/iu', self::fold($text), $m2)) {
			// Folded match loses accents — recover from original by locating colon near "đăng".
			if (preg_match('/đăng\s*k[ýíìỉịy]\s*[:：]\s*([^\r\n]+)/iu', $text, $m3)) {
				$name = trim($m3[1], " \t\"'“”");
				if ($name !== '' && !self::isPlaceholderName($name)) {
					return $name;
				}
			}
		}
		return '';
	}

	protected static function extractBusinessModel($text) {
		$t = self::fold($text);
		if (strpos($t, 'nguoi dang ky') !== false || strpos($t, 'da dang ky chuong trinh') !== false) {
			return '';
		}
		if (strpos($t, 'xe day') !== false) {
			return 'Xe đẩy';
		}
		if (strpos($t, 'cua hang') !== false) {
			return 'Cửa hàng';
		}
		if (preg_match('/\bonline\b/u', $t)) {
			return 'Online';
		}
		return '';
	}

	protected static function extractSalesChannel($text) {
		$t = self::fold($text);
		if (strpos($t, 'nguoi dang ky') !== false || strpos($t, 'da dang ky chuong trinh') !== false) {
			return '';
		}
		if (strpos($t, 'ket hop') !== false || strpos($t, 'kethop') !== false) {
			return 'Kết hợp';
		}
		if (preg_match('/\boffline\b/u', $t)) {
			return 'Offline';
		}
		if (preg_match('/\bonline\b/u', $t)) {
			return 'Online';
		}
		return '';
	}

	protected static function normalizeSalesChannel($value) {
		$f = self::fold($value);
		if (strpos($f, 'ket hop') !== false) {
			return 'ket_hop';
		}
		if (strpos($f, 'off') !== false) {
			return 'offline';
		}
		if (strpos($f, 'on') !== false) {
			return 'online';
		}
		return '';
	}

	protected static function extractEmail($text) {
		if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) $text, $m)) {
			return strtolower(trim($m[0]));
		}
		return '';
	}

	/**
	 * Normalize to 0xxxxxxxxx VN mobile, or empty if not a real mobile.
	 */
	protected static function extractPhone($text) {
		if (!is_string($text) || $text === '') {
			return '';
		}
		preg_match_all('/(\+?\d[\d\s\.\-]{7,}\d)/', $text, $m);
		if (empty($m[1])) {
			return '';
		}
		foreach ($m[1] as $raw) {
			$normalized = self::normalizeVnMobile($raw);
			if ($normalized !== '') {
				return $normalized;
			}
		}
		return '';
	}

	protected static function normalizeVnMobile($raw) {
		$digits = preg_replace('/\D+/', '', (string) $raw);
		if ($digits === '') {
			return '';
		}
		if (strpos($digits, '84') === 0 && strlen($digits) >= 11) {
			$digits = '0' . substr($digits, 2);
		}
		if (!self::isValidVnMobile($digits)) {
			return '';
		}
		return $digits;
	}

	protected static function isValidVnMobile($phone) {
		$digits = preg_replace('/\D+/', '', (string) $phone);
		if ($digits === '') {
			return false;
		}
		if (strpos($digits, '84') === 0 && strlen($digits) >= 11) {
			$digits = '0' . substr($digits, 2);
		}
		// VN mobile: 0 + (3|5|7|8|9) + 8 digits
		return (bool) preg_match('/^0[35789]\d{8}$/', $digits);
	}

	protected static function flatten($data, $prefix = '') {
		$out = array();
		if (!is_array($data)) {
			return $out;
		}
		foreach ($data as $k => $v) {
			$key = $prefix === '' ? (string) $k : ($prefix . '.' . $k);
			if (is_array($v)) {
				$out = array_merge($out, self::flatten($v, $key));
			} else {
				$out[$key] = $v;
			}
		}
		return $out;
	}

	protected static function fold($s) {
		$s = trim(mb_strtolower((string) $s, 'UTF-8'));
		$map = array(
			'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
			'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
			'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
			'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
			'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
			'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
			'đ'=>'d',
		);
		$s = strtr($s, $map);
		$s = preg_replace('/[^a-z0-9\+\@\.\-\s]+/u', ' ', $s);
		return trim(preg_replace('/\s+/', ' ', $s));
	}

	protected static function loadState($oaUserId) {
		$adb = PearDatabase::getInstance();
		$rs = $adb->pquery(
			'SELECT * FROM ' . self::STATE_TABLE . ' WHERE oa_user_id = ? LIMIT 1',
			array((string) $oaUserId)
		);
		if (!$rs || $adb->num_rows($rs) < 1) {
			return array();
		}
		return array(
			'oa_id' => (string) $adb->query_result($rs, 0, 'oa_id'),
			'full_name' => (string) $adb->query_result($rs, 0, 'full_name'),
			'phone' => (string) $adb->query_result($rs, 0, 'phone'),
			'email' => (string) $adb->query_result($rs, 0, 'email'),
			'business_model' => (string) $adb->query_result($rs, 0, 'business_model'),
			'sales_channel' => (string) $adb->query_result($rs, 0, 'sales_channel'),
			'answers_json' => (string) $adb->query_result($rs, 0, 'answers_json'),
			'last_payload' => (string) $adb->query_result($rs, 0, 'last_payload'),
			'leadid' => (int) $adb->query_result($rs, 0, 'leadid'),
			'is_completed' => (int) $adb->query_result($rs, 0, 'is_completed'),
		);
	}

	protected static function saveState($oaUserId, array $state) {
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT oa_user_id FROM ' . self::STATE_TABLE . ' WHERE oa_user_id = ? LIMIT 1', array($oaUserId));
		$params = array(
			(string) $state['oa_id'],
			(string) $state['full_name'],
			(string) $state['phone'],
			(string) $state['email'],
			(string) $state['business_model'],
			(string) $state['sales_channel'],
			(string) $state['answers_json'],
			(string) $state['last_payload'],
			(int) $state['leadid'],
			(int) $state['is_completed'],
			$now,
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE ' . self::STATE_TABLE . '
				 SET oa_id = ?, full_name = ?, phone = ?, email = ?, business_model = ?, sales_channel = ?,
					 answers_json = ?, last_payload = ?, leadid = ?, is_completed = ?, updated_at = ?
				 WHERE oa_user_id = ?',
				array_merge($params, array((string) $oaUserId))
			);
			return;
		}
		$adb->pquery(
			'INSERT INTO ' . self::STATE_TABLE . '
			 (oa_user_id, oa_id, full_name, phone, email, business_model, sales_channel, answers_json, last_payload, leadid, is_completed, created_at, updated_at)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
			array_merge(array((string) $oaUserId), $params, array($now))
		);
	}
}
