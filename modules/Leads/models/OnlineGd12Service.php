<?php
/*+***********************************************************************************
 * Giai đoạn 1.2 — lớp online miễn phí (Zalo OA).
 * Chấm Bộ 4 câu theo GD12 - Tieu chuan (cổng C1∩C4, điểm C2+C3+C4, trần ngân sách).
 * Tag trạng thái Online + nhắc D0 gửi từ CRM → Zalo OA (không set kịch bản OA).
 *************************************************************************************/

class Leads_OnlineGd12Service {

	const STATUS_CHUA_DIEN_FORM = 'online_chua_dien_form';
	const STATUS_CHUA_DK_TK = 'online_chua_dk_tk';
	const STATUS_KHONG_DU_DK = 'online_khong_du_dk';
	const STATUS_NGUNG_CSKH = 'online_ngung_cskh';

	const STATUS_TAGS = array(
		self::STATUS_CHUA_DIEN_FORM,
		self::STATUS_CHUA_DK_TK,
		self::STATUS_KHONG_DU_DK,
		self::STATUS_NGUNG_CSKH,
	);

	/**
	 * Catalog Q1–Q4 for CRM verify panel (Online OA).
	 */
	public static function optionsCatalog() {
		return array(
			'q1' => array(
				array('code' => 'A', 'label' => 'Học phục vụ gia đình / sở thích'),
				array('code' => 'B', 'label' => 'Xe đẩy / mang đi / online / tại nhà'),
				array('code' => 'C', 'label' => 'Chuẩn bị mở quán, đã có mặt bằng'),
				array('code' => 'D', 'label' => 'Đã có quán, kinh doanh chưa tốt'),
				array('code' => 'E', 'label' => 'Đã có quán, kinh doanh ổn định / tốt'),
			),
			'q2' => array(
				array('code' => 'A', 'label' => 'Trong 1 tháng / ngay bây giờ'),
				array('code' => 'B', 'label' => '1–3 tháng'),
				array('code' => 'C', 'label' => '3–6 tháng'),
				array('code' => 'D', 'label' => 'Trên 6 tháng / chưa xác định'),
			),
			'q3' => array(
				array('code' => 'A', 'label' => 'Dưới 50 triệu'),
				array('code' => 'B', 'label' => 'Từ 50 đến dưới 100 triệu'),
				array('code' => 'C', 'label' => 'Từ 100 đến dưới 300 triệu'),
				array('code' => 'D', 'label' => 'Từ 300 đến dưới 500 triệu'),
				array('code' => 'E', 'label' => 'Từ 500 triệu trở lên'),
			),
			'q4' => array(
				array('code' => 'A', 'label' => 'Xe đẩy cà phê – trà sữa – trà trái cây'),
				array('code' => 'B', 'label' => 'Trà sữa – topping, mặt bằng 20–30 m²'),
				array('code' => 'C', 'label' => 'Trà sữa pha máy, mặt bằng 20–30 m²'),
				array('code' => 'D', 'label' => 'Cà phê – trà sữa, máy lạnh'),
				array('code' => 'E', 'label' => 'Cà phê sân vườn, diện tích vừa – lớn'),
				array('code' => 'F', 'label' => 'Cà phê không gian mở, diện tích nhỏ'),
				array('code' => 'G', 'label' => 'Học pha chế cho gia đình / sở thích'),
			),
		);
	}

	public static function eligibilityLabel($code) {
		$map = array(
			'du_dk' => 'Đủ điều kiện',
			'khong_du_dk' => 'Không đủ điều kiện',
		);
		$code = trim((string) $code);
		return isset($map[$code]) ? $map[$code] : '';
	}

	public static function potentialLabelPublic($level) {
		return self::potentialLabel($level);
	}

	public static function optionLabel($question, $code) {
		$catalog = self::optionsCatalog();
		$code = strtoupper(trim((string) $code));
		if ($code === '' || empty($catalog[$question])) {
			return '';
		}
		foreach ($catalog[$question] as $opt) {
			if (isset($opt['code']) && strtoupper((string) $opt['code']) === $code) {
				return isset($opt['label']) ? (string) $opt['label'] : $code;
			}
		}
		return $code;
	}

	/**
	 * Sales re-score / save Online 4-question answers on a lead.
	 */
	public static function saveForLead($leadId, array $payload, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		$q1 = isset($payload['q1']) ? $payload['q1'] : (isset($payload['c1']) ? $payload['c1'] : '');
		$q2 = isset($payload['q2']) ? $payload['q2'] : (isset($payload['c2']) ? $payload['c2'] : '');
		$q3 = isset($payload['q3']) ? $payload['q3'] : (isset($payload['c3']) ? $payload['c3'] : '');
		$q4 = isset($payload['q4']) ? $payload['q4'] : (isset($payload['c4']) ? $payload['c4'] : '');
		$result = self::compute($q1, $q2, $q3, $q4);
		if (empty($result['success'])) {
			return array('success' => false, 'error' => 'Không chấm được bộ 4 câu', 'result' => $result);
		}
		self::applyToLead($leadId, $result, '');
		require_once 'modules/Leads/models/ModernService.php';
		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		return array('success' => true, 'result' => $result, 'lead' => $lead);
	}

	/**
	 * Profile columns for GD 1.2.
	 */
	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$prof = $adb->pquery("SHOW TABLES LIKE 'bace_lead_profile'", array());
		if (!$prof || $adb->num_rows($prof) < 1) {
			return;
		}
		$cols = array(
			'zalo_user_id' => "VARCHAR(128) DEFAULT NULL",
			'online_status' => "VARCHAR(48) DEFAULT NULL",
			'online_q1' => "VARCHAR(8) DEFAULT NULL",
			'online_q2' => "VARCHAR(8) DEFAULT NULL",
			'online_q3' => "VARCHAR(8) DEFAULT NULL",
			'online_q4' => "VARCHAR(8) DEFAULT NULL",
			'online_reminder_count' => "TINYINT(1) NOT NULL DEFAULT 0",
			'online_entered_at' => "DATETIME DEFAULT NULL",
			'online_last_remind_at' => "DATETIME DEFAULT NULL",
			'online_path' => "VARCHAR(16) DEFAULT NULL",
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
	}

	/**
	 * @param string $q1 A–E
	 * @param string $q2 A–D
	 * @param string $q3 A–E
	 * @param string $q4 A–G
	 * @return array
	 */
	public static function compute($q1, $q2, $q3, $q4) {
		$q1 = strtoupper(trim((string) $q1));
		$q2 = strtoupper(trim((string) $q2));
		$q3 = strtoupper(trim((string) $q3));
		$q4 = strtoupper(trim((string) $q4));

		$group = self::customerGroupFromQ1($q1);
		$modelCode = $q4;
		$modelLabel = self::modelLabel($q4);

		$gate1 = in_array($q1, array('C', 'D', 'E'), true);
		$gate2 = !in_array($q4, array('A', 'G'), true);
		$eligible = $gate1 && $gate2;

		$out = array(
			'success' => true,
			'q1' => $q1,
			'q2' => $q2,
			'q3' => $q3,
			'q4' => $q4,
			'eligibility_result' => $eligible ? 'du_dk' : 'khong_du_dk',
			'eligibility_label' => $eligible ? 'Đủ điều kiện' : 'Không đủ điều kiện',
			'customer_group' => $group['code'],
			'customer_group_label' => $group['label'],
			'business_model' => $modelCode,
			'business_model_label' => $modelLabel,
			'potential_level' => '',
			'potential_label' => 'Không đánh giá',
			'score' => null,
			'raw_band' => '',
			'status_tag' => $eligible ? self::STATUS_CHUA_DK_TK : self::STATUS_KHONG_DU_DK,
		);

		if (!$eligible) {
			return $out;
		}

		$p2 = self::pointsQ2($q2);
		$p3 = self::pointsQ3($q3);
		$p4 = self::pointsQ4($q4);
		$total = $p2 + $p3 + $p4;
		$raw = self::rawBand($total);
		$level = self::applyCeiling($raw, $q3);

		$out['score'] = $total;
		$out['raw_band'] = $raw;
		$out['potential_level'] = $level;
		$out['potential_label'] = self::potentialLabel($level);
		return $out;
	}

	public static function parseQ1($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDE');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false) {
			return 'A';
		}
		if (strpos($f, 'xe day') !== false || strpos($f, 'mang di') !== false || strpos($f, 'online') !== false || strpos($f, 'tai nha') !== false) {
			return 'B';
		}
		if (strpos($f, 'chuan bi mo') !== false || strpos($f, 'mat bang') !== false) {
			return 'C';
		}
		if (strpos($f, 'chua duoc nhu') !== false || strpos($f, 'chua tot') !== false || (strpos($f, 'da co quan') !== false && strpos($f, 'tot') === false)) {
			return 'D';
		}
		if (strpos($f, 'on dinh') !== false || strpos($f, 'kinh doanh tot') !== false) {
			return 'E';
		}
		if (strpos($f, 'da co quan') !== false) {
			return 'D';
		}
		return '';
	}

	public static function parseQ2($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCD');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, '1 thang') !== false || strpos($f, 'ngay bay gio') !== false || strpos($f, 'trong vong 1') !== false) {
			return 'A';
		}
		if (strpos($f, '1 den 3') !== false || strpos($f, '1-3') !== false || strpos($f, '1 – 3') !== false) {
			return 'B';
		}
		if (strpos($f, '3 den 6') !== false || strpos($f, '3-6') !== false) {
			return 'C';
		}
		if (strpos($f, '6 thang') !== false || strpos($f, 'chua xac dinh') !== false || strpos($f, 'tren 6') !== false) {
			return 'D';
		}
		return '';
	}

	public static function parseQ3($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDE');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'duoi 50') !== false) {
			return 'A';
		}
		if (strpos($f, '50') !== false && strpos($f, '100') !== false) {
			return 'B';
		}
		if (strpos($f, '100') !== false && strpos($f, '300') !== false) {
			return 'C';
		}
		if (strpos($f, '300') !== false && strpos($f, '500') !== false) {
			return 'D';
		}
		if (strpos($f, '500') !== false) {
			return 'E';
		}
		return '';
	}

	public static function parseQ4($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFG');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false) {
			return 'G';
		}
		if (strpos($f, 'xe day') !== false) {
			return 'A';
		}
		if (strpos($f, 'topping') !== false) {
			return 'B';
		}
		if (strpos($f, 'pha may') !== false) {
			return 'C';
		}
		if (strpos($f, 'san vuon') !== false) {
			return 'E';
		}
		if (strpos($f, 'khong gian mo') !== false) {
			return 'F';
		}
		if (strpos($f, 'may lanh') !== false) {
			return 'D';
		}
		return '';
	}

	/**
	 * Apply GD1.2 screening result onto lead profile + tags.
	 */
	public static function applyToLead($leadId, array $result, $oaUserId = '') {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || empty($result['success'])) {
			return;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$status = isset($result['status_tag']) ? $result['status_tag'] : self::STATUS_KHONG_DU_DK;

		$elig = isset($result['eligibility_result']) ? $result['eligibility_result'] : '';
		$pot = isset($result['potential_level']) ? $result['potential_level'] : '';
		$seg = self::segmentFromGroup(isset($result['customer_group']) ? $result['customer_group'] : '');
		$biz = self::businessModelKey(isset($result['business_model']) ? $result['business_model'] : '');

		$adb->pquery(
			"UPDATE bace_lead_profile SET
				online_status = ?, online_q1 = ?, online_q2 = ?, online_q3 = ?, online_q4 = ?,
				eligibility_result = ?, potential_level = ?, business_model = ?, segment = ?,
				zalo_user_id = IF(zalo_user_id IS NULL OR zalo_user_id = '', ?, zalo_user_id),
				online_path = 'oa',
				online_reminder_count = 0,
				modified_at = ?
			 WHERE leadid = ?",
			array(
				$status,
				isset($result['q1']) ? $result['q1'] : null,
				isset($result['q2']) ? $result['q2'] : null,
				isset($result['q3']) ? $result['q3'] : null,
				isset($result['q4']) ? $result['q4'] : null,
				$elig !== '' ? $elig : null,
				$pot !== '' ? $pot : null,
				$biz !== '' ? $biz : null,
				$seg !== '' ? $seg : null,
				(string) $oaUserId,
				$now,
				$leadId,
			)
		);

		$tags = array('zalo', 'mien_phi_online', $status);
		if ($elig === 'du_dk' && $pot === 'sieu_tiem_nang') {
			$tags[] = 'sieu_tiem_nang';
		} elseif ($elig === 'du_dk' && $pot === 'tiem_nang') {
			$tags[] = 'tiem_nang';
		}
		self::syncStatusTagsOnly($leadId, $tags);
	}

	/**
	 * Mốc 1 — hồ sơ khi vào OA (chưa form).
	 */
	public static function ensureStubLead($oaUserId, $oaId = '', $displayName = '') {
		$oaUserId = trim((string) $oaUserId);
		if ($oaUserId === '') {
			return null;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();

		$res = $adb->pquery(
			"SELECT leadid FROM bace_lead_profile WHERE zalo_user_id = ? AND online_status = ? LIMIT 1",
			array($oaUserId, self::STATUS_CHUA_DIEN_FORM)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'leadid');
		}

		require_once 'modules/Leads/models/ModernService.php';
		$name = trim((string) $displayName);
		if ($name === '') {
			$name = 'Khách Zalo OA';
		}
		$phone = self::stubPhoneFromOaUser($oaUserId);
		$payload = array(
			'name' => $name,
			'phone' => $phone,
			'companyName' => '-',
			'tags' => array('zalo', 'mien_phi_online', self::STATUS_CHUA_DIEN_FORM),
			'skip_potential' => 1,
			'force_create' => 1,
			'online_stub' => 1,
			'screening_result' => '',
		);
		$lead = Leads_ModernService::saveLead($payload, null);
		$leadId = 0;
		if (is_array($lead)) {
			$leadId = isset($lead['crmid']) ? (int) $lead['crmid'] : (isset($lead['id']) ? (int) $lead['id'] : 0);
		}
		if ($leadId <= 0) {
			return null;
		}
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			"UPDATE bace_lead_profile SET zalo_user_id = ?, online_status = ?, online_entered_at = ?, online_path = 'oa', online_reminder_count = 0, modified_at = ? WHERE leadid = ?",
			array($oaUserId, self::STATUS_CHUA_DIEN_FORM, $now, $now, $leadId)
		);
		self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', self::STATUS_CHUA_DIEN_FORM));

		// KB-01 from CRM → OA (best-effort)
		self::sendTemplate($oaUserId, 'kb01');

		return $leadId;
	}

	public static function stubPhoneFromOaUser($oaUserId) {
		$h = abs(crc32((string) $oaUserId));
		return '09' . str_pad((string) ($h % 100000000), 8, '0', STR_PAD_LEFT);
	}

	public static function messageTemplates() {
		return array(
			'kb01' => "Chào anh/chị,\nEm mời anh/chị điền form đăng ký lớp học online miễn phí của Nguyên Khoa để được xét cấp tài khoản học.\nVui lòng mở form trong Zalo OA và gửi đủ thông tin nhé.",
			'kb02a' => "Anh/chị ơi, form đăng ký lớp online miễn phí vẫn còn mở. Điền giúp em để nhận hướng dẫn học sớm nhất nhé.",
			'kb02b' => "Nhắc anh/chị: còn thiếu form đăng ký lớp online miễn phí nên chưa cấp được tài khoản học. Anh/chị dành 1 phút điền form giúp em nhé.",
			'kb02c' => "Đây là lần nhắc cuối về form lớp online miễn phí. Nếu anh/chị vẫn quan tâm, hãy gửi form trong hôm nay để em hỗ trợ tiếp.",
		);
	}

	public static function sendTemplate($oaUserId, $templateKey) {
		$templates = self::messageTemplates();
		if (!isset($templates[$templateKey])) {
			return array('success' => false, 'error' => 'unknown_template');
		}
		return self::sendOaText($oaUserId, $templates[$templateKey]);
	}

	public static function sendOaText($oaUserId, $text) {
		$oaUserId = trim((string) $oaUserId);
		$text = trim((string) $text);
		if ($oaUserId === '' || $text === '') {
			return array('success' => false, 'error' => 'missing_params');
		}
		try {
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			require_once 'modules/Vtiger/helpers/NkApi/ZaloOaAdapter.php';
			$adapter = new NkApi_ZaloOa_Adapter();
			return $adapter->sendTextMessage($oaUserId, $text);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		} catch (Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * D0 reminders: Chưa điền form, cách 3 ngày, tối đa 3 lần (CRM → OA).
	 */
	public static function processD0Reminders($limit = 50) {
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(200, (int) $limit));
		$sql = "SELECT p.leadid, p.zalo_user_id, p.online_reminder_count, p.online_entered_at, p.online_last_remind_at
			FROM bace_lead_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			WHERE p.online_status = ?
			  AND p.zalo_user_id IS NOT NULL AND p.zalo_user_id <> ''
			  AND IFNULL(p.online_reminder_count, 0) < 3
			ORDER BY p.online_entered_at ASC
			LIMIT {$limit}";
		$res = $adb->pquery($sql, array(self::STATUS_CHUA_DIEN_FORM));
		$sent = 0;
		$stopped = 0;
		if (!$res) {
			return array('sent' => 0, 'stopped' => 0);
		}
		$now = time();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$uid = (string) $adb->query_result($res, $i, 'zalo_user_id');
			$count = (int) $adb->query_result($res, $i, 'online_reminder_count');
			$entered = (string) $adb->query_result($res, $i, 'online_entered_at');
			$last = (string) $adb->query_result($res, $i, 'online_last_remind_at');
			$anchor = $last && $last !== '0000-00-00 00:00:00' ? strtotime($last) : strtotime($entered);
			if (!$anchor) {
				continue;
			}
			if (($now - $anchor) < 3 * 86400) {
				continue;
			}
			$keys = array('kb02a', 'kb02b', 'kb02c');
			$key = $keys[$count];
			$r = self::sendTemplate($uid, $key);
			$newCount = $count + 1;
			$adb->pquery(
				"UPDATE bace_lead_profile SET online_reminder_count = ?, online_last_remind_at = ?, modified_at = ? WHERE leadid = ?",
				array($newCount, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $leadId)
			);
			if (!empty($r['success'])) {
				$sent++;
			}
			if ($newCount >= 3) {
				$adb->pquery(
					"UPDATE bace_lead_profile SET online_status = ?, modified_at = ? WHERE leadid = ?",
					array(self::STATUS_NGUNG_CSKH, date('Y-m-d H:i:s'), $leadId)
				);
				self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', self::STATUS_NGUNG_CSKH));
				$stopped++;
			}
		}
		return array('sent' => $sent, 'stopped' => $stopped);
	}

	/**
	 * Tag sync without full saveLead name/phone requirement.
	 */
	public static function syncStatusTagsOnly($leadId, array $desiredTags) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return;
		}
		global $current_user;
		$userId = ($current_user && !empty($current_user->id)) ? (int) $current_user->id : 1;
		$cur = array();
		try {
			$ref = new ReflectionClass('Leads_ModernService');
			$getTags = $ref->getMethod('getTagsForLeadIds');
			$getTags->setAccessible(true);
			$existing = $getTags->invoke(null, array($leadId), $userId);
			$cur = isset($existing[$leadId]) ? $existing[$leadId] : array();
		} catch (Exception $e) {
			$cur = array();
		}
		$kept = array();
		foreach ($cur as $t) {
			$key = strtolower(trim((string) $t));
			if (in_array($key, self::STATUS_TAGS, true)) {
				continue;
			}
			if ($key === 'mien_phi_online' || $key === 'zalo') {
				continue;
			}
			$kept[] = $t;
		}
		$merged = array_values(array_unique(array_merge($kept, $desiredTags)));
		try {
			$ref = new ReflectionClass('Leads_ModernService');
			$m = $ref->getMethod('syncTags');
			$m->setAccessible(true);
			$m->invoke(null, $leadId, $merged, $userId);
			require_once 'modules/Leads/models/LeadProductsService.php';
			Leads_LeadProductsService::syncFromTags($leadId, $merged, $userId, true);
		} catch (Exception $e) {
			// best-effort
		}
	}

	protected static function customerGroupFromQ1($q1) {
		$map = array(
			'A' => array('code' => 'nhom_1', 'label' => 'Nhóm 1 — Gia đình, sở thích'),
			'B' => array('code' => 'nhom_2', 'label' => 'Nhóm 2 — Xe đẩy, online, tại nhà'),
			'C' => array('code' => 'nhom_3', 'label' => 'Nhóm 3 — Chuẩn bị mở quán có mặt bằng'),
			'D' => array('code' => 'nhom_4', 'label' => 'Nhóm 4 — Có quán, kinh doanh chưa tốt'),
			'E' => array('code' => 'nhom_5', 'label' => 'Nhóm 5 — Có quán, kinh doanh tốt'),
		);
		return isset($map[$q1]) ? $map[$q1] : array('code' => '', 'label' => '');
	}

	protected static function segmentFromGroup($groupCode) {
		$map = array(
			'nhom_1' => 'gia_dinh',
			'nhom_2' => 'chuan_bi_mo',
			'nhom_3' => 'chuan_bi_mo',
			'nhom_4' => 'co_quan',
			'nhom_5' => 'co_quan',
		);
		return isset($map[$groupCode]) ? $map[$groupCode] : '';
	}

	protected static function modelLabel($q4) {
		$map = array(
			'A' => 'Xe đẩy cà phê – trà sữa – trà trái cây',
			'B' => 'Trà sữa – topping, mặt bằng 20–30 m²',
			'C' => 'Trà sữa pha máy, mặt bằng 20–30 m²',
			'D' => 'Cà phê – trà sữa, máy lạnh',
			'E' => 'Cà phê sân vườn, diện tích vừa – lớn',
			'F' => 'Cà phê không gian mở, diện tích nhỏ',
			'G' => 'Học pha chế cho gia đình / sở thích',
		);
		return isset($map[$q4]) ? $map[$q4] : $q4;
	}

	public static function businessModelKey($q4) {
		$map = array(
			'A' => 'xe_day',
			'B' => 'tra_sua_topping',
			'C' => 'tra_sua_may',
			'D' => 'ca_phe_may_lanh',
			'E' => 'ca_phe_san_vuon',
			'F' => 'ca_phe_khong_gian_mo',
			'G' => 'gia_dinh',
		);
		return isset($map[$q4]) ? $map[$q4] : strtolower((string) $q4);
	}

	protected static function pointsQ2($q2) {
		$map = array('A' => 3, 'B' => 2, 'C' => 1, 'D' => 0);
		return isset($map[$q2]) ? $map[$q2] : 0;
	}

	protected static function pointsQ3($q3) {
		$map = array('A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 3);
		return isset($map[$q3]) ? $map[$q3] : 0;
	}

	protected static function pointsQ4($q4) {
		$map = array('A' => 1, 'B' => 2, 'C' => 2, 'D' => 3, 'E' => 3, 'F' => 2, 'G' => 0);
		return isset($map[$q4]) ? $map[$q4] : 0;
	}

	protected static function rawBand($total) {
		if ($total >= 7) {
			return 'sieu_tiem_nang';
		}
		if ($total >= 4) {
			return 'tiem_nang';
		}
		return 'binh_thuong';
	}

	protected static function applyCeiling($raw, $q3) {
		if (in_array($q3, array('A', 'B'), true) && $raw === 'sieu_tiem_nang') {
			return 'tiem_nang';
		}
		return $raw;
	}

	protected static function potentialLabel($level) {
		$map = array(
			'sieu_tiem_nang' => 'Siêu tiềm năng',
			'tiem_nang' => 'Tiềm năng',
			'binh_thuong' => 'Bình thường',
		);
		return isset($map[$level]) ? $map[$level] : '';
	}

	protected static function parseLeadingLetter($raw, $allowed) {
		$s = trim((string) $raw);
		if ($s === '') {
			return '';
		}
		if (preg_match('/^\s*([A-Za-z])\b/u', $s, $m)) {
			$c = strtoupper($m[1]);
			if (strpos($allowed, $c) !== false) {
				return $c;
			}
		}
		return '';
	}

	protected static function fold($s) {
		$s = trim(mb_strtolower((string) $s, 'UTF-8'));
		$map = array(
			'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
			'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
			'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
			'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
			'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
			'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
		);
		$s = strtr($s, $map);
		$s = preg_replace('/[^a-z0-9\s\-]+/u', ' ', $s);
		return trim(preg_replace('/\s+/', ' ', $s));
	}
}
