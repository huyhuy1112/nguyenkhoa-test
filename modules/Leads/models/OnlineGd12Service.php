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
	/** Đã cấp / kích hoạt khóa trên Edubit */
	const STATUS_DANG_HOC = 'online_dang_hoc';
	/** Vượt 50% tiến độ Edubit (chưa vượt 80%) */
	const STATUS_DAT_50 = 'online_dat_50';
	/** Ngày cuối của thời hạn truy cập */
	const STATUS_SAP_HET_HAN = 'online_sap_het_han';
	/** Hết hạn truy cập mà chưa đạt 80% */
	const STATUS_HET_HAN = 'online_het_han';
	/** Vượt 80% tiến độ Edubit */
	const STATUS_DAT_80 = 'online_dat_80';
	/** Đạt 100% tiến độ Edubit */
	const STATUS_HOAN_THANH = 'online_hoan_thanh';

	/** Thời hạn truy cập lần đầu / mỗi lần gia hạn (ngày) */
	const ACCESS_DAYS = 10;
	/** Trần số lần gia hạn — không bao giờ đặt lại */
	const RENEW_MAX = 3;

	/** Đường 1 OA / Đường 2 chuyển từ Offline 1.1 */
	const PATH_OA = 'oa';
	const PATH_GD11 = 'gd11';

	/** GD 1.2 — khóa miễn phí → Opp (theo dõi tiến trình trên Opp). */
	const COURSE_FREE_TO_OPP = '27312';
	/** GD 1.2 — khóa trả phí → Khách hàng (1 HV có thể học nhiều khóa). */
	const COURSE_PAID_TO_CONTACT = array('29403', '29218', '28108');

	const STATUS_TAGS = array(
		self::STATUS_CHUA_DIEN_FORM,
		self::STATUS_CHUA_DK_TK,
		self::STATUS_KHONG_DU_DK,
		self::STATUS_NGUNG_CSKH,
		self::STATUS_DANG_HOC,
		self::STATUS_DAT_50,
		self::STATUS_SAP_HET_HAN,
		self::STATUS_HET_HAN,
		self::STATUS_DAT_80,
		self::STATUS_HOAN_THANH,
	);

	/**
	 * Khóa miễn phí 27312 → Opp; khóa trả phí (29403/29218/28108) và còn lại → KH.
	 */
	public static function isFreeCourseToOpportunity($courseId) {
		return trim((string) $courseId) === self::COURSE_FREE_TO_OPP;
	}

	public static function isPaidCourseToContact($courseId) {
		$id = trim((string) $courseId);
		return $id !== '' && in_array($id, self::COURSE_PAID_TO_CONTACT, true);
	}

	public static function statusLabels() {
		return array(
			self::STATUS_CHUA_DIEN_FORM => 'Online — Chưa điền form',
			self::STATUS_CHUA_DK_TK => 'Online — Chưa đăng ký TK',
			self::STATUS_KHONG_DU_DK => 'Online — Không đủ ĐK',
			self::STATUS_NGUNG_CSKH => 'Online — Ngưng CSKH',
			self::STATUS_DANG_HOC => 'Online — Đang học',
			self::STATUS_DAT_50 => 'Online — Đạt 50%',
			self::STATUS_SAP_HET_HAN => 'Online — Sắp hết hạn',
			self::STATUS_HET_HAN => 'Online — Hết hạn',
			self::STATUS_DAT_80 => 'Online — Đạt ngưỡng 80%',
			self::STATUS_HOAN_THANH => 'Online — Hoàn thành',
		);
	}

	public static function statusLabel($code) {
		$labels = self::statusLabels();
		$code = trim((string) $code);
		return isset($labels[$code]) ? $labels[$code] : $code;
	}

	/**
	 * Ngày hết hạn = mốc kích hoạt/gia hạn + 10 ngày.
	 * @param string $from Y-m-d H:i:s|timestamp
	 * @return string Y-m-d H:i:s
	 */
	public static function computeExpiresAt($from = null) {
		$ts = $from ? strtotime((string) $from) : time();
		if ($ts === false) {
			$ts = time();
		}
		return date('Y-m-d H:i:s', strtotime('+' . (int) self::ACCESS_DAYS . ' days', $ts));
	}

	/**
	 * Tag học tập theo tiến độ + đồng hồ hạn (doc GD12).
	 * 100% = Hoàn thành; >80% = bàn giao. Hai mốc này thắng mọi tag thời hạn.
	 * @param int|null $pct
	 * @param string $expiresAt
	 * @return string
	 */
	public static function resolveLearningStatus($pct, $expiresAt) {
		if ($pct !== null && $pct !== '' && (int) $pct >= 100) {
			return self::STATUS_HOAN_THANH;
		}
		if ($pct !== null && $pct !== '' && (int) $pct > 80) {
			return self::STATUS_DAT_80;
		}
		$expTs = $expiresAt ? strtotime((string) $expiresAt) : false;
		if ($expTs !== false) {
			$today = date('Y-m-d');
			$expDay = date('Y-m-d', $expTs);
			if ($today > $expDay) {
				return self::STATUS_HET_HAN;
			}
			if ($today === $expDay) {
				return self::STATUS_SAP_HET_HAN;
			}
		}
		if ($pct !== null && $pct !== '' && (int) $pct > 50) {
			return self::STATUS_DAT_50;
		}
		return self::STATUS_DANG_HOC;
	}

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
		if (self::isScoreLocked($leadId)) {
			return array(
				'success' => false,
				'error' => 'Hồ sơ Đường 2 (từ Offline 1.1) đã khoá chấm tự động — không sửa bộ 4 câu.',
			);
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

		// GD 1.2: đủ ĐK → ở Lead để cấp TK Edubit; cấp xong mới xuống KH (không qua Opp).
		$out = array('success' => true, 'result' => $result, 'lead' => $lead);
		if (isset($result['eligibility_result']) && $result['eligibility_result'] === 'du_dk') {
			$out['next'] = 'edubit_provision';
			$out['message'] = 'Đủ điều kiện — chọn khóa và cấp TK Edubit trên Lead (không qua Opp).';
		}
		return $out;
	}

	/**
	 * Đường 2 — khoá chấm lại (online_path = gd11).
	 */
	public static function isScoreLocked($leadIdOrPath) {
		if (is_string($leadIdOrPath) && !ctype_digit((string) $leadIdOrPath)) {
			return trim((string) $leadIdOrPath) === self::PATH_GD11;
		}
		$leadId = (int) $leadIdOrPath;
		if ($leadId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT online_path FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if (!$res || $adb->num_rows($res) < 1) {
			return false;
		}
		return trim((string) $adb->query_result($res, 0, 'online_path')) === self::PATH_GD11;
	}

	/**
	 * C5 (1–4, mức 1 tốt nhất) → Q2 (A–D, A tốt nhất).
	 */
	public static function mapVerifyC5ToQ2($c5) {
		$map = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D');
		$n = (int) $c5;
		return isset($map[$n]) ? $map[$n] : '';
	}

	/** Q2 (A–D) → C5 (1–4). */
	public static function mapQ2ToVerifyC5($q2) {
		$map = array('A' => 1, 'B' => 2, 'C' => 3, 'D' => 4);
		$key = strtoupper(trim((string) $q2));
		return isset($map[$key]) ? $map[$key] : 0;
	}

	public static function findChildLeadIdBySource($offlineLeadId) {
		$offlineLeadId = (int) $offlineLeadId;
		if ($offlineLeadId <= 0) {
			return 0;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT p.leadid FROM bace_lead_profile p
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			 WHERE p.online_source_leadid = ? AND p.online_path = ?
			 ORDER BY p.leadid DESC LIMIT 1",
			array($offlineLeadId, self::PATH_GD11)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'leadid');
		}
		return 0;
	}

	/**
	 * Đường 2 — Sale chuyển Offline 1.1 → hồ sơ Online mới.
	 * Chép đáp án sau xác minh, khoá chấm, tag Chưa ĐK TK; soft-delete lead Offline cũ.
	 */
	public static function transferFromOffline($offlineLeadId, $userId = null) {
		$offlineLeadId = (int) $offlineLeadId;
		if ($offlineLeadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead Offline');
		}
		self::installSchema();
		require_once 'modules/Leads/models/ModernService.php';
		require_once 'modules/Leads/models/SheetImportService.php';

		$existingChild = self::findChildLeadIdBySource($offlineLeadId);
		if ($existingChild > 0) {
			$child = Leads_ModernService::getLead((string) $existingChild, $userId);
			try {
				Leads_ModernService::deleteLead((string) $offlineLeadId, false);
			} catch (Exception $e) {
				// best-effort
			}
			return array(
				'success' => true,
				'already' => true,
				'online_lead_id' => $existingChild,
				'source_deleted' => true,
				'lead' => $child,
				'message' => 'Đã có hồ sơ Online Đường 2 — lead Offline cũ đã xoá (thùng rác).',
			);
		}

		$src = Leads_ModernService::getLead((string) $offlineLeadId, $userId);
		if (!$src || empty($src['id'])) {
			return array('success' => false, 'error' => 'Không tìm thấy lead Offline');
		}

		$elig = isset($src['eligibility_result']) ? trim((string) $src['eligibility_result']) : '';
		$pot = isset($src['potential_level']) ? trim((string) $src['potential_level']) : '';
		if ($elig !== 'du_dk') {
			return array('success' => false, 'error' => 'Chỉ chuyển khi đã đủ điều kiện Offline 1.1');
		}
		if ($pot === '') {
			return array('success' => false, 'error' => 'Chưa phân mức độ tiềm năng — không chuyển Đường 2');
		}

		$c1 = isset($src['verify_c1']) ? strtoupper(trim((string) $src['verify_c1'])) : '';
		$c2 = isset($src['verify_c2']) ? strtoupper(trim((string) $src['verify_c2'])) : '';
		$c3 = isset($src['verify_c3']) ? strtoupper(trim((string) $src['verify_c3'])) : '';
		$c5 = isset($src['verify_c5']) ? (int) $src['verify_c5'] : 0;
		$q1 = $c1;
		$q2 = self::mapVerifyC5ToQ2($c5);
		$q3 = $c3;
		$q4 = $c2;
		if ($q1 === '' || $q2 === '' || $q3 === '' || $q4 === '') {
			return array(
				'success' => false,
				'error' => 'Thiếu đáp án sau xác minh (cần C1, C2, C3 và C5) để chép sang Online',
			);
		}

		$group = self::customerGroupFromQ1($q1);
		$seg = self::segmentFromGroup($group['code']);
		$biz = self::businessModelKey($q4);
		$phone = isset($src['phone']) ? trim((string) $src['phone']) : '';
		$name = isset($src['name']) ? trim((string) $src['name']) : '';
		if ($name === '' || $phone === '') {
			return array('success' => false, 'error' => 'Lead Offline thiếu tên hoặc SĐT');
		}

		$tags = array('mien_phi_online', self::STATUS_CHUA_DK_TK);
		if ($pot === 'sieu_tiem_nang' || $pot === 'tiem_nang') {
			$tags[] = $pot;
		}
		$cust = Leads_SheetImportService::customerTagFromQ1($q1);
		if ($cust !== '') {
			$tags[] = $cust;
		}

		$payload = array(
			'name' => $name,
			'phone' => $phone,
			'email' => isset($src['email']) ? $src['email'] : '',
			'address' => isset($src['address']) ? $src['address'] : '',
			'district' => isset($src['district']) ? $src['district'] : '',
			'companyName' => isset($src['companyName']) ? $src['companyName'] : '-',
			'tags' => $tags,
			'business_model' => $biz,
			'segment' => $seg,
			'screening_result' => ($pot === 'sieu_tiem_nang' || $pot === 'tiem_nang') ? $pot : '',
			'skip_potential' => 1,
			'force_create' => 1,
			'owner' => isset($src['owner_username']) ? $src['owner_username'] : '',
		);
		$created = Leads_ModernService::saveLead($payload, null);
		$onlineLeadId = 0;
		if (is_array($created)) {
			$onlineLeadId = isset($created['crmid']) ? (int) $created['crmid'] : (isset($created['id']) ? (int) $created['id'] : 0);
		}
		if ($onlineLeadId <= 0) {
			return array('success' => false, 'error' => 'Không tạo được lead Online');
		}

		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			"UPDATE bace_lead_profile SET
				online_status = ?, online_q1 = ?, online_q2 = ?, online_q3 = ?, online_q4 = ?,
				eligibility_result = 'du_dk', potential_level = ?, business_model = ?, segment = ?,
				online_path = ?, online_source_leadid = ?, online_reminder_count = 0,
				online_entered_at = ?, modified_at = ?
			 WHERE leadid = ?",
			array(
				self::STATUS_CHUA_DK_TK,
				$q1,
				$q2,
				$q3,
				$q4,
				$pot,
				$biz !== '' ? $biz : null,
				$seg !== '' ? $seg : null,
				self::PATH_GD11,
				$offlineLeadId,
				$now,
				$now,
				$onlineLeadId,
			)
		);
		self::syncStatusTagsOnly($onlineLeadId, $tags);

		$deleted = false;
		try {
			$deleted = (bool) Leads_ModernService::deleteLead((string) $offlineLeadId, false);
		} catch (Exception $e) {
			$deleted = false;
		}

		$fresh = Leads_ModernService::getLead((string) $onlineLeadId, $userId);
		return array(
			'success' => true,
			'already' => false,
			'online_lead_id' => $onlineLeadId,
			'source_lead_id' => $offlineLeadId,
			'source_deleted' => $deleted,
			'mapped' => array('q1' => $q1, 'q2' => $q2, 'q3' => $q3, 'q4' => $q4),
			'lead' => $fresh,
			'source_lead' => null,
			'message' => $deleted
				? 'Đã chuyển Đường 2 → Online (Chưa ĐK TK) và xoá lead Offline cũ.'
				: 'Đã tạo hồ sơ Online Đường 2 — không xoá được Offline (kiểm tra quyền).',
		);
	}

	/**
	 * Đường 2 ngược — Online đủ ĐK → Offline mới; soft-delete Online cũ.
	 * Map: Q1→C1, Q2→C5, Q3→C3, Q4→C2.
	 */
	public static function transferFromOnline($onlineLeadId, $userId = null) {
		$onlineLeadId = (int) $onlineLeadId;
		if ($onlineLeadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead Online');
		}
		self::installSchema();
		require_once 'modules/Leads/models/ModernService.php';
		require_once 'modules/Leads/models/SheetImportService.php';
		require_once 'modules/Leads/models/OfflineGd11Service.php';
		require_once 'modules/Leads/models/SalesVerifyService.php';

		$src = Leads_ModernService::getLead((string) $onlineLeadId, $userId);
		if (!$src || empty($src['id'])) {
			return array('success' => false, 'error' => 'Không tìm thấy lead Online');
		}

		$elig = isset($src['eligibility_result']) ? trim((string) $src['eligibility_result']) : '';
		$pot = isset($src['potential_level']) ? trim((string) $src['potential_level']) : '';
		if ($elig !== 'du_dk') {
			return array('success' => false, 'error' => 'Chỉ chuyển khi Online đã đủ điều kiện');
		}
		if ($pot === '') {
			return array('success' => false, 'error' => 'Chưa phân mức độ tiềm năng — không chuyển Offline');
		}

		$q1 = isset($src['online_q1']) ? strtoupper(trim((string) $src['online_q1'])) : '';
		$q2 = isset($src['online_q2']) ? strtoupper(trim((string) $src['online_q2'])) : '';
		$q3 = isset($src['online_q3']) ? strtoupper(trim((string) $src['online_q3'])) : '';
		$q4 = isset($src['online_q4']) ? strtoupper(trim((string) $src['online_q4'])) : '';
		$c1 = $q1;
		$c2 = $q4;
		$c3 = $q3;
		$c5 = self::mapQ2ToVerifyC5($q2);
		if ($c1 === '' || $c2 === '' || $c3 === '' || $c5 < 1) {
			return array(
				'success' => false,
				'error' => 'Thiếu đáp án Online (cần Q1–Q4) để chép sang Offline',
			);
		}

		$phone = isset($src['phone']) ? trim((string) $src['phone']) : '';
		$name = isset($src['name']) ? trim((string) $src['name']) : '';
		if ($name === '' || $phone === '') {
			return array('success' => false, 'error' => 'Lead Online thiếu tên hoặc SĐT');
		}

		$seg = isset($src['segment']) ? trim((string) $src['segment']) : '';
		$biz = isset($src['business_model']) ? trim((string) $src['business_model']) : self::businessModelKey($q4);
		$tags = array('mien_phi_offline');
		if ($pot === 'sieu_tiem_nang' || $pot === 'tiem_nang') {
			$tags[] = $pot;
		}
		$cust = Leads_SheetImportService::customerTagFromQ1($c1);
		if ($cust !== '') {
			$tags[] = $cust;
		}

		$payload = array(
			'name' => $name,
			'phone' => $phone,
			'email' => isset($src['email']) ? $src['email'] : '',
			'address' => isset($src['address']) ? $src['address'] : '',
			'district' => isset($src['district']) ? $src['district'] : '',
			'companyName' => isset($src['companyName']) ? $src['companyName'] : '-',
			'tags' => $tags,
			'business_model' => $biz,
			'segment' => $seg,
			'screening_result' => ($pot === 'sieu_tiem_nang' || $pot === 'tiem_nang') ? $pot : '',
			'skip_potential' => 1,
			'force_create' => 1,
			'owner' => isset($src['owner_username']) ? $src['owner_username'] : '',
		);
		$created = Leads_ModernService::saveLead($payload, null);
		$offlineLeadId = 0;
		if (is_array($created)) {
			$offlineLeadId = isset($created['crmid']) ? (int) $created['crmid'] : (isset($created['id']) ? (int) $created['id'] : 0);
		}
		if ($offlineLeadId <= 0) {
			return array('success' => false, 'error' => 'Không tạo được lead Offline');
		}

		Leads_SalesVerifyService::installSchema();
		Leads_OfflineGd11Service::installSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			"UPDATE bace_lead_profile SET
				form_c1 = ?, form_c2 = ?, form_c3 = ?,
				verify_c1 = ?, verify_c2 = ?, verify_c3 = ?, verify_c5 = ?,
				eligibility_result = 'du_dk', potential_level = ?, business_model = ?, segment = ?,
				online_status = NULL, online_path = NULL, online_source_leadid = ?,
				online_q1 = NULL, online_q2 = NULL, online_q3 = NULL, online_q4 = NULL,
				modified_at = ?
			 WHERE leadid = ?",
			array(
				$c1,
				$c2,
				$c3,
				$c1,
				$c2,
				$c3,
				$c5,
				$pot,
				$biz !== '' ? $biz : null,
				$seg !== '' ? $seg : null,
				$onlineLeadId,
				$now,
				$offlineLeadId,
			)
		);
		// tags mien_phi_offline đã gắn lúc saveLead

		$deleted = false;
		try {
			$deleted = (bool) Leads_ModernService::deleteLead((string) $onlineLeadId, false);
		} catch (Exception $e) {
			$deleted = false;
		}

		$fresh = Leads_ModernService::getLead((string) $offlineLeadId, $userId);
		return array(
			'success' => true,
			'already' => false,
			'offline_lead_id' => $offlineLeadId,
			'source_lead_id' => $onlineLeadId,
			'source_deleted' => $deleted,
			'mapped' => array('c1' => $c1, 'c2' => $c2, 'c3' => $c3, 'c5' => $c5),
			'lead' => $fresh,
			'message' => $deleted
				? 'Đã chuyển Đường 2 → Offline và xoá lead Online cũ.'
				: 'Đã tạo hồ sơ Offline — không xoá được Online (kiểm tra quyền).',
		);
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
			'online_source_leadid' => "INT(11) DEFAULT NULL",
			'edubit_user_id' => "VARCHAR(64) DEFAULT NULL",
			'edubit_course_id' => "VARCHAR(32) DEFAULT NULL",
			'edubit_email' => "VARCHAR(128) DEFAULT NULL",
			'edubit_activated_at' => "DATETIME DEFAULT NULL",
			'edubit_expires_at' => "DATETIME DEFAULT NULL",
			'edubit_renew_count' => "TINYINT(1) NOT NULL DEFAULT 0",
			'edubit_expiry_reason' => "VARCHAR(64) DEFAULT NULL",
			'edubit_progress_pct' => "TINYINT(3) DEFAULT NULL",
			'edubit_progress_at' => "DATETIME DEFAULT NULL",
			'edubit_last_error' => "VARCHAR(255) DEFAULT NULL",
			'zalo_progress_tag' => "VARCHAR(48) DEFAULT NULL",
			'zalo_progress_tag_at' => "DATETIME DEFAULT NULL",
			'zalo_progress_tag_error' => "VARCHAR(255) DEFAULT NULL",
			'online_care_sent' => "VARCHAR(128) DEFAULT ''",
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
		// D trước A: tránh "duoi 500" khớp nhầm "duoi 50".
		if (strpos($f, '300') !== false && strpos($f, '500') !== false) {
			return 'D';
		}
		if (preg_match('/\b50\b/', $f) && preg_match('/\b100\b/', $f)) {
			return 'B';
		}
		if (strpos($f, '100') !== false && strpos($f, '300') !== false) {
			return 'C';
		}
		if (preg_match('/\bduoi 50\b/', $f)) {
			return 'A';
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

		// Đường 2: chỉ gắn zalo_user_id khi khách vào OA — không chấm lại / không đổi Q.
		if (self::isScoreLocked($leadId)) {
			if ($oaUserId !== '') {
				$adb->pquery(
					"UPDATE bace_lead_profile SET
						zalo_user_id = IF(zalo_user_id IS NULL OR zalo_user_id = '', ?, zalo_user_id),
						modified_at = ?
					 WHERE leadid = ?",
					array((string) $oaUserId, $now, $leadId)
				);
			}
			return;
		}

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
				online_path = ?,
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
				self::PATH_OA,
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

		// KB-04 / KB-05 theo sự kiện đổi tag (OA hoặc Calendar fallback).
		if ($status === self::STATUS_CHUA_DK_TK) {
			self::sendCareToLead($leadId, 'kb04');
		} elseif ($status === self::STATUS_KHONG_DU_DK) {
			self::sendCareToLead($leadId, 'kb05');
		}

		// Đồng bộ OA id sang Offline cùng SĐT (nếu có).
		if ($oaUserId !== '') {
			try {
				$phoneRes = $adb->pquery(
					'SELECT phone FROM vtiger_leadaddress WHERE leadaddressid = ?',
					array($leadId)
				);
				$phone = ($phoneRes && $adb->num_rows($phoneRes) > 0)
					? (string) $adb->query_result($phoneRes, 0, 'phone') : '';
				if ($phone !== '') {
					require_once 'modules/Leads/models/OfflineGd11Service.php';
					Leads_OfflineGd11Service::linkZaloUserIdByPhone($phone, $oaUserId, $leadId);
				}
			} catch (Exception $e) {
				// ignore link failures
			}
		}
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
			'kb04' => "Chúc mừng anh/chị đủ điều kiện học lớp Online miễn phí.\nEm gửi hướng dẫn kích hoạt tài khoản học. Anh/chị kích hoạt sớm để bắt đầu học (thời hạn truy cập 10 ngày kể từ lúc kích hoạt).",
			'kb05' => "Cảm ơn anh/chị đã quan tâm lớp Online miễn phí.\nHiện hồ sơ chưa đủ điều kiện suất học này. Em gửi bảng so sánh sản phẩm phù hợp hơn — anh/chị chọn giúp em hướng đi tiếp theo nhé.",
			'kb06a' => "Anh/chị ơi, tài khoản học Online vẫn chưa kích hoạt. Em nhắc anh/chị kích hoạt sớm để không mất suất học nhé.",
			'kb06b' => "Nhắc anh/chị lần 2: còn thiếu bước kích hoạt tài khoản lớp Online miễn phí. Anh/chị làm giúp em để bắt đầu học ngay.",
			'kb06c' => "Đây là lần nhắc cuối về kích hoạt tài khoản Online. Nếu anh/chị vẫn muốn học, hãy kích hoạt trong hôm nay giúp em.",
			'kb07' => "Chúc mừng anh/chị đã kích hoạt thành công!\nThời hạn truy cập là 10 ngày kể từ hôm nay. Em chúc anh/chị học vui và áp dụng tốt vào quán.",
			'kb09' => "Tuyệt vời — anh/chị đã học vượt nửa khóa rồi!\nEm khuyến khích anh/chị giữ nhịp và hoàn thành phần còn lại trong thời hạn đang có.",
			'kb10' => "Hôm nay là ngày cuối của thời hạn truy cập khóa Online.\nAnh/chị tranh thủ hoàn thành bài còn lại. Nếu cần gia hạn, hãy báo em khi hết hạn nhé (gia hạn có giới hạn).",
			'kb11' => "Chúc mừng anh/chị đã đạt từ 80% khóa Online!\nĐây là mốc bàn giao tốt — em sẽ hỗ trợ anh/chị bước tiếp theo (học tiếp / sản phẩm phù hợp). Anh/chị cứ nhắn em nếu cần.",
			'kb16a' => "Tài khoản học Online của anh/chị đã hết hạn và chưa đạt ngưỡng hoàn thành.\nAnh/chị cho em biết lý do nhé — nếu còn muốn học, em có thể xin gia hạn giúp (tối đa 3 lần).",
			'kb16b' => "Em nhắc lại: khóa Online đã hết hạn. Nếu anh/chị còn muốn học tiếp, hãy phản hồi để em hỗ trợ gia hạn.",
			'kb16c' => "Lần nhắc cuối về khóa Online đã hết hạn. Nếu không phản hồi, em sẽ tạm ngưng chăm sóc hồ sơ này.",
			'kb20a' => "Anh/chị ơi, em gửi lại bảng so sánh sản phẩm. Anh/chị chọn giúp em hướng phù hợp để em hỗ trợ tiếp.",
			'kb20b' => "Nhắc anh/chị lần 2: còn chưa chọn sản phẩm thay thế sau khi không đủ điều kiện lớp Online miễn phí.",
			'kb20c' => "Lần nhắc cuối — nếu anh/chị không chọn sản phẩm khác, em sẽ tạm ngưng chăm sóc hồ sơ này.",
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

	/** Bốn nhãn tiến trình trên Zalo OA; chỉ giữ trạng thái hiện tại. */
	public static function zaloProgressTagLabels() {
		return array(
			self::STATUS_DANG_HOC => 'danghoc',
			self::STATUS_DAT_50 => '50',
			self::STATUS_DAT_80 => '80',
			self::STATUS_HOAN_THANH => '100',
		);
	}

	/**
	 * Đồng bộ tag tiến trình CRM → Zalo OA, không gửi tin nhắn.
	 * Idempotent bằng zalo_progress_tag; lỗi OA không làm hỏng sync Edubit/CRM.
	 */
	public static function syncProgressTagToZalo($leadId, $status, $userId = null) {
		$leadId = (int) $leadId;
		$status = trim((string) $status);
		$labels = self::zaloProgressTagLabels();
		if ($leadId <= 0 || !isset($labels[$status])) {
			return array('success' => true, 'skipped' => true, 'reason' => 'not_progress_milestone');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT zalo_user_id, zalo_progress_tag
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array('success' => false, 'error' => 'missing_online_profile');
		}
		$oaUserId = trim((string) $adb->query_result($res, 0, 'zalo_user_id'));
		$lastStatus = trim((string) $adb->query_result($res, 0, 'zalo_progress_tag'));
		if ($lastStatus === $status) {
			return array('success' => true, 'skipped' => true, 'reason' => 'already_synced');
		}
		$now = date('Y-m-d H:i:s');
		if ($oaUserId === '') {
			$error = 'Thiếu zalo_user_id — chưa thể gắn nhãn tiến trình OA';
			$adb->pquery(
				'UPDATE bace_lead_profile
				 SET zalo_progress_tag_error = ?, modified_at = ? WHERE leadid = ?',
				array($error, $now, $leadId)
			);
			return array('success' => false, 'error' => 'missing_zalo_user_id');
		}

		try {
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			$adapter = NkApiConnection::adapter('zalo_oa');
			$actorId = $userId !== null ? (int) $userId : 0;
			$add = $adapter->addFollowerTag($oaUserId, $labels[$status], $actorId);
			if (empty($add['success'])) {
				$error = isset($add['error']) ? (string) $add['error'] : 'add_tag_failed';
				$adb->pquery(
					'UPDATE bace_lead_profile
					 SET zalo_progress_tag_error = ?, modified_at = ? WHERE leadid = ?',
					array(mb_substr($error, 0, 250), $now, $leadId)
				);
				return array('success' => false, 'error' => $error);
			}

			// Chỉ gỡ mốc cũ do CRM từng gắn; không đụng nhãn OA khác của khách.
			$removeError = '';
			if ($lastStatus !== '' && $lastStatus !== $status && isset($labels[$lastStatus])) {
				$remove = $adapter->removeFollowerTag($oaUserId, $labels[$lastStatus], $actorId);
				if (empty($remove['success'])) {
					$removeError = isset($remove['error'])
						? (string) $remove['error'] : 'remove_previous_tag_failed';
				}
			}
			if ($removeError !== '') {
				$adb->pquery(
					'UPDATE bace_lead_profile
					 SET zalo_progress_tag_error = ?, modified_at = ? WHERE leadid = ?',
					array(mb_substr($removeError, 0, 250), $now, $leadId)
				);
				return array('success' => false, 'error' => $removeError, 'tag_added' => true);
			}
			$adb->pquery(
				'UPDATE bace_lead_profile SET
					zalo_progress_tag = ?,
					zalo_progress_tag_at = ?,
					zalo_progress_tag_error = NULL,
					modified_at = ?
				 WHERE leadid = ?',
				array($status, $now, $now, $leadId)
			);
			return array(
				'success' => true,
				'tag' => $labels[$status],
				'removed_tag' => isset($labels[$lastStatus]) ? $labels[$lastStatus] : '',
			);
		} catch (Exception $e) {
			$adb->pquery(
				'UPDATE bace_lead_profile
				 SET zalo_progress_tag_error = ?, modified_at = ? WHERE leadid = ?',
				array(mb_substr($e->getMessage(), 0, 250), $now, $leadId)
			);
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
				try {
					require_once 'modules/Leads/models/ModernService.php';
					Leads_ModernService::stampNgungCskhAt($leadId);
				} catch (Exception $e) {
					// best-effort
				}
				$stopped++;
			}
		}
		return array('sent' => $sent, 'stopped' => $stopped);
	}

	/**
	 * Gửi tin chăm sóc Online → OA; thiếu OA id thì Calendar fallback.
	 */
	public static function sendCareToLead($leadId, $templateKey, $userId = null, $force = false) {
		$leadId = (int) $leadId;
		$templateKey = trim((string) $templateKey);
		if ($leadId <= 0 || $templateKey === '') {
			return array('success' => false, 'error' => 'missing_params');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		if (!$force && self::hasCareSent($leadId, $templateKey)) {
			return array('success' => true, 'skipped' => true, 'reason' => 'already_sent', 'kb' => $templateKey);
		}
		$res = $adb->pquery(
			'SELECT zalo_user_id FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$uid = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'zalo_user_id')) : '';
		$r = array('success' => false, 'kb' => $templateKey);
		if ($uid !== '') {
			$r = self::sendTemplate($uid, $templateKey);
			$r['kb'] = $templateKey;
			$r['channel'] = 'oa';
		}
		if (empty($r['success'])) {
			try {
				require_once 'modules/Leads/models/OfflineGd11Service.php';
				$cal = Leads_OfflineGd11Service::createFollowUpTask(
					$leadId,
					Leads_OfflineGd11Service::STATUS_HEN_GOI_LAI,
					array('due_at' => date('Y-m-d H:i:s', time() + 3600)),
					$userId
				);
				$r['calendar_fallback'] = $cal;
				$r['success'] = !empty($cal['success']);
				$r['channel'] = 'calendar';
				$r['note'] = ($uid === '')
					? 'Thiếu zalo_user_id — Calendar fallback'
					: 'OA lỗi — Calendar fallback';
			} catch (Exception $e) {
				$r['calendar_error'] = $e->getMessage();
			}
		}
		if (!empty($r['success']) || !empty($r['skipped'])) {
			self::markCareSent($leadId, $templateKey);
		}
		return $r;
	}

	public static function hasCareSent($leadId, $key) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT online_care_sent FROM bace_lead_profile WHERE leadid = ?', array((int) $leadId));
		if (!$res || $adb->num_rows($res) < 1) {
			return false;
		}
		$raw = trim((string) $adb->query_result($res, 0, 'online_care_sent'));
		if ($raw === '') {
			return false;
		}
		$parts = preg_split('/\s*,\s*/', $raw);
		return in_array((string) $key, $parts, true);
	}

	public static function markCareSent($leadId, $key) {
		$leadId = (int) $leadId;
		$key = trim((string) $key);
		if ($leadId <= 0 || $key === '') {
			return;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT online_care_sent FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		$raw = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'online_care_sent')) : '';
		$parts = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : array();
		if (!in_array($key, $parts, true)) {
			$parts[] = $key;
		}
		$adb->pquery(
			'UPDATE bace_lead_profile SET online_care_sent = ?, modified_at = ? WHERE leadid = ?',
			array(implode(',', $parts), date('Y-m-d H:i:s'), $leadId)
		);
	}

	protected static function stopToNgungCskh($leadId) {
		$leadId = (int) $leadId;
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET online_status = ?, modified_at = ? WHERE leadid = ?',
			array(self::STATUS_NGUNG_CSKH, date('Y-m-d H:i:s'), $leadId)
		);
		self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', self::STATUS_NGUNG_CSKH));
		try {
			require_once 'modules/Leads/models/ModernService.php';
			Leads_ModernService::stampNgungCskhAt($leadId);
		} catch (Exception $e) {
			// best-effort
		}
	}

	/**
	 * D1 — Chưa đăng ký/kích hoạt TK: cách 3 ngày, tối đa 3 (KB-06a/b/c).
	 */
	public static function processD1Reminders($limit = 50) {
		return self::processStatusReminders(array(
			'status' => self::STATUS_CHUA_DK_TK,
			'keys' => array('kb06a', 'kb06b', 'kb06c'),
			'interval_days' => 3,
			'limit' => $limit,
			'extra_where' => "(p.edubit_user_id IS NULL OR p.edubit_user_id = '')",
			'anchor_prefer_entered' => false,
		));
	}

	/**
	 * D2 — Hết hạn chưa đạt ngưỡng: cách 3 ngày, tối đa 3 (KB-16a/b/c).
	 * Lần 1 thường đã bắn ngay khi vào tag Hết hạn.
	 */
	public static function processD2Reminders($limit = 50) {
		return self::processStatusReminders(array(
			'status' => self::STATUS_HET_HAN,
			'keys' => array('kb16a', 'kb16b', 'kb16c'),
			'interval_days' => 3,
			'limit' => $limit,
		));
	}

	/**
	 * D3 — Không đủ ĐK, chưa chọn SP khác: mỗi tuần, tối đa 3 (KB-20a/b/c).
	 */
	public static function processD3Reminders($limit = 50) {
		return self::processStatusReminders(array(
			'status' => self::STATUS_KHONG_DU_DK,
			'keys' => array('kb20a', 'kb20b', 'kb20c'),
			'interval_days' => 7,
			'limit' => $limit,
		));
	}

	/**
	 * @param array $opt status, keys[3], interval_days, limit, extra_where?
	 */
	protected static function processStatusReminders(array $opt) {
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$status = $opt['status'];
		$keys = $opt['keys'];
		$interval = max(1, (int) $opt['interval_days']) * 86400;
		$limit = max(1, min(200, (int) $opt['limit']));
		$extra = isset($opt['extra_where']) ? (' AND ' . $opt['extra_where']) : '';
		$sql = "SELECT p.leadid, p.zalo_user_id, p.online_reminder_count, p.online_entered_at, p.online_last_remind_at, p.modified_at
			FROM bace_lead_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			WHERE p.online_status = ?
			  AND IFNULL(p.online_reminder_count, 0) < 3
			  {$extra}
			ORDER BY IFNULL(p.online_last_remind_at, p.modified_at) ASC
			LIMIT {$limit}";
		$res = $adb->pquery($sql, array($status));
		$sent = 0;
		$stopped = 0;
		if (!$res) {
			return array('sent' => 0, 'stopped' => 0);
		}
		$now = time();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$count = (int) $adb->query_result($res, $i, 'online_reminder_count');
			$last = (string) $adb->query_result($res, $i, 'online_last_remind_at');
			$entered = (string) $adb->query_result($res, $i, 'online_entered_at');
			$modified = (string) $adb->query_result($res, $i, 'modified_at');
			$anchorRaw = ($last && $last !== '0000-00-00 00:00:00')
				? $last
				: (($modified && $modified !== '0000-00-00 00:00:00') ? $modified : $entered);
			$anchor = $anchorRaw ? strtotime($anchorRaw) : false;
			if (!$anchor) {
				continue;
			}
			// count=0: chờ đủ interval kể từ lúc vào tag; count>0: kể từ lần nhắc trước.
			if (($now - $anchor) < $interval) {
				continue;
			}
			$key = isset($keys[$count]) ? $keys[$count] : $keys[count($keys) - 1];
			$r = self::sendCareToLead($leadId, $key, null, true);
			$newCount = $count + 1;
			$adb->pquery(
				'UPDATE bace_lead_profile SET online_reminder_count = ?, online_last_remind_at = ?, modified_at = ? WHERE leadid = ?',
				array($newCount, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $leadId)
			);
			if (!empty($r['success'])) {
				$sent++;
			}
			if ($newCount >= 3) {
				self::stopToNgungCskh($leadId);
				$stopped++;
			}
		}
		return array('sent' => $sent, 'stopped' => $stopped);
	}

	/**
	 * Một lần chạy: D0 + D1 + D2 + D3 + đồng hồ hạn.
	 */
	public static function processCareReminders($limit = 50) {
		$d0 = self::processD0Reminders($limit);
		$d1 = self::processD1Reminders($limit);
		$d2 = self::processD2Reminders($limit);
		$d3 = self::processD3Reminders($limit);
		$win = self::processAccessWindowTags(300);
		return array(
			'd0' => $d0,
			'd1' => $d1,
			'd2' => $d2,
			'd3' => $d3,
			'access_window' => $win,
		);
	}

	public static function registerCareReminderCron() {
		require_once 'vtlib/Vtiger/Cron.php';
		$name = 'OnlineGd12CareReminders';
		$handler = 'cron/modules/Leads/OnlineGd12CareReminders.service';
		$desc = 'GD 1.2 Online — D0/D1/D2/D3 + hạn 10 ngày';
		$existing = Vtiger_Cron::getInstance($name);
		if ($existing) {
			return;
		}
		Vtiger_Cron::register($name, $handler, 3600, 'Leads', 1, 0, $desc);
	}

	/**
	 * Đăng ký cron D0 (nhắc chưa điền form Online) — mỗi giờ (3600s).
	 * Logic bên trong vẫn cách 3 ngày / tối đa 3 lần.
	 */
	public static function registerD0ReminderCron() {
		require_once 'vtlib/Vtiger/Cron.php';
		$name = 'OnlineGd12D0Reminders';
		$handler = 'cron/modules/Leads/OnlineGd12D0Reminders.service';
		$desc = 'GD 1.2 Online — nhắc D0 chưa điền form (OA KB-02)';
		$existing = Vtiger_Cron::getInstance($name);
		if ($existing) {
			return;
		}
		Vtiger_Cron::register($name, $handler, 3600, 'Leads', 1, 0, $desc);
	}

	/**
	 * Catalog khóa Edubit cho UI (không có course_id mặc định).
	 */
	public static function edubitCoursesCatalog() {
		try {
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			$adapter = NkApiConnection::adapter('edubit');
			return $adapter->listCoursesForUi();
		} catch (Exception $e) {
			require_once 'modules/Vtiger/helpers/NkApi/EdubitAdapter.php';
			return NkApi_Edubit_Adapter::suggestedCourses();
		}
	}

	/**
	 * Cấp TK Edubit + kích hoạt khóa. Bắt buộc course_id (Sales chọn).
	 * @param int $leadId
	 * @param array $payload course_id (required), email?, name?, phone?, password?
	 */
	public static function provisionEdubitForLead($leadId, array $payload = array(), $userId = null) {
		global $current_user;
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		$courseId = isset($payload['course_id']) ? trim((string) $payload['course_id']) : '';
		if ($courseId === '') {
			return array('success' => false, 'error' => 'Phải chọn khóa học (course_id). Không có mặc định.');
		}

		self::installSchema();
		$adb = PearDatabase::getInstance();
		$lead = self::loadLeadContactFields($leadId);
		if (empty($lead)) {
			return array('success' => false, 'error' => 'Không tìm thấy Lead');
		}

		$name = isset($payload['name']) ? trim((string) $payload['name']) : $lead['name'];
		$phone = isset($payload['phone']) ? trim((string) $payload['phone']) : $lead['phone'];
		$email = '';
		if (!empty($payload['email'])) {
			$email = trim((string) $payload['email']);
		}
		if ($email === '' && !empty($lead['email'])) {
			$email = trim((string) $lead['email']);
		}
		$password = isset($payload['password']) ? (string) $payload['password'] : '';

		if ($name === '') {
			return array('success' => false, 'error' => 'Thiếu họ tên học viên');
		}
		if ($phone === '') {
			return array('success' => false, 'error' => 'Thiếu số điện thoại');
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return array('success' => false, 'error' => 'Cần email hợp lệ để cấp TK Edubit (điền trên form cấp TK).');
		}

		require_once 'modules/Vtiger/helpers/NkApiConnection.php';
		/** @var NkApi_Edubit_Adapter $adapter */
		$adapter = NkApiConnection::adapter('edubit');

		try {
			$created = $adapter->createUser(array(
				'name' => $name,
				'phone' => $phone,
				'email' => $email,
				'password' => $password,
			));
			$activated = $adapter->activateCourse(array(
				'name' => $name,
				'phone' => $phone,
				'email' => $email,
				'password' => $password,
				'course_id' => $courseId,
			));
		} catch (Exception $e) {
			$adb->pquery(
				'UPDATE bace_lead_profile SET edubit_last_error = ?, modified_at = ? WHERE leadid = ?',
				array(mb_substr($e->getMessage(), 0, 250), date('Y-m-d H:i:s'), $leadId)
			);
			return array('success' => false, 'error' => $e->getMessage());
		}

		$userIdEd = isset($created['user_id']) ? (string) $created['user_id'] : '';
		$now = date('Y-m-d H:i:s');
		$expiresAt = self::computeExpiresAt($now);
		$adb->pquery(
			'UPDATE bace_lead_profile SET
				online_status = ?,
				edubit_user_id = ?,
				edubit_course_id = ?,
				edubit_email = ?,
				edubit_activated_at = ?,
				edubit_expires_at = ?,
				edubit_renew_count = 0,
				edubit_progress_pct = COALESCE(edubit_progress_pct, 0),
				edubit_last_error = NULL,
				modified_at = ?
			 WHERE leadid = ?',
			array(self::STATUS_DANG_HOC, $userIdEd, $courseId, $email, $now, $expiresAt, $now, $leadId)
		);
		self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', self::STATUS_DANG_HOC));
		self::sendCareToLead($leadId, 'kb07');

		$genPass = isset($created['password']) ? (string) $created['password'] : '';
		$out = array(
			'success' => true,
			'status' => self::STATUS_DANG_HOC,
			'status_label' => self::statusLabel(self::STATUS_DANG_HOC),
			'edubit_user_id' => $userIdEd,
			'edubit_course_id' => $courseId,
			'edubit_email' => $email,
			'edubit_activated_at' => date('c', strtotime($now)),
			'edubit_expires_at' => date('c', strtotime($expiresAt)),
			'edubit_renew_count' => 0,
			'edubit_renew_remaining' => self::RENEW_MAX,
			'generated_password' => $genPass,
			'create_status' => isset($created['status']) ? $created['status'] : '',
			'activate_status' => isset($activated['status']) ? $activated['status'] : '',
			'message' => $genPass !== ''
				? ('Đã cấp TK Edubit. Mật khẩu tạm: ' . $genPass . '. Hạn truy cập đến ' . date('d/m/Y', strtotime($expiresAt)) . '.')
				: ('Đã cấp TK / kích hoạt khóa Edubit. Hạn truy cập đến ' . date('d/m/Y', strtotime($expiresAt)) . '.'),
			'courses' => self::edubitCoursesCatalog(),
		);

		// GD 1.2: 27312 (miễn phí) → Opp; khóa trả phí / khác → Khách hàng.
		if (self::isFreeCourseToOpportunity($courseId)) {
			$promoted = self::promoteLeadToOpportunityAfterEdubit($leadId, $out, $userId);
			$out['opportunity'] = $promoted;
			if (!empty($promoted['success'])) {
				$out['message'] = (isset($out['message']) ? $out['message'] . ' ' : '')
					. 'Đã chuyển xuống Cơ hội (khóa miễn phí 27312).';
				$out['potential_id'] = isset($promoted['potential_id']) ? $promoted['potential_id'] : 0;
				$out['contact_id'] = isset($promoted['contact_id']) ? $promoted['contact_id'] : 0;
				$out['list_url'] = 'index.php?module=Potentials&view=List&app=SALES';
			} else {
				$out['opportunity_error'] = isset($promoted['error']) ? $promoted['error'] : 'Chuyển Opp thất bại';
			}
		} else {
			$customer = self::promoteLeadToCustomerAfterEdubit($leadId, $out, $userId);
			$out['customer'] = $customer;
			if (!empty($customer['success'])) {
				$out['message'] = (isset($out['message']) ? $out['message'] . ' ' : '')
					. 'Đã chuyển xuống Khách hàng.';
				$out['contact_id'] = isset($customer['contact_id']) ? $customer['contact_id'] : 0;
				$out['list_url'] = 'index.php?module=Contacts&view=List&app=SALES';
			} else {
				$out['customer_error'] = isset($customer['error']) ? $customer['error'] : 'Chuyển KH thất bại';
			}
		}
		return $out;
	}

	/**
	 * Sau cấp TK khóa miễn phí 27312: Lead → Contact + Opp (theo dõi tiến trình trên Opp).
	 */
	public static function promoteLeadToOpportunityAfterEdubit($leadId, array $edubitMeta = array(), $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		try {
			require_once 'modules/Leads/models/ConvertService.php';
			$converted = Leads_ConvertService::convertLead($leadId, array(
				'modules' => array('Contacts', 'Potentials'),
				'order_category' => 'Internal',
			));
			if (!empty($converted['already_converted'])) {
				$potentialId = isset($converted['potentialId']) ? (int) $converted['potentialId'] : 0;
				$contactId = 0;
				if ($potentialId > 0) {
					try {
						$opp = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
						$contactId = (int) $opp->get('contact_id');
					} catch (Exception $e) {
						$contactId = 0;
					}
				}
				if ($contactId <= 0) {
					$contactId = (int) Leads_ConvertService::getLinkedContactId($leadId, true);
				}
			} else {
				$potentialId = isset($converted['potentialId']) ? (int) $converted['potentialId'] : 0;
				$contactId = isset($converted['contactId']) ? (int) $converted['contactId'] : 0;
			}
			if ($potentialId <= 0) {
				return array('success' => false, 'error' => 'Không tạo được Cơ hội từ Lead.');
			}

			$pct = 0;
			$courseId = isset($edubitMeta['edubit_course_id']) ? trim((string) $edubitMeta['edubit_course_id']) : self::COURSE_FREE_TO_OPP;
			$emailEd = isset($edubitMeta['edubit_email']) ? trim((string) $edubitMeta['edubit_email']) : '';
			$userEd = isset($edubitMeta['edubit_user_id']) ? trim((string) $edubitMeta['edubit_user_id']) : '';
			$adb = PearDatabase::getInstance();
			$pr = $adb->pquery(
				'SELECT edubit_progress_pct, edubit_course_id, edubit_email, edubit_user_id
				 FROM bace_lead_profile WHERE leadid = ?',
				array($leadId)
			);
			if ($pr && $adb->num_rows($pr) > 0) {
				$pct = (int) $adb->query_result($pr, 0, 'edubit_progress_pct');
				if ($courseId === '') {
					$courseId = trim((string) $adb->query_result($pr, 0, 'edubit_course_id'));
				}
				if ($emailEd === '') {
					$emailEd = trim((string) $adb->query_result($pr, 0, 'edubit_email'));
				}
				if ($userEd === '') {
					$userEd = trim((string) $adb->query_result($pr, 0, 'edubit_user_id'));
				}
			}

			if ($contactId > 0) {
				require_once 'modules/Contacts/models/ModernService.php';
				Contacts_ModernService::ensureCredentialFields();
				Contacts_ModernService::ensureEdubitProgressColumns();
				Contacts_ModernService::saveCredentialFields($contactId, 'Chưa cấp', 'Đã cấp');
				Contacts_ModernService::saveEdubitProgressOnContact($contactId, $pct, $courseId, $emailEd, $userEd);
				Contacts_ModernService::upsertEdubitCourseOnContact($contactId, array(
					'course_id' => $courseId,
					'progress_pct' => $pct,
					'email' => $emailEd,
					'user_id' => $userEd,
					'route' => 'opportunity',
				));
				Contacts_ModernService::markEdubitProvisionTimes($contactId, $courseId);
				self::syncAccessWindowToContact($leadId, $contactId);
				Leads_ConvertService::syncLeadProfileExtrasToContact($leadId, $contactId);
			}

			return array(
				'success' => true,
				'potential_id' => $potentialId,
				'contact_id' => $contactId,
				'list_url' => 'index.php?module=Potentials&view=List&app=SALES',
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * Sau cấp TK trên Lead (khóa trả phí): tạo/gắn Contact, set Đã cấp, copy tiến độ — không tạo Opp.
	 */
	public static function promoteLeadToCustomerAfterEdubit($leadId, array $edubitMeta = array(), $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		try {
			require_once 'modules/Leads/models/ConvertService.php';
			// Giữ phụ trách Lead (round-robin lúc tạo); không gán lại theo user đang thao tác.
			$converted = Leads_ConvertService::convertLeadToContactOnly($leadId, array());
			$contactId = isset($converted['contactId']) ? (int) $converted['contactId'] : 0;
			if ($contactId <= 0) {
				return array('success' => false, 'error' => 'Không tạo được Khách hàng từ Lead.');
			}

			$pct = 0;
			$courseId = isset($edubitMeta['edubit_course_id']) ? trim((string) $edubitMeta['edubit_course_id']) : '';
			$emailEd = isset($edubitMeta['edubit_email']) ? trim((string) $edubitMeta['edubit_email']) : '';
			$userEd = isset($edubitMeta['edubit_user_id']) ? trim((string) $edubitMeta['edubit_user_id']) : '';
			$adb = PearDatabase::getInstance();
			$pr = $adb->pquery(
				'SELECT edubit_progress_pct, edubit_course_id, edubit_email, edubit_user_id
				 FROM bace_lead_profile WHERE leadid = ?',
				array($leadId)
			);
			if ($pr && $adb->num_rows($pr) > 0) {
				$pct = (int) $adb->query_result($pr, 0, 'edubit_progress_pct');
				if ($courseId === '') {
					$courseId = trim((string) $adb->query_result($pr, 0, 'edubit_course_id'));
				}
				if ($emailEd === '') {
					$emailEd = trim((string) $adb->query_result($pr, 0, 'edubit_email'));
				}
				if ($userEd === '') {
					$userEd = trim((string) $adb->query_result($pr, 0, 'edubit_user_id'));
				}
			}

			require_once 'modules/Contacts/models/ModernService.php';
			Contacts_ModernService::ensureCredentialFields();
			Contacts_ModernService::ensureEdubitProgressColumns();
			Contacts_ModernService::saveCredentialFields($contactId, 'Chưa cấp', 'Đã cấp');
			Contacts_ModernService::saveEdubitProgressOnContact($contactId, $pct, $courseId, $emailEd, $userEd);
			Contacts_ModernService::upsertEdubitCourseOnContact($contactId, array(
				'course_id' => $courseId,
				'progress_pct' => $pct,
				'email' => $emailEd,
				'user_id' => $userEd,
				'route' => 'contact',
			));
			Contacts_ModernService::markEdubitProvisionTimes($contactId, $courseId);
			self::syncAccessWindowToContact($leadId, $contactId);
			Leads_ConvertService::syncLeadProfileExtrasToContact($leadId, $contactId);

			return array(
				'success' => true,
				'contact_id' => $contactId,
				'list_url' => 'index.php?module=Contacts&view=List&app=SALES',
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * Cấp TK Edubit từ Opp (map → lead), rồi auto chuyển xuống Khách hàng.
	 * @deprecated GD 1.2 không còn qua Opp — giữ để tương thích cũ.
	 */
	public static function provisionEdubitForPotential($potentialId, array $payload = array(), $userId = null) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu Opportunity id');
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Online — không cấp được TK Edubit.');
		}

		// Prefill name/phone/email từ Contact Opp nếu payload thiếu.
		if (empty($payload['email']) || empty($payload['name']) || empty($payload['phone'])) {
			try {
				$opp = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
				$contactId = (int) $opp->get('contact_id');
				if ($contactId > 0) {
					$contact = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
					if (empty($payload['email'])) {
						$em = trim((string) $contact->get('email'));
						if ($em !== '') {
							$payload['email'] = $em;
						}
					}
					if (empty($payload['phone'])) {
						$ph = trim((string) $contact->get('phone'));
						if ($ph === '') {
							$ph = trim((string) $contact->get('mobile'));
						}
						if ($ph !== '') {
							$payload['phone'] = $ph;
						}
					}
					if (empty($payload['name'])) {
						$fn = trim((string) $contact->get('firstname'));
						$ln = trim((string) $contact->get('lastname'));
						$nm = trim($fn . ' ' . $ln);
						if ($nm !== '') {
							$payload['name'] = $nm;
						}
					}
				}
			} catch (Exception $e) {
				// best-effort
			}
		}

		$provisioned = self::provisionEdubitForLead($leadId, $payload, $userId);
		if (empty($provisioned['success'])) {
			return $provisioned;
		}
		// Nếu Opp còn tồn tại (lead cũ), đánh dấu đã xuống KH để ẩn khỏi list Opp.
		try {
			require_once 'modules/Potentials/models/ModernService.php';
			$cid = isset($provisioned['contact_id']) ? (int) $provisioned['contact_id'] : 0;
			if ($cid > 0) {
				Potentials_ModernService::markConvertedToCustomer($potentialId, $cid);
			}
		} catch (Exception $e) {
			// ignore
		}
		return $provisioned;
	}

	/**
	 * Sau cấp TK (legacy Opp path): đánh dấu Opp → Customer.
	 */
	public static function promotePotentialToCustomerAfterEdubit($potentialId, $leadId, array $edubitMeta = array(), $userId = null) {
		$potentialId = (int) $potentialId;
		$leadId = (int) $leadId;
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu Opp id');
		}
		try {
			$opp = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
			$contactId = (int) $opp->get('contact_id');
			if ($contactId <= 0) {
				$adb = PearDatabase::getInstance();
				$res = $adb->pquery(
					'SELECT contactid FROM vtiger_contpotentialrel WHERE potentialid=? ORDER BY contactid DESC LIMIT 1',
					array($potentialId)
				);
				if ($res && $adb->num_rows($res) > 0) {
					$contactId = (int) $adb->query_result($res, 0, 'contactid');
				}
			}
			if ($contactId <= 0) {
				return array('success' => false, 'error' => 'Opp chưa có Contact — convert Lead trước.');
			}

			require_once 'modules/Potentials/models/ModernService.php';
			Potentials_ModernService::markConvertedToCustomer($potentialId, $contactId);

			$pct = 0;
			$courseId = isset($edubitMeta['edubit_course_id']) ? trim((string) $edubitMeta['edubit_course_id']) : '';
			$emailEd = isset($edubitMeta['edubit_email']) ? trim((string) $edubitMeta['edubit_email']) : '';
			$userEd = isset($edubitMeta['edubit_user_id']) ? trim((string) $edubitMeta['edubit_user_id']) : '';
			if ($leadId > 0) {
				$adb = PearDatabase::getInstance();
				$pr = $adb->pquery(
					'SELECT edubit_progress_pct, edubit_course_id, edubit_email, edubit_user_id
					 FROM bace_lead_profile WHERE leadid = ?',
					array($leadId)
				);
				if ($pr && $adb->num_rows($pr) > 0) {
					$pct = (int) $adb->query_result($pr, 0, 'edubit_progress_pct');
					if ($courseId === '') {
						$courseId = trim((string) $adb->query_result($pr, 0, 'edubit_course_id'));
					}
					if ($emailEd === '') {
						$emailEd = trim((string) $adb->query_result($pr, 0, 'edubit_email'));
					}
					if ($userEd === '') {
						$userEd = trim((string) $adb->query_result($pr, 0, 'edubit_user_id'));
					}
				}
			}

			require_once 'modules/Contacts/models/ModernService.php';
			Contacts_ModernService::ensureCredentialFields();
			Contacts_ModernService::ensureEdubitProgressColumns();
			Contacts_ModernService::saveCredentialFields($contactId, 'Chưa cấp', 'Đã cấp');
			Contacts_ModernService::saveEdubitProgressOnContact($contactId, $pct, $courseId, $emailEd, $userEd);
			Contacts_ModernService::markEdubitProvisionTimes($contactId, $courseId);

			try {
				require_once 'modules/Leads/models/ConvertService.php';
				Leads_ConvertService::syncContactTagsFromPotentials($contactId);
			} catch (Exception $e) {
				// ignore
			}

			return array(
				'success' => true,
				'contact_id' => $contactId,
				'potential_id' => $potentialId,
				'list_url' => 'index.php?module=Contacts&view=List&app=SALES',
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * Đồng bộ tiến độ học Edubit cho lead đã gắn course_id + email.
	 */
	public static function syncEdubitProgressForLead($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT edubit_email, edubit_course_id, edubit_progress_pct, online_status,
				edubit_activated_at, edubit_expires_at, edubit_renew_count
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array('success' => false, 'error' => 'Chưa có hồ sơ Online');
		}
		$email = trim((string) $adb->query_result($res, 0, 'edubit_email'));
		$courseId = trim((string) $adb->query_result($res, 0, 'edubit_course_id'));
		$previousPctRaw = $adb->query_result($res, 0, 'edubit_progress_pct');
		$previousPct = ($previousPctRaw === null || $previousPctRaw === '')
			? null : (int) $previousPctRaw;
		$activatedAt = trim((string) $adb->query_result($res, 0, 'edubit_activated_at'));
		$expiresAt = trim((string) $adb->query_result($res, 0, 'edubit_expires_at'));
		$renewCount = (int) $adb->query_result($res, 0, 'edubit_renew_count');
		if ($email === '' || $courseId === '') {
			return array('success' => false, 'error' => 'Lead chưa gắn email / course_id Edubit. Cấp TK trước.');
		}
		if (($expiresAt === '' || $expiresAt === '0000-00-00 00:00:00')
			&& $activatedAt !== '' && $activatedAt !== '0000-00-00 00:00:00') {
			$expiresAt = self::computeExpiresAt($activatedAt);
			$adb->pquery(
				'UPDATE bace_lead_profile SET edubit_expires_at = ?, modified_at = ? WHERE leadid = ?',
				array($expiresAt, date('Y-m-d H:i:s'), $leadId)
			);
		}

		require_once 'modules/Vtiger/helpers/NkApiConnection.php';
		$adapter = NkApiConnection::adapter('edubit');
		try {
			$prog = $adapter->getProcessLearnStudent($email, $courseId, 2);
		} catch (Exception $e) {
			$adb->pquery(
				'UPDATE bace_lead_profile SET edubit_last_error = ?, modified_at = ? WHERE leadid = ?',
				array(mb_substr($e->getMessage(), 0, 250), date('Y-m-d H:i:s'), $leadId)
			);
			return array('success' => false, 'error' => $e->getMessage());
		}

		$pct = isset($prog['progress_pct']) && $prog['progress_pct'] !== null
			? (int) $prog['progress_pct']
			: null;
		// Quy trình GD1.2 chỉ ghi nhận mốc cao nhất; tiến độ/tag không được tụt.
		if ($previousPct !== null && ($pct === null || $previousPct > $pct)) {
			$pct = $previousPct;
		}
		$now = date('Y-m-d H:i:s');
		$prevStatus = '';
		$prSt = $adb->pquery('SELECT online_status FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if ($prSt && $adb->num_rows($prSt) > 0) {
			$prevStatus = trim((string) $adb->query_result($prSt, 0, 'online_status'));
		}
		$status = self::resolveLearningStatus($pct, $expiresAt);
		$parseNote = null;
		if ($pct === null) {
			$rawSnap = isset($prog['data']) ? $prog['data'] : (isset($prog['raw']) ? $prog['raw'] : null);
			$json = is_array($rawSnap) || is_object($rawSnap)
				? json_encode($rawSnap, JSON_UNESCAPED_UNICODE)
				: (string) $rawSnap;
			$parseNote = 'Edubit OK nhưng chưa parse được % (v'
				. (isset($prog['version_used']) ? (int) $prog['version_used'] : 2)
				. '). raw: ' . mb_substr((string) $json, 0, 180);
		}
		$adb->pquery(
			'UPDATE bace_lead_profile SET
				edubit_progress_pct = ?,
				edubit_progress_at = ?,
				online_status = ?,
				edubit_last_error = ?,
				modified_at = ?
			 WHERE leadid = ?',
			array($pct, $now, $status, $parseNote, $now, $leadId)
		);
		self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', $status));
		// Mốc 50/80/100 chỉ đồng bộ nhãn Zalo OA; khách hàng tự vận hành tin nhắn.
		$zaloProgressTag = self::syncProgressTagToZalo($leadId, $status, $userId);
		if ($status === self::STATUS_SAP_HET_HAN && $prevStatus !== self::STATUS_SAP_HET_HAN) {
			self::sendCareToLead($leadId, 'kb10');
		}
		if ($status === self::STATUS_HET_HAN && $prevStatus !== self::STATUS_HET_HAN) {
			// D2 lần 1 ngay khi hết hạn
			$adb->pquery(
				'UPDATE bace_lead_profile SET online_reminder_count = 0, online_last_remind_at = NULL, modified_at = ? WHERE leadid = ?',
				array($now, $leadId)
			);
			$r16 = self::sendCareToLead($leadId, 'kb16a', null, true);
			if (!empty($r16['success'])) {
				$adb->pquery(
					'UPDATE bace_lead_profile SET online_reminder_count = 1, online_last_remind_at = ?, modified_at = ? WHERE leadid = ?',
					array($now, $now, $leadId)
				);
			}
		}

		try {
			require_once 'modules/Leads/models/ConvertService.php';
			require_once 'modules/Contacts/models/ModernService.php';
			$contactId = (int) Leads_ConvertService::getLinkedContactId($leadId, true);
			if ($contactId <= 0) {
				$potentialId = (int) Leads_ConvertService::getLinkedPotentialId($leadId);
				if ($potentialId > 0) {
					$opp = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
					$contactId = (int) $opp->get('contact_id');
					if ($contactId <= 0) {
						$adb2 = PearDatabase::getInstance();
						$pr = $adb2->pquery(
							'SELECT contact_customer_id FROM bace_potential_profile WHERE potentialid = ?',
							array($potentialId)
						);
						if ($pr && $adb2->num_rows($pr) > 0) {
							$contactId = (int) $adb2->query_result($pr, 0, 'contact_customer_id');
						}
					}
				}
			}
			if ($contactId > 0) {
				$userEd = '';
				$ur = $adb->pquery('SELECT edubit_user_id FROM bace_lead_profile WHERE leadid = ?', array($leadId));
				if ($ur && $adb->num_rows($ur) > 0) {
					$userEd = trim((string) $adb->query_result($ur, 0, 'edubit_user_id'));
				}
				Contacts_ModernService::saveEdubitProgressOnContact(
					$contactId,
					$pct,
					$courseId,
					$email,
					$userEd
				);
				self::syncAccessWindowToContact($leadId, $contactId);
				$createdAt = '';
				$cr = $adb->pquery(
					'SELECT createdtime FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
					array($contactId)
				);
				if ($cr && $adb->num_rows($cr) > 0) {
					$createdAt = trim((string) $adb->query_result($cr, 0, 'createdtime'));
				}
				Contacts_ModernService::markEdubitProvisionTimes($contactId, $courseId, $createdAt);
				Leads_ConvertService::syncLeadProfileExtrasToContact($leadId, $contactId);
			}
		} catch (Exception $e) {
			// best-effort
		}

		return array(
			'success' => true,
			'status' => $status,
			'status_label' => self::statusLabel($status),
			'progress_pct' => $pct,
			'edubit_course_id' => $courseId,
			'edubit_email' => $email,
			'edubit_expires_at' => ($expiresAt !== '' && $expiresAt !== '0000-00-00 00:00:00')
				? date('c', strtotime($expiresAt)) : '',
			'edubit_renew_count' => $renewCount,
			'edubit_renew_remaining' => max(0, self::RENEW_MAX - $renewCount),
			'can_edubit_renew' => (
				$renewCount < self::RENEW_MAX
				&& $status !== self::STATUS_DAT_80
				&& $status !== self::STATUS_HOAN_THANH
			) ? 1 : 0,
			'message' => $pct !== null ? ('Tiến độ: ' . $pct . '%') : 'Đã sync tiến độ (chưa parse được %).',
			'version_used' => isset($prog['version_used']) ? (int) $prog['version_used'] : null,
			'raw_data' => isset($prog['data']) ? $prog['data'] : null,
			'zalo_progress_tag' => $zaloProgressTag,
		);
	}

	/**
	 * Đồng bộ tiến độ Edubit theo Contact (Lead đã convert / ẩn).
	 * Ưu tiên lead liên kết; không có lead thì sync thẳng từ contactscf.
	 */
	public static function syncEdubitProgressForContact($contactId, $userId = null) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			return array('success' => false, 'error' => 'Thiếu contact id');
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByContact($contactId);
		if ($leadId > 0) {
			$out = self::syncEdubitProgressForLead($leadId, $userId);
			$out['lead_id'] = $leadId;
			$out['contact_id'] = $contactId;
			return $out;
		}

		require_once 'modules/Contacts/models/ModernService.php';
		Contacts_ModernService::ensureEdubitProgressColumns();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT edubit_email, edubit_course_id, edubit_user_id, edubit_activated_at,
				edubit_expires_at, edubit_renew_count, edubit_progress_pct, online_status
			 FROM vtiger_contactscf WHERE contactid = ?',
			array($contactId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array('success' => false, 'error' => 'Contact chưa có dữ liệu Edubit');
		}
		$email = trim((string) $adb->query_result($res, 0, 'edubit_email'));
		$courseId = trim((string) $adb->query_result($res, 0, 'edubit_course_id'));
		$userEd = trim((string) $adb->query_result($res, 0, 'edubit_user_id'));
		$activatedAt = trim((string) $adb->query_result($res, 0, 'edubit_activated_at'));
		$expiresAt = trim((string) $adb->query_result($res, 0, 'edubit_expires_at'));
		$renewCount = (int) $adb->query_result($res, 0, 'edubit_renew_count');
		$previousPctRaw = $adb->query_result($res, 0, 'edubit_progress_pct');
		$previousPct = ($previousPctRaw === null || $previousPctRaw === '')
			? null : (int) $previousPctRaw;
		$currentStatus = trim((string) $adb->query_result($res, 0, 'online_status'));
		if ($email === '' || $courseId === '') {
			return array('success' => false, 'error' => 'Contact chưa gắn email / course_id Edubit');
		}
		if (($expiresAt === '' || $expiresAt === '0000-00-00 00:00:00')
			&& $activatedAt !== '' && $activatedAt !== '0000-00-00 00:00:00') {
			$expiresAt = self::computeExpiresAt($activatedAt);
			Contacts_ModernService::saveEdubitAccessWindowOnContact(
				$contactId, $activatedAt, $expiresAt, $renewCount, '', ''
			);
		}

		require_once 'modules/Vtiger/helpers/NkApiConnection.php';
		$adapter = NkApiConnection::adapter('edubit');
		try {
			$prog = $adapter->getProcessLearnStudent($email, $courseId, 2);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage(), 'contact_id' => $contactId);
		}
		$pct = isset($prog['progress_pct']) && $prog['progress_pct'] !== null
			? (int) $prog['progress_pct']
			: null;
		if ($previousPct !== null && ($pct === null || $previousPct > $pct)) {
			$pct = $previousPct;
		}
		$status = $pct !== null ? self::resolveLearningStatus($pct, $expiresAt) : $currentStatus;
		Contacts_ModernService::saveEdubitProgressOnContact($contactId, $pct, $courseId, $email, $userEd);
		Contacts_ModernService::saveEdubitAccessWindowOnContact(
			$contactId, $activatedAt, $expiresAt, $renewCount, '', $status
		);
		$msg = $pct !== null
			? ('Tiến độ: ' . $pct . '%')
			: 'Edubit OK nhưng chưa parse được % — kiểm tra cấu trúc data / total_lessons khóa học.';
		return array(
			'success' => true,
			'contact_id' => $contactId,
			'progress_pct' => $pct,
			'status' => $status,
			'status_label' => self::statusLabel($status),
			'edubit_expires_at' => ($expiresAt !== '' && $expiresAt !== '0000-00-00 00:00:00')
				? date('c', strtotime($expiresAt)) : '',
			'edubit_renew_count' => $renewCount,
			'edubit_renew_remaining' => max(0, self::RENEW_MAX - $renewCount),
			'version_used' => isset($prog['version_used']) ? (int) $prog['version_used'] : null,
			'raw_data' => isset($prog['data']) ? $prog['data'] : null,
			'message' => $msg,
		);
	}

	/**
	 * Đồng bộ tiến độ tất cả Contact đã cấp TK Edubit.
	 * @param int $limit trần số hồ sơ / lần
	 */
	public static function syncEdubitProgressForAllContacts($limit = 150, $userId = null) {
		require_once 'modules/Contacts/models/ModernService.php';
		Contacts_ModernService::ensureEdubitProgressColumns();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(300, (int) $limit));
		$res = $adb->pquery(
			"SELECT cf.contactid
			 FROM vtiger_contactscf cf
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cf.contactid AND ce.deleted = 0
			 WHERE cf.edubit_email IS NOT NULL AND cf.edubit_email <> ''
			   AND cf.edubit_course_id IS NOT NULL AND cf.edubit_course_id <> ''
			 ORDER BY cf.contactid ASC
			 LIMIT {$limit}",
			array()
		);
		$ok = 0;
		$fail = 0;
		$parsed = 0;
		$maxPct = 0;
		$items = array();
		$rows = ($res && $adb->num_rows($res) > 0) ? $adb->num_rows($res) : 0;
		for ($i = 0; $i < $rows; $i++) {
			$cid = (int) $adb->query_result($res, $i, 'contactid');
			$one = self::syncEdubitProgressForContact($cid, $userId);
			if (!empty($one['success'])) {
				$ok++;
				$pctOne = array_key_exists('progress_pct', $one) && $one['progress_pct'] !== null
					? (int) $one['progress_pct'] : null;
				if ($pctOne !== null) {
					$parsed++;
					$maxPct = max($maxPct, $pctOne);
				}
				$entry = array(
					'contact_id' => $cid,
					'progress_pct' => $pctOne,
					'status' => isset($one['status']) ? $one['status'] : '',
					'message' => isset($one['message']) ? $one['message'] : '',
					'version_used' => isset($one['version_used']) ? $one['version_used'] : null,
					'zalo_progress_tag' => isset($one['zalo_progress_tag'])
						? $one['zalo_progress_tag'] : null,
				);
				// Giữ mẫu raw của tối đa 3 hồ sơ đầu để chẩn đoán thay đổi payload Edubit.
				if (isset($one['raw_data']) && count($items) < 3) {
					$entry['raw_data'] = $one['raw_data'];
				}
				$items[] = $entry;
			} else {
				$fail++;
				$items[] = array(
					'contact_id' => $cid,
					'error' => isset($one['error']) ? $one['error'] : 'fail',
				);
			}
		}
		$msg = 'Đã đồng bộ ' . $ok . '/' . $rows . ' khách hàng'
			. ($fail > 0 ? (' (lỗi ' . $fail . ')') : '')
			. ($parsed > 0 ? ('; · có %: ' . $parsed . ' · max ' . $maxPct . '%') : '')
			. ($ok > 0 && $parsed === 0 ? ' · Edubit OK nhưng chưa parse được % (xem raw_data trong response)' : '')
			. '.';
		return array(
			'success' => true,
			'scanned' => $rows,
			'ok' => $ok,
			'fail' => $fail,
			'parsed' => $parsed,
			'max_progress_pct' => $maxPct,
			'items' => $items,
			'message' => $msg,
		);
	}

	/**
	 * Sales gia hạn truy cập (+10 ngày), tối đa 3 lần — không đặt lại bộ đếm.
	 * Không tự động: chỉ khi Sales bấm (khách xin).
	 */
	public static function renewEdubitAccessForLead($leadId, array $payload = array(), $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT edubit_user_id, edubit_course_id, edubit_email, edubit_activated_at,
				edubit_expires_at, edubit_renew_count, edubit_progress_pct, online_status
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array('success' => false, 'error' => 'Chưa có hồ sơ Online');
		}
		$userEd = trim((string) $adb->query_result($res, 0, 'edubit_user_id'));
		$courseId = trim((string) $adb->query_result($res, 0, 'edubit_course_id'));
		$email = trim((string) $adb->query_result($res, 0, 'edubit_email'));
		$activatedAt = trim((string) $adb->query_result($res, 0, 'edubit_activated_at'));
		$renewCount = (int) $adb->query_result($res, 0, 'edubit_renew_count');
		$pctRaw = $adb->query_result($res, 0, 'edubit_progress_pct');
		$pct = ($pctRaw === null || $pctRaw === '') ? null : (int) $pctRaw;
		if ($userEd === '' && $courseId === '' && $email === '') {
			return array('success' => false, 'error' => 'Chưa cấp TK Edubit — không gia hạn được.');
		}
		if ($pct !== null && $pct >= 80) {
			return array('success' => false, 'error' => 'Đã đạt ≥80% — không cần gia hạn.');
		}
		if ($renewCount >= self::RENEW_MAX) {
			return array(
				'success' => false,
				'error' => 'Đã hết ' . self::RENEW_MAX . ' lần gia hạn — không mở lại được.',
				'edubit_renew_count' => $renewCount,
				'edubit_renew_remaining' => 0,
			);
		}
		if ($activatedAt === '' || $activatedAt === '0000-00-00 00:00:00') {
			$activatedAt = date('Y-m-d H:i:s');
		}
		$reason = isset($payload['reason']) ? trim((string) $payload['reason']) : '';
		if ($reason === '' && isset($payload['edubit_expiry_reason'])) {
			$reason = trim((string) $payload['edubit_expiry_reason']);
		}
		$now = date('Y-m-d H:i:s');
		$newExpires = self::computeExpiresAt($now);
		$newCount = $renewCount + 1;
		$status = self::resolveLearningStatus($pct, $newExpires);
		$adb->pquery(
			'UPDATE bace_lead_profile SET
				edubit_expires_at = ?,
				edubit_renew_count = ?,
				edubit_expiry_reason = ?,
				edubit_activated_at = COALESCE(NULLIF(edubit_activated_at, \'0000-00-00 00:00:00\'), ?),
				online_status = ?,
				edubit_last_error = NULL,
				modified_at = ?
			 WHERE leadid = ?',
			array(
				$newExpires,
				$newCount,
				$reason !== '' ? mb_substr($reason, 0, 64) : null,
				$activatedAt,
				$status,
				$now,
				$leadId,
			)
		);
		self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', $status));

		$contactId = 0;
		try {
			require_once 'modules/Leads/models/ConvertService.php';
			$contactId = (int) Leads_ConvertService::getLinkedContactId($leadId, true);
			if ($contactId > 0) {
				self::syncAccessWindowToContact($leadId, $contactId);
			}
		} catch (Exception $e) {
			// best-effort
		}

		$remaining = max(0, self::RENEW_MAX - $newCount);
		return array(
			'success' => true,
			'status' => $status,
			'status_label' => self::statusLabel($status),
			'edubit_expires_at' => date('c', strtotime($newExpires)),
			'edubit_renew_count' => $newCount,
			'edubit_renew_remaining' => $remaining,
			'can_edubit_renew' => $remaining > 0 ? 1 : 0,
			'edubit_expiry_reason' => $reason,
			'contact_id' => $contactId,
			'message' => 'Đã gia hạn lần ' . $newCount . '/' . self::RENEW_MAX
				. '. Hạn mới đến ' . date('d/m/Y', strtotime($newExpires))
				. ($remaining > 0 ? (' — còn ' . $remaining . ' lần.') : ' — đây là lần cuối.'),
		);
	}

	public static function renewEdubitAccessForContact($contactId, array $payload = array(), $userId = null) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			return array('success' => false, 'error' => 'Thiếu contact id');
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByContact($contactId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Contact chưa gắn Lead Online — không gia hạn được.');
		}
		$out = self::renewEdubitAccessForLead($leadId, $payload, $userId);
		$out['lead_id'] = $leadId;
		$out['contact_id'] = $contactId;
		return $out;
	}

	/**
	 * Mirror hạn / số lần gia hạn / online_status lên Contact list.
	 */
	public static function syncAccessWindowToContact($leadId, $contactId) {
		$leadId = (int) $leadId;
		$contactId = (int) $contactId;
		if ($leadId <= 0 || $contactId <= 0) {
			return;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT edubit_activated_at, edubit_expires_at, edubit_renew_count, edubit_expiry_reason, online_status
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return;
		}
		require_once 'modules/Contacts/models/ModernService.php';
		Contacts_ModernService::saveEdubitAccessWindowOnContact(
			$contactId,
			trim((string) $adb->query_result($res, 0, 'edubit_activated_at')),
			trim((string) $adb->query_result($res, 0, 'edubit_expires_at')),
			(int) $adb->query_result($res, 0, 'edubit_renew_count'),
			trim((string) $adb->query_result($res, 0, 'edubit_expiry_reason')),
			trim((string) $adb->query_result($res, 0, 'online_status'))
		);
	}

	/**
	 * Cron: cập nhật tag Sắp hết hạn / Hết hạn theo đồng hồ (không gọi Edubit API).
	 */
	public static function processAccessWindowTags($limit = 200) {
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(500, (int) $limit));
		$res = $adb->pquery(
			"SELECT leadid, edubit_progress_pct, edubit_activated_at, edubit_expires_at, online_status
			 FROM bace_lead_profile
			 WHERE edubit_activated_at IS NOT NULL
			   AND edubit_activated_at <> '0000-00-00 00:00:00'
			   AND online_status IN (?, ?, ?, ?, ?, ?)
			 ORDER BY leadid ASC
			 LIMIT {$limit}",
			array(
				self::STATUS_DANG_HOC,
				self::STATUS_DAT_50,
				self::STATUS_SAP_HET_HAN,
				self::STATUS_HET_HAN,
				self::STATUS_DAT_80,
				self::STATUS_HOAN_THANH,
			)
		);
		$updated = 0;
		$rows = ($res && $adb->num_rows($res) > 0) ? $adb->num_rows($res) : 0;
		for ($i = 0; $i < $rows; $i++) {
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$pctRaw = $adb->query_result($res, $i, 'edubit_progress_pct');
			$pct = ($pctRaw === null || $pctRaw === '') ? null : (int) $pctRaw;
			$activatedAt = trim((string) $adb->query_result($res, $i, 'edubit_activated_at'));
			$expiresAt = trim((string) $adb->query_result($res, $i, 'edubit_expires_at'));
			$cur = trim((string) $adb->query_result($res, $i, 'online_status'));
			if (($expiresAt === '' || $expiresAt === '0000-00-00 00:00:00') && $activatedAt !== '') {
				$expiresAt = self::computeExpiresAt($activatedAt);
				$adb->pquery(
					'UPDATE bace_lead_profile SET edubit_expires_at = ?, modified_at = ? WHERE leadid = ?',
					array($expiresAt, date('Y-m-d H:i:s'), $leadId)
				);
			}
			$next = self::resolveLearningStatus($pct, $expiresAt);
			if ($next === $cur) {
				continue;
			}
			$nowWin = date('Y-m-d H:i:s');
			$adb->pquery(
				'UPDATE bace_lead_profile SET online_status = ?, modified_at = ? WHERE leadid = ?',
				array($next, $nowWin, $leadId)
			);
			self::syncStatusTagsOnly($leadId, array('zalo', 'mien_phi_online', $next));
			if ($next === self::STATUS_SAP_HET_HAN) {
				self::sendCareToLead($leadId, 'kb10');
			}
			if ($next === self::STATUS_HET_HAN && $cur !== self::STATUS_HET_HAN) {
				$adb->pquery(
					'UPDATE bace_lead_profile SET online_reminder_count = 0, online_last_remind_at = NULL, modified_at = ? WHERE leadid = ?',
					array($nowWin, $leadId)
				);
				$r16 = self::sendCareToLead($leadId, 'kb16a', null, true);
				if (!empty($r16['success'])) {
					$adb->pquery(
						'UPDATE bace_lead_profile SET online_reminder_count = 1, online_last_remind_at = ?, modified_at = ? WHERE leadid = ?',
						array($nowWin, $nowWin, $leadId)
					);
				}
			}
			try {
				require_once 'modules/Leads/models/ConvertService.php';
				$contactId = (int) Leads_ConvertService::getLinkedContactId($leadId, true);
				if ($contactId > 0) {
					self::syncAccessWindowToContact($leadId, $contactId);
				}
			} catch (Exception $e) {
				// best-effort
			}
			$updated++;
		}
		return array('scanned' => $rows, 'updated' => $updated);
	}

	public static function registerAccessWindowCron() {
		require_once 'vtlib/Vtiger/Cron.php';
		$name = 'OnlineGd12AccessWindow';
		$handler = 'cron/modules/Leads/OnlineGd12AccessWindow.service';
		$desc = 'GD 1.2 Online — cập nhật Sắp hết hạn / Hết hạn (10 ngày)';
		$existing = Vtiger_Cron::getInstance($name);
		if ($existing) {
			return;
		}
		Vtiger_Cron::register($name, $handler, 3600, 'Leads', 1, 0, $desc);
	}

	/**
	 * Đồng bộ tiến độ Edubit tự động mỗi giờ.
	 * Vẫn giữ nút sync thủ công trên Contacts cho trường hợp cần cập nhật ngay.
	 */
	public static function registerEdubitProgressCron() {
		require_once 'vtlib/Vtiger/Cron.php';
		$name = 'OnlineGd12EdubitProgress';
		$handler = 'cron/modules/Leads/OnlineGd12EdubitProgress.service';
		$desc = 'GD 1.2 Online — đồng bộ tiến độ Edubit mỗi giờ';
		$existing = Vtiger_Cron::getInstance($name);
		if ($existing) {
			// Bảo đảm task tạo bằng SQL / bản cũ vẫn chạy đúng chu kỳ.
			$existing->setFrequency(3600);
			return;
		}
		Vtiger_Cron::register($name, $handler, 3600, 'Leads', 1, 0, $desc);
	}

	protected static function loadLeadContactFields($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT ld.firstname, ld.lastname, ld.email, la.phone
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = ld.leadid
			 WHERE ld.leadid = ?",
			array((int) $leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		$fn = trim((string) $adb->query_result($res, 0, 'firstname'));
		$ln = trim((string) $adb->query_result($res, 0, 'lastname'));
		$name = trim($fn . ' ' . $ln);
		if ($name === '') {
			$name = $ln !== '' ? $ln : $fn;
		}
		return array(
			'name' => $name,
			'email' => trim((string) $adb->query_result($res, 0, 'email')),
			'phone' => trim((string) $adb->query_result($res, 0, 'phone')),
		);
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
