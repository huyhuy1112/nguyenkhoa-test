<?php
/*+***********************************************************************************
 * Zalo OA scripted chat -> Leads (test scope).
 * - Collect answers per OA user from webhook events.
 * - Create/update Lead only when full questionnaire is completed.
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

		$lead = self::upsertLeadFromState($merged);
		$merged['leadid'] = isset($lead['crmid']) ? (int) $lead['crmid'] : 0;
		$merged['is_completed'] = 1;
		self::saveState($ev['oa_user_id'], $merged);

		return array(
			'success' => true,
			'created' => true,
			'lead' => $lead,
			'oa_user_id' => $ev['oa_user_id'],
		);
	}

	protected static function upsertLeadFromState(array $state) {
		self::ensureCurrentUser();
		$name = trim((string) $state['full_name']);
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
		return Leads_ModernService::saveLead($payload, null);
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

	protected static function missingFields(array $state) {
		$missing = array();
		if (trim((string) $state['business_model']) === '') {
			$missing[] = 'business_model';
		}
		if (trim((string) $state['sales_channel']) === '') {
			$missing[] = 'sales_channel';
		}
		if (trim((string) $state['email']) === '') {
			$missing[] = 'email';
		}
		if (trim((string) $state['phone']) === '') {
			$missing[] = 'phone';
		}
		return $missing;
	}

	protected static function mergeState(array $state, array $ev, array $payload) {
		$out = $state;
		if (!$out) {
			$out = self::emptyState();
		}
		if (!empty($ev['oa_id'])) {
			$out['oa_id'] = $ev['oa_id'];
		}
		if (!empty($ev['full_name'])) {
			$out['full_name'] = $ev['full_name'];
		}
		if (!empty($ev['phone'])) {
			$out['phone'] = $ev['phone'];
		}
		if (!empty($ev['email'])) {
			$out['email'] = $ev['email'];
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
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
		$ev = array(
			'oa_user_id' => '',
			'oa_id' => '',
			'full_name' => '',
			'phone' => '',
			'email' => '',
			'business_model' => '',
			'sales_channel' => '',
		);

		$flat = self::flatten($payload);
		foreach (array('sender.id', 'source.uid', 'from.id', 'user_id', 'uid', 'recipient.id') as $key) {
			if (!empty($flat[$key])) {
				$ev['oa_user_id'] = trim((string) $flat[$key]);
				break;
			}
		}
		foreach (array('oa_id', 'oa.id', 'app.oa_id', 'recipient.oa_id') as $key) {
			if (!empty($flat[$key])) {
				$ev['oa_id'] = trim((string) $flat[$key]);
				break;
			}
		}
		foreach (array('sender.name', 'sender.display_name', 'contact.name', 'shared_info.name', 'user.name') as $key) {
			if (!empty($flat[$key])) {
				$ev['full_name'] = trim((string) $flat[$key]);
				break;
			}
		}

		$texts = array();
		foreach ($flat as $k => $v) {
			if (!is_scalar($v)) {
				continue;
			}
			$ks = strtolower((string) $k);
			if (strpos($ks, 'message') !== false || strpos($ks, 'text') !== false || strpos($ks, 'content') !== false) {
				$vv = trim((string) $v);
				if ($vv !== '') {
					$texts[] = $vv;
				}
			}
		}
		if ($json) {
			$texts[] = $json;
		}
		$joined = implode("\n", array_values(array_unique($texts)));

		$email = self::extractEmail($joined);
		if ($email !== '') {
			$ev['email'] = $email;
		}
		$phone = self::extractPhone($joined);
		if ($phone !== '') {
			$ev['phone'] = $phone;
		}

		$bm = self::extractBusinessModel($joined);
		if ($bm !== '') {
			$ev['business_model'] = $bm;
		}
		$ch = self::extractSalesChannel($joined);
		if ($ch !== '') {
			$ev['sales_channel'] = $ch;
		}
		return $ev;
	}

	protected static function extractBusinessModel($text) {
		$t = self::fold($text);
		if (strpos($t, 'xe day') !== false) {
			return 'Xe đẩy';
		}
		if (strpos($t, 'cua hang') !== false) {
			return 'Cửa hàng';
		}
		// business model "Online" (not sales channel) is accepted for test.
		if (preg_match('/\bonline\b/u', $t)) {
			return 'Online';
		}
		return '';
	}

	protected static function extractSalesChannel($text) {
		$t = self::fold($text);
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

	protected static function extractPhone($text) {
		if (!is_string($text) || $text === '') {
			return '';
		}
		preg_match_all('/(\+?\d[\d\s\.\-]{7,}\d)/', $text, $m);
		if (empty($m[1])) {
			return '';
		}
		foreach ($m[1] as $raw) {
			$digits = preg_replace('/\D+/', '', $raw);
			if ($digits === '') {
				continue;
			}
			if (strpos($digits, '84') === 0 && strlen($digits) >= 11) {
				$digits = '0' . substr($digits, 2);
			}
			if (strlen($digits) >= 9 && strlen($digits) <= 11) {
				return $digits;
			}
		}
		return '';
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

