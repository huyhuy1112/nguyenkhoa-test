<?php
/*+***********************************************************************************
 * Bộ 3 câu hỏi sàng lọc (GD 1.1 mới) — Online + Offline dùng chung.
 * Đầu vào: C1–C3 sau xác minh. Không còn C4/C5.
 * Đầu ra: Kết luận ĐK · Mức tiềm năng · Nhóm KH · Mô hình KD.
 *************************************************************************************/

class Leads_SalesVerifyService {

	/**
	 * Ensure Bộ B columns on bace_lead_profile.
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
			'form_c1' => "VARCHAR(8) DEFAULT NULL",
			'form_c2' => "VARCHAR(8) DEFAULT NULL",
			'form_c3' => "VARCHAR(8) DEFAULT NULL",
			'verify_c1' => "VARCHAR(8) DEFAULT NULL",
			'verify_c2' => "VARCHAR(8) DEFAULT NULL",
			'verify_c3' => "VARCHAR(8) DEFAULT NULL",
			'verify_c4' => "TINYINT(1) DEFAULT NULL",
			'verify_c5' => "TINYINT(1) DEFAULT NULL",
			'eligibility_result' => "VARCHAR(32) DEFAULT NULL",
			'potential_level' => "VARCHAR(32) DEFAULT NULL",
			'verify_score' => "INT(11) DEFAULT NULL",
			'verify_change_reason' => "TEXT DEFAULT NULL",
			'verified_at' => "DATETIME DEFAULT NULL",
			'verified_by' => "INT(19) DEFAULT NULL",
			// GD1.1: khoá đáp án sau khi Sales thông báo kết quả / tên lớp.
			'answers_locked_at' => "DATETIME DEFAULT NULL",
			'answers_locked_by' => "INT(19) DEFAULT NULL",
			'verify_extra_json' => "TEXT DEFAULT NULL",
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
		// Backfill: hồ sơ đã xác minh trước đó coi như đã khoá.
		try {
			$adb->pquery(
				"UPDATE bace_lead_profile
				 SET answers_locked_at = verified_at,
				     answers_locked_by = verified_by
				 WHERE answers_locked_at IS NULL
				   AND verified_at IS NOT NULL
				   AND verified_at <> ''
				   AND verified_at <> '0000-00-00 00:00:00'",
				array()
			);
		} catch (Exception $e) {
			// ignore
		}
	}

	/**
	 * Đáp án sau xác minh đã khoá (GD1.1 — sau khi thông báo kết quả).
	 */
	public static function isAnswersLocked($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT answers_locked_at FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return false;
		}
		$at = trim((string) $adb->query_result($res, 0, 'answers_locked_at'));
		return ($at !== '' && $at !== '0000-00-00 00:00:00');
	}

	public static function lockAnswers($leadId, $userId = 0) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;
		$adb->pquery(
			'UPDATE bace_lead_profile SET answers_locked_at = ?, answers_locked_by = ?, modified_at = ? WHERE leadid = ?',
			array($now, $userId > 0 ? $userId : null, $now, $leadId)
		);
		return true;
	}

	/**
	 * Mở khoá khi khách tự khai lại form sau ≥ 3 tháng (GD1.1).
	 */
	public static function unlockAnswersIfReFormAllowed($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::isAnswersLocked($leadId)) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT answers_locked_at FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$at = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'answers_locked_at'))
			: '';
		$ts = $at !== '' ? strtotime($at) : false;
		if (!$ts || $ts > strtotime('-3 months')) {
			return false;
		}
		$adb->pquery(
			'UPDATE bace_lead_profile SET answers_locked_at = NULL, answers_locked_by = NULL, modified_at = ? WHERE leadid = ?',
			array(date('Y-m-d H:i:s'), $leadId)
		);
		return true;
	}

	/**
	 * Luật chấm GD1.1 mới (dừng khi khớp):
	 * 1) C1=C (gia đình) → Không đủ ĐK
	 * 2) C3=F (≥500tr) → Siêu tiềm năng
	 * 3) C2=A (mặt bằng) → Tiềm năng
	 * 4) Còn lại → Bình thường
	 * @return array
	 */
	public static function compute(array $input) {
		$c1 = strtoupper(trim((string) (isset($input['c1']) ? $input['c1'] : '')));
		$c2 = strtoupper(trim((string) (isset($input['c2']) ? $input['c2'] : '')));
		$c3 = strtoupper(trim((string) (isset($input['c3']) ? $input['c3'] : '')));

		$group = self::customerGroupFromC1($c1);
		$biz = self::businessModelFromC2($c2);
		$matched = self::matchScreeningLevel(self::getScreeningBank(), $input);
		if (!$matched) {
			return array(
				'success' => false,
				'eligibility_result' => '',
				'eligibility_label' => '',
				'potential_level' => '',
				'potential_label' => '',
				'customer_group' => '',
				'customer_group_label' => '',
				'business_model' => '',
				'business_model_label' => '',
				'score' => null,
				'ask_c4_c5' => false,
				'reason' => 'Chưa khớp mức xếp loại. Kiểm tra câu bắt buộc và quy tắc trong Quản lý rule.',
			);
		}
		$code = $matched['code'];
		$elig = ($code === 'khong_du_dk') ? 'khong_du_dk' : 'du_dk';

		return array(
			'success' => true,
			'eligibility_result' => $elig,
			'eligibility_label' => self::eligibilityLabel($elig),
			'potential_level' => $code,
			'potential_label' => $matched['label'] !== '' ? $matched['label'] : self::potentialLabel($code),
			'customer_group' => $group,
			'customer_group_label' => self::customerGroupLabel($group),
			'business_model' => $biz,
			'business_model_label' => $biz,
			'score' => null,
			'ask_c4_c5' => false,
			'reason' => '',
		);
	}

	public static function customerGroupFromC1($c1) {
		$c1 = strtoupper(trim((string) $c1));
		if ($c1 === 'A') {
			return 'nhom_2';
		}
		if ($c1 === 'B') {
			return 'nhom_3';
		}
		if ($c1 === 'C') {
			return 'nhom_1';
		}
		return '';
	}

	public static function customerGroupLabel($code) {
		$map = array(
			'nhom_1' => 'Nhóm 1 — Gia đình, sở thích',
			'nhom_2' => 'Nhóm 2 — Chuẩn bị mở quán',
			'nhom_3' => 'Nhóm 3 — Đã có quán',
		);
		return isset($map[$code]) ? $map[$code] : '';
	}

	public static function businessModelFromC2($c2) {
		$c2 = strtoupper(trim((string) $c2));
		if ($c2 === 'A') {
			return 'Thuê hoặc có sẵn mặt bằng';
		}
		if ($c2 === 'B') {
			return 'Mở vỉa hè, bán online';
		}
		return '';
	}

	public static function potentialLabel($code) {
		$map = array(
			'sieu_tiem_nang' => 'Siêu tiềm năng',
			'tiem_nang' => 'Tiềm năng',
			'binh_thuong' => 'Bình thường',
			'khong_du_dk' => 'Không đủ điều kiện',
		);
		return isset($map[$code]) ? $map[$code] : '';
	}

	public static function eligibilityLabel($code) {
		if ($code === 'du_dk') {
			return 'Đủ điều kiện';
		}
		if ($code === 'khong_du_dk') {
			return 'Không đủ điều kiện';
		}
		return '';
	}

	/**
	 * Save verification for a lead. Keeps form_c* untouched unless empty (seed from form).
	 */
	/**
	 * 11 tag giai đoạn 1.4. Mỗi hồ sơ chỉ mang một tag.
	 * Tag ①–⑥ và ⑪ ở KH tiềm năng. Tag ⑦ gắn lên Khách hàng khi thanh toán lớp 990k.
	 */
	public static function gd14TagCatalog() {
		return array(
			'gd14_moi_dang_ky' => '990k — Mới đăng ký',
			'gd14_hen_goi_lai' => '990k — Hẹn gọi lại',
			'gd14_khong_nghe_may' => '990k — Không nghe máy',
			'gd14_sai_thong_tin' => '990k — Sai thông tin liên hệ',
			'gd14_dang_can_nhac' => '990k — Đang cân nhắc',
			'gd14_cho_thanh_toan' => '990k — Chờ thanh toán',
			'gd14_chua_xep_buoi' => '990k — Chưa xếp buổi học',
			'gd14_da_xac_nhan_lich' => '990k — Đã xác nhận lịch học',
			'gd14_khong_tham_gia' => '990k — Không tham gia lớp học',
			'gd14_da_tham_gia' => '990k — Đã tham gia lớp học',
			'gd14_ngung_cham_soc' => '990k — Ngưng chăm sóc',
		);
	}

	public static function gd14CourseCatalog() {
		return array(
			'lop_990k' => array('label' => 'Lớp Pha chế Chuyên đề 990k', 'tag' => 'gd14_chua_xep_buoi', 'leave' => false),
			'pcth' => array('label' => 'Pha chế tổng hợp', 'tag' => 'da_pcth', 'leave' => true),
			'mqbb' => array('label' => 'Mở quán bài bản', 'tag' => 'da_mqbb', 'leave' => true),
			'combo' => array('label' => 'Combo giải pháp mở quán', 'tag' => 'combo_mo_quan', 'leave' => true),
		);
	}

	/**
	 * Xác minh 3 câu và ghi kết quả cuộc gọi. Hồ sơ vẫn ở KH tiềm năng.
	 */
	public static function saveGd14ForLead($leadIdOrCacheId, array $payload, $userId = 0) {
		require_once 'modules/Leads/models/ModernService.php';
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		Leads_ModernService::installSchema($adb);
		$leadId = self::resolveGd14LeadId($leadIdOrCacheId);
		$bank = self::getGd14QuestionBank();
		$outcome = strtolower(trim((string) (isset($payload['outcome']) ? $payload['outcome'] : '')));
		$outcomeTags = array(
			'hen_goi_lai' => 'gd14_hen_goi_lai',
			'khong_nghe_may' => 'gd14_khong_nghe_may',
			'sai_thong_tin' => 'gd14_sai_thong_tin',
			'dang_can_nhac' => 'gd14_dang_can_nhac',
			'chon_khoa' => 'gd14_cho_thanh_toan',
			'khong_chon' => 'gd14_ngung_cham_soc',
			'tu_choi' => 'gd14_ngung_cham_soc',
		);
		if (!isset($outcomeTags[$outcome])) {
			throw new Exception('Chọn kết quả cuộc gọi 990k.');
		}
		$needsAnswers = in_array($outcome, array('dang_can_nhac', 'chon_khoa'), true);
		$answers = self::readGd14AnswerPayload($payload, $bank, $needsAnswers);
		$courses = self::gd14CourseCatalog();
		$course = strtolower(trim((string) (isset($payload['course']) ? $payload['course'] : '')));
		if ($outcome === 'chon_khoa') {
			if (!isset($courses[$course])) {
				throw new Exception('Chọn khoá khách đã chốt.');
			}
		} else {
			$course = '';
		}
		$topic = strtolower(trim((string) (isset($payload['topic']) ? $payload['topic'] : '')));
		if (!in_array($topic, array('tra_sua', 'cafe', 'chua_chon'), true)) {
			$topic = '';
		}
		$extra = self::loadGd14Extra($adb, $leadId);
		if (!empty($answers)) {
			if (empty($extra['gd14_form_answers'])) {
				$form = array();
				if (!empty($payload['form_answers']) && is_array($payload['form_answers'])) {
					$form = self::readGd14AnswerPayload($payload['form_answers'], $bank, false);
				}
				if (!empty($form)) {
					$extra['gd14_form_answers'] = $form;
					$extra['gd14_form_result'] = self::classifyGd14Answers($form, $bank);
				}
			}
			$extra['gd14_answers'] = $answers;
			foreach ($answers as $qid => $code) {
				$extra['gd14_' . $qid] = $code;
			}
			$extra['gd14_verified'] = 1;
			$extra['gd14_result'] = self::classifyGd14Answers($answers, $bank);
		}
		$goal = trim((string) (isset($payload['goal']) ? $payload['goal'] : ''));
		if ($goal !== '') {
			$extra['gd14_goal'] = $goal;
		}
		if (!empty($extra['gd14_drop'])) {
			throw new Exception('Hồ sơ đã ngưng chăm sóc tại ' . $extra['gd14_drop'] . '.');
		}
		$extra['gd14_outcome'] = $outcome;
		if ($course !== '') {
			$extra['gd14_course'] = $course;
			$extra['gd14_topic'] = ($course === 'lop_990k') ? $topic : '';
		}
		if ($outcome === 'chon_khoa' && empty($extra['gd14_waiting_at'])) {
			$extra['gd14_waiting_at'] = date('Y-m-d H:i:s');
		}
		$drop = self::bumpGd14Drop($extra, $outcome);
		$tag = !empty($drop['stopped']) ? 'gd14_ngung_cham_soc' : $outcomeTags[$outcome];
		if ($tag === 'gd14_ngung_cham_soc' && empty($extra['gd14_drop'])) {
			$extra['gd14_drop'] = 'Ngưng';
			$extra['gd14_drop_reason'] = $outcome === 'tu_choi' ? 'Từ chối trao đổi' : 'Không chọn khoá';
			$drop['reason'] = $extra['gd14_drop_reason'];
			$drop['stopped'] = true;
		}
		self::storeGd14Extra($adb, $leadId, $extra);
		self::replaceGd14Tag($leadId, $tag, $userId);
		$labels = self::gd14TagCatalog();
		try {
			require_once 'modules/Vtiger/models/CareActivityService.php';
			Vtiger_CareActivityService::log(
				'Leads',
				$leadId,
				'990k',
				isset($labels[$tag]) ? $labels[$tag] : $tag,
				'',
				$userId
			);
		} catch (Exception $e) {
			// ignore
		}
		$lead = Leads_ModernService::getLead($leadId, $userId > 0 ? $userId : null);
		$message = 'Đã ghi xác minh 990k. Hồ sơ vẫn ở KH tiềm năng · ' . (isset($labels[$tag]) ? $labels[$tag] : $tag);
		if (!empty($drop['code'])) {
			$message .= ' · ' . $drop['code'] . ' ' . (int) $drop['count'] . '/3';
		}
		if (!empty($drop['stopped'])) {
			$message .= ' · Ngưng chăm sóc: ' . $drop['reason'];
		}
		return array(
			'success' => true,
			'lead' => $lead,
			'tag' => $tag,
			'message' => $message,
		);
	}

	/**
	 * Thanh toán được xác nhận thì mới sang Khách hàng.
	 * Lớp 990k gắn tag chưa xếp buổi. Khoá cao hơn gắn tag khoá đó và rời giai đoạn 1.4.
	 */
	public static function confirmGd14Payment($leadIdOrCacheId, array $payload, $userId = 0) {
		require_once 'modules/Leads/models/ModernService.php';
		require_once 'modules/Leads/models/ConvertService.php';
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$leadId = self::resolveGd14LeadId($leadIdOrCacheId);
		$extra = self::loadGd14Extra($adb, $leadId);
		$lead = Leads_ModernService::getLead($leadId, $userId > 0 ? $userId : null);
		$tags = isset($lead['tags']) && is_array($lead['tags']) ? $lead['tags'] : array();
		$waiting = false;
		foreach ($tags as $tagName) {
			if (strtolower(trim((string) $tagName)) === 'gd14_cho_thanh_toan') {
				$waiting = true;
				break;
			}
		}
		if (!$waiting) {
			throw new Exception('Chỉ xác nhận thanh toán khi hồ sơ đang ở tag 990k — Chờ thanh toán.');
		}
		$courses = self::gd14CourseCatalog();
		$course = strtolower(trim((string) (isset($payload['course']) ? $payload['course'] : '')));
		if ($course === '' && !empty($extra['gd14_course'])) {
			$course = strtolower(trim((string) $extra['gd14_course']));
		}
		if (!isset($courses[$course])) {
			throw new Exception('Chưa có khoá đã chọn để xác nhận thanh toán.');
		}
		$spec = $courses[$course];
		$paidAt = date('Y-m-d H:i:s');
		$extra['gd14_course'] = $course;
		$extra['gd14_paid_at'] = $paidAt;
		$giftWindow = '';
		$retentionUntil = '';
		if ($course === 'lop_990k') {
			if (empty($extra['gd14_waiting_at'])) {
				$extra['gd14_waiting_at'] = $paidAt;
			}
			$waitingTs = strtotime((string) $extra['gd14_waiting_at']);
			$paidTs = strtotime($paidAt);
			$giftWindow = ($waitingTs && $paidTs && $paidTs <= ($waitingTs + 3 * 86400)) ? 'trong_han' : 'sau_han';
			$extra['gd14_gift_window'] = $giftWindow;
			$retentionUntil = date('Y-m-d H:i:s', strtotime('+1 year', $paidTs ? $paidTs : time()));
			$extra['gd14_retention_until'] = $retentionUntil;
			$extra['gd14_retention_expired'] = 0;
		}
		self::storeGd14Extra($adb, $leadId, $extra);
		$converted = Leads_ConvertService::convertLeadToContactOnly($leadId, array());
		$contactId = isset($converted['contactId']) ? (int) $converted['contactId'] : 0;
		if ($contactId > 0) {
			self::markPaidContact($contactId, $spec['tag'], $userId);
		}
		$fresh = Leads_ModernService::getLead($leadId, $userId > 0 ? $userId : null);
		$tail = !empty($spec['leave'])
			? 'Đã xác nhận thanh toán ' . $spec['label'] . ' và chuyển sang Khách hàng, rời giai đoạn 1.4.'
			: 'Đã xác nhận thanh toán lớp 990k · 990k — Chưa xếp buổi học. '
				. ($giftWindow === 'sau_han' ? 'Sau hạn quà.' : 'Trong hạn quà.')
				. ($retentionUntil !== '' ? ' Hạn bảo lưu đến ' . date('d/m/Y', strtotime($retentionUntil)) . '.' : '');
		return array(
			'success' => true,
			'lead' => $fresh,
			'contact_id' => $contactId,
			'convert' => $converted,
			'gift_window' => $giftWindow,
			'retention_until' => $retentionUntil,
			'message' => $tail,
		);
	}

	/**
	 * Hết hạn bảo lưu 1 năm: lớp 990k quay về tag Mới đăng ký.
	 */
	public static function expireDueGd14Retentions($limit = 40) {
		require_once 'modules/Leads/models/ModernService.php';
		$adb = PearDatabase::getInstance();
		if (!Leads_ModernService::isInstalled($adb)) {
			return 0;
		}
		$limit = max(1, min(80, (int) $limit));
		$res = $adb->pquery(
			"SELECT leadid, verify_extra_json FROM bace_lead_profile
			 WHERE verify_extra_json LIKE '%gd14_retention_until%'
			   AND verify_extra_json NOT LIKE '%gd14_retention_expired\":1%'
			   AND verify_extra_json NOT LIKE '%gd14_retention_expired\":true%'
			 LIMIT {$limit}",
			array()
		);
		if (!$res) {
			return 0;
		}
		$changed = 0;
		$now = time();
		while ($row = $adb->fetchByAssoc($res)) {
			$extra = json_decode((string) $row['verify_extra_json'], true);
			if (!is_array($extra) || !empty($extra['gd14_retention_expired'])) {
				continue;
			}
			if ((string) (isset($extra['gd14_course']) ? $extra['gd14_course'] : '') !== 'lop_990k') {
				continue;
			}
			$until = strtotime((string) (isset($extra['gd14_retention_until']) ? $extra['gd14_retention_until'] : ''));
			if (!$until || $until > $now) {
				continue;
			}
			self::markGd14RetentionExpired($adb, (int) $row['leadid'], $extra);
			$changed++;
		}
		return $changed;
	}

	protected static function markGd14RetentionExpired($adb, $leadId, array $extra) {
		$extra['gd14_retention_expired'] = 1;
		$extra['gd14_outcome'] = 'moi_dang_ky';
		self::storeGd14Extra($adb, $leadId, $extra);
		require_once 'modules/Leads/models/ConvertService.php';
		$contactId = (int) Leads_ConvertService::getLinkedContactId($leadId, true);
		if ($contactId > 0) {
			self::markPaidContact($contactId, 'gd14_moi_dang_ky', 0);
		}
		$conv = $adb->pquery('SELECT converted FROM vtiger_leaddetails WHERE leadid = ?', array((int) $leadId));
		$converted = ($conv && $adb->num_rows($conv) > 0) ? (int) $adb->query_result($conv, 0, 'converted') : 1;
		if ($converted === 0) {
			self::replaceGd14Tag($leadId, 'gd14_moi_dang_ky', 0);
		}
	}

	protected static function resolveGd14LeadId($leadIdOrCacheId) {
		require_once 'modules/Leads/models/ModernService.php';
		$leadId = Leads_ModernService::resolveLeadRecordId($leadIdOrCacheId);
		if (!$leadId && is_numeric($leadIdOrCacheId)) {
			$leadId = (int) $leadIdOrCacheId;
		}
		if (!$leadId) {
			throw new Exception('Lead not found.');
		}
		return (int) $leadId;
	}

	protected static function readGd14AnswerPayload(array $payload, array $bank, $required) {
		$answers = array();
		$missing = '';
		foreach ($bank['questions'] as $question) {
			$qid = $question['id'];
			$code = strtolower(trim((string) (isset($payload[$qid]) ? $payload[$qid] : '')));
			$allowed = array();
			foreach ($question['options'] as $opt) {
				$allowed[] = strtolower((string) $opt['code']);
			}
			if ($code === '' || !in_array($code, $allowed, true)) {
				$missing = (string) $question['label'];
				continue;
			}
			$answers[$qid] = $code;
		}
		if ($required && ($missing !== '' || count($answers) !== count($bank['questions']))) {
			throw new Exception('Chọn đủ câu xác minh 990k' . ($missing !== '' ? ': ' . $missing : '.'));
		}
		return $answers;
	}

	public static function decodeStoredJson($raw) {
		if (is_array($raw)) {
			return $raw;
		}
		$text = trim((string) $raw);
		if ($text === '') {
			return array();
		}
		if (function_exists('decode_html')) {
			$text = decode_html($text);
		} else {
			$prev = $text;
			for ($i = 0; $i < 3; $i++) {
				$decoded = html_entity_decode($prev, ENT_QUOTES | ENT_HTML5, 'UTF-8');
				if ($decoded === $prev) {
					break;
				}
				$prev = $decoded;
			}
			$text = $prev;
		}
		$data = json_decode($text, true);
		return is_array($data) ? $data : array();
	}

	/**
	 * R1 liên hệ không thành, R2 đã chọn khoá chưa thanh toán, R3 đã tư vấn chưa chọn khoá. Đủ 3 lần thì ngưng.
	 */
	protected static function bumpGd14Drop(array &$extra, $outcome) {
		$map = array(
			'hen_goi_lai' => array('field' => 'gd14_r1', 'code' => 'R1', 'reason' => 'Liên hệ không thành quá 3 lần'),
			'khong_nghe_may' => array('field' => 'gd14_r1', 'code' => 'R1', 'reason' => 'Liên hệ không thành quá 3 lần'),
			'sai_thong_tin' => array('field' => 'gd14_r1', 'code' => 'R1', 'reason' => 'Liên hệ không thành quá 3 lần'),
			'chon_khoa' => array('field' => 'gd14_r2', 'code' => 'R2', 'reason' => 'Không thanh toán'),
			'dang_can_nhac' => array('field' => 'gd14_r3', 'code' => 'R3', 'reason' => 'Đã tư vấn nhưng không chọn khoá quá 3 lần'),
		);
		if (!isset($map[$outcome])) {
			return array('stopped' => false, 'code' => '', 'reason' => '', 'count' => 0);
		}
		$spec = $map[$outcome];
		$count = isset($extra[$spec['field']]) ? (int) $extra[$spec['field']] : 0;
		$count = min(3, $count + 1);
		$extra[$spec['field']] = $count;
		$stopped = $count >= 3;
		if ($stopped) {
			$extra['gd14_drop'] = $spec['code'];
			$extra['gd14_drop_reason'] = $spec['reason'];
			$extra['gd14_outcome'] = 'ngung';
		}
		return array(
			'stopped' => $stopped,
			'code' => $spec['code'],
			'reason' => $spec['reason'],
			'count' => $count,
		);
	}

	protected static function loadGd14Extra($adb, $leadId) {
		$res = $adb->pquery('SELECT verify_extra_json FROM bace_lead_profile WHERE leadid = ?', array((int) $leadId));
		if ($res && $adb->num_rows($res) > 0) {
			return self::decodeStoredJson($adb->query_result($res, 0, 'verify_extra_json'));
		}
		return array();
	}

	protected static function storeGd14Extra($adb, $leadId, array $extra) {
		$json = json_encode($extra, JSON_UNESCAPED_UNICODE);
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT leadid FROM bace_lead_profile WHERE leadid = ?', array((int) $leadId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_lead_profile SET verify_extra_json = ?, modified_at = ? WHERE leadid = ?',
				array($json, $now, (int) $leadId)
			);
			return;
		}
		require_once 'modules/Leads/models/ModernService.php';
		Leads_ModernService::installSchema($adb);
		$adb->pquery(
			'INSERT INTO bace_lead_profile (leadid, verify_extra_json, modified_at) VALUES (?,?,?)',
			array((int) $leadId, $json, $now)
		);
	}

	protected static function replaceGd14Tag($leadId, $nextTag, $userId) {
		require_once 'modules/Leads/models/ModernService.php';
		global $current_user;
		if ((int) $userId <= 0) {
			$userId = (!empty($current_user) && !empty($current_user->id)) ? (int) $current_user->id : 1;
		}
		$lead = Leads_ModernService::getLead($leadId, $userId);
		$tags = isset($lead['tags']) && is_array($lead['tags']) ? $lead['tags'] : array();
		$pool = array_keys(self::gd14TagCatalog());
		$pool[] = 'gd14_990';
		$pool[] = '990k';
		$pool[] = '990';
		$keep = array();
		foreach ($tags as $name) {
			$key = strtolower(trim((string) $name));
			if ($key === '' || in_array($key, $pool, true) || strpos($key, 'gd14_') === 0) {
				continue;
			}
			$keep[] = (string) $name;
		}
		$keep[] = $nextTag;
		Leads_ModernService::syncTagsPublic((int) $leadId, $keep, (int) $userId);
	}

	protected static function markPaidContact($contactId, $tagName, $userId) {
		global $current_user;
		$contactId = (int) $contactId;
		if ($contactId <= 0 || $tagName === '') {
			return;
		}
		if ((int) $userId <= 0) {
			$userId = (!empty($current_user) && !empty($current_user->id)) ? (int) $current_user->id : 1;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		$existing = Vtiger_Tag_Model::getAllAccessible($userId, 'Contacts', $contactId);
		$remove = array();
		foreach ($existing as $tagModel) {
			$name = strtolower(trim((string) $tagModel->getName()));
			if (strpos($name, 'gd14_') === 0) {
				$remove[] = (int) $tagModel->getId();
			}
		}
		if (!empty($remove)) {
			Vtiger_Tag_Model::deleteForRecord($contactId, $remove, $userId, 'Contacts');
		}
		$tagModel = Vtiger_Tag_Model::getInstanceByName($tagName, $userId);
		if ($tagModel) {
			$tagId = (int) $tagModel->getId();
		} else {
			$newTag = new Vtiger_Tag_Model();
			$newTag->setName($tagName)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$tagId = (int) $newTag->create();
		}
		if ($tagId > 0) {
			Vtiger_Tag_Model::saveForRecord($contactId, array($tagId), $userId, 'Contacts');
		}
	}

	public static function saveForLead($leadIdOrCacheId, array $payload, $userId = 0) {
		require_once 'modules/Leads/models/ModernService.php';
		require_once 'modules/Leads/models/SheetImportService.php';
		require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		Leads_ModernService::installSchema($adb);

		$leadId = Leads_ModernService::resolveLeadRecordId($leadIdOrCacheId);
		if (!$leadId && is_numeric($leadIdOrCacheId)) {
			$leadId = (int) $leadIdOrCacheId;
		}
		if (!$leadId) {
			throw new Exception('Lead not found.');
		}

		if (self::isAnswersLocked($leadId)) {
			throw new Exception(
				'Đáp án đã khoá sau khi thông báo kết quả. Khách muốn đổi thì đăng ký lại form sau 3 tháng.'
			);
		}

		$c1 = strtoupper(trim((string) (isset($payload['c1']) ? $payload['c1'] : '')));
		$c2 = strtoupper(trim((string) (isset($payload['c2']) ? $payload['c2'] : '')));
		$c3 = strtoupper(trim((string) (isset($payload['c3']) ? $payload['c3'] : '')));
		$reason = isset($payload['change_reason']) ? trim((string) $payload['change_reason']) : '';

		if ($c1 === '' || $c2 === '' || $c3 === '') {
			throw new Exception('Thiếu C1 / C2 / C3 sau xác minh.');
		}
		$extra = array();
		if (!empty($payload['extra']) && is_array($payload['extra'])) {
			foreach ($payload['extra'] as $qid => $ans) {
				$qid = strtolower(trim((string) $qid));
				$ans = strtoupper(trim((string) $ans));
				if ($qid === '' || $ans === '') {
					continue;
				}
				$extra[$qid] = $ans;
			}
		}
		$bank = self::getScreeningBank();
		foreach ($bank['questions'] as $q) {
			if (empty($q['active']) || empty($q['required'])) {
				continue;
			}
			$qid = $q['id'];
			if (in_array($qid, array('c1', 'c2', 'c3'), true)) {
				continue;
			}
			if (empty($extra[$qid])) {
				throw new Exception('Thiếu câu bắt buộc: ' . $q['label']);
			}
		}

		$exists = $adb->pquery('SELECT leadid, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if (!$exists || $adb->num_rows($exists) < 1) {
			throw new Exception('Lead profile missing.');
		}

		$formC1 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c1')));
		$formC2 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c2')));
		$formC3 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c3')));

		$result = self::compute(array_merge(array(
			'c1' => $c1,
			'c2' => $c2,
			'c3' => $c3,
		), $extra));
		if (empty($result['success'])) {
			throw new Exception(isset($result['reason']) && $result['reason'] !== '' ? $result['reason'] : 'Không chấm được bộ 3 câu.');
		}

		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;

		$adb->pquery(
			'UPDATE bace_lead_profile SET
				verify_c1=?, verify_c2=?, verify_c3=?, verify_c4=NULL, verify_c5=NULL,
				eligibility_result=?, potential_level=?, verify_score=NULL,
				verify_change_reason=?, verified_at=?, verified_by=?,
				answers_locked_at=?, answers_locked_by=?, verify_extra_json=?, modified_at=?
			 WHERE leadid=?',
			array(
				$c1,
				$c2,
				$c3,
				$result['eligibility_result'],
				$result['potential_level'] !== '' ? $result['potential_level'] : null,
				$reason !== '' ? $reason : null,
				$now,
				$userId > 0 ? $userId : null,
				$now,
				$userId > 0 ? $userId : null,
				$extra ? json_encode($extra) : null,
				$now,
				$leadId,
			)
		);

		$biz = isset($result['business_model']) && $result['business_model'] !== ''
			? $result['business_model']
			: Vtiger_BusinessModel_Helper::fromFormAnswer($c2);
		$patch = array(
			'business_model' => $biz,
		);
		if ($result['potential_level'] === 'sieu_tiem_nang' || $result['potential_level'] === 'tiem_nang') {
			$patch['screening_result'] = $result['potential_level'];
		} elseif ($result['eligibility_result'] === 'khong_du_dk') {
			$patch['screening_result'] = 'so_luoc_khong_dk';
		}

		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		$tags = isset($lead['tags']) && is_array($lead['tags']) ? $lead['tags'] : array();
		$tags = self::applyPotentialTags($tags, $result['potential_level'], $result['eligibility_result']);
		$cust = Leads_SheetImportService::customerTagFromQ1($c1);
		if ($cust !== '') {
			$pool = array('chuan_bi_mo', 'co_quan', 'gia_dinh');
			$tags = array_values(array_filter($tags, function ($t) use ($pool) {
				return !in_array(strtolower((string) $t), $pool, true);
			}));
			$tags[] = $cust;
		}

		$savePayload = array(
			'name' => isset($lead['name']) ? $lead['name'] : '',
			'phone' => isset($lead['phone']) ? $lead['phone'] : '',
			'email' => isset($lead['email']) ? $lead['email'] : '',
			'address' => isset($lead['address']) ? $lead['address'] : '',
			'district' => isset($lead['district']) ? $lead['district'] : '',
			'tags' => $tags,
			'business_model' => $biz,
			'screening_result' => isset($patch['screening_result']) ? $patch['screening_result'] : (isset($lead['screening_result']) ? $lead['screening_result'] : ''),
			'qa_raw' => isset($lead['qa_raw']) ? $lead['qa_raw'] : null,
			'owner' => isset($lead['owner_username']) ? $lead['owner_username'] : '',
		);
		Leads_ModernService::saveLead($savePayload, $leadId);

		$offlineMeta = null;
		try {
			require_once 'modules/Leads/models/OfflineGd11Service.php';
			$scheduleOutcome = isset($payload['schedule_outcome']) ? $payload['schedule_outcome'] : '';
			$result['schedule_outcome'] = $scheduleOutcome;
			if (!empty($payload['class_date'])) {
				$result['class_date'] = $payload['class_date'];
			}
			$offlineMeta = Leads_OfflineGd11Service::onSalesVerified($leadId, $result, $userId);
		} catch (Exception $e) {
			$offlineMeta = array('error' => $e->getMessage());
		}

		$fresh = Leads_ModernService::getLead((string) $leadId, $userId);
		$out = array(
			'success' => true,
			'result' => $result,
			'form_c1' => $formC1,
			'form_c2' => $formC2,
			'form_c3' => $formC3,
			'lead' => $fresh,
		);
		if (is_array($offlineMeta)) {
			$out['offline'] = $offlineMeta;
			if (!empty($offlineMeta['convert'])) {
				$out['convert'] = $offlineMeta['convert'];
			}
			if (!empty($offlineMeta['calendar'])) {
				$out['calendar'] = $offlineMeta['calendar'];
			}
		}
		return $out;
	}

	public static function applyPotentialTags(array $tags, $potentialLevel, $eligibility) {
		$pool = array('tiem_nang', 'sieu_tiem_nang', 'binh_thuong');
		$out = array();
		foreach ($tags as $tag) {
			$key = strtolower(trim((string) $tag));
			if (in_array($key, $pool, true)) {
				continue;
			}
			$out[] = $tag;
		}
		if ($eligibility === 'du_dk') {
			if ($potentialLevel === 'sieu_tiem_nang') {
				$out[] = 'sieu_tiem_nang';
			} elseif ($potentialLevel === 'tiem_nang') {
				$out[] = 'tiem_nang';
			} elseif ($potentialLevel === 'binh_thuong') {
				$out[] = 'binh_thuong';
			}
		}
		return array_values(array_unique($out));
	}

	/** Lưu đáp án Form — chỉ ghi khi cột còn trống; hoặc ghi đè nếu đã mở khoá sau 3 tháng. */
	public static function seedFormAnswers($leadId, $c1, $c2, $c3) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$c1 = strtoupper(trim((string) $c1));
		$c2 = strtoupper(trim((string) $c2));
		$c3 = strtoupper(trim((string) $c3));
		if ($c1 === '' && $c2 === '' && $c3 === '') {
			return;
		}
		$unlocked = self::unlockAnswersIfReFormAllowed($leadId);
		$res = $adb->pquery(
			'SELECT form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE leadid = ?',
			array((int) $leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return;
		}
		$fc1 = trim((string) $adb->query_result($res, 0, 'form_c1'));
		$fc2 = trim((string) $adb->query_result($res, 0, 'form_c2'));
		$fc3 = trim((string) $adb->query_result($res, 0, 'form_c3'));
		if ($unlocked) {
			// Form mới sau ≥ 3 tháng: ghi đè đáp án form; verify sẽ do Sales chấm lại.
			$adb->pquery(
				'UPDATE bace_lead_profile SET form_c1=?, form_c2=?, form_c3=?,
					verify_c1=NULL, verify_c2=NULL, verify_c3=NULL,
					eligibility_result=NULL, potential_level=NULL, verified_at=NULL, verified_by=NULL,
					modified_at=? WHERE leadid=?',
				array(
					$c1 !== '' ? $c1 : null,
					$c2 !== '' ? $c2 : null,
					$c3 !== '' ? $c3 : null,
					date('Y-m-d H:i:s'),
					(int) $leadId,
				)
			);
			return;
		}
		$adb->pquery(
			'UPDATE bace_lead_profile SET form_c1=?, form_c2=?, form_c3=?, modified_at=? WHERE leadid=?',
			array(
				$fc1 !== '' ? $fc1 : ($c1 !== '' ? $c1 : null),
				$fc2 !== '' ? $fc2 : ($c2 !== '' ? $c2 : null),
				$fc3 !== '' ? $fc3 : ($c3 !== '' ? $c3 : null),
				date('Y-m-d H:i:s'),
				(int) $leadId,
			)
		);
	}

	public static function optionsCatalog() {
		$bank = self::getScreeningBank();
		$byId = array();
		$extra = array();
		foreach ($bank['questions'] as $q) {
			if (empty($q['active'])) {
				continue;
			}
			$byId[$q['id']] = $q;
			if (!in_array($q['id'], array('c1', 'c2', 'c3'), true)) {
				$extra[] = $q;
			}
		}
		$pick = function ($id, $fallbackLabel) use ($byId) {
			if (!isset($byId[$id])) {
				return array('label' => $fallbackLabel, 'options' => array());
			}
			return $byId[$id];
		};
		$c1 = $pick('c1', 'Câu 1 — Tình trạng hiện tại');
		$c2 = $pick('c2', 'Câu 2 — Mô hình dự định / đang kinh doanh');
		$c3 = $pick('c3', 'Câu 3 — Khả năng tài chính tối đa');
		return array(
			'c1' => $c1['options'],
			'c2' => $c2['options'],
			'c3' => $c3['options'],
			'c1_label' => $c1['label'],
			'c2_label' => $c2['label'],
			'c3_label' => $c3['label'],
			'extra' => $extra,
			'questions' => $bank['questions'],
			'levels' => $bank['levels'],
			'ask_c4_c5' => false,
		);
	}

	public static function defaultScreeningBank() {
		return array(
			'questions' => array(
				array(
					'id' => 'c1',
					'label' => 'Câu 1 — Tình trạng hiện tại',
					'required' => true,
					'active' => true,
					'core' => true,
					'options' => array(
						array('code' => 'A', 'label' => 'Chuẩn bị mở quán'),
						array('code' => 'B', 'label' => 'Đã có quán'),
						array('code' => 'C', 'label' => 'Học pha chế để phục vụ gia đình hoặc sở thích cá nhân'),
					),
				),
				array(
					'id' => 'c2',
					'label' => 'Câu 2 — Mô hình dự định / đang kinh doanh',
					'required' => true,
					'active' => true,
					'core' => true,
					'options' => array(
						array('code' => 'A', 'label' => 'Thuê mặt bằng / có sẵn mặt bằng để mở quán'),
						array('code' => 'B', 'label' => 'Mở vỉa hè / bán online'),
					),
				),
				array(
					'id' => 'c3',
					'label' => 'Câu 3 — Khả năng tài chính tối đa',
					'required' => true,
					'active' => true,
					'core' => true,
					'options' => array(
						array('code' => 'A', 'label' => 'Dưới 100 triệu'),
						array('code' => 'B', 'label' => 'Từ 100 đến dưới 200 triệu'),
						array('code' => 'C', 'label' => 'Từ 200 đến dưới 300 triệu'),
						array('code' => 'D', 'label' => 'Từ 300 đến dưới 400 triệu'),
						array('code' => 'E', 'label' => 'Từ 400 đến dưới 500 triệu'),
						array('code' => 'F', 'label' => 'Từ 500 triệu trở lên'),
					),
				),
			),
			'levels' => array(
				array('code' => 'khong_du_dk', 'label' => 'Không đủ điều kiện', 'priority' => 10, 'when' => array(array('q' => 'c1', 'op' => 'eq', 'value' => 'C'))),
				array('code' => 'sieu_tiem_nang', 'label' => 'Siêu tiềm năng', 'priority' => 20, 'when' => array(
					array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'),
					array('q' => 'c3', 'op' => 'eq', 'value' => 'F'),
				)),
				array('code' => 'tiem_nang', 'label' => 'Tiềm năng', 'priority' => 30, 'when' => array(
					array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'),
					array('q' => 'c2', 'op' => 'eq', 'value' => 'A'),
				)),
				array('code' => 'binh_thuong', 'label' => 'Bình thường', 'priority' => 40, 'when' => array(
					array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'),
				)),
			),
		);
	}

	public static function defaultGd14QuestionBank() {
		return array(
			'questions' => array(
				array(
					'id' => 'c1',
					'label' => 'Câu 1 — Tình trạng hiện tại',
					'options' => array(
						array('code' => 'a', 'label' => 'Chuẩn bị mở quán'),
						array('code' => 'b', 'label' => 'Đã có quán nhưng đang gặp vấn đề'),
						array('code' => 'c', 'label' => 'Đã có quán muốn cập nhật kiến thức'),
						array('code' => 'd', 'label' => 'Học pha chế để phục vụ gia đình hoặc sở thích'),
					),
				),
				array(
					'id' => 'c2',
					'label' => 'Câu 2 — Mô hình',
					'options' => array(
						array('code' => 'a', 'label' => 'Xe đẩy vỉa hè'),
						array('code' => 'b', 'label' => 'Bán online'),
						array('code' => 'c', 'label' => 'Có mặt bằng — bán take away'),
						array('code' => 'd', 'label' => 'Có mặt bằng — bán ngồi lại'),
						array('code' => 'e', 'label' => 'Phục vụ gia đình, sở thích cá nhân'),
					),
				),
				array(
					'id' => 'c3',
					'label' => 'Câu 3 — Khả năng tài chính tối đa',
					'options' => array(
						array('code' => 'a', 'label' => 'Dưới 100 triệu'),
						array('code' => 'b', 'label' => 'Từ 100 triệu đến dưới 300 triệu'),
						array('code' => 'c', 'label' => 'Từ 300 triệu đến dưới 500 triệu'),
						array('code' => 'd', 'label' => 'Từ 500 triệu trở lên'),
					),
				),
			),
			'results' => self::defaultGd14Results(),
		);
	}

	public static function defaultGd14Results() {
		return array(
			array('group' => 'chan_moi', 'label' => 'Không mời Combo và Mở quán bài bản — xe đẩy hoặc bán online', 'priority' => 10, 'when' => array(array('q' => 'c2', 'op' => 'in', 'value' => 'a,b'))),
			array('group' => 'chan_moi', 'label' => 'Không mời Combo và Mở quán bài bản — học vì sở thích', 'priority' => 20, 'when' => array(array('q' => 'c1', 'op' => 'eq', 'value' => 'd'))),
			array('group' => 'chan_moi', 'label' => 'Không mời Combo và Mở quán bài bản — mô hình sở thích', 'priority' => 30, 'when' => array(array('q' => 'c2', 'op' => 'eq', 'value' => 'e'))),
			array('group' => 'chan_moi', 'label' => 'Không mời Combo và Mở quán bài bản — ngân sách dưới 100 triệu', 'priority' => 40, 'when' => array(array('q' => 'c3', 'op' => 'eq', 'value' => 'a'))),
			array('group' => 'loi_tu_van', 'label' => 'Lời tư vấn: Sở thích', 'priority' => 10, 'when' => array(array('q' => 'c1', 'op' => 'eq', 'value' => 'd'))),
			array('group' => 'loi_tu_van', 'label' => 'Lời tư vấn: Sở thích', 'priority' => 20, 'when' => array(array('q' => 'c2', 'op' => 'eq', 'value' => 'e'))),
			array('group' => 'loi_tu_van', 'label' => 'Lời tư vấn: Xe đẩy / online', 'priority' => 30, 'when' => array(array('q' => 'c2', 'op' => 'in', 'value' => 'a,b'))),
			array('group' => 'mau_thuan', 'label' => 'Hỏi lại câu 1 và câu 2 vì một bên là sở thích, bên kia không phải', 'priority' => 10, 'when' => array(
				array('q' => 'c1', 'op' => 'eq', 'value' => 'd'),
				array('q' => 'c2', 'op' => 'neq', 'value' => 'e'),
			)),
			array('group' => 'mau_thuan', 'label' => 'Hỏi lại câu 1 và câu 2 vì một bên là sở thích, bên kia không phải', 'priority' => 20, 'when' => array(
				array('q' => 'c1', 'op' => 'neq', 'value' => 'd'),
				array('q' => 'c2', 'op' => 'eq', 'value' => 'e'),
			)),
		);
	}

	public static function classifyGd14Answers(array $answers, array $bank = null) {
		if ($bank === null) {
			$bank = self::getGd14QuestionBank();
		}
		$results = isset($bank['results']) && is_array($bank['results']) ? $bank['results'] : self::defaultGd14Results();
		$matched = array('chan_moi' => array(), 'loi_tu_van' => array(), 'mau_thuan' => array());
		foreach ($results as $row) {
			if (!is_array($row)) {
				continue;
			}
			$group = isset($row['group']) ? (string) $row['group'] : '';
			if (!isset($matched[$group])) {
				continue;
			}
			if (!self::gd14WhenMatches(isset($row['when']) ? $row['when'] : array(), $answers)) {
				continue;
			}
			$matched[$group][] = $row;
		}
		$invite = empty($matched['chan_moi'])
			? 'Được mời Combo và Mở quán bài bản. Vẫn nói Pha chế tổng hợp cùng lớp 990k.'
			: 'Không mời Combo và Mở quán bài bản. Vẫn nói Pha chế tổng hợp cùng lớp 990k.';
		$reasons = array();
		foreach ($matched['chan_moi'] as $row) {
			$reasons[] = (string) $row['label'];
		}
		usort($matched['loi_tu_van'], function ($a, $b) {
			$pa = isset($a['priority']) ? (int) $a['priority'] : 100;
			$pb = isset($b['priority']) ? (int) $b['priority'] : 100;
			if ($pa === $pb) {
				return 0;
			}
			return ($pa < $pb) ? -1 : 1;
		});
		$variant = !empty($matched['loi_tu_van'])
			? (string) $matched['loi_tu_van'][0]['label']
			: 'Lời tư vấn: Quán';
		$conflict = empty($matched['mau_thuan'])
			? 'Đáp án khớp nhau, không cần hỏi lại.'
			: (string) $matched['mau_thuan'][0]['label'];
		return array(
			'invite' => $invite,
			'invite_reasons' => $reasons,
			'variant' => $variant,
			'conflict' => $conflict,
			'invited' => empty($matched['chan_moi']) ? 1 : 0,
			'contradict' => empty($matched['mau_thuan']) ? 0 : 1,
		);
	}

	protected static function gd14WhenMatches($when, array $answers) {
		if (!is_array($when) || empty($when)) {
			return false;
		}
		foreach ($when as $cond) {
			if (!is_array($cond)) {
				return false;
			}
			$q = strtolower(trim((string) (isset($cond['q']) ? $cond['q'] : '')));
			$op = strtolower(trim((string) (isset($cond['op']) ? $cond['op'] : 'eq')));
			$want = strtolower(trim((string) (isset($cond['value']) ? $cond['value'] : '')));
			$got = isset($answers[$q]) ? strtolower(trim((string) $answers[$q])) : '';
			if ($got === '') {
				return false;
			}
			if ($op === 'in') {
				$bag = array();
				foreach (explode(',', $want) as $part) {
					$part = trim($part);
					if ($part !== '') {
						$bag[] = $part;
					}
				}
				if (!in_array($got, $bag, true)) {
					return false;
				}
			} elseif ($op === 'neq') {
				if ($got === $want) {
					return false;
				}
			} elseif ($got !== $want) {
				return false;
			}
		}
		return true;
	}

	public static function getGd14QuestionBank() {
		$adb = PearDatabase::getInstance();
		self::ensureScreeningBankTable($adb);
		$res = $adb->pquery('SELECT config_json FROM bace_screening_bank WHERE id = 2', array());
		if ($res && $adb->num_rows($res) > 0) {
			$data = json_decode((string) $adb->query_result($res, 0, 'config_json'), true);
			if (is_array($data) && !empty($data['questions'])) {
				return self::sanitizeGd14QuestionBank($data);
			}
		}
		$bank = self::defaultGd14QuestionBank();
		self::saveGd14QuestionBank($bank);
		return $bank;
	}

	public static function saveGd14QuestionBank(array $payload) {
		$adb = PearDatabase::getInstance();
		self::ensureScreeningBankTable($adb);
		$bank = self::sanitizeGd14QuestionBank($payload);
		$json = json_encode($bank, JSON_UNESCAPED_UNICODE);
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT id FROM bace_screening_bank WHERE id = 2', array());
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery('UPDATE bace_screening_bank SET config_json = ?, modified_at = ? WHERE id = 2', array($json, $now));
		} else {
			$adb->pquery('INSERT INTO bace_screening_bank (id, config_json, modified_at) VALUES (2, ?, ?)', array($json, $now));
		}
		return $bank;
	}

	public static function sanitizeGd14QuestionBank(array $payload) {
		$questions = array();
		$seen = array();
		$rows = isset($payload['questions']) && is_array($payload['questions']) ? $payload['questions'] : array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = strtolower(trim((string) (isset($row['id']) ? $row['id'] : '')));
			$id = preg_replace('/[^a-z0-9_]/', '', $id);
			if ($id === '' || isset($seen[$id])) {
				continue;
			}
			$options = array();
			if (!empty($row['options']) && is_array($row['options'])) {
				foreach ($row['options'] as $opt) {
					if (!is_array($opt)) {
						continue;
					}
					$code = strtolower(trim((string) (isset($opt['code']) ? $opt['code'] : '')));
					$code = preg_replace('/[^a-z0-9]/', '', $code);
					$label = trim((string) (isset($opt['label']) ? $opt['label'] : ''));
					if ($code === '' || $label === '') {
						continue;
					}
					$options[] = array('code' => $code, 'label' => $label);
				}
			}
			if (!$options) {
				continue;
			}
			$seen[$id] = true;
			$questions[] = array(
				'id' => $id,
				'label' => trim((string) (isset($row['label']) ? $row['label'] : $id)),
				'options' => $options,
			);
		}
		if (empty($questions)) {
			return self::defaultGd14QuestionBank();
		}
		$results = array();
		$rows = isset($payload['results']) && is_array($payload['results']) ? $payload['results'] : array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$group = isset($row['group']) ? (string) $row['group'] : '';
			if (!in_array($group, array('chan_moi', 'loi_tu_van', 'mau_thuan'), true)) {
				continue;
			}
			$label = trim((string) (isset($row['label']) ? $row['label'] : ''));
			$when = array();
			if (!empty($row['when']) && is_array($row['when'])) {
				foreach ($row['when'] as $cond) {
					if (!is_array($cond)) {
						continue;
					}
					$q = strtolower(preg_replace('/[^a-z0-9_]/', '', (string) (isset($cond['q']) ? $cond['q'] : '')));
					$op = strtolower(trim((string) (isset($cond['op']) ? $cond['op'] : 'eq')));
					if (!in_array($op, array('eq', 'in', 'neq'), true)) {
						$op = 'eq';
					}
					$value = strtolower(trim((string) (isset($cond['value']) ? $cond['value'] : '')));
					if ($q === '' || $value === '') {
						continue;
					}
					$when[] = array('q' => $q, 'op' => $op, 'value' => $value);
				}
			}
			if ($label === '' || empty($when)) {
				continue;
			}
			$results[] = array(
				'group' => $group,
				'label' => $label,
				'priority' => isset($row['priority']) ? (int) $row['priority'] : 100,
				'when' => $when,
			);
		}
		if (empty($results)) {
			$results = self::defaultGd14Results();
		}
		return array('questions' => $questions, 'results' => $results);
	}

	public static function getScreeningBank() {
		$adb = PearDatabase::getInstance();
		self::ensureScreeningBankTable($adb);
		$res = $adb->pquery('SELECT config_json FROM bace_screening_bank WHERE id = 1', array());
		if ($res && $adb->num_rows($res) > 0) {
			$raw = $adb->query_result($res, 0, 'config_json');
			$data = json_decode((string) $raw, true);
			if (is_array($data) && !empty($data['questions']) && !empty($data['levels'])) {
				return self::sanitizeScreeningBank($data);
			}
		}
		$bank = self::defaultScreeningBank();
		self::saveScreeningBank($bank);
		return $bank;
	}

	public static function saveScreeningBank(array $payload) {
		$adb = PearDatabase::getInstance();
		self::ensureScreeningBankTable($adb);
		$bank = self::sanitizeScreeningBank($payload);
		$json = json_encode($bank, JSON_UNESCAPED_UNICODE);
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT id FROM bace_screening_bank WHERE id = 1', array());
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery('UPDATE bace_screening_bank SET config_json = ?, modified_at = ? WHERE id = 1', array($json, $now));
		} else {
			$adb->pquery('INSERT INTO bace_screening_bank (id, config_json, modified_at) VALUES (1, ?, ?)', array($json, $now));
		}
		return $bank;
	}

	protected static function ensureScreeningBankTable(PearDatabase $adb) {
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS bace_screening_bank (
				id INT NOT NULL PRIMARY KEY,
				config_json MEDIUMTEXT,
				modified_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
	}

	public static function sanitizeScreeningBank(array $payload) {
		$base = self::defaultScreeningBank();
		$questions = array();
		$seen = array();
		$rows = isset($payload['questions']) && is_array($payload['questions']) ? $payload['questions'] : array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = strtolower(trim((string) (isset($row['id']) ? $row['id'] : '')));
			$id = preg_replace('/[^a-z0-9_]/', '', $id);
			if ($id === '' || isset($seen[$id])) {
				continue;
			}
			$core = in_array($id, array('c1', 'c2', 'c3'), true);
			$options = array();
			if (!empty($row['options']) && is_array($row['options'])) {
				foreach ($row['options'] as $opt) {
					if (!is_array($opt)) {
						continue;
					}
					$code = strtoupper(trim((string) (isset($opt['code']) ? $opt['code'] : '')));
					$label = trim((string) (isset($opt['label']) ? $opt['label'] : ''));
					if ($code === '' || $label === '') {
						continue;
					}
					$options[] = array('code' => $code, 'label' => $label);
				}
			}
			if (!$options) {
				continue;
			}
			$seen[$id] = true;
			$questions[] = array(
				'id' => $id,
				'label' => trim((string) (isset($row['label']) ? $row['label'] : $id)),
				'required' => $core ? true : !empty($row['required']),
				'active' => $core ? true : (!isset($row['active']) || !empty($row['active'])),
				'core' => $core,
				'options' => $options,
			);
		}
		foreach ($base['questions'] as $coreQ) {
			if (!isset($seen[$coreQ['id']])) {
				array_unshift($questions, $coreQ);
				$seen[$coreQ['id']] = true;
			}
		}
		$levels = array();
		$levelRows = isset($payload['levels']) && is_array($payload['levels']) ? $payload['levels'] : $base['levels'];
		foreach ($levelRows as $level) {
			if (!is_array($level)) {
				continue;
			}
			$code = strtolower(trim((string) (isset($level['code']) ? $level['code'] : '')));
			$code = preg_replace('/[^a-z0-9_]/', '', $code);
			if ($code === '') {
				continue;
			}
			$when = array();
			if (!empty($level['when']) && is_array($level['when'])) {
				foreach ($level['when'] as $cond) {
					if (!is_array($cond)) {
						continue;
					}
					$q = strtolower(trim((string) (isset($cond['q']) ? $cond['q'] : '')));
					$op = (isset($cond['op']) && $cond['op'] === 'in') ? 'in' : 'eq';
					$value = strtoupper(trim((string) (isset($cond['value']) ? $cond['value'] : '')));
					if ($q === '' || $value === '') {
						continue;
					}
					$when[] = array('q' => $q, 'op' => $op, 'value' => $value);
				}
			}
			$levels[] = array(
				'code' => $code,
				'label' => trim((string) (isset($level['label']) ? $level['label'] : $code)),
				'priority' => isset($level['priority']) ? (int) $level['priority'] : 100,
				'when' => $when,
			);
		}
		if (!$levels) {
			$levels = $base['levels'];
		}
		usort($levels, function ($a, $b) {
			return $a['priority'] - $b['priority'];
		});
		return array('questions' => $questions, 'levels' => $levels);
	}

	public static function matchScreeningLevel(array $bank, array $input) {
		$levels = isset($bank['levels']) ? $bank['levels'] : array();
		usort($levels, function ($a, $b) {
			return ((int) $a['priority']) - ((int) $b['priority']);
		});
		foreach ($levels as $level) {
			$when = isset($level['when']) ? $level['when'] : array();
			if (!$when) {
				continue;
			}
			$ok = true;
			foreach ($when as $cond) {
				$answer = strtoupper(trim((string) (isset($input[$cond['q']]) ? $input[$cond['q']] : '')));
				$value = strtoupper($cond['value']);
				if ($cond['op'] === 'in') {
					$parts = array_map('trim', explode(',', $value));
					if (!in_array($answer, $parts, true)) {
						$ok = false;
						break;
					}
				} elseif ($answer !== $value) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				return $level;
			}
		}
		return null;
	}

	/**
	 * Đem đáp án xác minh từ Lead sang Opp hoặc Khách hàng để đối chiếu sau.
	 */
	public static function copyLeadVerifyOnto($leadId, $targetModule, $targetId) {
		$leadId = (int) $leadId;
		$targetId = (int) $targetId;
		if ($leadId <= 0 || $targetId <= 0) {
			return;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return;
		}
		$pack = array(
			'copied_at' => date('Y-m-d H:i:s'),
			'lead_id' => $leadId,
			'eligibility_result' => (string) $adb->query_result($res, 0, 'eligibility_result'),
			'screening_result' => (string) $adb->query_result($res, 0, 'screening_result'),
			'form_c1' => (string) $adb->query_result($res, 0, 'form_c1'),
			'form_c2' => (string) $adb->query_result($res, 0, 'form_c2'),
			'form_c3' => (string) $adb->query_result($res, 0, 'form_c3'),
			'extra' => self::decodeStoredJson($adb->query_result($res, 0, 'verify_extra_json')),
		);
		$json = json_encode($pack, JSON_UNESCAPED_UNICODE);
		if ($targetModule === 'Potentials') {
			require_once 'modules/Potentials/models/ModernService.php';
			Potentials_ModernService::ensureProfileSchema($adb);
			self::storeVerifyJson($adb, 'bace_potential_profile', 'potentialid', $targetId, $json);
			return;
		}
		if ($targetModule === 'Contacts') {
			require_once 'modules/Contacts/models/ModernService.php';
			Contacts_ModernService::ensureBusinessModelSchema($adb);
			self::storeVerifyJson($adb, 'bace_contact_profile', 'contactid', $targetId, $json);
		}
	}

	public static function verificationLines($module, $recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$raw = '';
		if ($module === 'Potentials') {
			$raw = self::readVerifyJson($adb, 'bace_potential_profile', 'potentialid', $recordId);
			if ($raw === '') {
				$lead = $adb->pquery('SELECT leadid, verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE potential_id = ? ORDER BY leadid DESC LIMIT 1', array($recordId));
				return self::linesFromLeadRow($adb, $lead);
			}
		} elseif ($module === 'Contacts') {
			require_once 'modules/Contacts/models/ModernService.php';
			Contacts_ModernService::ensureBusinessModelSchema($adb);
			$raw = self::readVerifyJson($adb, 'bace_contact_profile', 'contactid', $recordId);
			if ($raw === '') {
				$lead = $adb->pquery('SELECT leadid, verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE contact_id = ? ORDER BY leadid DESC LIMIT 1', array($recordId));
				return self::linesFromLeadRow($adb, $lead);
			}
		}
		$pack = self::decodeStoredJson($raw);
		if (!self::packHasGd14($pack)) {
			if ($module === 'Potentials') {
				$lead = $adb->pquery('SELECT leadid, verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE potential_id = ? ORDER BY leadid DESC LIMIT 1', array($recordId));
				return self::linesFromLeadRow($adb, $lead);
			}
			if ($module === 'Contacts') {
				$lead = $adb->pquery('SELECT leadid, verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE contact_id = ? ORDER BY leadid DESC LIMIT 1', array($recordId));
				return self::linesFromLeadRow($adb, $lead);
			}
		}
		return self::linesFromPack($pack);
	}

	protected static function packHasGd14(array $pack) {
		$extra = isset($pack['extra']) && is_array($pack['extra']) ? $pack['extra'] : $pack;
		return !empty($extra['gd14_answers']) || !empty($extra['gd14_form_answers']) || !empty($extra['gd14_outcome'])
			|| !empty($pack['form_c1']) || !empty($pack['form_c2']) || !empty($pack['form_c3']);
	}

	protected static function linesFromLeadRow($adb, $lead) {
		if (!$lead || $adb->num_rows($lead) < 1) {
			return array();
		}
		return self::linesFromPack(array(
			'eligibility_result' => (string) $adb->query_result($lead, 0, 'eligibility_result'),
			'screening_result' => (string) $adb->query_result($lead, 0, 'screening_result'),
			'form_c1' => (string) $adb->query_result($lead, 0, 'form_c1'),
			'form_c2' => (string) $adb->query_result($lead, 0, 'form_c2'),
			'form_c3' => (string) $adb->query_result($lead, 0, 'form_c3'),
			'extra' => self::decodeStoredJson($adb->query_result($lead, 0, 'verify_extra_json')),
		));
	}

	protected static function linesFromPack(array $pack) {
		$lines = array();
		$extra = isset($pack['extra']) && is_array($pack['extra']) ? $pack['extra'] : array();
		$bank = self::defaultGd14QuestionBank();
		$questions = isset($bank['questions']) ? $bank['questions'] : array();
		$form = isset($extra['gd14_form_answers']) && is_array($extra['gd14_form_answers']) ? $extra['gd14_form_answers'] : array();
		$verified = isset($extra['gd14_answers']) && is_array($extra['gd14_answers']) ? $extra['gd14_answers'] : array();
		foreach ($questions as $q) {
			$qid = $q['id'];
			if (!empty($form[$qid])) {
				$lines[] = array('label' => 'Form · ' . $q['label'], 'value' => self::gd14OptionText($q, $form[$qid]));
			}
			if (!empty($verified[$qid])) {
				$lines[] = array('label' => 'Xác minh · ' . $q['label'], 'value' => self::gd14OptionText($q, $verified[$qid]));
			}
		}
		if (empty($verified) && empty($form)) {
			foreach (array('c1' => 'Câu 1', 'c2' => 'Câu 2', 'c3' => 'Câu 3') as $qid => $label) {
				$code = isset($pack['form_' . $qid]) ? trim((string) $pack['form_' . $qid]) : '';
				if ($code !== '') {
					$lines[] = array('label' => $label, 'value' => $code);
				}
			}
		}
		if (!empty($extra['gd14_goal'])) {
			$lines[] = array('label' => 'Mục tiêu khách nêu', 'value' => (string) $extra['gd14_goal']);
		}
		if (!empty($extra['gd14_course'])) {
			$courses = self::gd14CourseCatalog();
			$course = (string) $extra['gd14_course'];
			$lines[] = array(
				'label' => 'Khoá đã chọn',
				'value' => isset($courses[$course]['label']) ? $courses[$course]['label'] : $course,
			);
		}
		if (!empty($pack['eligibility_result'])) {
			$lines[] = array('label' => 'Điều kiện', 'value' => self::eligibilityLabel($pack['eligibility_result']));
		}
		if (!empty($pack['screening_result'])) {
			$lines[] = array('label' => 'Mức tiềm năng', 'value' => self::potentialLabel($pack['screening_result']));
		}
		return $lines;
	}

	protected static function gd14OptionText(array $question, $code) {
		$want = strtolower(trim((string) $code));
		foreach ($question['options'] as $opt) {
			if (strtolower((string) $opt['code']) === $want) {
				return (string) $opt['label'];
			}
		}
		return (string) $code;
	}

	protected static function storeVerifyJson($adb, $table, $pk, $recordId, $json) {
		$existsTable = $adb->pquery('SHOW TABLES LIKE ?', array($table));
		if (!$existsTable || $adb->num_rows($existsTable) < 1) {
			return;
		}
		$col = $adb->pquery('SHOW COLUMNS FROM ' . $table . " LIKE 'verify_json'", array());
		if (!$col || $adb->num_rows($col) < 1) {
			$adb->pquery('ALTER TABLE ' . $table . ' ADD COLUMN verify_json MEDIUMTEXT NULL', array());
		}
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT ' . $pk . ' FROM ' . $table . ' WHERE ' . $pk . ' = ?', array((int) $recordId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE ' . $table . ' SET verify_json = ? WHERE ' . $pk . ' = ?',
				array($json, (int) $recordId)
			);
			return;
		}
		if ($table === 'bace_potential_profile') {
			$adb->pquery(
				'INSERT INTO bace_potential_profile (potentialid, verify_json, modified_at) VALUES (?,?,?)',
				array((int) $recordId, $json, $now)
			);
			return;
		}
		$adb->pquery(
			'INSERT INTO bace_contact_profile (contactid, verify_json, modified_at) VALUES (?,?,?)',
			array((int) $recordId, $json, $now)
		);
	}

	protected static function readVerifyJson($adb, $table, $pk, $recordId) {
		$existsTable = $adb->pquery('SHOW TABLES LIKE ?', array($table));
		if (!$existsTable || $adb->num_rows($existsTable) < 1) {
			return '';
		}
		$col = $adb->pquery('SHOW COLUMNS FROM ' . $table . " LIKE 'verify_json'", array());
		if (!$col || $adb->num_rows($col) < 1) {
			return '';
		}
		$res = $adb->pquery('SELECT verify_json FROM ' . $table . ' WHERE ' . $pk . ' = ?', array((int) $recordId));
		if (!$res || $adb->num_rows($res) < 1) {
			return '';
		}
		return (string) $adb->query_result($res, 0, 'verify_json');
	}

	public static function gd14PublicState(array $extra) {
		return array(
			'class_date' => isset($extra['gd14_class_date']) ? (string) $extra['gd14_class_date'] : '',
			'class_time' => isset($extra['gd14_class_time']) ? (string) $extra['gd14_class_time'] : '',
			'class_place' => isset($extra['gd14_class_place']) ? (string) $extra['gd14_class_place'] : '',
			'preclass' => !empty($extra['gd14_preclass_confirm']) ? 1 : 0,
			'checked_in_at' => isset($extra['gd14_checked_in_at']) ? (string) $extra['gd14_checked_in_at'] : '',
			'r1' => isset($extra['gd14_r1']) ? (int) $extra['gd14_r1'] : 0,
			'r2' => isset($extra['gd14_r2']) ? (int) $extra['gd14_r2'] : 0,
			'r3' => isset($extra['gd14_r3']) ? (int) $extra['gd14_r3'] : 0,
			'r4' => isset($extra['gd14_r4']) ? (int) $extra['gd14_r4'] : 0,
			'drop' => isset($extra['gd14_drop']) ? (string) $extra['gd14_drop'] : '',
			'drop_reason' => isset($extra['gd14_drop_reason']) ? (string) $extra['gd14_drop_reason'] : '',
			'course' => isset($extra['gd14_course']) ? (string) $extra['gd14_course'] : '',
			'outcome' => isset($extra['gd14_outcome']) ? (string) $extra['gd14_outcome'] : '',
		);
	}

	public static function linesForLeadColumns(array $row) {
		return self::linesFromPack(array(
			'eligibility_result' => isset($row['eligibility_result']) ? (string) $row['eligibility_result'] : '',
			'screening_result' => isset($row['screening_result']) ? (string) $row['screening_result'] : '',
			'form_c1' => isset($row['form_c1']) ? (string) $row['form_c1'] : '',
			'form_c2' => isset($row['form_c2']) ? (string) $row['form_c2'] : '',
			'form_c3' => isset($row['form_c3']) ? (string) $row['form_c3'] : '',
			'extra' => self::decodeStoredJson(isset($row['verify_extra_json']) ? $row['verify_extra_json'] : ''),
		));
	}

	public static function gd14SnapshotsForContacts(array $contactIds) {
		$contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
		if (empty($contactIds)) {
			return array();
		}
		require_once 'modules/Leads/models/ModernService.php';
		$adb = PearDatabase::getInstance();
		if (!Leads_ModernService::isInstalled($adb)) {
			return array();
		}
		$res = $adb->pquery(
			'SELECT contact_id, verify_extra_json, eligibility_result, screening_result, form_c1, form_c2, form_c3
			 FROM bace_lead_profile
			 WHERE contact_id IN (' . generateQuestionMarks($contactIds) . ')
			 ORDER BY leadid DESC',
			$contactIds
		);
		$out = array();
		if (!$res) {
			return $out;
		}
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->raw_query_result_rowdata($res, $i);
			if (!is_array($row)) {
				continue;
			}
			$cid = isset($row['contact_id']) ? (int) $row['contact_id'] : 0;
			if ($cid <= 0 || isset($out[$cid])) {
				continue;
			}
			$extra = self::decodeStoredJson(isset($row['verify_extra_json']) ? $row['verify_extra_json'] : '');
			$out[$cid] = array(
				'lines' => self::linesForLeadColumns($row),
				'gd14' => self::gd14PublicState($extra),
				'compare' => self::gd14CompareState($extra, $row),
			);
		}
		return $out;
	}

	public static function gd14CompareState(array $extra, array $row = array()) {
		$form = isset($extra['gd14_form_answers']) && is_array($extra['gd14_form_answers']) ? $extra['gd14_form_answers'] : array();
		$answers = isset($extra['gd14_answers']) && is_array($extra['gd14_answers']) ? $extra['gd14_answers'] : array();
		foreach (array('c1', 'c2', 'c3') as $qid) {
			if (empty($form[$qid]) && !empty($row['form_' . $qid])) {
				$form[$qid] = strtolower(trim((string) $row['form_' . $qid]));
			}
		}
		$editable = !empty($answers) || !empty($extra['gd14_outcome']) || !empty($extra['gd14_course']) || !empty($extra['gd14_verified']);
		return array(
			'form' => $form,
			'answers' => $answers,
			'goal' => isset($extra['gd14_goal']) ? (string) $extra['gd14_goal'] : '',
			'course' => isset($extra['gd14_course']) ? (string) $extra['gd14_course'] : '',
			'editable' => $editable ? 1 : 0,
			'eligibility_result' => isset($row['eligibility_result']) ? strtolower(trim((string) $row['eligibility_result'])) : '',
		);
	}

	/**
	 * Sales sửa đáp án sau đối chiếu trên Khách hàng. Đáp án form khách đã khai giữ nguyên.
	 */
	public static function saveGd14ContactAnswers($contactId, array $payload, $userId = 0) {
		require_once 'modules/Leads/models/ModernService.php';
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			throw new Exception('Không tìm thấy khách hàng.');
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		Leads_ModernService::installSchema($adb);
		$res = $adb->pquery(
			'SELECT leadid, verify_extra_json FROM bace_lead_profile WHERE contact_id = ? ORDER BY leadid DESC LIMIT 1',
			array($contactId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			throw new Exception('Không tìm thấy hồ sơ đối chiếu của khách này.');
		}
		$leadId = (int) $adb->query_result($res, 0, 'leadid');
		$extra = self::decodeStoredJson($adb->query_result($res, 0, 'verify_extra_json'));
		$bank = self::getGd14QuestionBank();
		$answers = self::readGd14AnswerPayload($payload, $bank, false);
		if (!empty($answers)) {
			$extra['gd14_answers'] = $answers;
			foreach ($answers as $qid => $code) {
				$extra['gd14_' . $qid] = $code;
			}
			$extra['gd14_verified'] = 1;
			$extra['gd14_result'] = self::classifyGd14Answers($answers, $bank);
		}
		if (array_key_exists('goal', $payload)) {
			$extra['gd14_goal'] = trim((string) $payload['goal']);
		}
		$courses = self::gd14CourseCatalog();
		$course = strtolower(trim((string) (isset($payload['course']) ? $payload['course'] : '')));
		if ($course !== '' && isset($courses[$course])) {
			$extra['gd14_course'] = $course;
		}
		self::storeGd14Extra($adb, $leadId, $extra);
		try {
			self::copyLeadVerifyOnto($leadId, 'Contacts', $contactId);
		} catch (Exception $e) {
			// best-effort
		}
		return array(
			'success' => true,
			'compare' => self::gd14CompareState($extra),
			'gd14' => self::gd14PublicState($extra),
			'verify_lines' => self::linesFromPack(array('extra' => $extra)),
			'message' => 'Đã cập nhật đáp án sau đối chiếu.',
		);
	}

	/**
	 * Lớp 990k, chăm sóc trước buổi và điểm danh QR nằm trên Khách hàng sau khi đã thanh toán.
	 */
	public static function applyGd14ContactStep($contactId, $action, array $payload, $userId = 0) {
		require_once 'modules/Leads/models/ModernService.php';
		$contactId = (int) $contactId;
		$action = strtolower(trim((string) $action));
		if ($contactId <= 0) {
			throw new Exception('Không tìm thấy khách hàng.');
		}
		$allowed = array('schedule', 'preclass_yes', 'preclass_no', 'da_tham_gia', 'khong_tham_gia');
		if (!in_array($action, $allowed, true)) {
			throw new Exception('Thao tác lớp 990k không hợp lệ.');
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		Leads_ModernService::installSchema($adb);
		$res = $adb->pquery(
			'SELECT leadid, verify_extra_json FROM bace_lead_profile WHERE contact_id = ? ORDER BY leadid DESC LIMIT 1',
			array($contactId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			throw new Exception('Không tìm thấy hồ sơ 990k của khách này.');
		}
		$leadId = (int) $adb->query_result($res, 0, 'leadid');
		$extra = self::decodeStoredJson($adb->query_result($res, 0, 'verify_extra_json'));
		if (!empty($extra['gd14_drop']) && $extra['gd14_drop'] !== 'R4') {
			throw new Exception('Hồ sơ đã ngưng chăm sóc tại ' . $extra['gd14_drop'] . '.');
		}
		$tag = '';
		$note = '';
		if ($action === 'schedule') {
			$date = trim((string) (isset($payload['class_date']) ? $payload['class_date'] : ''));
			$time = trim((string) (isset($payload['class_time']) ? $payload['class_time'] : ''));
			$place = trim((string) (isset($payload['class_place']) ? $payload['class_place'] : ''));
			if ($date === '') {
				throw new Exception('Chọn ngày lớp 990k.');
			}
			$extra['gd14_class_date'] = $date;
			$extra['gd14_class_time'] = $time;
			$extra['gd14_class_place'] = $place;
			$tag = 'gd14_da_xac_nhan_lich';
			$note = 'Đã xếp lớp 990k ' . $date . ($time !== '' ? ' ' . $time : '');
		} elseif ($action === 'preclass_yes' || $action === 'preclass_no') {
			$extra['gd14_preclass_confirm'] = $action === 'preclass_yes' ? 1 : 0;
			$note = $action === 'preclass_yes' ? 'Khách xác nhận sẽ đến lớp 990k' : 'Khách chưa chắc sẽ đến lớp 990k';
		} elseif ($action === 'da_tham_gia') {
			$extra['gd14_checked_in_at'] = date('Y-m-d H:i:s');
			$tag = 'gd14_da_tham_gia';
			$note = 'Đã điểm danh lớp 990k';
		} else {
			$count = isset($extra['gd14_r4']) ? (int) $extra['gd14_r4'] : 0;
			$count = min(3, $count + 1);
			$extra['gd14_r4'] = $count;
			if ($count >= 3) {
				$extra['gd14_drop'] = 'R4';
				$extra['gd14_drop_reason'] = 'Đã thanh toán nhưng không tham gia quá 3 lần';
				$tag = 'gd14_ngung_cham_soc';
				$note = 'R4 3/3 · Ngưng chăm sóc: không tham gia lớp';
			} else {
				$tag = 'gd14_khong_tham_gia';
				$note = 'Không tham gia lớp 990k · R4 ' . $count . '/3. Có thể xếp lịch lại.';
			}
		}
		self::storeGd14Extra($adb, $leadId, $extra);
		if ($tag !== '') {
			self::markPaidContact($contactId, $tag, $userId);
		}
		try {
			self::copyLeadVerifyOnto($leadId, 'Contacts', $contactId);
		} catch (Exception $e) {
			// best-effort
		}
		if ($note !== '') {
			try {
				require_once 'modules/Vtiger/models/CareActivityService.php';
				Vtiger_CareActivityService::log('Contacts', $contactId, '990k', $note, '', $userId);
			} catch (Exception $e) {
				// ignore
			}
		}
		$labels = self::gd14TagCatalog();
		return array(
			'success' => true,
			'tag' => $tag,
			'tag_label' => ($tag !== '' && isset($labels[$tag])) ? $labels[$tag] : '',
			'gd14' => self::gd14PublicState($extra),
			'compare' => self::gd14CompareState($extra),
			'verify_lines' => self::linesFromPack(array('extra' => $extra)),
			'message' => $note !== '' ? $note : 'Đã cập nhật lớp 990k.',
		);
	}
}
