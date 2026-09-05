<?php
/*+***********************************************************************************
 * Bộ B – 5 câu Sales xác minh (GD 1.1).
 * Đầu vào: C1–C3 sau xác minh (+ C4, C5 nếu đủ ĐK).
 * Đầu ra: Kết luận điều kiện + Mức tiềm năng. Không ghi đè đáp án Form.
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
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
	}

	/**
	 * Lớp 0 + điểm + trần → kết luận.
	 * @return array
	 */
	public static function compute(array $input) {
		$c1 = strtoupper(trim((string) (isset($input['c1']) ? $input['c1'] : '')));
		$c2 = strtoupper(trim((string) (isset($input['c2']) ? $input['c2'] : '')));
		$c3 = strtoupper(trim((string) (isset($input['c3']) ? $input['c3'] : '')));
		$c4 = (int) (isset($input['c4']) ? $input['c4'] : 0);
		$c5 = (int) (isset($input['c5']) ? $input['c5'] : 0);

		$excluded = self::isLayer0Excluded($c1, $c2, $c3);
		if ($excluded) {
			return array(
				'success' => true,
				'eligibility_result' => 'khong_du_dk',
				'eligibility_label' => 'Không đủ điều kiện',
				'potential_level' => '',
				'potential_label' => '',
				'score' => null,
				'raw_band' => '',
				'ceiling' => '',
				'ask_c4_c5' => false,
				'reason' => $excluded,
			);
		}

		$askC45 = true;
		$score = null;
		$rawBand = '';
		$ceiling = self::ceilingFor($c2, $c3);
		$level = '';
		if ($c4 >= 1 && $c4 <= 4 && $c5 >= 1 && $c5 <= 4) {
			$score = self::scoreC3($c3) + self::levelPoints($c4) + self::levelPoints($c5);
			$rawBand = self::rawBand($score);
			$level = self::applyCeiling($rawBand, $ceiling);
		}

		return array(
			'success' => true,
			'eligibility_result' => 'du_dk',
			'eligibility_label' => 'Đủ điều kiện',
			'potential_level' => $level,
			'potential_label' => self::potentialLabel($level),
			'score' => $score,
			'raw_band' => $rawBand,
			'ceiling' => $ceiling,
			'ask_c4_c5' => $askC45,
			'reason' => '',
		);
	}

	protected static function isLayer0Excluded($c1, $c2, $c3) {
		if ($c1 === 'D') {
			return 'C1 = D — học gia đình / sở thích';
		}
		if ($c2 === 'G') {
			return 'C2 = G — học pha chế gia đình / sở thích';
		}
		if ($c3 === 'A') {
			return 'C3 = A — ngân sách dưới 50 triệu';
		}
		if ($c2 === 'A' && $c3 === 'B') {
			return 'C2 = A và C3 = B — xe đẩy + 50–100 triệu';
		}
		return '';
	}

	protected static function scoreC3($c3) {
		if ($c3 === 'D' || $c3 === 'E') {
			return 3;
		}
		if ($c3 === 'C') {
			return 2;
		}
		if ($c3 === 'B') {
			return 1;
		}
		return 0;
	}

	protected static function levelPoints($level) {
		$level = (int) $level;
		if ($level === 1) {
			return 3;
		}
		if ($level === 2) {
			return 2;
		}
		if ($level === 3) {
			return 1;
		}
		return 0;
	}

	protected static function rawBand($score) {
		$score = (int) $score;
		if ($score >= 7) {
			return 'cao';
		}
		if ($score >= 4) {
			return 'trung_binh';
		}
		return 'thap';
	}

	/** Trần thấp nhất khớp. */
	protected static function ceilingFor($c2, $c3) {
		if ($c3 === 'B') {
			return 'binh_thuong';
		}
		if ($c2 === 'A') {
			return 'tiem_nang';
		}
		if (in_array($c3, array('C', 'D', 'E'), true) && in_array($c2, array('B', 'C', 'D', 'E', 'F'), true)) {
			return 'sieu_tiem_nang';
		}
		return 'sieu_tiem_nang';
	}

	protected static function applyCeiling($rawBand, $ceiling) {
		$order = array('binh_thuong' => 0, 'tiem_nang' => 1, 'sieu_tiem_nang' => 2);
		$fromBand = array(
			'cao' => 'sieu_tiem_nang',
			'trung_binh' => 'tiem_nang',
			'thap' => 'binh_thuong',
		);
		$raw = isset($fromBand[$rawBand]) ? $fromBand[$rawBand] : 'binh_thuong';
		$rawN = isset($order[$raw]) ? $order[$raw] : 0;
		$ceilN = isset($order[$ceiling]) ? $order[$ceiling] : 2;
		$finalN = min($rawN, $ceilN);
		foreach ($order as $k => $n) {
			if ($n === $finalN) {
				return $k;
			}
		}
		return 'binh_thuong';
	}

	public static function potentialLabel($code) {
		$map = array(
			'sieu_tiem_nang' => 'Siêu tiềm năng',
			'tiem_nang' => 'Tiềm năng',
			'binh_thuong' => 'Bình thường',
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

		$c1 = strtoupper(trim((string) (isset($payload['c1']) ? $payload['c1'] : '')));
		$c2 = strtoupper(trim((string) (isset($payload['c2']) ? $payload['c2'] : '')));
		$c3 = strtoupper(trim((string) (isset($payload['c3']) ? $payload['c3'] : '')));
		$c4 = isset($payload['c4']) ? (int) $payload['c4'] : 0;
		$c5 = isset($payload['c5']) ? (int) $payload['c5'] : 0;
		$reason = isset($payload['change_reason']) ? trim((string) $payload['change_reason']) : '';

		if ($c1 === '' || $c2 === '' || $c3 === '') {
			throw new Exception('Thiếu C1 / C2 / C3 sau xác minh.');
		}

		$exists = $adb->pquery('SELECT leadid, form_c1, form_c2, form_c3 FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if (!$exists || $adb->num_rows($exists) < 1) {
			throw new Exception('Lead profile missing.');
		}

		$formC1 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c1')));
		$formC2 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c2')));
		$formC3 = strtoupper(trim((string) $adb->query_result($exists, 0, 'form_c3')));
		$changedFromForm = ($formC1 !== '' && $formC1 !== $c1)
			|| ($formC2 !== '' && $formC2 !== $c2)
			|| ($formC3 !== '' && $formC3 !== $c3);
		if ($changedFromForm && $reason === '') {
			throw new Exception('Đáp án sau xác minh khác Form — vui lòng ghi lý do thay đổi.');
		}

		$result = self::compute(array(
			'c1' => $c1,
			'c2' => $c2,
			'c3' => $c3,
			'c4' => $c4,
			'c5' => $c5,
		));
		if (!empty($result['ask_c4_c5']) && ($c4 < 1 || $c5 < 1)) {
			throw new Exception('Khách đủ điều kiện — cần chọn Câu 4 và Câu 5 (mức 1–4).');
		}
		if (empty($result['ask_c4_c5'])) {
			$c4 = 0;
			$c5 = 0;
		}

		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;

		$adb->pquery(
			'UPDATE bace_lead_profile SET
				verify_c1=?, verify_c2=?, verify_c3=?, verify_c4=?, verify_c5=?,
				eligibility_result=?, potential_level=?, verify_score=?,
				verify_change_reason=?, verified_at=?, verified_by=?, modified_at=?
			 WHERE leadid=?',
			array(
				$c1,
				$c2,
				$c3,
				$c4 > 0 ? $c4 : null,
				$c5 > 0 ? $c5 : null,
				$result['eligibility_result'],
				$result['potential_level'] !== '' ? $result['potential_level'] : null,
				$result['score'] !== null ? (int) $result['score'] : null,
				$reason !== '' ? $reason : null,
				$now,
				$userId > 0 ? $userId : null,
				$now,
				$leadId,
			)
		);

		$biz = Vtiger_BusinessModel_Helper::fromFormAnswer($c2);
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
			}
		}
		return array_values(array_unique($out));
	}

	/** Lưu đáp án Form (Bộ A) — chỉ ghi khi cột còn trống. */
	public static function seedFormAnswers($leadId, $c1, $c2, $c3) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$c1 = strtoupper(trim((string) $c1));
		$c2 = strtoupper(trim((string) $c2));
		$c3 = strtoupper(trim((string) $c3));
		if ($c1 === '' && $c2 === '' && $c3 === '') {
			return;
		}
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
		return array(
			'c1' => array(
				array('code' => 'A', 'label' => 'Chuẩn bị mở quán'),
				array('code' => 'B', 'label' => 'Đã có quán, muốn cập nhật kiến thức / công thức / menu'),
				array('code' => 'C', 'label' => 'Đã có quán, đang gặp vấn đề cần cải thiện'),
				array('code' => 'D', 'label' => 'Học để biết thêm, phục vụ gia đình hoặc sở thích'),
			),
			'c2' => array(
				array('code' => 'A', 'label' => 'Xe đẩy cà phê – trà sữa – trà trái cây'),
				array('code' => 'B', 'label' => 'Trà sữa – topping, có mặt bằng 20–30 m²'),
				array('code' => 'C', 'label' => 'Trà sữa pha máy, có mặt bằng 20–30 m²'),
				array('code' => 'D', 'label' => 'Cà phê – trà sữa, máy lạnh'),
				array('code' => 'E', 'label' => 'Cà phê sân vườn, diện tích vừa – lớn'),
				array('code' => 'F', 'label' => 'Cà phê không gian mở, diện tích nhỏ'),
				array('code' => 'G', 'label' => 'Học pha chế cho gia đình / sở thích'),
			),
			'c3' => array(
				array('code' => 'A', 'label' => 'Dưới 50 triệu'),
				array('code' => 'B', 'label' => 'Từ 50 đến dưới 100 triệu'),
				array('code' => 'C', 'label' => 'Từ 100 đến dưới 300 triệu'),
				array('code' => 'D', 'label' => 'Từ 300 đến dưới 500 triệu'),
				array('code' => 'E', 'label' => 'Từ 500 triệu trở lên'),
			),
			'c4' => array(
				array('code' => '1', 'label' => 'Mức 1 — Rất cao'),
				array('code' => '2', 'label' => 'Mức 2 — Cao'),
				array('code' => '3', 'label' => 'Mức 3 — Trung bình'),
				array('code' => '4', 'label' => 'Mức 4 — Thấp'),
			),
			'c5' => array(
				array('code' => '1', 'label' => 'Mức 1 — Rất cao'),
				array('code' => '2', 'label' => 'Mức 2 — Cao'),
				array('code' => '3', 'label' => 'Mức 3 — Trung bình'),
				array('code' => '4', 'label' => 'Mức 4 — Thấp'),
			),
			'c4_label' => 'Câu 4 — Đánh giá mức độ quyết tâm / nhu cầu (1–4)',
			'c5_label' => 'Câu 5 — Đánh giá mức độ phù hợp / khả năng triển khai (1–4)',
			'c4_levels' => array(1, 2, 3, 4),
			'c5_levels' => array(1, 2, 3, 4),
		);
	}
}
