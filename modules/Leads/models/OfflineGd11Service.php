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
	const STATUS_NGUNG_CSKH_TAM = 'offline_ngung_cskh_tam';

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
		self::STATUS_NGUNG_CSKH_TAM,
	);

	const R1_TAGS = array(
		self::STATUS_HEN_GOI_LAI,
		self::STATUS_KHONG_NGHE_MAY,
		self::STATUS_SAI_THONG_TIN,
	);

	const COUNTER_MAX = 3;

	/** Rank bước Offline: R1 liên hệ → R2 lịch → R3 lớp → R4 chuyển CT. */
	const STEP_R1 = 1;
	const STEP_R2 = 2;
	const STEP_R3 = 3;
	const STEP_R4 = 4;

	/**
	 * Map action/status → bước. 0 = thoát (Ngưng CSKH) — luôn cho phép.
	 * @param string $actionOrStatus
	 * @return int
	 */
	public static function stepRankOf($actionOrStatus) {
		$key = strtolower(trim((string) $actionOrStatus));
		$map = array(
			'hen_goi_lai' => self::STEP_R1,
			self::STATUS_HEN_GOI_LAI => self::STEP_R1,
			'khong_nghe_may' => self::STEP_R1,
			self::STATUS_KHONG_NGHE_MAY => self::STEP_R1,
			'sai_thong_tin' => self::STEP_R1,
			self::STATUS_SAI_THONG_TIN => self::STEP_R1,
			'chua_xac_nhan_lich' => self::STEP_R2,
			self::STATUS_CHUA_XN_LICH => self::STEP_R2,
			'da_xac_nhan_lich' => self::STEP_R2,
			self::STATUS_DA_XN_LICH => self::STEP_R2,
			'hen_lich_lai' => self::STEP_R2,
			self::STATUS_HEN_LICH_LAI => self::STEP_R2,
			'khong_tham_gia' => self::STEP_R3,
			self::STATUS_KHONG_THAM_GIA => self::STEP_R3,
			'da_tham_gia' => self::STEP_R3,
			self::STATUS_DA_THAM_GIA => self::STEP_R3,
			self::STATUS_NGUNG_CSKH_TAM => self::STEP_R3,
			'chuyen_chuong_trinh' => self::STEP_R4,
			self::STATUS_CHUYEN_CT => self::STEP_R4,
			'ngung_cskh' => 0,
			self::STATUS_NGUNG_CSKH => 0,
		);
		return isset($map[$key]) ? (int) $map[$key] : 0;
	}

	/**
	 * Bước cao nhất đã tới (theo status + bộ đếm R1–R4).
	 * @param array $row bace_lead_profile fields
	 * @return int
	 */
	public static function highestReachedStepFromRow(array $row) {
		$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
		$rank = self::stepRankOf($status);
		$r1 = 0;
		$r1 += isset($row['offline_r1_hen_goi']) ? (int) $row['offline_r1_hen_goi'] : 0;
		$r1 += isset($row['offline_r1_khong_nghe']) ? (int) $row['offline_r1_khong_nghe'] : 0;
		$r1 += isset($row['offline_r1_sai_tt']) ? (int) $row['offline_r1_sai_tt'] : 0;
		if ($r1 <= 0 && !empty($row['offline_r1_contact'])) {
			$r1 = (int) $row['offline_r1_contact'];
		}
		if ($r1 > 0) {
			$rank = max($rank, self::STEP_R1);
		}
		if (!empty($row['offline_r2_schedule']) && (int) $row['offline_r2_schedule'] > 0) {
			$rank = max($rank, self::STEP_R2);
		}
		if (!empty($row['offline_r3_class']) && (int) $row['offline_r3_class'] > 0) {
			$rank = max($rank, self::STEP_R3);
		}
		if (!empty($row['offline_r4_transfer']) && (int) $row['offline_r4_transfer'] > 0) {
			$rank = max($rank, self::STEP_R4);
		}
		return (int) $rank;
	}

	/**
	 * Cho phép xếp lịch lại sau no-show / điểm danh (R3 → R2).
	 */
	public static function isRescheduleException($prevStatus, $action) {
		$prev = strtolower(trim((string) $prevStatus));
		$act = strtolower(trim((string) $action));
		$from = array(
			self::STATUS_KHONG_THAM_GIA,
			self::STATUS_DA_THAM_GIA,
			self::STATUS_NGUNG_CSKH_TAM,
		);
		$to = array('hen_lich_lai', 'da_xac_nhan_lich', self::STATUS_HEN_LICH_LAI, self::STATUS_DA_XN_LICH);
		return in_array($prev, $from, true) && in_array($act, $to, true);
	}

	/**
	 * @return array{allowed:bool,error?:string,highest:int,target:int}
	 */
	public static function canApplyStepAction($prevStatus, $action, array $row = array()) {
		$action = strtolower(trim((string) $action));
		$target = self::stepRankOf($action);
		if ($target === 0 && ($action === 'ngung_cskh' || $action === self::STATUS_NGUNG_CSKH)) {
			return array('allowed' => true, 'highest' => self::highestReachedStepFromRow($row), 'target' => 0);
		}
		$rowWithStatus = $row;
		if (!isset($rowWithStatus['offline_status']) || $rowWithStatus['offline_status'] === '') {
			$rowWithStatus['offline_status'] = $prevStatus;
		}
		$highest = self::highestReachedStepFromRow($rowWithStatus);
		if ($target > 0 && $target < $highest && !self::isRescheduleException($prevStatus, $action)) {
			$names = array(
				1 => 'R1 (Hẹn gọi / Không nghe / Sai TT)',
				2 => 'R2 (Lịch học)',
				3 => 'R3 (Lớp / điểm danh)',
				4 => 'R4 (Chuyển CT)',
			);
			$hi = isset($names[$highest]) ? $names[$highest] : ('bước ' . $highest);
			$tg = isset($names[$target]) ? $names[$target] : ('bước ' . $target);
			return array(
				'allowed' => false,
				'error' => 'Đã ở ' . $hi . ' — không được bấm điểm hẹn ' . $tg . ' (bước trước).',
				'highest' => $highest,
				'target' => $target,
			);
		}
		return array('allowed' => true, 'highest' => $highest, 'target' => $target);
	}

	public static function statusLabels() {
		return array(
			self::STATUS_HEN_GOI_LAI => 'Hẹn gọi lại',
			self::STATUS_KHONG_NGHE_MAY => 'Không nghe máy',
			self::STATUS_SAI_THONG_TIN => 'Sai thông tin',
			self::STATUS_CHUYEN_CT => 'Chuyển CT',
			self::STATUS_CHUA_XN_LICH => 'Chưa xác nhận lịch',
			self::STATUS_DA_XN_LICH => 'Đã xác nhận lịch',
			self::STATUS_HEN_LICH_LAI => 'Hẹn lịch lại',
			self::STATUS_KHONG_THAM_GIA => 'Không tham gia',
			self::STATUS_DA_THAM_GIA => 'Đã tham gia',
			self::STATUS_NGUNG_CSKH => 'Ngưng CSKH',
			self::STATUS_NGUNG_CSKH_TAM => 'Dừng CSKH tạm thời',
		);
	}

	public static function installSchema(PearDatabase $adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
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
			// R1 trần kép: 3 lượt / tag (Hẹn gọi · Không nghe · Sai TT), tối đa 9.
			'offline_r1_hen_goi' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r1_khong_nghe' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r1_sai_tt' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r2_schedule' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r3_class' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_r4_transfer' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_preclass_confirm' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_class_date' => "DATE DEFAULT NULL",
			'offline_checked_in_at' => "DATETIME NULL",
			'offline_post_noshow_miss' => "TINYINT(1) NOT NULL DEFAULT 0",
			'offline_oa_scanned_at' => "DATETIME NULL",
			'offline_oa_scan_note' => "VARCHAR(255) DEFAULT NULL",
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
		$done = true;
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
		$prev = '';
		$pr = $adb->pquery('SELECT offline_status FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if ($pr && $adb->num_rows($pr) > 0) {
			$prev = strtolower(trim((string) $adb->query_result($pr, 0, 'offline_status')));
		}
		$adb->pquery(
			"UPDATE bace_lead_profile SET offline_status = ?, modified_at = ? WHERE leadid = ?",
			array($statusTag, $now, $leadId)
		);
		self::syncStatusTags($leadId, $statusTag, $userId);
		try {
			require_once 'modules/Leads/models/ModernService.php';
			if ($statusTag === self::STATUS_NGUNG_CSKH) {
				Leads_ModernService::stampNgungCskhAt($leadId, $now);
			} elseif ($prev === self::STATUS_NGUNG_CSKH && $statusTag !== self::STATUS_NGUNG_CSKH) {
				Leads_ModernService::clearNgungCskhAt($leadId);
			}
		} catch (Exception $e) {
			// best-effort
		}
		return true;
	}

	/**
	 * @param string $counter r2|r3|r4 (R1 dùng bumpR1ForTag)
	 * @return array{count:int,stopped:bool}
	 */
	public static function bumpCounter($leadId, $counter) {
		$leadId = (int) $leadId;
		$map = array(
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
	 * R1 trần kép: mỗi tag 3 lượt; đổi tag đếm theo cột riêng; hết cả 3 tag → Ngưng.
	 * @return array{count:int,stopped:bool,tag_exhausted:bool,sum:int,per_tag:array}
	 */
	public static function bumpR1ForTag($leadId, $statusTag) {
		$leadId = (int) $leadId;
		$statusTag = strtolower(trim((string) $statusTag));
		$colMap = array(
			self::STATUS_HEN_GOI_LAI => 'offline_r1_hen_goi',
			self::STATUS_KHONG_NGHE_MAY => 'offline_r1_khong_nghe',
			self::STATUS_SAI_THONG_TIN => 'offline_r1_sai_tt',
		);
		if ($leadId <= 0 || !isset($colMap[$statusTag])) {
			return array('count' => 0, 'stopped' => false, 'tag_exhausted' => false, 'sum' => 0, 'per_tag' => array());
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_r1_hen_goi AS h, offline_r1_khong_nghe AS k, offline_r1_sai_tt AS s
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$h = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'h') : 0;
		$k = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'k') : 0;
		$s = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 's') : 0;
		$per = array(
			self::STATUS_HEN_GOI_LAI => $h,
			self::STATUS_KHONG_NGHE_MAY => $k,
			self::STATUS_SAI_THONG_TIN => $s,
		);
		$col = $colMap[$statusTag];
		$cur = $per[$statusTag];
		if ($cur >= self::COUNTER_MAX) {
			$allDone = ($h >= self::COUNTER_MAX && $k >= self::COUNTER_MAX && $s >= self::COUNTER_MAX);
			return array(
				'count' => $cur,
				'stopped' => $allDone,
				'tag_exhausted' => true,
				'sum' => $h + $k + $s,
				'per_tag' => $per,
			);
		}
		$next = $cur + 1;
		$per[$statusTag] = $next;
		$sum = $per[self::STATUS_HEN_GOI_LAI] + $per[self::STATUS_KHONG_NGHE_MAY] + $per[self::STATUS_SAI_THONG_TIN];
		$adb->pquery(
			"UPDATE bace_lead_profile SET {$col} = ?, offline_r1_contact = ?, modified_at = ? WHERE leadid = ?",
			array($next, $sum, date('Y-m-d H:i:s'), $leadId)
		);
		$stopped = (
			$per[self::STATUS_HEN_GOI_LAI] >= self::COUNTER_MAX
			&& $per[self::STATUS_KHONG_NGHE_MAY] >= self::COUNTER_MAX
			&& $per[self::STATUS_SAI_THONG_TIN] >= self::COUNTER_MAX
		);
		return array(
			'count' => $next,
			'stopped' => $stopped,
			'tag_exhausted' => false,
			'sum' => $sum,
			'per_tag' => $per,
		);
	}

	/**
	 * Last Touch — Không nghe máy trên lead Offline → R1.
	 * Calendar nhắc gọi đã do LastTouch tạo — không nhân đôi ở đây.
	 */
	public static function onLastTouchMissed($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::leadIsOffline($leadId)) {
			return null;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT offline_status FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		$cur = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'offline_status')) : '';

		// Đã XN lịch / Hẹn lại / Không tham gia: gọi không bắt máy 3 lần → Dừng CSKH tạm.
		$postNoshowStates = array(
			self::STATUS_KHONG_THAM_GIA,
			self::STATUS_HEN_LICH_LAI,
			self::STATUS_DA_XN_LICH,
		);
		if (in_array($cur, $postNoshowStates, true)) {
			return self::onPostNoshowCallMissed($leadId, $userId);
		}

		// Điểm danh / ngưng: không đụng R1 từ Opp Last Touch.
		$skipR1 = array(
			self::STATUS_DA_THAM_GIA,
			self::STATUS_NGUNG_CSKH,
			self::STATUS_NGUNG_CSKH_TAM,
			self::STATUS_CHUYEN_CT,
		);
		if (in_array($cur, $skipR1, true)) {
			return array(
				'status' => $cur,
				'skipped_r1' => true,
				'drop' => '',
			);
		}

		$bump = self::bumpR1ForTag($leadId, self::STATUS_KHONG_NGHE_MAY);
		if (!empty($bump['stopped'])) {
			self::applyStatus($leadId, self::STATUS_NGUNG_CSKH, $userId);
			self::setNextActionHint($leadId, self::STATUS_NGUNG_CSKH);
			return array(
				'status' => self::STATUS_NGUNG_CSKH,
				'r1' => $bump['count'],
				'r1_sum' => $bump['sum'],
				'drop' => 'R1',
			);
		}
		if (!empty($bump['tag_exhausted'])) {
			self::setNextActionHint($leadId, self::STATUS_KHONG_NGHE_MAY);
			return array(
				'status' => self::STATUS_KHONG_NGHE_MAY,
				'r1' => $bump['count'],
				'r1_sum' => $bump['sum'],
				'drop' => '',
				'tag_exhausted' => true,
			);
		}
		self::applyStatus($leadId, self::STATUS_KHONG_NGHE_MAY, $userId);
		self::setNextActionHint($leadId, self::STATUS_KHONG_NGHE_MAY);
		return array(
			'status' => self::STATUS_KHONG_NGHE_MAY,
			'r1' => $bump['count'],
			'r1_sum' => $bump['sum'],
			'drop' => '',
		);
	}

	/**
	 * Trạng thái được đếm “Không gọi được” (3 lần → Dừng CSKH tạm).
	 */
	public static function unreachableCallStatuses() {
		return array(
			self::STATUS_KHONG_THAM_GIA,
			self::STATUS_HEN_LICH_LAI,
			self::STATUS_DA_XN_LICH,
		);
	}

	/**
	 * Sau XN lịch / no-show: mỗi lần Không gọi được +1; đủ 3 → Dừng CSKH tạm thời và reset R3.
	 */
	public static function onPostNoshowCallMissed($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_post_noshow_miss AS m, offline_status AS st FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$curMiss = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'm') : 0;
		$curStatus = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'st')) : '';
		if ($curStatus === '' || !in_array($curStatus, self::unreachableCallStatuses(), true)) {
			return array(
				'success' => false,
				'error' => 'Chỉ đếm “Không gọi được” khi Đã XN lịch / Hẹn lịch lại / Không tham gia.',
				'status' => $curStatus,
				'post_noshow_miss' => $curMiss,
			);
		}
		$next = min(self::COUNTER_MAX, $curMiss + 1);
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_post_noshow_miss = ?, modified_at = ? WHERE leadid = ?',
			array($next, date('Y-m-d H:i:s'), $leadId)
		);
		if ($next >= self::COUNTER_MAX) {
			self::resetAttendanceCycleCounters($leadId);
			self::applyStatus($leadId, self::STATUS_NGUNG_CSKH_TAM, $userId);
			self::setNextActionHint($leadId, self::STATUS_NGUNG_CSKH_TAM);
			self::syncOfflineStatusToPotential($leadId, self::STATUS_NGUNG_CSKH_TAM, $userId);
			return array(
				'success' => true,
				'status' => self::STATUS_NGUNG_CSKH_TAM,
				'status_label' => 'Dừng CSKH tạm thời',
				'post_noshow_miss' => $next,
				'drop' => 'POST_NOSHOW_MISS',
				'cycle_reset' => true,
				'can_unreachable' => false,
				'message' => 'Không gọi được 3 lần → Dừng CSKH tạm thời. Được đặt lịch lại 3 lần.',
			);
		}
		self::setNextActionHint($leadId, $curStatus !== '' ? $curStatus : self::STATUS_KHONG_THAM_GIA);
		$labels = self::statusLabels();
		return array(
			'success' => true,
			'status' => $curStatus,
			'status_label' => isset($labels[$curStatus]) ? $labels[$curStatus] : $curStatus,
			'post_noshow_miss' => $next,
			'drop' => '',
			'can_unreachable' => true,
			'message' => 'Không gọi được · ' . $next . '/3',
		);
	}

	/**
	 * Opp drawer — nút “Không gọi được”: đếm miss (+ ghi Last Touch nếu còn slot).
	 */
	public static function markUnreachableFromPotential($potentialId, $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}
		if (!self::leadIsOffline($leadId)) {
			return array('success' => false, 'error' => 'Lead không phải Offline');
		}

		$offline = self::onPostNoshowCallMissed($leadId, $userId);
		if (empty($offline['success'])) {
			return $offline;
		}

		$lastTouch = null;
		try {
			require_once 'modules/Potentials/models/LastTouchCallService.php';
			$summary = Potentials_LastTouchCallService::getSummary($potentialId);
			if (!empty($summary['can_add'])) {
				// Ghi log hiển thị; skip hook offline (đã đếm ở trên).
				$lastTouch = Potentials_LastTouchCallService::logCall(
					$potentialId,
					'Không gọi được',
					'',
					$userId,
					array('skip_offline' => true)
				);
			} else {
				$lastTouch = $summary;
			}
		} catch (Exception $e) {
			$lastTouch = null;
		}

		$listRow = null;
		try {
			require_once 'modules/Potentials/models/ModernService.php';
			$list = Potentials_ModernService::listPotentials($userId);
			foreach ($list as $row) {
				if ((int) $row['crmid'] === $potentialId || (string) $row['id'] === (string) $potentialId) {
					$listRow = $row;
					break;
				}
			}
		} catch (Exception $e) {
			$listRow = null;
		}

		return array(
			'success' => true,
			'status' => isset($offline['status']) ? $offline['status'] : '',
			'status_label' => isset($offline['status_label']) ? $offline['status_label'] : '',
			'post_noshow_miss' => isset($offline['post_noshow_miss']) ? (int) $offline['post_noshow_miss'] : 0,
			'drop' => isset($offline['drop']) ? $offline['drop'] : '',
			'cycle_reset' => !empty($offline['cycle_reset']),
			'can_unreachable' => !empty($offline['can_unreachable']),
			'message' => isset($offline['message']) ? $offline['message'] : 'Đã ghi Không gọi được',
			'lastTouchCalls' => $lastTouch,
			'opportunity' => $listRow,
		);
	}

	/** Reset R3 + đếm gọi miss sau no-show (chu kỳ mới). */
	public static function resetAttendanceCycleCounters($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_r3_class = 0, offline_post_noshow_miss = 0, modified_at = ? WHERE leadid = ?',
			array(date('Y-m-d H:i:s'), $leadId)
		);
	}

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
	 * @return array|null includes convert meta when đủ ĐK
	 */
	public static function onSalesVerified($leadId, array $result, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0 || !self::leadIsOffline($leadId)) {
			return null;
		}
		$elig = isset($result['eligibility_result']) ? $result['eligibility_result'] : '';
		$out = array();
		if ($elig === 'khong_du_dk') {
			$bump = self::bumpCounter($leadId, 'r4');
			if (!empty($bump['stopped'])) {
				self::applyStatus($leadId, self::STATUS_NGUNG_CSKH, $userId);
				self::setNextActionHint($leadId, self::STATUS_NGUNG_CSKH);
				$out['status'] = self::STATUS_NGUNG_CSKH;
				$out['drop'] = 'R4';
			} else {
				self::applyStatus($leadId, self::STATUS_CHUYEN_CT, $userId);
				self::setNextActionHint($leadId, self::STATUS_CHUYEN_CT);
				$out['status'] = self::STATUS_CHUYEN_CT;
				$out['calendar'] = self::createFollowUpTask($leadId, self::STATUS_CHUYEN_CT, array(), $userId);
			}
			return $out;
		}
		if ($elig === 'du_dk') {
			$schedule = isset($result['schedule_outcome']) ? $result['schedule_outcome'] : '';
			if ($schedule === 'da_xac_nhan_lich' || $schedule === self::STATUS_DA_XN_LICH) {
				self::applyStatus($leadId, self::STATUS_DA_XN_LICH, $userId);
				$classDate = isset($result['class_date']) ? trim((string) $result['class_date']) : '';
				if ($classDate !== '') {
					self::setClassDate($leadId, $classDate);
				}
				self::setNextActionHint($leadId, self::STATUS_DA_XN_LICH, $classDate);
				$out['status'] = self::STATUS_DA_XN_LICH;
				$out['calendar'] = self::createFollowUpTask(
					$leadId,
					self::STATUS_DA_XN_LICH,
					array('class_date' => $classDate),
					$userId
				);
				try {
					require_once 'modules/Leads/models/OfflineGd11Step2Service.php';
					$out['step2'] = Leads_OfflineGd11Step2Service::onScheduleConfirmed(
						$leadId,
						array('class_date' => $classDate),
						$userId
					);
				} catch (Exception $e) {
					$out['step2'] = array('success' => false, 'error' => $e->getMessage());
				}
			} else {
				$bump = self::bumpCounter($leadId, 'r2');
				if (!empty($bump['stopped'])) {
					self::applyStatus($leadId, self::STATUS_NGUNG_CSKH, $userId);
					self::setNextActionHint($leadId, self::STATUS_NGUNG_CSKH);
					$out['status'] = self::STATUS_NGUNG_CSKH;
					$out['drop'] = 'R2';
				} else {
					self::applyStatus($leadId, self::STATUS_CHUA_XN_LICH, $userId);
					self::setNextActionHint($leadId, self::STATUS_CHUA_XN_LICH);
					$out['status'] = self::STATUS_CHUA_XN_LICH;
					$out['calendar'] = self::createFollowUpTask($leadId, self::STATUS_CHUA_XN_LICH, array(), $userId);
				}
			}
			// Không convert tại Lưu xác minh — đợi Bước 2 (giờ/địa điểm) rồi mới xuống Opp.
			$out['convert'] = array(
				'converted' => false,
				'skipped' => true,
				'reason' => 'await_step2',
			);
			return $out;
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
		$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
		$calendarMeta = null;

		$adbPrev = PearDatabase::getInstance();
		$prevRes = $adbPrev->pquery(
			'SELECT offline_status, offline_r1_contact, offline_r1_hen_goi, offline_r1_khong_nghe, offline_r1_sai_tt,
			        offline_r2_schedule, offline_r3_class, offline_r4_transfer
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$prevRow = array();
		if ($prevRes && $adbPrev->num_rows($prevRes) > 0) {
			$prevRow = $adbPrev->query_result_rowdata($prevRes, 0);
		}
		$prevStatus = isset($prevRow['offline_status']) ? trim((string) $prevRow['offline_status']) : '';

		$stepGate = self::canApplyStepAction($prevStatus, $action, $prevRow);
		if (empty($stepGate['allowed'])) {
			return array(
				'success' => false,
				'error' => isset($stepGate['error']) ? $stepGate['error'] : 'Không được quay lại bước trước.',
				'offline_step_rank' => isset($stepGate['highest']) ? $stepGate['highest'] : 0,
				'offline_target_step' => isset($stepGate['target']) ? $stepGate['target'] : 0,
			);
		}

		if (in_array($status, self::R1_TAGS, true)) {
			$bump = self::bumpR1ForTag($leadId, $status);
			if (!empty($bump['tag_exhausted']) && empty($bump['stopped'])) {
				$labels = self::statusLabels();
				$lab = isset($labels[$status]) ? $labels[$status] : $status;
				return array(
					'success' => false,
					'error' => $lab . ' đã hết 3 lượt R1. Chọn tag R1 khác hoặc Ngưng CSKH.',
					'r1' => $bump['count'],
					'r1_sum' => $bump['sum'],
					'per_tag' => $bump['per_tag'],
				);
			}
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
			// R3 chỉ đếm Không tham gia (no-show), không + khi chốt/xác nhận lịch.
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
		if ($status === self::STATUS_DA_XN_LICH && $classDate !== '') {
			self::setClassDate($leadId, $classDate);
		}
		if ($status === self::STATUS_DA_THAM_GIA) {
			self::setCheckedInAt($leadId, date('Y-m-d H:i:s'));
		} elseif ($status === self::STATUS_KHONG_THAM_GIA) {
			self::setCheckedInAt($leadId, null);
		}

		// Không đến / đã điểm danh xong → xếp lịch lại: xóa Step 4 + mở lại điểm danh.
		if (
			($status === self::STATUS_HEN_LICH_LAI || $status === self::STATUS_DA_XN_LICH)
			&& in_array($prevStatus, array(self::STATUS_KHONG_THAM_GIA, self::STATUS_DA_THAM_GIA, self::STATUS_NGUNG_CSKH_TAM), true)
		) {
			$adbClear = PearDatabase::getInstance();
			$adbClear->pquery(
				'UPDATE bace_lead_profile SET offline_post_noshow_miss = 0, modified_at = ? WHERE leadid = ?',
				array(date('Y-m-d H:i:s'), $leadId)
			);
			try {
				require_once 'modules/Leads/models/OfflineGd11Step4Service.php';
				Leads_OfflineGd11Step4Service::clearForReschedule($leadId);
			} catch (Exception $e) {
				// ignore
			}
			self::syncOfflineStatusToPotential($leadId, $status, $userId);
		}

		self::setNextActionHint($leadId, $status, $classDate);

		$taskStatuses = array(
			self::STATUS_HEN_GOI_LAI,
			self::STATUS_KHONG_NGHE_MAY,
			self::STATUS_SAI_THONG_TIN,
			self::STATUS_CHUA_XN_LICH,
			self::STATUS_DA_XN_LICH,
			self::STATUS_HEN_LICH_LAI,
			self::STATUS_CHUYEN_CT,
		);
		if (in_array($status, $taskStatuses, true)) {
			$calendarMeta = self::createFollowUpTask($leadId, $status, $payload, $userId);
		}

		$step2Meta = null;
		if ($status === self::STATUS_DA_XN_LICH) {
			try {
				require_once 'modules/Leads/models/OfflineGd11Step2Service.php';
				$step2Meta = Leads_OfflineGd11Step2Service::onScheduleConfirmed($leadId, $payload, $userId);
			} catch (Exception $e) {
				$step2Meta = array('success' => false, 'error' => $e->getMessage());
			}
		}

		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		$labels = self::statusLabels();
		$out = array(
			'success' => true,
			'status' => $status,
			'status_label' => isset($labels[$status]) ? $labels[$status] : $status,
			'drop' => $drop,
			'calendar' => $calendarMeta,
			'step2' => $step2Meta,
			'lead' => $lead,
		);
		if ($status === self::STATUS_DA_XN_LICH || $status === self::STATUS_HEN_LICH_LAI) {
			// Chỉ convert khi đã đủ Bước 2 (giờ + địa điểm); không tạo Contact.
			$out['convert'] = self::tryConvertEligible($leadId, $userId);
		}
		return $out;
	}

	/**
	 * @param array $row
	 * @param bool $detailed true = panel/getLead (KB + plan Bước 2); false = list (nhẹ)
	 */
	public static function profileBlock(array $row, $detailed = false) {
		$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
		$labels = self::statusLabels();
		$r1h = isset($row['offline_r1_hen_goi']) ? (int) $row['offline_r1_hen_goi'] : 0;
		$r1k = isset($row['offline_r1_khong_nghe']) ? (int) $row['offline_r1_khong_nghe'] : 0;
		$r1s = isset($row['offline_r1_sai_tt']) ? (int) $row['offline_r1_sai_tt'] : 0;
		$r1Sum = $r1h + $r1k + $r1s;
		if ($r1Sum <= 0 && !empty($row['offline_r1_contact'])) {
			$r1Sum = (int) $row['offline_r1_contact'];
		}
		$stepRank = self::highestReachedStepFromRow($row);
		$out = array(
			'offline_status' => $status,
			'offline_status_label' => isset($labels[$status]) ? $labels[$status] : '',
			'offline_step_rank' => $stepRank,
			'offline_r1_contact' => $r1Sum,
			'offline_r1_hen_goi' => $r1h,
			'offline_r1_khong_nghe' => $r1k,
			'offline_r1_sai_tt' => $r1s,
			'offline_r2_schedule' => isset($row['offline_r2_schedule']) ? (int) $row['offline_r2_schedule'] : 0,
			'offline_r3_class' => isset($row['offline_r3_class']) ? (int) $row['offline_r3_class'] : 0,
			'offline_r4_transfer' => isset($row['offline_r4_transfer']) ? (int) $row['offline_r4_transfer'] : 0,
			'offline_post_noshow_miss' => isset($row['offline_post_noshow_miss']) ? (int) $row['offline_post_noshow_miss'] : 0,
			'offline_preclass_confirm' => !empty($row['offline_preclass_confirm']) ? 1 : 0,
			'offline_class_date' => (!empty($row['offline_class_date']) && $row['offline_class_date'] !== '0000-00-00')
				? (string) $row['offline_class_date'] : '',
			'offline_checked_in_at' => (!empty($row['offline_checked_in_at']) && $row['offline_checked_in_at'] !== '0000-00-00 00:00:00')
				? date('c', strtotime((string) $row['offline_checked_in_at'])) : '',
			'zalo_user_id' => isset($row['zalo_user_id']) ? trim((string) $row['zalo_user_id']) : '',
			'offline_oa_scanned_at' => (!empty($row['offline_oa_scanned_at']) && $row['offline_oa_scanned_at'] !== '0000-00-00 00:00:00')
				? date('c', strtotime((string) $row['offline_oa_scanned_at'])) : '',
			'offline_oa_scan_note' => isset($row['offline_oa_scan_note'])
				? trim((string) $row['offline_oa_scan_note']) : '',
		);

		// Đường 2 — nút chuyển Offline → Online
		$elig = isset($row['eligibility_result']) ? trim((string) $row['eligibility_result']) : '';
		$pot = isset($row['potential_level']) ? trim((string) $row['potential_level']) : '';
		$onlineTransferId = 0;
		try {
			require_once 'modules/Leads/models/OnlineGd12Service.php';
			$srcId = isset($row['leadid']) ? (int) $row['leadid'] : 0;
			if ($srcId > 0) {
				$onlineTransferId = Leads_OnlineGd12Service::findChildLeadIdBySource($srcId);
			}
		} catch (Exception $e) {
			$onlineTransferId = 0;
		}
		$out['online_transfer_leadid'] = $onlineTransferId;
		$hasOnlineSelf = isset($row['online_status']) && trim((string) $row['online_status']) !== '';
		$out['can_transfer_online'] = ($elig === 'du_dk' && $pot !== '' && $onlineTransferId <= 0 && !$hasOnlineSelf) ? 1 : 0;

		if ($detailed) {
			$out['offline_status_options'] = $labels;
		}
		return $out + self::composeStep2Block($row, $detailed);
	}

	/**
	 * Opp list join chỉ theo potential_id. Nếu convert chỉ gắn vtiger_crmentityrel
	 * thì cột Tham gia trống — lấy lại hồ sơ Lead và ghi potential_id.
	 * @param array $rows
	 * @return array
	 */
	public static function fillMissingOfflineOnPotentialRows(array $rows) {
		if (!$rows) {
			return $rows;
		}
		$missing = array();
		foreach ($rows as $row) {
			$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
			$date = isset($row['offline_class_date']) ? trim((string) $row['offline_class_date']) : '';
			$pid = isset($row['potentialid']) ? (int) $row['potentialid'] : 0;
			if ($pid > 0 && $status === '' && ($date === '' || $date === '0000-00-00')) {
				$missing[$pid] = true;
			}
		}
		if (!$missing) {
			return $rows;
		}
		$ids = array_keys($missing);
		$adb = PearDatabase::getInstance();
		$map = array();
		$queries = array(
			"SELECT rel.relcrmid AS potentialid, lp.leadid, lp.offline_status, lp.offline_r1_contact,
				lp.offline_r1_hen_goi, lp.offline_r1_khong_nghe, lp.offline_r1_sai_tt,
				lp.offline_r2_schedule, lp.offline_r3_class, lp.offline_r4_transfer,
				lp.offline_post_noshow_miss, lp.offline_preclass_confirm, lp.offline_class_date,
				lp.offline_class_time, lp.offline_class_place, lp.offline_checked_in_at,
				lp.zalo_user_id, lp.offline_oa_scanned_at, lp.offline_oa_scan_note
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile lp ON lp.leadid = rel.crmid
			 WHERE rel.module = 'Leads' AND rel.relmodule = 'Potentials'
			   AND rel.relcrmid IN (" . generateQuestionMarks($ids) . ")",
			"SELECT rel.crmid AS potentialid, lp.leadid, lp.offline_status, lp.offline_r1_contact,
				lp.offline_r1_hen_goi, lp.offline_r1_khong_nghe, lp.offline_r1_sai_tt,
				lp.offline_r2_schedule, lp.offline_r3_class, lp.offline_r4_transfer,
				lp.offline_post_noshow_miss, lp.offline_preclass_confirm, lp.offline_class_date,
				lp.offline_class_time, lp.offline_class_place, lp.offline_checked_in_at,
				lp.zalo_user_id, lp.offline_oa_scanned_at, lp.offline_oa_scan_note
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile lp ON lp.leadid = rel.relcrmid
			 WHERE rel.module = 'Potentials' AND rel.relmodule = 'Leads'
			   AND rel.crmid IN (" . generateQuestionMarks($ids) . ")",
		);
		foreach ($queries as $sql) {
			$res = $adb->pquery($sql, $ids);
			if (!$res) {
				continue;
			}
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$pid = (int) $adb->query_result($res, $i, 'potentialid');
				if ($pid <= 0 || isset($map[$pid])) {
					continue;
				}
				$map[$pid] = array(
					'linked_leadid' => (int) $adb->query_result($res, $i, 'leadid'),
					'offline_status' => $adb->query_result($res, $i, 'offline_status'),
					'offline_r1_contact' => $adb->query_result($res, $i, 'offline_r1_contact'),
					'offline_r1_hen_goi' => $adb->query_result($res, $i, 'offline_r1_hen_goi'),
					'offline_r1_khong_nghe' => $adb->query_result($res, $i, 'offline_r1_khong_nghe'),
					'offline_r1_sai_tt' => $adb->query_result($res, $i, 'offline_r1_sai_tt'),
					'offline_r2_schedule' => $adb->query_result($res, $i, 'offline_r2_schedule'),
					'offline_r3_class' => $adb->query_result($res, $i, 'offline_r3_class'),
					'offline_r4_transfer' => $adb->query_result($res, $i, 'offline_r4_transfer'),
					'offline_post_noshow_miss' => $adb->query_result($res, $i, 'offline_post_noshow_miss'),
					'offline_preclass_confirm' => $adb->query_result($res, $i, 'offline_preclass_confirm'),
					'offline_class_date' => $adb->query_result($res, $i, 'offline_class_date'),
					'offline_class_time' => $adb->query_result($res, $i, 'offline_class_time'),
					'offline_class_place' => $adb->query_result($res, $i, 'offline_class_place'),
					'offline_checked_in_at' => $adb->query_result($res, $i, 'offline_checked_in_at'),
					'zalo_user_id' => $adb->query_result($res, $i, 'zalo_user_id'),
					'offline_oa_scanned_at' => $adb->query_result($res, $i, 'offline_oa_scanned_at'),
					'offline_oa_scan_note' => $adb->query_result($res, $i, 'offline_oa_scan_note'),
				);
				$leadId = (int) $map[$pid]['linked_leadid'];
				if ($leadId > 0) {
					$adb->pquery(
						'UPDATE bace_lead_profile SET potential_id = ? WHERE leadid = ? AND (potential_id IS NULL OR potential_id = 0 OR potential_id = ?)',
						array($pid, $leadId, $pid)
					);
				}
			}
		}
		if (!$map) {
			return $rows;
		}
		foreach ($rows as $i => $row) {
			$pid = isset($row['potentialid']) ? (int) $row['potentialid'] : 0;
			if ($pid <= 0 || empty($map[$pid])) {
				continue;
			}
			foreach ($map[$pid] as $key => $value) {
				if (!isset($rows[$i][$key]) || $rows[$i][$key] === '' || $rows[$i][$key] === null) {
					$rows[$i][$key] = $value;
				}
			}
		}
		return $rows;
	}

	protected static function composeStep2Block(array $row, $detailed = false) {
		$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
		// List: chỉ trả field nhẹ nếu đã vào Offline; bỏ plan/config.
		$needsStep2 = ($status !== '' || !empty($row['offline_class_date']) || !empty($row['offline_step2_entered_at']));
		if (!$detailed && !$needsStep2) {
			return array();
		}
		try {
			require_once 'modules/Leads/models/OfflineGd11Step2Service.php';
			return Leads_OfflineGd11Step2Service::profileExtras($row, $detailed);
		} catch (Exception $e) {
			return array();
		}
	}

	public static function nextActionForStatus($status, $classDate = '') {
		$map = array(
			self::STATUS_HEN_GOI_LAI => 'Gọi lại theo lịch hẹn (R1)',
			self::STATUS_KHONG_NGHE_MAY => 'Gọi lại — không nghe máy (R1)',
			self::STATUS_SAI_THONG_TIN => 'Xác minh lại thông tin / nguồn (R1)',
			self::STATUS_CHUYEN_CT => 'Chuyển chương trình phù hợp (R4)',
			self::STATUS_CHUA_XN_LICH => 'Chốt & xác nhận lịch Offline (R2)',
			self::STATUS_DA_XN_LICH => $classDate !== ''
				? ('Nhắc lịch lớp Offline ' . $classDate)
				: 'Chuẩn bị lớp / nhắc lịch Offline (R2)',
			self::STATUS_HEN_LICH_LAI => 'Hẹn lịch lại với HV (R2)',
			self::STATUS_KHONG_THAM_GIA => 'Không tham gia lớp — điểm rơi R3 (max 3)',
			self::STATUS_DA_THAM_GIA => 'CSKH sau lớp Offline',
			self::STATUS_NGUNG_CSKH => 'Ngưng CSKH Offline (đã đủ điểm rơi R)',
			self::STATUS_NGUNG_CSKH_TAM => 'Dừng CSKH tạm thời — được đặt lịch lại (R3)',
		);
		return isset($map[$status]) ? $map[$status] : '';
	}

	public static function setNextActionHint($leadId, $status, $classDate = '') {
		$leadId = (int) $leadId;
		$text = self::nextActionForStatus($status, $classDate);
		if ($leadId <= 0 || $text === '') {
			return;
		}
		require_once 'modules/Leads/models/ModernService.php';
		try {
			Leads_ModernService::updateNextAction($leadId, $text);
		} catch (Exception $e) {
			// ignore
		}
	}

	/**
	 * Tạo Calendar Call (Planned) gắn Lead — assign owner Lead (Sales) hoặc user hiện tại.
	 * @return array
	 */
	public static function createFollowUpTask($leadId, $status, array $payload = array(), $userId = null) {
		global $current_user;
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'invalid_lead');
		}
		$assignId = self::resolveTaskAssignee($leadId, $userId);
		$when = self::resolveTaskDueAt($status, $payload);
		$labels = self::statusLabels();
		$statusLabel = isset($labels[$status]) ? $labels[$status] : $status;
		$subject = 'Offline 1.1 — ' . $statusLabel;
		$desc = self::nextActionForStatus(
			$status,
			isset($payload['class_date']) ? trim((string) $payload['class_date']) : ''
		);
		if ($desc === '') {
			$desc = $subject;
		}
		try {
			$record = Vtiger_Record_Model::getCleanInstance('Calendar');
			$record->set('mode', '');
			$record->set('subject', $subject);
			$record->set('activitytype', 'Call');
			$record->set('date_start', date('Y-m-d', strtotime($when)));
			$record->set('time_start', date('H:i:s', strtotime($when)));
			$endTs = strtotime($when) + 30 * 60;
			$record->set('due_date', date('Y-m-d', $endTs));
			$record->set('time_end', date('H:i:s', $endTs));
			$record->set('assigned_user_id', $assignId);
			$record->set('parent_id', $leadId);
			$record->set('visibility', 'Public');
			$record->set('description', $desc . "\n(GD 1.1 Bước 1)");
			$record->set('eventstatus', 'Planned');
			$record->set('taskstatus', 'Not Started');
			$record->set('taskpriority', 'High');
			$record->set('set_reminder', 'Yes');
			$record->set('remdays', '0');
			$record->set('remhrs', '0');
			$record->set('remmin', '15');
			$record->save();
			$activityId = (int) $record->getId();
			if ($activityId <= 0) {
				return array('success' => false, 'error' => 'save_failed');
			}
			require_once 'modules/Leads/models/CommerceService.php';
			Leads_CommerceService::linkActivityToLead($leadId, $activityId);
			$notifMeta = null;
			if (in_array($status, self::R1_TAGS, true)) {
				$notifMeta = self::scheduleR1Notification($leadId, $status, $when, $assignId);
			}
			return array(
				'success' => true,
				'activity_id' => $activityId,
				'due_at' => $when,
				'assigned_user_id' => $assignId,
				'r1_notification' => $notifMeta,
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		} catch (Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * Lên lịch chuông R1 (Modern Notifications) với marker action cho popup.
	 */
	public static function scheduleR1Notification($leadId, $status, $deliverAt, $userId = null) {
		$leadId = (int) $leadId;
		$userId = (int) $userId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'invalid_lead');
		}
		if ($userId <= 0) {
			$userId = self::resolveTaskAssignee($leadId, null);
		}
		$labels = self::statusLabels();
		$statusLabel = isset($labels[$status]) ? $labels[$status] : 'Hẹn gọi lại';
		$leadName = '';
		try {
			$record = Vtiger_Record_Model::getInstanceById($leadId, 'Leads');
			$leadName = trim((string) $record->get('lastname') . ' ' . $record->get('firstname'));
			if ($leadName === '') {
				$leadName = trim((string) $record->get('company'));
			}
		} catch (Exception $e) {
			$leadName = '';
		}
		$marker = '[[mk_r1:leadId=' . $leadId . ';status=' . $status . ']]';
		$message = "R1 · {$statusLabel}\n"
			. ($leadName !== '' ? $leadName . ' — ' : '')
			. "Lead #{$leadId}\n"
			. "Đến giờ gọi lại. Chọn Hẹn gọi lại hoặc Đã xác nhận.\n"
			. $marker;
		require_once 'modules/Vtiger/models/NotificationSchedule.php';
		$sourceKey = 'r1_lead_' . $leadId;
		$schedId = Vtiger_NotificationSchedule::schedule(
			$userId,
			'Leads',
			$leadId,
			$message,
			$deliverAt,
			$sourceKey
		);
		return array(
			'success' => true,
			'schedule_id' => $schedId,
			'deliver_at' => $deliverAt,
			'userid' => $userId,
		);
	}

	/**
	 * Action từ popup thông báo R1.
	 * hen_goi_lai → +1 R1 + nhắc tiếp
	 * da_xac_nhan → thoát R1 (không bump), sale sang form 3 câu / R2
	 */
	public static function handleR1NotifAction($leadId, $action, $userId = null) {
		$leadId = (int) $leadId;
		$action = strtolower(trim((string) $action));
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Thiếu lead id');
		}
		require_once 'modules/Vtiger/models/NotificationSchedule.php';
		Vtiger_NotificationSchedule::cancelBySourcePrefix('r1_lead_' . $leadId);

		if ($action === 'hen_goi_lai' || $action === 'callback') {
			return self::applyAction($leadId, 'hen_goi_lai', array(), $userId);
		}
		if ($action === 'da_xac_nhan' || $action === 'confirmed' || $action === 'answered') {
			// Thoát vòng R1: gắn hint next action, không + counter.
			require_once 'modules/Leads/models/ModernService.php';
			try {
				Leads_ModernService::updateNextAction($leadId, 'Đã nhận máy — xác minh 3 câu / xếp lịch R2');
			} catch (Exception $e) {
				$adb = PearDatabase::getInstance();
				$adb->pquery(
					"UPDATE bace_lead_profile SET next_action = ?, modified_at = ? WHERE leadid = ?",
					array('Đã nhận máy — xác minh 3 câu / xếp lịch R2', date('Y-m-d H:i:s'), $leadId)
				);
			}
			$lead = Leads_ModernService::getLead((string) $leadId, $userId);
			return array(
				'success' => true,
				'action' => 'da_xac_nhan',
				'next' => 'verify_form',
				'message' => 'Đã xác nhận nhận máy — tiếp tục form 3 câu / xếp lịch.',
				'lead' => $lead,
				'detail_url' => 'index.php?module=Leads&view=Detail&record=' . $leadId,
			);
		}
		return array('success' => false, 'error' => 'Action không hợp lệ');
	}

	protected static function resolveTaskAssignee($leadId, $userId = null) {
		global $current_user;
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT smownerid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
			array((int) $leadId)
		);
		$owner = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'smownerid') : 0;
		if ($owner > 0) {
			return $owner;
		}
		if ($userId) {
			return (int) $userId;
		}
		return !empty($current_user->id) ? (int) $current_user->id : 1;
	}

	protected static function resolveTaskDueAt($status, array $payload) {
		foreach (array('due_at', 'callback_at', 'follow_up_at') as $key) {
			if (!empty($payload[$key])) {
				$ts = strtotime((string) $payload[$key]);
				if ($ts && $ts > time() - 3600) {
					require_once 'modules/Vtiger/models/R1ReminderSettings.php';
					if (in_array($status, self::R1_TAGS, true)) {
						return Vtiger_R1ReminderSettings::snapToBusinessHours(date('Y-m-d H:i:s', $ts));
					}
					return date('Y-m-d H:i:s', $ts);
				}
			}
		}
		$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
		if ($status === self::STATUS_DA_XN_LICH && $classDate !== '') {
			$ts = strtotime($classDate . ' 09:00:00');
			if ($ts && $ts > time()) {
				return date('Y-m-d H:i:s', $ts);
			}
		}
		// R1: +gap giờ trong khung làm việc (mặc định 3h, 08:00–16:00).
		if (in_array($status, self::R1_TAGS, true)) {
			require_once 'modules/Vtiger/models/R1ReminderSettings.php';
			return Vtiger_R1ReminderSettings::addGapWithinBusinessHours(date('Y-m-d H:i:s'));
		}
		// Mặc định: ngày mai 09:00 (Sales/Admin chỉnh trên Calendar nếu cần).
		$tomorrow = strtotime('+1 day');
		return date('Y-m-d', $tomorrow) . ' 09:00:00';
	}

	/**
	 * Convert Lead → Opp khi đủ ĐK Offline + đã nhập Bước 2 (giờ/địa điểm).
	 * Chỉ tạo Opportunity, không tạo Contact.
	 */
	public static function tryConvertEligible($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('converted' => false, 'reason' => 'invalid');
		}
		if (!self::isOfflineStep2Ready($leadId)) {
			return array(
				'converted' => false,
				'skipped' => true,
				'reason' => 'await_step2',
			);
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$status = Leads_ConvertService::getConversionStatus($leadId);
		if (empty($status['canConvert'])) {
			return array(
				'converted' => false,
				'reason' => 'already_opportunity',
				'skipped' => true,
				'potentialId' => isset($status['potentialId']) ? $status['potentialId'] : null,
			);
		}
		try {
			$opts = array(
				'create_account' => false,
				'create_contact' => false,
				'modules' => array('Potentials'),
				'order_category' => 'Internal',
			);
			if ($userId) {
				$opts['assigned_user_id'] = (int) $userId;
			}
			$res = Leads_ConvertService::convertLead($leadId, $opts);
			if (!empty($res['already_converted'])) {
				return array(
					'converted' => false,
					'reason' => 'already_opportunity',
					'skipped' => true,
					'potentialId' => isset($res['potentialId']) ? $res['potentialId'] : null,
				);
			}
			$ok = is_array($res) && !empty($res['success']);
			if ($ok) {
				try {
					$adb = PearDatabase::getInstance();
					$stRes = $adb->pquery(
						'SELECT offline_status FROM bace_lead_profile WHERE leadid = ?',
						array($leadId)
					);
					$st = ($stRes && $adb->num_rows($stRes) > 0)
						? trim((string) $adb->query_result($stRes, 0, 'offline_status'))
						: '';
					if ($st !== '') {
						self::syncOfflineStatusToPotential($leadId, $st, $userId);
					}
				} catch (Exception $eSync) {
					// best-effort
				}
			}
			return array(
				'converted' => $ok,
				'potentialId' => isset($res['potentialId']) ? $res['potentialId'] : null,
				'contactId' => isset($res['contactId']) ? $res['contactId'] : null,
				'redirect' => isset($res['redirect']) ? $res['redirect'] : '',
				'reason' => $ok ? 'ok' : 'convert_failed',
			);
		} catch (Exception $e) {
			return array('converted' => false, 'reason' => $e->getMessage());
		}
	}

	/**
	 * Bước 2 đủ để xuống Opp: đã XN lịch (hoặc hẹn lịch lại) + có giờ học.
	 */
	public static function isOfflineStep2Ready($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return false;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_status, offline_class_time
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) <= 0) {
			return false;
		}
		$st = trim((string) $adb->query_result($res, 0, 'offline_status'));
		if ($st !== self::STATUS_DA_XN_LICH && $st !== self::STATUS_HEN_LICH_LAI) {
			return false;
		}
		$time = trim((string) $adb->query_result($res, 0, 'offline_class_time'));
		return $time !== '';
	}

	protected static function leadIsOffline($leadId) {
		require_once 'modules/Leads/models/ModernService.php';
		$lead = Leads_ModernService::getLead((string) $leadId);
		if (!$lead || !is_array($lead)) {
			return false;
		}
		return self::isOfflineLead($lead, isset($lead['tags']) ? $lead['tags'] : array());
	}

	/**
	 * Chuẩn hoá SĐT VN → 0xxxxxxxxx (rỗng nếu không hợp lệ).
	 */
	public static function normalizeVnPhone($raw) {
		$digits = preg_replace('/\D+/', '', (string) $raw);
		if ($digits === '') {
			return '';
		}
		if (strpos($digits, '84') === 0 && strlen($digits) >= 11) {
			$digits = '0' . substr($digits, 2);
		}
		if (!preg_match('/^0[35789]\d{8}$/', $digits)) {
			return '';
		}
		return $digits;
	}

	/**
	 * Biến thể digits để so khớp cột phone (0… / 84… / không 0).
	 * @return string[]
	 */
	public static function phoneMatchVariants($phone) {
		$norm = self::normalizeVnPhone($phone);
		if ($norm === '') {
			$digits = preg_replace('/\D+/', '', (string) $phone);
			return $digits !== '' ? array($digits) : array();
		}
		$variants = array($norm, '84' . substr($norm, 1), substr($norm, 1));
		return array_values(array_unique($variants));
	}

	/**
	 * Gắn zalo_user_id lên lead Offline cùng SĐT (chỉ khi đang trống).
	 * Gọi từ OA webhook khi có phone + oa_user_id.
	 *
	 * @return array{updated:int,lead_ids:int[]}
	 */
	public static function linkZaloUserIdByPhone($phone, $oaUserId, $excludeLeadId = null) {
		self::installSchema();
		$oaUserId = trim((string) $oaUserId);
		$variants = self::phoneMatchVariants($phone);
		if ($oaUserId === '' || empty($variants)) {
			return array('updated' => 0, 'lead_ids' => array());
		}
		$adb = PearDatabase::getInstance();
		$ph = implode(',', array_fill(0, count($variants), '?'));
		$params = $variants;
		$sql = "SELECT p.leadid, p.zalo_user_id, p.offline_status, p.sheet_source
			FROM bace_lead_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			WHERE p.is_modern = 1
			  AND REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(la.phone,''),' ',''),'-',''),'.',''),'+','') IN ($ph)
			  AND (p.zalo_user_id IS NULL OR p.zalo_user_id = '')
			  AND (
				(p.offline_status IS NOT NULL AND p.offline_status <> '')
				OR IFNULL(p.sheet_source, 0) = 1
				OR EXISTS (
					SELECT 1 FROM vtiger_freetagged_objects fo
					INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
					WHERE fo.object_id = p.leadid
					  AND (LOWER(t.tag) = 'mien_phi_offline' OR LOWER(t.tag) LIKE 'offline_%')
				)
			  )";
		if ($excludeLeadId) {
			$sql .= ' AND p.leadid != ?';
			$params[] = (int) $excludeLeadId;
		}
		$res = $adb->pquery($sql, $params);
		$updated = array();
		$now = date('Y-m-d H:i:s');
		if ($res) {
			$n = $adb->num_rows($res);
			for ($i = 0; $i < $n; $i++) {
				$leadId = (int) $adb->query_result($res, $i, 'leadid');
				if ($leadId <= 0) {
					continue;
				}
				$adb->pquery(
					'UPDATE bace_lead_profile SET
						zalo_user_id = ?,
						offline_oa_scanned_at = COALESCE(NULLIF(offline_oa_scanned_at, \'0000-00-00 00:00:00\'), ?),
						modified_at = ?
					 WHERE leadid = ?
					 AND (zalo_user_id IS NULL OR zalo_user_id = \'\')',
					array($oaUserId, $now, $now, $leadId)
				);
				$updated[] = $leadId;
			}
		}
		return array('updated' => count($updated), 'lead_ids' => $updated);
	}

	/**
	 * Lazy: nếu lead thiếu OA id, copy từ lead khác cùng SĐT đã có id.
	 * @return string zalo_user_id (có thể rỗng)
	 */
	public static function ensureZaloUserId($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return '';
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT p.zalo_user_id, la.phone
			 FROM bace_lead_profile p
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			 WHERE p.leadid = ? LIMIT 1',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return '';
		}
		$cur = trim((string) $adb->query_result($res, 0, 'zalo_user_id'));
		if ($cur !== '') {
			return $cur;
		}
		$phone = (string) $adb->query_result($res, 0, 'phone');
		$variants = self::phoneMatchVariants($phone);
		if (empty($variants)) {
			return '';
		}
		$ph = implode(',', array_fill(0, count($variants), '?'));
		$params = $variants;
		$params[] = $leadId;
		$peer = $adb->pquery(
			"SELECT p.zalo_user_id
			 FROM bace_lead_profile p
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			 WHERE p.is_modern = 1
			   AND p.zalo_user_id IS NOT NULL AND p.zalo_user_id <> ''
			   AND REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(la.phone,''),' ',''),'-',''),'.',''),'+','') IN ($ph)
			   AND p.leadid != ?
			 ORDER BY (CASE WHEN p.online_path = 'oa' THEN 0 ELSE 1 END), p.leadid DESC
			 LIMIT 1",
			$params
		);
		if (!$peer || $adb->num_rows($peer) < 1) {
			return '';
		}
		$uid = trim((string) $adb->query_result($peer, 0, 'zalo_user_id'));
		if ($uid === '') {
			return '';
		}
		$adb->pquery(
			'UPDATE bace_lead_profile SET zalo_user_id = ?, modified_at = ? WHERE leadid = ?
			 AND (zalo_user_id IS NULL OR zalo_user_id = \'\')',
			array($uid, date('Y-m-d H:i:s'), $leadId)
		);
		return $uid;
	}

	/**
	 * Đăng ký cron Step2 + Step4 (15 phút) vào vtiger_cron_task.
	 */
	public static function registerReminderCrons() {
		require_once 'vtlib/Vtiger/Cron.php';
		$jobs = array(
			array(
				'name' => 'OfflineGd11Step2Reminders',
				'handler' => 'cron/modules/Leads/OfflineGd11Step2Reminders.service',
				'desc' => 'GD 1.1 Bước 2 — nhắc trước lớp (OA/Calendar)',
			),
			array(
				'name' => 'OfflineGd11Step4Reminders',
				'handler' => 'cron/modules/Leads/OfflineGd11Step4Reminders.service',
				'desc' => 'GD 1.1 Bước 4 — CSKH sau lớp / no-show',
			),
			array(
				'name' => 'LeadRetentionLifecycle',
				'handler' => 'cron/modules/Leads/LeadRetentionLifecycle.service',
				'desc' => 'Leads — Ngưng CSKH 30 ngày → thùng rác; thùng rác 30 ngày → xóa vĩnh viễn',
				'freq' => 86400,
			),
		);
		foreach ($jobs as $job) {
			$existing = Vtiger_Cron::getInstance($job['name']);
			if ($existing) {
				continue;
			}
			$freq = isset($job['freq']) ? (int) $job['freq'] : 900;
			Vtiger_Cron::register($job['name'], $job['handler'], $freq, 'Leads', 1, 0, $job['desc']);
		}
	}

	public static function setClassDatePublic($leadId, $classDate) {
		self::setClassDate($leadId, $classDate);
	}

	/**
	 * Bước 3 — Admin check-in tại máy điểm danh (Opp). Không gửi OA.
	 * @param string $action da_tham_gia|khong_tham_gia
	 * @param array $opts skip_admin_check=true khi webhook OA tự điểm danh
	 */
	public static function checkinFromPotential($potentialId, $action, $userId = null, array $opts = array()) {
		global $current_user;
		$potentialId = (int) $potentialId;
		$action = strtolower(trim((string) $action));
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if (!in_array($action, array('da_tham_gia', 'khong_tham_gia'), true)) {
			return array('success' => false, 'error' => 'Action check-in không hợp lệ');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		$isAdmin = !empty($current_user->is_admin) && $current_user->is_admin === 'on';
		$skipAdmin = !empty($opts['skip_admin_check']);
		if (!$skipAdmin && !$isAdmin) {
			return array('success' => false, 'error' => 'Chỉ Admin được điểm danh lớp Offline (Bước 3).');
		}

		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}

		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_status FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$cur = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'offline_status')) : '';
		$eligible = array(
			self::STATUS_DA_XN_LICH,
			self::STATUS_HEN_LICH_LAI,
		);
		if ($cur === self::STATUS_DA_THAM_GIA || $cur === self::STATUS_KHONG_THAM_GIA) {
			return array(
				'success' => false,
				'error' => 'Đã ghi nhận tham gia · không chọn lại.',
				'offline_status' => $cur,
				'locked' => true,
			);
		}
		if ($cur === '' || !in_array($cur, $eligible, true)) {
			return array(
				'success' => false,
				'error' => 'Chỉ điểm danh khi đã xác nhận lịch Offline.',
				'offline_status' => $cur,
			);
		}

		$out = self::applyAction($leadId, $action, array(), $userId);
		if (empty($out['success'])) {
			return $out;
		}
		$sync = self::syncOfflineStatusToPotential($leadId, isset($out['status']) ? $out['status'] : '', $userId);
		$step4 = null;
		try {
			require_once 'modules/Leads/models/OfflineGd11Step4Service.php';
			$step4Status = ($action === 'da_tham_gia')
				? self::STATUS_DA_THAM_GIA
				: self::STATUS_KHONG_THAM_GIA;
			$step4 = Leads_OfflineGd11Step4Service::onAttendanceRecorded(
				$leadId,
				$step4Status,
				$userId
			);
		} catch (Exception $e) {
			$step4 = array('success' => false, 'error' => $e->getMessage());
		}
		$out['potential_id'] = $potentialId;
		$out['lead_id'] = $leadId;
		$out['opp_tags'] = isset($sync['tags']) ? $sync['tags'] : array();
		$out['checked_in_at'] = ($action === 'da_tham_gia') ? date('c') : '';
		$out['step4'] = $step4;
		if ($action === 'da_tham_gia') {
			$out['oa_qr'] = self::getOaQrForLead($leadId);
		}
		return $out;
	}

	/**
	 * Opp — Chăm sóc trước lớp: áp điểm rơi R1→R4 (giữ rule không lùi bước / max 3 / noti).
	 */
	public static function applyFromPotential($potentialId, $action, array $payload = array(), $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		$action = strtolower(trim((string) $action));
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if ($action === '') {
			return array('success' => false, 'error' => 'Thiếu action Offline');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}

		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}

		$out = self::applyAction($leadId, $action, $payload, $userId);
		if (empty($out['success'])) {
			return $out;
		}
		$sync = self::syncOfflineStatusToPotential(
			$leadId,
			isset($out['status']) ? $out['status'] : '',
			$userId
		);
		$out['potential_id'] = $potentialId;
		$out['lead_id'] = $leadId;
		$out['opp_tags'] = isset($sync['tags']) ? $sync['tags'] : array();
		return $out;
	}

	/**
	 * Link / QR Zalo OA cho quầy check-in (Bước 3).
	 * @return array
	 */
	public static function getOaQrForLead($leadId) {
		$leadId = (int) $leadId;
		$out = array(
			'lead_id' => $leadId,
			'phone' => '',
			'zalo_user_id' => '',
			'scanned' => false,
			'scanned_at' => '',
			'note' => '',
			'follow_url' => '',
			'qr_image_url' => '',
			'oa_id' => '',
			'oa_name' => '',
			'instructions' => array(
				'«Làm mới trạng thái»: kiểm tra lại xem khách đã quét QR / điền form chưa — nếu CRM đã khớp SĐT thì hiện OA id.',
				'«Không dùng Zalo»: ghi chú khách không có Zalo, vẫn cho vào lớp; liên hệ sau qua SĐT đăng ký.',
				'«Không chịu quét»: ghi chú khách từ chối quét QR form tại quầy (để Sales phụ trách xử lý tiếp).',
			),
		);
		if ($leadId <= 0) {
			return $out;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT p.zalo_user_id, p.offline_oa_scanned_at, p.offline_oa_scan_note, la.phone
			 FROM bace_lead_profile p
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			 WHERE p.leadid = ? LIMIT 1',
			array($leadId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$uid = trim((string) $adb->query_result($res, 0, 'zalo_user_id'));
			$out['zalo_user_id'] = $uid;
			$out['scanned'] = ($uid !== '');
			$scannedAt = trim((string) $adb->query_result($res, 0, 'offline_oa_scanned_at'));
			if ($scannedAt !== '' && $scannedAt !== '0000-00-00 00:00:00') {
				$out['scanned_at'] = date('c', strtotime($scannedAt));
			} elseif ($uid !== '') {
				$out['scanned_at'] = date('c');
			}
			$out['note'] = trim((string) $adb->query_result($res, 0, 'offline_oa_scan_note'));
			$out['phone'] = trim((string) $adb->query_result($res, 0, 'phone'));
		}

		$follow = self::resolveOaFollowUrl();
		$out['follow_url'] = $follow['url'];
		$out['oa_id'] = $follow['oa_id'];
		$out['oa_name'] = $follow['oa_name'];
		// Ưu tiên QR form tĩnh (mở form điền thông tin), không generate từ follow OA.
		$out['qr_image_url'] = self::offlineOaFormQrImageUrl();
		if ($out['qr_image_url'] === '' && $out['follow_url'] !== '') {
			$out['qr_image_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='
				. rawurlencode($out['follow_url']);
		}
		return $out;
	}

	/**
	 * QR form Offline tại quầy (asset trong layouts).
	 * @return string
	 */
	public static function offlineOaFormQrImageUrl() {
		global $site_URL;
		$rel = 'layouts/v7/modules/Potentials/resources/offline-oa-form-qr.png';
		$abs = $rel;
		if (defined('ROOT_DIRECTORY')) {
			$abs = rtrim(ROOT_DIRECTORY, '/\\') . '/' . $rel;
		} elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
			$candidate = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $rel;
			if (is_file($candidate)) {
				$abs = $candidate;
			}
		}
		if (!is_file($abs) && is_file($rel)) {
			$abs = $rel;
		}
		if (!is_file($abs)) {
			return '';
		}
		$ver = (string) @filemtime($abs);
		$base = !empty($site_URL) ? rtrim((string) $site_URL, '/') : '';
		$path = '/' . ltrim($rel, '/') . ($ver !== '' ? ('?v=' . $ver) : '');
		return $base !== '' ? ($base . $path) : $path;
	}

	/**
	 * @return array{url:string,oa_id:string,oa_name:string}
	 */
	public static function resolveOaFollowUrl() {
		$out = array('url' => '', 'oa_id' => '', 'oa_name' => '');
		try {
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			$row = NkApiConnection::getRow('zalo_oa');
			$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
			$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
			$oaId = isset($creds['oa_id']) ? trim((string) $creds['oa_id']) : '';
			if ($oaId === '' && isset($extra['oa_id'])) {
				$oaId = trim((string) $extra['oa_id']);
			}
			$out['oa_id'] = $oaId;
			$out['oa_name'] = isset($extra['oa_name']) ? trim((string) $extra['oa_name']) : '';
			$custom = '';
			if (!empty($extra['follow_url'])) {
				$custom = trim((string) $extra['follow_url']);
			} elseif (!empty($creds['follow_url'])) {
				$custom = trim((string) $creds['follow_url']);
			}
			if ($custom !== '') {
				$out['url'] = $custom;
			} elseif ($oaId !== '') {
				$out['url'] = 'https://zalo.me/' . rawurlencode($oaId);
			}
		} catch (Exception $e) {
			// ignore
		}
		return $out;
	}

	/**
	 * Opp list — lấy / làm mới trạng thái QR OA sau check-in.
	 */
	public static function getOaQrFromPotential($potentialId) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}
		// Thử sync OA id từ lead Online cùng SĐT (nếu khách vừa quét).
		self::ensureZaloUserId($leadId);
		$qr = self::getOaQrForLead($leadId);
		$qr['success'] = true;
		$qr['potential_id'] = $potentialId;
		return $qr;
	}

	/**
	 * Ghi chú quầy: không dùng Zalo / không chịu quét / ghi chú khác.
	 * @param string $noteKind no_zalo|refused|custom
	 */
	public static function saveOaScanNoteFromPotential($potentialId, $noteKind, $customNote = '', $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		$noteKind = strtolower(trim((string) $noteKind));
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}
		self::installSchema();
		$note = '';
		if ($noteKind === 'no_zalo') {
			$note = 'khách không dùng Zalo, liên hệ qua số điện thoại';
		} elseif ($noteKind === 'refused') {
			$note = 'khách có Zalo nhưng không chịu quét QR OA tại quầy';
		} else {
			$note = trim(decode_html((string) $customNote));
			if ($note === '') {
				return array('success' => false, 'error' => 'Thiếu ghi chú');
			}
			if (function_exists('mb_substr')) {
				$note = mb_substr($note, 0, 250);
			} else {
				$note = substr($note, 0, 250);
			}
		}
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_oa_scan_note = ?, modified_at = ? WHERE leadid = ?',
			array($note, $now, $leadId)
		);
		// Append CRM description best-effort
		try {
			$lead = Vtiger_Record_Model::getInstanceById($leadId, 'Leads');
			$desc = trim(decode_html((string) $lead->get('description')));
			$line = '[Bước 3 OA] ' . $note . ' (' . $now . ')';
			$lead->set('mode', 'edit');
			$lead->set('description', $desc === '' ? $line : ($desc . "\n" . $line));
			$lead->save();
		} catch (Exception $e) {
			// ignore
		}
		$qr = self::getOaQrForLead($leadId);
		$qr['success'] = true;
		$qr['potential_id'] = $potentialId;
		$qr['saved_note'] = $note;
		return $qr;
	}

	/**
	 * Opp list — sau Không tham gia: Hẹn lịch lại / Chốt lịch mới.
	 * @param string $action hen_lich_lai|chot_lich_moi
	 */
	public static function rescheduleFromPotential($potentialId, $action, array $payload = array(), $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		$action = strtolower(trim((string) $action));
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if (!in_array($action, array('hen_lich_lai', 'chot_lich_moi'), true)) {
			return array('success' => false, 'error' => 'Action xếp lịch lại không hợp lệ');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}

		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'Opp chưa gắn Lead Offline');
		}

		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_status FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$cur = ($res && $adb->num_rows($res) > 0)
			? trim((string) $adb->query_result($res, 0, 'offline_status')) : '';
		$allowedFrom = array(
			self::STATUS_KHONG_THAM_GIA,
			self::STATUS_HEN_LICH_LAI,
			self::STATUS_DA_XN_LICH,
			self::STATUS_NGUNG_CSKH_TAM,
		);
		if ($cur === '' || !in_array($cur, $allowedFrom, true)) {
			return array(
				'success' => false,
				'error' => 'Chỉ xếp lịch lại khi Không tham gia / Đã XN lịch / Hẹn lịch lại.',
				'offline_status' => $cur,
			);
		}

		require_once 'modules/Leads/models/OfflineGd11Step2Service.php';
		$out = Leads_OfflineGd11Step2Service::applyAction($leadId, $action, $payload, $userId);
		if (empty($out['success'])) {
			return $out;
		}
		$status = isset($out['status']) ? $out['status'] : '';
		if ($status === '' && isset($out['lead']['offline_status'])) {
			$status = $out['lead']['offline_status'];
		}
		if ($status === '') {
			$res2 = $adb->pquery(
				'SELECT offline_status FROM bace_lead_profile WHERE leadid = ?',
				array($leadId)
			);
			$status = ($res2 && $adb->num_rows($res2) > 0)
				? trim((string) $adb->query_result($res2, 0, 'offline_status')) : '';
		}
		$sync = self::syncOfflineStatusToPotential($leadId, $status, $userId);
		$out['potential_id'] = $potentialId;
		$out['lead_id'] = $leadId;
		$out['opp_tags'] = isset($sync['tags']) ? $sync['tags'] : array();
		$out['checked_in_at'] = '';
		return $out;
	}

	/**
	 * Đồng bộ tag trạng thái Offline lên Opportunity (list Opp đọc tags trên Potentials).
	 */
	public static function syncOfflineStatusToPotential($leadId, $statusTag, $userId = null) {
		global $current_user;
		$leadId = (int) $leadId;
		$statusTag = strtolower(trim((string) $statusTag));
		if ($leadId <= 0 || $statusTag === '' || !in_array($statusTag, self::STATUS_TAGS, true)) {
			return array('success' => false, 'tags' => array());
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT potential_id FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		$potentialId = ($res && $adb->num_rows($res) > 0)
			? (int) $adb->query_result($res, 0, 'potential_id') : 0;
		if ($potentialId <= 0) {
			return array('success' => false, 'tags' => array());
		}
		try {
			require_once 'modules/Potentials/models/ModernService.php';
			$tagsMap = Potentials_ModernService::getTagsForPotentialIdsPublic(array($potentialId), $userId);
			$current = isset($tagsMap[$potentialId]) ? $tagsMap[$potentialId] : array();
			$kept = array();
			foreach ($current as $t) {
				$key = strtolower(trim((string) $t));
				if (in_array($key, self::STATUS_TAGS, true)) {
					continue;
				}
				$kept[] = $t;
			}
			$kept = self::ensureProgramTag($kept);
			$kept[] = $statusTag;
			if ($statusTag === self::STATUS_DA_THAM_GIA) {
				$kept[] = 'da_tg_free';
			} else {
				$kept = array_values(array_filter($kept, function ($t) {
					return strtolower(trim((string) $t)) !== 'da_tg_free';
				}));
			}
			$saved = Potentials_ModernService::saveTags($potentialId, $kept, $userId);
			return array(
				'success' => !empty($saved['success']),
				'tags' => isset($saved['tags']) ? $saved['tags'] : $kept,
				'potential_id' => $potentialId,
			);
		} catch (Exception $e) {
			return array('success' => false, 'tags' => array(), 'error' => $e->getMessage());
		}
	}

	protected static function setCheckedInAt($leadId, $datetimeOrNull) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_checked_in_at = ?, modified_at = ? WHERE leadid = ?',
			array($datetimeOrNull, date('Y-m-d H:i:s'), $leadId)
		);
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

	/**
	 * Log quầy QR dùng chung — khớp / không khớp Opp theo SĐT.
	 */
	public static function ensureOppDeskCheckinLogSchema($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->query(
			"CREATE TABLE IF NOT EXISTS bace_opp_oa_desk_checkin (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				phone VARCHAR(32) NOT NULL DEFAULT '',
				oa_user_id VARCHAR(64) NULL,
				result VARCHAR(32) NOT NULL DEFAULT 'unmatched',
				potential_id INT UNSIGNED NULL,
				lead_id INT UNSIGNED NULL,
				opp_name VARCHAR(255) NULL,
				message VARCHAR(255) NULL,
				created_at DATETIME NOT NULL,
				KEY idx_desk_created (created_at),
				KEY idx_desk_result (result),
				KEY idx_desk_phone (phone)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);
		$done = true;
	}

	/**
	 * QR form dùng chung tại quầy — deprecated (điểm danh chuyển sang tìm SĐT).
	 * @return array
	 */
	public static function getDeskOaQr() {
		$image = self::offlineOaFormQrImageUrl();
		return array(
			'success' => true,
			'shared' => true,
			'qr_image_url' => $image,
			'follow_url' => 'https://chatbot.zalo.me/ref/2080837349914896711?id=nhap-thong-tin-lop-offline',
			'instructions' => array(
				'Học viên quét mã, nhập họ tên và số điện thoại.',
				'CRM lấy SĐT để điểm danh lớp. Form giai đoạn 1.2 không đi vào luồng này.',
			),
		);
	}

	/**
	 * Quầy: tìm Opp theo SĐT (chưa điểm danh). Không tự Có tham gia.
	 * @return array
	 */
	public static function lookupDeskByPhone($phone, $logUnmatched = true) {
		$phone = trim((string) $phone);
		$norm = self::normalizeVnPhone($phone);
		$digits = preg_replace('/\D+/', '', $phone);
		if ($norm === '' && $digits === '') {
			return array(
				'success' => false,
				'result' => 'invalid_phone',
				'error' => 'SĐT không hợp lệ — nhập đủ số điện thoại.',
				'phone' => $phone,
				'matches' => array(),
			);
		}
		if ($norm !== '' && !preg_match('/^0\d{9}$/', $norm) && strlen($digits) < 9) {
			return array(
				'success' => false,
				'result' => 'invalid_phone',
				'error' => 'SĐT không hợp lệ — kiểm tra lại số khách đọc.',
				'phone' => $norm !== '' ? $norm : $phone,
				'matches' => array(),
			);
		}
		$matches = self::findEligibleOppsByPhone($phone);
		$labels = self::statusLabels();
		foreach ($matches as &$m) {
			$st = isset($m['offline_status']) ? $m['offline_status'] : '';
			$m['status_label'] = isset($labels[$st]) ? $labels[$st] : $st;
			$m['detail_url'] = 'index.php?module=Potentials&view=Detail&record='
				. (int) $m['potential_id'] . '&app=SALES';
		}
		unset($m);

		$displayPhone = $norm !== '' ? $norm : $digits;
		if (count($matches) === 0) {
			if ($logUnmatched) {
				self::writeDeskCheckinLog(
					$displayPhone,
					'',
					'unmatched',
					0,
					0,
					'',
					'Không tìm thấy Opp với SĐT này (đã XN lịch)'
				);
			}
			return array(
				'success' => true,
				'result' => 'unmatched',
				'phone' => $displayPhone,
				'message' => 'Không tìm thấy Opp với SĐT này',
				'matches' => array(),
			);
		}
		if (count($matches) === 1) {
			return array(
				'success' => true,
				'result' => 'matched',
				'phone' => $displayPhone,
				'message' => 'Đã tìm thấy Opp — bấm Xác nhận tham gia',
				'matches' => $matches,
				'opportunity' => $matches[0],
			);
		}
		return array(
			'success' => true,
			'result' => 'ambiguous',
			'phone' => $displayPhone,
			'message' => 'SĐT khớp nhiều Opp — chọn đúng hồ sơ rồi xác nhận',
			'matches' => $matches,
		);
	}

	/**
	 * Quầy: xác nhận Có tham gia sau khi đã tìm thấy Opp.
	 * @return array
	 */
	public static function confirmDeskAttendance($potentialId, $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return array('success' => false, 'error' => 'Thiếu opportunity id');
		}
		if ($userId === null && !empty($current_user->id)) {
			$userId = (int) $current_user->id;
		}
		$checkin = self::checkinFromPotential(
			$potentialId,
			'da_tham_gia',
			$userId,
			array('skip_admin_check' => true)
		);
		if (empty($checkin['success'])) {
			$err = isset($checkin['error']) ? $checkin['error'] : 'Không xác nhận tham gia được';
			self::writeDeskCheckinLog(
				'',
				'',
				'error',
				$potentialId,
				isset($checkin['lead_id']) ? (int) $checkin['lead_id'] : 0,
				'',
				$err
			);
			return $checkin;
		}
		$oppName = '';
		try {
			$rec = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
			$oppName = trim(decode_html((string) $rec->get('potentialname')));
		} catch (Exception $e) {
			$oppName = '';
		}
		$phone = '';
		try {
			$adb = PearDatabase::getInstance();
			$pr = $adb->pquery('SELECT phone FROM bace_potential_profile WHERE potentialid = ?', array($potentialId));
			if ($pr && $adb->num_rows($pr) > 0) {
				$phone = trim((string) $adb->query_result($pr, 0, 'phone'));
			}
		} catch (Exception $e) {
			$phone = '';
		}
		self::writeDeskCheckinLog(
			self::normalizeVnPhone($phone) !== '' ? self::normalizeVnPhone($phone) : $phone,
			'',
			'matched',
			$potentialId,
			isset($checkin['lead_id']) ? (int) $checkin['lead_id'] : 0,
			$oppName,
			'Quầy xác nhận · Có tham gia'
		);
		$checkin['result'] = 'matched';
		$checkin['message'] = 'Đã xác nhận tham gia';
		$checkin['opp_name'] = $oppName;
		return $checkin;
	}

	/**
	 * Tìm Opp Offline đủ điều kiện điểm danh theo SĐT.
	 * @return array list of {potential_id, lead_id, offline_status, name, phone}
	 */
	public static function findEligibleOppsByPhone($phone) {
		$want = array();
		foreach (self::phoneMatchVariants($phone) as $variant) {
			$want[$variant] = true;
		}
		$norm = self::normalizeVnPhone($phone);
		if ($norm !== '') {
			$want[$norm] = true;
		}
		if (!$want) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$sql = "SELECT p.potentialid, p.potentialname, lp.leadid, lp.offline_status,
				pp.phone AS pot_phone, cd.phone AS contact_phone, cd.mobile AS contact_mobile,
				la.phone AS lead_phone, la.mobile AS lead_mobile
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN bace_potential_profile pp ON pp.potentialid = p.potentialid
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			LEFT JOIN bace_lead_profile lp ON lp.potential_id = p.potentialid
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = lp.leadid
			WHERE (pp.converted_to_customer_at IS NULL OR pp.converted_to_customer_at = '' OR pp.converted_to_customer_at = '0000-00-00 00:00:00')
			  AND lp.offline_status IN (?, ?)
			ORDER BY p.potentialid DESC";
		$res = $adb->pquery($sql, array(self::STATUS_DA_XN_LICH, self::STATUS_HEN_LICH_LAI));
		if (!$res) {
			error_log('[MK_DESK] findEligibleOppsByPhone query failed');
			return array();
		}
		$out = array();
		$seen = array();
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			$phones = array(
				$adb->query_result($res, $i, 'pot_phone'),
				$adb->query_result($res, $i, 'contact_phone'),
				$adb->query_result($res, $i, 'contact_mobile'),
				$adb->query_result($res, $i, 'lead_phone'),
				$adb->query_result($res, $i, 'lead_mobile'),
			);
			if (!self::phoneListMatches($phones, $want)) {
				continue;
			}
			$pid = (int) $adb->query_result($res, $i, 'potentialid');
			if ($pid <= 0 || isset($seen[$pid])) {
				continue;
			}
			$seen[$pid] = 1;
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$display = '';
			foreach ($phones as $raw) {
				$display = trim((string) $raw);
				if ($display !== '') {
					break;
				}
			}
			$out[] = array(
				'potential_id' => $pid,
				'lead_id' => $leadId,
				'offline_status' => trim((string) $adb->query_result($res, $i, 'offline_status')),
				'name' => decode_html((string) $adb->query_result($res, $i, 'potentialname')),
				'phone' => $display,
			);
		}
		$relSql = "SELECT p.potentialid, p.potentialname, lp.leadid, lp.offline_status,
				pp.phone AS pot_phone, la.phone AS lead_phone, la.mobile AS lead_mobile
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			INNER JOIN vtiger_crmentityrel rel
				ON (rel.crmid = p.potentialid AND rel.module = 'Potentials' AND rel.relmodule = 'Leads')
				OR (rel.relcrmid = p.potentialid AND rel.relmodule = 'Potentials' AND rel.module = 'Leads')
			INNER JOIN bace_lead_profile lp ON lp.leadid = IF(rel.module = 'Leads', rel.crmid, rel.relcrmid)
			LEFT JOIN bace_potential_profile pp ON pp.potentialid = p.potentialid
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = lp.leadid
			WHERE lp.offline_status IN (?, ?)
			  AND (pp.converted_to_customer_at IS NULL OR pp.converted_to_customer_at = '' OR pp.converted_to_customer_at = '0000-00-00 00:00:00')";
		$rel = $adb->pquery($relSql, array(self::STATUS_DA_XN_LICH, self::STATUS_HEN_LICH_LAI));
		if ($rel) {
			$n = $adb->num_rows($rel);
			for ($i = 0; $i < $n; $i++) {
				$pid = (int) $adb->query_result($rel, $i, 'potentialid');
				if ($pid <= 0 || isset($seen[$pid])) {
					continue;
				}
				$phones = array(
					$adb->query_result($rel, $i, 'pot_phone'),
					$adb->query_result($rel, $i, 'lead_phone'),
					$adb->query_result($rel, $i, 'lead_mobile'),
				);
				if (!self::phoneListMatches($phones, $want)) {
					continue;
				}
				$seen[$pid] = 1;
				$leadId = (int) $adb->query_result($rel, $i, 'leadid');
				if ($leadId > 0) {
					$adb->pquery(
						'UPDATE bace_lead_profile SET potential_id = ? WHERE leadid = ? AND (potential_id IS NULL OR potential_id = 0)',
						array($pid, $leadId)
					);
				}
				$display = '';
				foreach ($phones as $raw) {
					$display = trim((string) $raw);
					if ($display !== '') {
						break;
					}
				}
				$out[] = array(
					'potential_id' => $pid,
					'lead_id' => $leadId,
					'offline_status' => trim((string) $adb->query_result($rel, $i, 'offline_status')),
					'name' => decode_html((string) $adb->query_result($rel, $i, 'potentialname')),
					'phone' => $display,
				);
			}
		}
		return $out;
	}

	protected static function phoneListMatches(array $phones, array $want) {
		foreach ($phones as $raw) {
			$digits = preg_replace('/\D+/', '', (string) $raw);
			if ($digits !== '' && isset($want[$digits])) {
				return true;
			}
			$norm = self::normalizeVnPhone($digits);
			if ($norm !== '' && isset($want[$norm])) {
				return true;
			}
		}
		return false;
	}

	protected static function writeDeskCheckinLog($phone, $oaUserId, $result, $potentialId, $leadId, $oppName, $message) {
		self::ensureOppDeskCheckinLogSchema();
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO bace_opp_oa_desk_checkin
				(phone, oa_user_id, result, potential_id, lead_id, opp_name, message, created_at)
			 VALUES (?,?,?,?,?,?,?,?)',
			array(
				substr(preg_replace('/\D+/', '', (string) $phone), 0, 32),
				substr(trim((string) $oaUserId), 0, 64),
				substr((string) $result, 0, 32),
				(int) $potentialId,
				(int) $leadId,
				$oppName !== '' ? substr((string) $oppName, 0, 255) : null,
				$message !== '' ? substr((string) $message, 0, 255) : null,
				date('Y-m-d H:i:s'),
			)
		);
	}

	/**
	 * Webhook OA form: đối chiếu SĐT → Opp Offline → tự Có tham gia nếu khớp đúng 1.
	 * @return array
	 */
	public static function processDeskCheckinByPhone($phone, $oaUserId = '') {
		$phone = trim((string) $phone);
		$oaUserId = trim((string) $oaUserId);
		$norm = self::normalizeVnPhone($phone);
		if ($norm === '' && preg_replace('/\D+/', '', $phone) === '') {
			return array('success' => false, 'result' => 'invalid_phone', 'error' => 'Thiếu SĐT');
		}
		$matches = self::findEligibleOppsByPhone($phone);
		if (count($matches) === 0) {
			self::writeDeskCheckinLog(
				$norm !== '' ? $norm : $phone,
				$oaUserId,
				'unmatched',
				null,
				null,
				'',
				'Không trùng khớp với SĐT Opp nào (đã XN lịch)'
			);
			return array(
				'success' => true,
				'result' => 'unmatched',
				'message' => 'Không trùng khớp với SĐT Opp nào',
				'matches' => 0,
			);
		}
		if (count($matches) > 1) {
			$names = array();
			foreach ($matches as $m) {
				$names[] = $m['name'] . ' #' . $m['potential_id'];
			}
			self::writeDeskCheckinLog(
				$norm !== '' ? $norm : $phone,
				$oaUserId,
				'ambiguous',
				null,
				null,
				'',
				'Trùng ' . count($matches) . ' Opp: ' . implode(', ', $names)
			);
			return array(
				'success' => true,
				'result' => 'ambiguous',
				'message' => 'SĐT khớp nhiều Opp — cần chọn thủ công',
				'matches' => count($matches),
				'opps' => $matches,
			);
		}
		$hit = $matches[0];
		if ($oaUserId !== '' && !empty($hit['lead_id'])) {
			try {
				self::linkZaloUserIdByPhone($phone, $oaUserId, null);
			} catch (Exception $e) {
				// best-effort
			}
		}
		$checkin = self::checkinFromPotential(
			(int) $hit['potential_id'],
			'da_tham_gia',
			1,
			array('skip_admin_check' => true)
		);
		if (empty($checkin['success'])) {
			$err = isset($checkin['error']) ? $checkin['error'] : 'Check-in thất bại';
			self::writeDeskCheckinLog(
				$norm !== '' ? $norm : $phone,
				$oaUserId,
				'error',
				(int) $hit['potential_id'],
				(int) $hit['lead_id'],
				$hit['name'],
				$err
			);
			return array(
				'success' => false,
				'result' => 'error',
				'error' => $err,
				'potential_id' => (int) $hit['potential_id'],
			);
		}
		self::writeDeskCheckinLog(
			$norm !== '' ? $norm : $phone,
			$oaUserId,
			'matched',
			(int) $hit['potential_id'],
			(int) $hit['lead_id'],
			$hit['name'],
			'QR khớp · Đã Có tham gia'
		);
		return array(
			'success' => true,
			'result' => 'matched',
			'message' => 'QR khớp · Đã Có tham gia',
			'potential_id' => (int) $hit['potential_id'],
			'lead_id' => (int) $hit['lead_id'],
			'opp_name' => $hit['name'],
			'checkin' => $checkin,
		);
	}

	/**
	 * Feed quầy: khớp / không khớp trong N giờ gần nhất.
	 * @return array
	 */
	public static function listDeskCheckinFeed($hours = 12) {
		self::ensureOppDeskCheckinLogSchema();
		$hours = max(1, min(72, (int) $hours));
		$adb = PearDatabase::getInstance();
		$since = date('Y-m-d H:i:s', time() - $hours * 3600);
		$res = $adb->pquery(
			'SELECT id, phone, oa_user_id, result, potential_id, lead_id, opp_name, message, created_at
			 FROM bace_opp_oa_desk_checkin
			 WHERE created_at >= ?
			 ORDER BY created_at DESC, id DESC
			 LIMIT 200',
			array($since)
		);
		$matched = array();
		$unmatched = array();
		$ambiguous = array();
		if ($res) {
			$n = $adb->num_rows($res);
			for ($i = 0; $i < $n; $i++) {
				$row = array(
					'id' => (int) $adb->query_result($res, $i, 'id'),
					'phone' => (string) $adb->query_result($res, $i, 'phone'),
					'oa_user_id' => (string) $adb->query_result($res, $i, 'oa_user_id'),
					'result' => (string) $adb->query_result($res, $i, 'result'),
					'potential_id' => (int) $adb->query_result($res, $i, 'potential_id'),
					'lead_id' => (int) $adb->query_result($res, $i, 'lead_id'),
					'opp_name' => decode_html((string) $adb->query_result($res, $i, 'opp_name')),
					'message' => decode_html((string) $adb->query_result($res, $i, 'message')),
					'created_at' => (string) $adb->query_result($res, $i, 'created_at'),
				);
				if ($row['result'] === 'matched') {
					$matched[] = $row;
				} elseif ($row['result'] === 'ambiguous') {
					$ambiguous[] = $row;
				} else {
					$unmatched[] = $row;
				}
			}
		}
		return array(
			'success' => true,
			'hours' => $hours,
			'matched' => $matched,
			'unmatched' => $unmatched,
			'ambiguous' => $ambiguous,
			'counts' => array(
				'matched' => count($matched),
				'unmatched' => count($unmatched),
				'ambiguous' => count($ambiguous),
			),
		);
	}
}
