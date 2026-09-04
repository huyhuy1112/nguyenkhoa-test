<?php
/*+***********************************************************************************
 * Giai đoạn 1.1 — lớp offline miễn phí.
 * Tag Offline — [trạng thái] + 4 bộ đếm R1–R4 (không trùng Last Touch goi_lan_*).
 * Last Touch vẫn là nhật ký gọi; service này gắn trạng thái Offline / điểm rơi.
 *************************************************************************************/

class Leads_OfflineGd11Service {

	const STATUS_HEN_GOI_LAI = 'offline_hen_goi_lai';
	const STATUS_KHONG_NGHE_MAY = 'offline_khong_nghe_may';
	const STATUS_SAI_THONG_TIN = 'offline_sai_thong_tin';
	const STATUS_CHUYEN_CT = 'offline_chuyen_chuong_trinh';
	const STATUS_CHUA_XN_LICH = 'offline_chua_xac_nhan_lich';
	const STATUS_DA_XN_LICH = 'offline_da_xac_nhan_lich';
	const STATUS_HEN_LICH_LAI = 'offline_hen_lich_lai';
	const STATUS_KHONG_THAM_GIA = 'offline_khong_tham_gia';
	const STATUS_DA_THAM_GIA = 'offline_da_tham_gia';
	const STATUS_NGUNG_CSKH = 'offline_ngung_cskh';

	const STATUS_TAGS = array(
		self::STATUS_HEN_GOI_LAI,
		self::STATUS_KHONG_NGHE_MAY,
		self::STATUS_SAI_THONG_TIN,
		self::STATUS_CHUYEN_CT,
		self::STATUS_CHUA_XN_LICH,
		self::STATUS_DA_XN_LICH,
		self::STATUS_HEN_LICH_LAI,
		self::STATUS_KHONG_THAM_GIA,
		self::STATUS_DA_THAM_GIA,
		self::STATUS_NGUNG_CSKH,
	);

	const R1_TAGS = array(
		self::STATUS_HEN_GOI_LAI,
		self::STATUS_KHONG_NGHE_MAY,
		self::STATUS_SAI_THONG_TIN,
	);

	const COUNTER_MAX = 3;

	public static function statusLabels() {
		return array(
			self::STATUS_HEN_GOI_LAI => 'Offline — Hẹn gọi lại',
			self::STATUS_KHONG_NGHE_MAY => 'Offline — Không nghe máy',
			self::STATUS_SAI_THONG_TIN => 'Offline — Sai thông tin liên hệ',
			self::STATUS_CHUYEN_CT => 'Offline — Chuyển chương trình khác',
			self::STATUS_CHUA_XN_LICH => 'Offline — Chưa xác nhận lịch học',
			self::STATUS_DA_XN_LICH => 'Offline — Đã xác nhận lịch học',
			self::STATUS_HEN_LICH_LAI => 'Offline — Hẹn lịch học lại',
			self::STATUS_KHONG_THAM_GIA => 'Offline — Không tham gia lớp học free',
			self::STATUS_DA_THAM_GIA => 'Offline — Đã tham gia lớp học free',
			self::STATUS_NGUNG_CSKH => 'Offline — Ngưng chăm sóc',
		);
	}

	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$prof = $adb->pquery("SHOW TABLES LIKE 'bace_lead_profile'", array());
		if (!$prof || $adb->num_rows($prof) < 1) {
			return;
		}
		$cols = array(
			'offline_status' => "VARCHAR(48) DEFAULT NULL",
			'offline_r1_contact' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r2_schedule' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r3_class' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r4_transfer' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_preclass_confirm' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_class_date' => "DATE DEFAULT NULL",
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
	}

	/**
	 * Lead thuộc luồng Offline 1.1 (Sheet / sản phẩm offline / đã có offline_status).
	 */
	public static function isOfflineLead(array $leadOrRow, array $tags = array()) {
		if (!empty($leadOrRow['sheet_source'])) {
			return true;
		}
		$status = isset($leadOrRow['offline_status']) ? trim((string) $leadOrRow['offline_status']) : '';
		if ($status !== '') {
			return true;
		}
		if (empty($tags) && isset($leadOrRow['tags']) && is_array($leadOrRow['tags'])) {
			$tags = $leadOrRow['tags'];
		}
		foreach ($tags as $tag) {
			$t = strtolower(trim((string) $tag));
			if ($t === 'mien_phi_offline' || strpos($t, 'offline_') === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sheet ingest — gắn sản phẩm Offline.
	 */
	public static function ensureProgramTag(array $tags) {
		$out = array();
		$has = false;
		foreach ($tags as $tag) {
			$t = strtolower(trim((string) $tag));
			if ($t === '') {
				continue;
			}
			$out[] = $tag;
			if ($t === 'mien_phi_offline') {
				$has = true;
			}
		}
		if (!$has) {
			$out[] = 'mien_phi_offline';
		}
		return array_values(array_unique($out));
	}

	/**
	 * Gắn đúng 1 tag trạng thái Offline (+ giữ mien_phi_offline / khác).
	 */
	public static function applyStatus($leadId, $statusTag, $userId = null) {
		$leadId = (int) $leadId;
		$statusTag = strtolower(trim((string) $statusTag));
		if ($leadId <= 0 || !in_array($statusTag, self::STATUS_TAGS, true)) {
			return false;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			"UPDATE bace_lead_profile SET offline_status = ?, modified_at = ? WHERE leadid = ?",
			array($statusTag, $now, $leadId)
		);
		self::syncStatusTags($leadId, $statusTag, $userId);
		return true;
	}

	/**
	 * @param string $counter r1|r2|r3|r4
	 * @return array{count:int,stopped:bool}
	 */
	public static function bumpCounter($leadId, $counter) {
		$leadId = (int) $leadId;
		$map = array(
			'r1' => 'offline_r1_contact',
			'r2' => 'offline_r2_schedule',
			'r3' => 'offline_r3_class',
			'r4' => 'offline_r4_transfer',
		);
		$col = isset($map[$counter]) ? $map[$counter] : '';
		if ($leadId <= 0 || $col === '') {
			return array('count' => 0, 'stopped' => false);
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT {$col} AS c FROM bace_lead_profile WHERE leadid = ?", array($leadId));
		$cur = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'c') : 0;
		$next = min(self::COUNTER_MAX, $cur + 1);
		$adb->pquery(
			"UPDATE bace_lead_profile SET {$col} = ?, modified_at = ? WHERE leadid = ?",
			array($next, date('Y-m-d H:i:s'), $leadId)
		);
		return array('count' => $next, 'stopped' => $next >= self::COUNTER_MAX);
	}

	/**
	 * Last Touch — Không nghe máy trên lead Offline → R1.
	 */
	public static function onLastTouchMissed($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::leadIsOffline($leadId)) {
			return null;
		}
		$bump = self::bumpCounter($leadId, 'r1');
		if (!empty($bump['stopped'])) {
			self::applyStatus($leadId, self::STATUS_NGUNG_CSKH, $userId);
			return array('status' => self::STATUS_NGUNG_CSKH, 'r1' => $bump['count'], 'drop' => 'R1');
		}
		self::applyStatus($leadId, self::STATUS_KHONG_NGHE_MAY, $userId);
		return array('status' => self::STATUS_KHONG_NGHE_MAY, 'r1' => $bump['count'], 'drop' => '');
	}

	/**
	 * Last Touch — Nghe máy trên Offline: không convert Opp tại đây (Opp sau đủ ĐK Bộ B).
	 */
	public static function onLastTouchAnswered($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::leadIsOffline($leadId)) {
			return null;
		}
		// Giữ trạng thái hiện tại; chỉ đánh dấu đang xử lý nếu chưa có offline_status.
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT offline_status FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		$cur = ($res && $adb->num_rows($res) > 0) ? trim((string) $adb->query_result($res, 0, 'offline_status')) : '';
		if ($cur === '' || in_array($cur, self::R1_TAGS, true)) {
			// Đã liên hệ được — chờ / đang xác minh; chưa gắn lịch.
			return array('status' => $cur, 'answered' => true);
		}
		return array('status' => $cur, 'answered' => true);
	}

	/**
	 * Sau Sales Bộ B lưu xác minh.
	 */
	public static function onSalesVerified($leadId, array $result, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::leadIsOffline($leadId)) {
			return null;
		}
		$elig = isset($result['eligibility_result']) ? $result['eligibility_result'] : '';
		if ($elig === 'khong_du_dk') {
			self::applyStatus($leadId, self::STATUS_CHUYEN_CT, $userId);
			return array('status' => self::STATUS_CHUYEN_CT);
		}
		if ($elig === 'du_dk') {
			// Đủ ĐK nhưng chưa chốt lịch trong payload → Chưa xác nhận lịch.
			$schedule = isset($result['schedule_outcome']) ? $result['schedule_outcome'] : '';
			if ($schedule === 'da_xac_nhan_lich' || $schedule === self::STATUS_DA_XN_LICH) {
				self::applyStatus($leadId, self::STATUS_DA_XN_LICH, $userId);
				$classDate = isset($result['class_date']) ? trim((string) $result['class_date']) : '';
				if ($classDate !== '') {
					self::setClassDate($leadId, $classDate);
				}
				self::bumpCounter($leadId, 'r3'); // lần hẹn lớp = 1 khi chốt lần đầu
				return array('status' => self::STATUS_DA_XN_LICH);
			}
			self::applyStatus($leadId, self::STATUS_CHUA_XN_LICH, $userId);
			return array('status' => self::STATUS_CHUA_XN_LICH);
		}
		return null;
	}

	/**
	 * API / UI — áp dụng kết quả Bước 1 (và một phần Bước 2 tag).
	 * @param string $action hen_goi_lai|khong_nghe_may|sai_thong_tin|chua_xac_nhan_lich|da_xac_nhan_lich|hen_lich_lai|chuyen_chuong_trinh|ngung_cskh|khong_tham_gia|da_tham_gia
	 */
	public static function applyAction($leadId, $action, array $payload = array(), $userId = null) {
		$leadId = (int) $leadId;
		$action = strtolower(trim((string) $action));
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		self::installSchema();
		require_once 'modules/Leads/models/ModernService.php';

		$map = array(
			'hen_goi_lai' => self::STATUS_HEN_GOI_LAI,
			'khong_nghe_may' => self::STATUS_KHONG_NGHE_MAY,
			'sai_thong_tin' => self::STATUS_SAI_THONG_TIN,
			'chua_xac_nhan_lich' => self::STATUS_CHUA_XN_LICH,
			'da_xac_nhan_lich' => self::STATUS_DA_XN_LICH,
			'hen_lich_lai' => self::STATUS_HEN_LICH_LAI,
			'chuyen_chuong_trinh' => self::STATUS_CHUYEN_CT,
			'ngung_cskh' => self::STATUS_NGUNG_CSKH,
			'khong_tham_gia' => self::STATUS_KHONG_THAM_GIA,
			'da_tham_gia' => self::STATUS_DA_THAM_GIA,
		);
		if (!isset($map[$action])) {
			return array('success' => false, 'error' => 'Action không hợp lệ');
		}
		$status = $map[$action];
		$drop = '';

		if (in_array($status, self::R1_TAGS, true)) {
			$bump = self::bumpCounter($leadId, 'r1');
			if (!empty($bump['stopped'])) {
				$status = self::STATUS_NGUNG_CSKH;
				$drop = 'R1';
			}
		} elseif ($status === self::STATUS_CHUA_XN_LICH || $status === self::STATUS_HEN_LICH_LAI) {
			$bump = self::bumpCounter($leadId, 'r2');
			if (!empty($bump['stopped'])) {
				$status = self::STATUS_NGUNG_CSKH;
				$drop = 'R2';
			}
		} elseif ($status === self::STATUS_DA_XN_LICH) {
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery(
				'SELECT offline_r3_class AS c FROM bace_lead_profile WHERE leadid = ?',
				array($leadId)
			);
			$cur = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'c') : 0;
			if ($cur < 1) {
				self::bumpCounter($leadId, 'r3');
			}
			$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
			if ($classDate !== '') {
				self::setClassDate($leadId, $classDate);
			}
		} elseif ($status === self::STATUS_KHONG_THAM_GIA) {
			$bump = self::bumpCounter($leadId, 'r3');
			if (!empty($bump['stopped'])) {
				$status = self::STATUS_NGUNG_CSKH;
				$drop = 'R3';
			}
		} elseif ($status === self::STATUS_CHUYEN_CT) {
			$bump = self::bumpCounter($leadId, 'r4');
			if (!empty($bump['stopped'])) {
				$status = self::STATUS_NGUNG_CSKH;
				$drop = 'R4';
			}
		}

		self::applyStatus($leadId, $status, $userId);
		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		$labels = self::statusLabels();
		return array(
			'success' => true,
			'status' => $status,
			'status_label' => isset($labels[$status]) ? $labels[$status] : $status,
			'drop' => $drop,
			'lead' => $lead,
		);
	}

	public static function profileBlock(array $row) {
		$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
		$labels = self::statusLabels();
		return array(
			'offline_status' => $status,
			'offline_status_label' => isset($labels[$status]) ? $labels[$status] : '',
			'offline_r1_contact' => isset($row['offline_r1_contact']) ? (int) $row['offline_r1_contact'] : 0,
			'offline_r2_schedule' => isset($row['offline_r2_schedule']) ? (int) $row['offline_r2_schedule'] : 0,
			'offline_r3_class' => isset($row['offline_r3_class']) ? (int) $row['offline_r3_class'] : 0,
			'offline_r4_transfer' => isset($row['offline_r4_transfer']) ? (int) $row['offline_r4_transfer'] : 0,
			'offline_preclass_confirm' => !empty($row['offline_preclass_confirm']) ? 1 : 0,
			'offline_class_date' => (!empty($row['offline_class_date']) && $row['offline_class_date'] !== '0000-00-00')
				? (string) $row['offline_class_date'] : '',
			'offline_status_options' => self::statusLabels(),
		);
	}

	protected static function leadIsOffline($leadId) {
		require_once 'modules/Leads/models/ModernService.php';
		$lead = Leads_ModernService::getLead((string) $leadId);
		if (!$lead || !is_array($lead)) {
			return false;
		}
		return self::isOfflineLead($lead, isset($lead['tags']) ? $lead['tags'] : array());
	}

	protected static function setClassDate($leadId, $classDate) {
		$ts = strtotime($classDate);
		if (!$ts) {
			return;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_class_date = ?, modified_at = ? WHERE leadid = ?',
			array(date('Y-m-d', $ts), date('Y-m-d H:i:s'), (int) $leadId)
		);
	}

	protected static function syncStatusTags($leadId, $statusTag, $userId = null) {
		global $current_user;
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Leads/models/ModernService.php';
		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		$tags = isset($lead['tags']) && is_array($lead['tags']) ? $lead['tags'] : array();
		$kept = array();
		foreach ($tags as $t) {
			$key = strtolower(trim((string) $t));
			if (in_array($key, self::STATUS_TAGS, true)) {
				continue;
			}
			$kept[] = $t;
		}
		$kept = self::ensureProgramTag($kept);
		$kept[] = $statusTag;
		if (!empty($lead['potential_level']) && $lead['potential_level'] === 'sieu_tiem_nang') {
			$kept[] = 'sieu_tiem_nang';
		} elseif (!empty($lead['potential_level']) && $lead['potential_level'] === 'tiem_nang') {
			$kept[] = 'tiem_nang';
		}
		$merged = array_values(array_unique($kept));
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
}
