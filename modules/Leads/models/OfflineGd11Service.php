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
		$adb->pquery(
			"UPDATE bace_lead_profile SET offline_status = ?, modified_at = ? WHERE leadid = ?",
			array($statusTag, $now, $leadId)
		);
		self::syncStatusTags($leadId, $statusTag, $userId);
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
			// Tag Không nghe đã hết 3 — giữ trạng thái, Sales chọn Hẹn gọi / Sai TT khác.
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
				$res = PearDatabase::getInstance()->pquery(
					'SELECT offline_r3_class AS c FROM bace_lead_profile WHERE leadid = ?',
					array($leadId)
				);
				$adb = PearDatabase::getInstance();
				$cur = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'c') : 0;
				if ($cur < 1) {
					self::bumpCounter($leadId, 'r3');
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
			$out['convert'] = self::tryConvertEligible($leadId, $userId);
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
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery(
				'SELECT offline_r3_class AS c FROM bace_lead_profile WHERE leadid = ?',
				array($leadId)
			);
			$cur = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'c') : 0;
			if ($cur < 1) {
				self::bumpCounter($leadId, 'r3');
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
		if ($status === self::STATUS_DA_XN_LICH && $classDate !== '') {
			self::setClassDate($leadId, $classDate);
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
		if ($status === self::STATUS_DA_XN_LICH || $status === self::STATUS_CHUA_XN_LICH) {
			// Đủ ĐK thường đã convert lúc verify; nếu Sales gắn lịch sau thì thử convert lần nữa (idempotent).
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
		$out = array(
			'offline_status' => $status,
			'offline_status_label' => isset($labels[$status]) ? $labels[$status] : '',
			'offline_r1_contact' => $r1Sum,
			'offline_r1_hen_goi' => $r1h,
			'offline_r1_khong_nghe' => $r1k,
			'offline_r1_sai_tt' => $r1s,
			'offline_r2_schedule' => isset($row['offline_r2_schedule']) ? (int) $row['offline_r2_schedule'] : 0,
			'offline_r3_class' => isset($row['offline_r3_class']) ? (int) $row['offline_r3_class'] : 0,
			'offline_r4_transfer' => isset($row['offline_r4_transfer']) ? (int) $row['offline_r4_transfer'] : 0,
			'offline_preclass_confirm' => !empty($row['offline_preclass_confirm']) ? 1 : 0,
			'offline_class_date' => (!empty($row['offline_class_date']) && $row['offline_class_date'] !== '0000-00-00')
				? (string) $row['offline_class_date'] : '',
		);
		if ($detailed) {
			$out['offline_status_options'] = $labels;
			$out['offline_kb'] = self::kbSnippets();
		}
		return $out + self::composeStep2Block($row, $detailed);
	}

	protected static function composeStep2Block(array $row, $detailed = false) {
		$status = isset($row['offline_status']) ? trim((string) $row['offline_status']) : '';
		// List: chỉ trả field nhẹ nếu đã vào Offline; bỏ plan/config/KB.
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

	/**
	 * KB 1.1 — mẫu copy ngắn cho Sales (Bước 1).
	 */
	public static function kbSnippets() {
		return array(
			array(
				'id' => 'mo_dau',
				'title' => 'Mở đầu gọi',
				'text' => "Em chào anh/chị, em [Tên] bên [Thương hiệu]. Anh/chị vừa đăng ký lớp miễn phí Offline, em gọi xác nhận thông tin và hỗ trợ xếp lịch ạ.",
			),
			array(
				'id' => 'xac_minh_b',
				'title' => 'Xác minh Bộ B',
				'text' => "Em xin phép hỏi nhanh vài câu để xếp đúng nhóm: mục tiêu học, thời gian có thể đến lớp, và khu vực thuận tiện nhất của anh/chị ạ.",
			),
			array(
				'id' => 'hen_goi_lai',
				'title' => 'Hẹn gọi lại',
				'text' => "Dạ em hiểu anh/chị đang bận. Em xin phép gọi lại vào [giờ/ngày] được không ạ? Em sẽ nhắc lịch ngắn gọn thôi.",
			),
			array(
				'id' => 'khong_nghe',
				'title' => 'Không nghe máy (ghi chú)',
				'text' => "Gọi lần [n] — không nghe máy. Đã để lại tin nhắn/Zalo (nếu có). Hẹn follow theo SLA R1.",
			),
			array(
				'id' => 'sai_tt',
				'title' => 'Sai thông tin',
				'text' => "Số/thông tin trên form không khớp. Em đã ghi chú sai thông tin — cần xác minh lại nguồn hoặc chuyển xử lý theo quy trình.",
			),
			array(
				'id' => 'chot_lich',
				'title' => 'Chốt lịch Offline',
				'text' => "Em xếp anh/chị lớp Offline ngày [ngày] lúc [giờ] tại [địa điểm]. Anh/chị xác nhận giúp em để giữ chỗ nhé.",
			),
			array(
				'id' => 'chua_xn_lich',
				'title' => 'Chưa xác nhận lịch',
				'text' => "Anh/chị đủ điều kiện lớp Offline rồi ạ. Em gửi lại khung giờ gần nhất — anh/chị chọn giúp em 1 slot để em giữ chỗ.",
			),
			array(
				'id' => 'chuyen_ct',
				'title' => 'Chuyển chương trình',
				'text' => "Hiện lớp Offline chưa phù hợp nhu cầu anh/chị. Em đề xuất chuyển sang chương trình phù hợp hơn và nhờ team hỗ trợ tiếp ạ.",
			),
		);
	}

	public static function nextActionForStatus($status, $classDate = '') {
		$map = array(
			self::STATUS_HEN_GOI_LAI => 'Gọi lại theo lịch hẹn (R1)',
			self::STATUS_KHONG_NGHE_MAY => 'Gọi lại — không nghe máy (R1)',
			self::STATUS_SAI_THONG_TIN => 'Xác minh lại thông tin / nguồn',
			self::STATUS_CHUYEN_CT => 'Chuyển chương trình phù hợp (R4)',
			self::STATUS_CHUA_XN_LICH => 'Chốt & xác nhận lịch Offline (R2)',
			self::STATUS_DA_XN_LICH => $classDate !== ''
				? ('Nhắc lịch lớp Offline ' . $classDate)
				: 'Chuẩn bị lớp / nhắc lịch Offline',
			self::STATUS_HEN_LICH_LAI => 'Hẹn lịch lại với HV (R3)',
			self::STATUS_KHONG_THAM_GIA => 'Ghi nhận không tham gia — follow nếu cần',
			self::STATUS_DA_THAM_GIA => 'CSKH sau lớp Offline',
			self::STATUS_NGUNG_CSKH => 'Ngưng CSKH Offline (đã đủ R)',
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
			return array(
				'success' => true,
				'activity_id' => $activityId,
				'due_at' => $when,
				'assigned_user_id' => $assignId,
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		} catch (Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
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
		// Mặc định: ngày mai 09:00 (Sales/Admin chỉnh trên Calendar nếu cần).
		$tomorrow = strtotime('+1 day');
		return date('Y-m-d', $tomorrow) . ' 09:00:00';
	}

	/**
	 * Convert Lead → Opp khi đủ ĐK Offline (idempotent nếu đã có Opp).
	 */
	public static function tryConvertEligible($leadId, $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('converted' => false, 'reason' => 'invalid');
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
			return array(
				'converted' => $ok,
				'potentialId' => isset($res['potentialId']) ? $res['potentialId'] : null,
				'redirect' => isset($res['redirect']) ? $res['redirect'] : '',
				'reason' => $ok ? 'ok' : 'convert_failed',
			);
		} catch (Exception $e) {
			return array('converted' => false, 'reason' => $e->getMessage());
		}
	}

	protected static function leadIsOffline($leadId) {
		require_once 'modules/Leads/models/ModernService.php';
		$lead = Leads_ModernService::getLead((string) $leadId);
		if (!$lead || !is_array($lead)) {
			return false;
		}
		return self::isOfflineLead($lead, isset($lead['tags']) ? $lead['tags'] : array());
	}

	public static function setClassDatePublic($leadId, $classDate) {
		self::setClassDate($leadId, $classDate);
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
