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
}
