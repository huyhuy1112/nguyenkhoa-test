<?php
/*+***********************************************************************************
 * Zalo OA contact form -> Leads.
 * Parses OA confirmation message after user submits the Zalo OA form:
 *   Họ Tên, Số điện thoại, Địa chỉ, Mô hình kinh doanh
 * Lead source: tag zalo (leadsource).
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
				address VARCHAR(500) DEFAULT NULL,
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
		try {
			$cols = $adb->pquery("SHOW COLUMNS FROM " . self::STATE_TABLE . " LIKE 'address'", array());
			if (!$cols || $adb->num_rows($cols) === 0) {
				$adb->pquery(
					'ALTER TABLE ' . self::STATE_TABLE . ' ADD COLUMN address VARCHAR(500) DEFAULT NULL AFTER email',
					array()
				);
			}
		} catch (Exception $e) {
			// column may already exist
		}
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
		if ($name === '' || self::isPlaceholderName($name)) {
			$name = 'Khách Zalo OA';
		}

		$payload = array(
			'name' => $name,
			'phone' => (string) $state['phone'],
			'email' => (string) $state['email'],
			'address' => (string) $state['address'],
			'companyName' => '-',
			'tags' => array('zalo'),
			'business_model' => (string) $state['business_model'],
			'qa_raw' => isset($state['answers_json']) ? $state['answers_json'] : '',
			'skip_potential' => 1,
			'sheet_source' => 0,
			'force_create' => 0,
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
	 * Complete when we have name + valid VN mobile (email optional).
	 */
	protected static function missingFields(array $state) {
		$missing = array();
		$name = trim((string) $state['full_name']);
		if ($name === '' || self::isPlaceholderName($name)) {
			$missing[] = 'full_name';
		}
		$phone = trim((string) $state['phone']);
		if ($phone === '' || !self::isValidVnMobile($phone)) {
			$missing[] = 'phone';
		}
		return $missing;
	}

	protected static function mergeState(array $state, array $ev, array $payload) {
		$out = $state;
		if (!$out) {
			$out = self::emptyState();
		}
		if (!empty($out['phone']) && !self::isValidVnMobile($out['phone'])) {
			$out['phone'] = '';
		}

		$fromForm = !empty($ev['contact_form_raw']);
		if (!empty($ev['oa_id'])) {
			$out['oa_id'] = $ev['oa_id'];
		}

		$incomingName = trim((string) (isset($ev['full_name']) ? $ev['full_name'] : ''));
		if ($incomingName !== '' && !self::isPlaceholderName($incomingName)) {
			if ($fromForm || trim((string) $out['full_name']) === '' || self::isPlaceholderName($out['full_name'])) {
				$out['full_name'] = $incomingName;
			}
		}

		$incomingPhone = trim((string) (isset($ev['phone']) ? $ev['phone'] : ''));
		if ($incomingPhone !== '' && self::isValidVnMobile($incomingPhone)) {
			if ($fromForm || trim((string) $out['phone']) === '' || !self::isValidVnMobile($out['phone'])) {
				$out['phone'] = $incomingPhone;
			}
		}

		if (!empty($ev['email'])) {
			$out['email'] = $ev['email'];
		}
		if (!empty($ev['address'])) {
			$out['address'] = $ev['address'];
		}
		if (!empty($ev['business_model'])) {
			$out['business_model'] = $ev['business_model'];
		}

		$answers = array('source' => 'zalo_oa_form');
		if (!empty($out['answers_json'])) {
			$decoded = json_decode($out['answers_json'], true);
			if (is_array($decoded)) {
				$answers = array_merge($decoded, $answers);
			}
		}
		foreach (array('full_name', 'phone', 'email', 'address', 'business_model') as $k) {
			if (!empty($out[$k])) {
				$answers[$k] = $out[$k];
			}
		}
		if ($fromForm) {
			$answers['contact_form_raw'] = $ev['contact_form_raw'];
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
			'address' => '',
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
			'address' => '',
			'business_model' => '',
			'contact_form_raw' => '',
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

		$form = self::extractZaloOaContactForm($messageText);
		if (!empty($form['matched'])) {
			if (!empty($form['full_name'])) {
				$ev['full_name'] = $form['full_name'];
			}
			if (!empty($form['phone'])) {
				$ev['phone'] = $form['phone'];
			}
			if (!empty($form['address'])) {
				$ev['address'] = $form['address'];
			}
			if (!empty($form['business_model'])) {
				$ev['business_model'] = $form['business_model'];
			}
			$ev['contact_form_raw'] = $messageText;
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

		if ($ev['phone'] === '' && $messageText !== '') {
			$phone = self::extractPhone($messageText);
			if ($phone !== '') {
				$ev['phone'] = $phone;
			}
		}

		return $ev;
	}

	/**
	 * Parse Zalo OA contact form confirmation.
	 * Example:
	 * Bạn đã gửi thông tin cho OA ... với nội dung:
	 * Họ Tên: Đặng Quốc Huy
	 * Số điện thoại: 0906345551
	 * Địa chỉ: 213 LTT, Phường Phước Long, Thành phố Hồ Chí Minh
	 * Mô hình kinh doanh: cà phê sân vườn
	 */
	protected static function extractZaloOaContactForm($text) {
		$out = array(
			'matched' => false,
			'full_name' => '',
			'phone' => '',
			'address' => '',
			'business_model' => '',
		);
		$text = self::normalizeMessageText($text);
		if ($text === '') {
			return $out;
		}

		$fold = self::fold($text);
		$looksLikeForm = (strpos($fold, 'ban da gui thong tin cho oa') !== false)
			|| (strpos($fold, 'chia se thong tin') !== false)
			|| (strpos($fold, 'ho ten') !== false && strpos($fold, 'so dien thoai') !== false);
		if (!$looksLikeForm) {
			return $out;
		}

		$out['matched'] = true;
		$out['full_name'] = self::extractLabeledValue($text, array(
			'họ\s*tên',
			'ho\s*ten',
		));
		$phoneRaw = self::extractLabeledValue($text, array(
			'số\s*điện\s*thoại',
			'so\s*dien\s*thoai',
		));
		$out['phone'] = self::extractPhone($phoneRaw !== '' ? $phoneRaw : $text);
		$out['address'] = self::extractLabeledValue($text, array(
			'địa\s*chỉ',
			'dia\s*chi',
		));
		$out['business_model'] = self::extractLabeledValue($text, array(
			'mô\s*hình\s*kinh\s*doanh',
			'mo\s*hinh\s*kinh\s*doanh',
		));

		return $out;
	}

	protected static function normalizeMessageText($text) {
		$text = trim((string) $text);
		if ($text === '') {
			return '';
		}
		$text = str_replace(array("\\r\\n", "\\n", "\\r"), array("\n", "\n", "\n"), $text);
		return html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * @param string $text
	 * @param string[] $labelPatterns regex fragments (unicode), without trailing colon
	 */
	protected static function extractLabeledValue($text, array $labelPatterns) {
		$text = self::normalizeMessageText($text);
		foreach ($labelPatterns as $label) {
			$pattern = '/(?:\*\*)?\s*' . $label . '\s*(?:\*\*)?\s*[:：]\s*([^\r\n]+)/iu';
			if (preg_match($pattern, $text, $m)) {
				$value = trim((string) $m[1]);
				$value = preg_replace('/\*+/u', '', $value);
				$value = trim($value, " \t\"'“”");
				$value = preg_replace('/\s+/u', ' ', $value);
				if ($value !== '') {
					return $value;
				}
			}
		}
		return '';
	}

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
			'address' => self::columnValue($adb, $rs, 'address'),
			'business_model' => (string) $adb->query_result($rs, 0, 'business_model'),
			'sales_channel' => (string) $adb->query_result($rs, 0, 'sales_channel'),
			'answers_json' => (string) $adb->query_result($rs, 0, 'answers_json'),
			'last_payload' => (string) $adb->query_result($rs, 0, 'last_payload'),
			'leadid' => (int) $adb->query_result($rs, 0, 'leadid'),
			'is_completed' => (int) $adb->query_result($rs, 0, 'is_completed'),
		);
	}

	protected static function columnValue($adb, $rs, $column) {
		$val = $adb->query_result($rs, 0, $column);
		return $val !== null && $val !== '' ? (string) $val : '';
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
			(string) $state['address'],
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
				 SET oa_id = ?, full_name = ?, phone = ?, email = ?, address = ?, business_model = ?, sales_channel = ?,
					 answers_json = ?, last_payload = ?, leadid = ?, is_completed = ?, updated_at = ?
				 WHERE oa_user_id = ?',
				array_merge($params, array((string) $oaUserId))
			);
			return;
		}
		$adb->pquery(
			'INSERT INTO ' . self::STATE_TABLE . '
			 (oa_user_id, oa_id, full_name, phone, email, address, business_model, sales_channel, answers_json, last_payload, leadid, is_completed, created_at, updated_at)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
			array_merge(array((string) $oaUserId), $params, array($now))
		);
	}
}
