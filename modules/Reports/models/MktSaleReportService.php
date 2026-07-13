<?php
/*+***********************************************************************************
 * MKT SALE — 3 bảng theo mẫu Excel vận hành:
 *  1) Theo ngày: Data MKT / nguồn / KV / tình trạng lead
 *  2) Theo ngày học (Thứ): funnel hẹn → show → chốt + %
 *  3) Ma trận tình trạng × ngày học
 * Data: Leads + freetags, Calendar, Potentials.
 *************************************************************************************/

class Reports_MktSaleReportService {

	/**
	 * @param array $filters date_from, date_to, owner_id
	 * @return array
	 */
	public static function build(array $filters) {
		$db = PearDatabase::getInstance();
		$range = self::resolveDateRange($filters);
		$ownerId = (int) (isset($filters['owner_id']) ? $filters['owner_id'] : 0);

		$leadFacts = self::loadLeadFacts($db, $range, $ownerId);
		$apptFacts = self::loadAppointmentFacts($db, $range, $ownerId);
		$oppFacts = self::loadOppFacts($db, $range, $ownerId);

		// Bổ sung tag Lead gắn lịch nhưng tạo ngoài khoảng lọc (để bảng 2/3 đủ status).
		$extraLeadIds = array();
		foreach ($apptFacts as $ap) {
			$lid = (int) $ap['lead_id'];
			if ($lid > 0 && !isset($leadFacts[$lid])) {
				$extraLeadIds[] = $lid;
			}
		}
		if (!empty($extraLeadIds)) {
			$extra = self::loadLeadFactsByIds($db, $extraLeadIds);
			foreach ($extra as $id => $fact) {
				$leadFacts[$id] = $fact;
			}
		}

		// Không có Calendar gắn Lead/Opp → suy ngày học từ tag Lead / Thứ Ba trong tháng
		if (empty($apptFacts)) {
			$apptFacts = self::synthesizeAppointmentsFromLeads($range, $leadFacts);
		}

		$dailyBundle = self::buildDailySheet($range, $leadFacts);
		$classDays = self::buildClassDaySheet($range, $leadFacts, $apptFacts, $oppFacts);
		$statusMatrix = self::buildStatusMatrix($classDays, $apptFacts, $leadFacts, $oppFacts);
		$summary = self::buildSummary($range, $dailyBundle['rows'], $dailyBundle['total'], $classDays);

		$monthlyRange = self::resolveMonthlyLookback($range);
		$mLead = self::loadLeadFacts($db, $monthlyRange, $ownerId);
		$mAppt = self::loadAppointmentFacts($db, $monthlyRange, $ownerId);
		$mOpp = self::loadOppFacts($db, $monthlyRange, $ownerId);
		$monthly = self::buildMonthlyRows($monthlyRange, $mLead, $mAppt, $mOpp);

		return array(
			'daily' => $dailyBundle['rows'],
			'daily_total' => $dailyBundle['total'],
			'class_days' => $classDays,
			'status_matrix' => $statusMatrix,
			'funnel' => $statusMatrix['columns'],
			'monthly' => $monthly,
			'summary' => $summary,
			'meta' => array(
				'month_label' => 'THÁNG ' . (int) date('n', strtotime($range['from'])),
				'month_total_label' => 'TỔNG THÁNG ' . (int) date('n', strtotime($range['from'])),
				'date_from' => $range['from'],
				'date_to' => $range['to'],
				'data_source' => 'live',
				'generated_at' => date('Y-m-d H:i:s'),
			),
		);
	}

	protected static function resolveDateRange(array $filters) {
		$from = trim((string) (isset($filters['date_from']) ? $filters['date_from'] : ''));
		$to = trim((string) (isset($filters['date_to']) ? $filters['date_to'] : ''));
		if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
			$from = date('Y-m-01');
		}
		if ($to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
			$to = date('Y-m-t');
		}
		if ($from > $to) {
			$tmp = $from;
			$from = $to;
			$to = $tmp;
		}
		$fromTs = strtotime($from);
		$toTs = strtotime($to);
		if (($toTs - $fromTs) > 93 * 86400) {
			$from = date('Y-m-d', $toTs - 93 * 86400);
		}
		return array('from' => $from, 'to' => $to);
	}

	protected static function resolveMonthlyLookback(array $range) {
		$toMonthEnd = date('Y-m-t', strtotime($range['to']));
		$from = date('Y-m-01', strtotime($toMonthEnd . ' -11 months'));
		return array('from' => $from, 'to' => $toMonthEnd);
	}

	protected static function emptyDailyCounters() {
		return array(
			'total_leads' => 0,
			'n_khoa' => 0,
			'tiktok' => 0,
			'kv1' => 0,
			'kv2' => 0,
			'kv3' => 0,
			'region_unknown' => 0,
			'consulting' => 0,
			'unreachable' => 0,
			'invalid' => 0,
			'online_class' => 0,
			// giữ field cũ cho export/chart tương thích
			'contacted' => 0,
			'appointments' => 0,
			'kv4' => 0,
			'consult_direct' => 0,
			'guest_new' => 0,
			'guest_old' => 0,
			'consult_online' => 0,
			'meet_direct' => 0,
			'show' => 0,
			'confirmed' => 0,
			'closed' => 0,
			'reschedule' => 0,
			'no_contact' => 0,
			'potential' => 0,
		);
	}

	protected static function emptyClassDayCounters() {
		return array(
			'total_leads' => 0,
			'appointments' => 0,
			'reschedule' => 0,
			'khong_hoc' => 0,
			'confirmed' => 0,
			'show' => 0,
			'potential' => 0,
			'closed' => 0,
			'pcth' => 0,
			'pcth_mq' => 0,
			'mq' => 0,
			'knm_ban' => 0,
			'phan_van' => 0,
			'moi_lai' => 0,
			'ngong_cho' => 0,
			'chua_xac_dinh' => 0,
			'hoc_cho_khac' => 0,
			'quan_tam_nq' => 0,
			'quan_tam_nl' => 0,
			'fifty_fifty' => 0,
			'companion' => 0,
			'material_bought' => 0,
			'stop_care' => 0,
			'no_call' => 0,
		);
	}

	/**
	 * @return array leadId => fact
	 */
	protected static function loadLeadFacts(PearDatabase $db, array $range, $ownerId) {
		$params = array($range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59');
		$ownerSql = '';
		if ($ownerId > 0) {
			$ownerSql = ' AND ce.smownerid = ?';
			$params[] = $ownerId;
		}
		$sql = "SELECT l.leadid, l.leadsource, ce.createdtime, ce.smownerid,
				COALESCE(p.district, '') AS district,
				COALESCE(p.last_touch, '') AS last_touch,
				COALESCE(p.segment, '') AS segment
			FROM vtiger_leaddetails l
			INNER JOIN vtiger_crmentity ce ON ce.crmid = l.leadid AND ce.deleted = 0 AND ce.setype = 'Leads'
			LEFT JOIN bace_lead_profile p ON p.leadid = l.leadid
			WHERE ce.createdtime >= ? AND ce.createdtime <= ?" . $ownerSql;
		$rs = $db->pquery($sql, $params);
		$facts = array();
		$ids = array();
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$id = (int) $row['leadid'];
			$ids[] = $id;
			$day = substr((string) $row['createdtime'], 0, 10);
			$facts[$id] = self::makeLeadFactSkeleton($id, $day, $row);
		}
		self::attachLeadTags($db, $facts, $ids);
		return $facts;
	}

	protected static function loadLeadFactsByIds(PearDatabase $db, array $ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) {
			return array();
		}
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$sql = "SELECT l.leadid, l.leadsource, ce.createdtime, ce.smownerid,
				COALESCE(p.district, '') AS district,
				COALESCE(p.last_touch, '') AS last_touch,
				COALESCE(p.segment, '') AS segment
			FROM vtiger_leaddetails l
			INNER JOIN vtiger_crmentity ce ON ce.crmid = l.leadid AND ce.deleted = 0 AND ce.setype = 'Leads'
			LEFT JOIN bace_lead_profile p ON p.leadid = l.leadid
			WHERE l.leadid IN ($placeholders)";
		$rs = $db->pquery($sql, $ids);
		$facts = array();
		$found = array();
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$id = (int) $row['leadid'];
			$found[] = $id;
			$day = substr((string) $row['createdtime'], 0, 10);
			$facts[$id] = self::makeLeadFactSkeleton($id, $day, $row);
		}
		self::attachLeadTags($db, $facts, $found);
		return $facts;
	}

	protected static function makeLeadFactSkeleton($id, $day, array $row) {
		return array(
			'id' => (int) $id,
			'created_day' => $day,
			'leadsource' => self::decode((string) $row['leadsource']),
			'district' => self::decode((string) $row['district']),
			'last_touch' => (string) $row['last_touch'],
			'segment' => self::decode((string) $row['segment']),
			'tags' => array(),
			'region' => self::regionFromDistrict((string) $row['district']),
			'source_bucket' => '',
			'contacted' => false,
			'potential' => false,
			'closed' => false,
			'online' => false,
			'offline' => false,
			'new_contract' => false,
			'old_contract' => false,
			'consulting' => false,
			'unreachable' => false,
			'invalid' => false,
			'online_class' => false,
		);
	}

	protected static function attachLeadTags(PearDatabase $db, array &$facts, array $ids) {
		if (empty($ids) || empty($facts)) {
			return;
		}
		$tagMap = self::loadTagsForRecords($db, $ids, 'Leads');
		foreach ($tagMap as $id => $tags) {
			if (!isset($facts[$id])) {
				continue;
			}
			$facts[$id]['tags'] = $tags;
			$facts[$id]['region'] = self::regionFromTags($tags, $facts[$id]['region']);
			$facts[$id]['source_bucket'] = self::classifySource($tags, $facts[$id]['leadsource']);
			$facts[$id]['contacted'] = self::hasCallTag($tags) || trim($facts[$id]['last_touch']) !== '';
			$facts[$id]['potential'] = self::hasAnyTag($tags, array('tiem_nang', 'tiềm_năng'));
			$facts[$id]['closed'] = self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'da_ky_quy', 'da_990k'));
			$facts[$id]['online'] = self::hasAnyTag($tags, array('mien_phi_online'));
			$facts[$id]['offline'] = self::hasAnyTag($tags, array('mien_phi_offline', 'da_tg_free', 'da_tg_fb1'));
			$facts[$id]['new_contract'] = self::hasAnyTag($tags, array('mua_lan_dau', 'chua_hoc'));
			$facts[$id]['old_contract'] = self::hasAnyTag($tags, array('mua_lai', 'mua_on_dinh', 'da_hoc'));
			$facts[$id]['consulting'] = self::hasAnyTag($tags, array(
				'dang_tu_van', 'kh_can_nhac', 'moi_lai', 'doi_lich', 'thu_3', 'dang_cham_soc',
			)) || self::hasCallTag($tags);
			$facts[$id]['unreachable'] = self::hasAnyTag($tags, array('khong_nghe_may'));
			$facts[$id]['invalid'] = self::hasAnyTag($tags, array('khong_hoc', 'thue_bao', 'trung_so'));
			$facts[$id]['online_class'] = self::hasAnyTag($tags, array('lop_online', 'mien_phi_online'));
		}
		// Lead không có tag nguồn: suy từ leadsource
		foreach ($facts as $id => $f) {
			if ($f['source_bucket'] === '') {
				$facts[$id]['source_bucket'] = self::classifySource(array(), $f['leadsource']);
			}
		}
	}

	/**
	 * @return array list of appointment facts
	 */
	protected static function loadAppointmentFacts(PearDatabase $db, array $range, $ownerId) {
		$out = array();
		$seen = array();

		$ownerSql = '';
		$params = array($range['from'], $range['to']);
		if ($ownerId > 0) {
			$ownerSql = ' AND ce.smownerid = ?';
			$params[] = $ownerId;
		}

		// 1) Lịch gắn Lead/Opp qua seactivityrel
		$sql = "SELECT a.activityid, a.date_start, a.activitytype, a.eventstatus, a.subject,
				ce.smownerid, rel.crmid AS related_id, crel.setype AS related_type
			FROM vtiger_activity a
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			INNER JOIN vtiger_seactivityrel rel ON rel.activityid = a.activityid
			INNER JOIN vtiger_crmentity crel ON crel.crmid = rel.crmid AND crel.deleted = 0
			WHERE a.date_start >= ? AND a.date_start <= ?
			  AND a.activitytype IN ('Meeting','Call','Task')
			  AND crel.setype IN ('Leads','Potentials')" . $ownerSql;
		$rs = $db->pquery($sql, $params);
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			self::pushAppointmentFact($out, $seen, $row);
		}

		// 2) Mọi Meeting/Call trong khoảng (kể cả chưa gắn Lead) — vẫn tính ngày học
		$params2 = array($range['from'], $range['to']);
		$ownerSql2 = '';
		if ($ownerId > 0) {
			$ownerSql2 = ' AND ce.smownerid = ?';
			$params2[] = $ownerId;
		}
		$sql2 = "SELECT a.activityid, a.date_start, a.activitytype, a.eventstatus, a.subject,
				ce.smownerid, 0 AS related_id, '' AS related_type
			FROM vtiger_activity a
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			WHERE a.date_start >= ? AND a.date_start <= ?
			  AND a.activitytype IN ('Meeting','Call')" . $ownerSql2;
		$rs2 = $db->pquery($sql2, $params2);
		while ($rs2 && ($row = $db->fetchByAssoc($rs2))) {
			self::pushAppointmentFact($out, $seen, $row);
		}

		return $out;
	}

	protected static function pushAppointmentFact(array &$out, array &$seen, array $row) {
		$aid = (int) $row['activityid'];
		$rid = (int) $row['related_id'];
		$key = $aid . ':' . $rid;
		if (isset($seen[$key])) {
			return;
		}
		$type = (string) $row['related_type'];
		// Ưu tiên bản ghi đã gắn Lead/Opp: bỏ bản activityid:0 nếu có
		if ($rid > 0 && isset($seen[$aid . ':0'])) {
			unset($seen[$aid . ':0']);
			foreach ($out as $i => $f) {
				if ((int) $f['activity_id'] === $aid && empty($f['lead_id']) && empty($f['opp_id'])) {
					unset($out[$i]);
				}
			}
			$out = array_values($out);
		}
		if ($rid === 0 && isset($seen[$aid . ':0']) === false) {
			foreach ($seen as $k => $_) {
				if (strpos((string) $k, $aid . ':') === 0 && $k !== $aid . ':0') {
					return;
				}
			}
		}
		$seen[$key] = true;
		$day = substr((string) $row['date_start'], 0, 10);
		$subject = mb_strtolower(self::decode((string) $row['subject']), 'UTF-8');
		$status = mb_strtolower(self::decode((string) $row['eventstatus']), 'UTF-8');
		$out[] = array(
			'activity_id' => $aid,
			'day' => $day,
			'lead_id' => $type === 'Leads' ? $rid : 0,
			'opp_id' => $type === 'Potentials' ? $rid : 0,
			'activity_type' => (string) $row['activitytype'],
			'is_meeting' => ((string) $row['activitytype'] === 'Meeting'),
			'is_reschedule' => (
				strpos($subject, 'dời') !== false
				|| strpos($subject, 'doi lich') !== false
				|| strpos($subject, 'dời lịch') !== false
				|| strpos($status, 'deferred') !== false
			),
			'is_online' => (strpos($subject, 'online') !== false || strpos($subject, 'zoom') !== false),
			'from_lead_tag' => false,
		);
	}

	/**
	 * Khi không có Calendar: suy ngày học từ Lead (Thứ 3 / tag lớp / cohort theo tuần).
	 */
	protected static function synthesizeAppointmentsFromLeads(array $range, array $leadFacts) {
		$out = array();
		$classDays = array();
		$cursor = strtotime($range['from']);
		$end = strtotime($range['to']);
		while ($cursor <= $end) {
			// Ưu tiên Thứ Ba (thu_3); thêm mọi ngày có lead mang tag lớp/hẹn
			$w = (int) date('w', $cursor);
			$day = date('Y-m-d', $cursor);
			if ($w === 2) { // Tuesday
				$classDays[$day] = true;
			}
			$cursor = strtotime('+1 day', $cursor);
		}
		foreach ($leadFacts as $lead) {
			$day = $lead['created_day'];
			if ($day < $range['from'] || $day > $range['to']) {
				continue;
			}
			$tags = isset($lead['tags']) ? $lead['tags'] : array();
			$isClassish = self::hasAnyTag($tags, array(
				'thu_3', 'doi_lich', 'lop_online', 'da_tg_free', 'da_tg_fb1',
				'xac_nhan_tham_gia', 'mien_phi_offline', 'mien_phi_online',
			));
			if ($isClassish) {
				// Gán về Thứ Ba gần nhất trong tuần (hoặc chính ngày đó nếu là Tue)
				$ts = strtotime($day);
				$w = (int) date('w', $ts);
				$offset = ($w >= 2) ? (2 - $w) : (2 - $w - 7);
				$tue = date('Y-m-d', strtotime($offset . ' days', $ts));
				if ($tue < $range['from']) {
					$tue = $day;
				}
				if ($tue > $range['to']) {
					$tue = $day;
				}
				$classDays[$tue] = true;
				$out[] = array(
					'activity_id' => 0,
					'day' => $tue,
					'lead_id' => (int) $lead['id'],
					'opp_id' => 0,
					'activity_type' => 'Meeting',
					'is_meeting' => true,
					'is_reschedule' => self::hasAnyTag($tags, array('doi_lich')),
					'is_online' => self::hasAnyTag($tags, array('lop_online', 'mien_phi_online')),
					'from_lead_tag' => true,
				);
			}
		}
		// Nếu vẫn chưa có gì nhưng có lead trong tháng — tạo dòng Thứ Ba với cohort marketing
		if (empty($out) && !empty($leadFacts)) {
			foreach (array_keys($classDays) as $tue) {
				// một "slot" trống để bảng 2 vẫn hiện ngày học + total_leads cohort
				$out[] = array(
					'activity_id' => 0,
					'day' => $tue,
					'lead_id' => 0,
					'opp_id' => 0,
					'activity_type' => 'Meeting',
					'is_meeting' => true,
					'is_reschedule' => false,
					'is_online' => false,
					'from_lead_tag' => true,
					'placeholder' => true,
				);
			}
		}
		return $out;
	}

	protected static function loadOppFacts(PearDatabase $db, array $range, $ownerId) {
		$params = array(
			$range['from'] . ' 00:00:00',
			$range['to'] . ' 23:59:59',
			$range['from'],
			$range['to'],
		);
		$ownerSql = '';
		if ($ownerId > 0) {
			$ownerSql = ' AND ce.smownerid = ?';
			$params[] = $ownerId;
		}
		$sql = "SELECT p.potentialid, p.sales_stage, p.closingdate, ce.createdtime
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0 AND ce.setype = 'Potentials'
			WHERE (
				(ce.createdtime >= ? AND ce.createdtime <= ?)
				OR (p.closingdate IS NOT NULL AND p.closingdate <> '' AND p.closingdate >= ? AND p.closingdate <= ?)
			)" . $ownerSql;
		$rs = $db->pquery($sql, $params);
		$facts = array();
		$ids = array();
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$id = (int) $row['potentialid'];
			$ids[] = $id;
			$stage = mb_strtolower(self::decode((string) $row['sales_stage']), 'UTF-8');
			$facts[$id] = array(
				'id' => $id,
				'created_day' => substr((string) $row['createdtime'], 0, 10),
				'closing_day' => substr((string) $row['closingdate'], 0, 10),
				'sales_stage' => $stage,
				'tags' => array(),
				'show' => false,
				'confirmed' => false,
				'closed' => (strpos($stage, 'closed won') !== false || strpos($stage, 'đã chốt') !== false || strpos($stage, 'da chot') !== false),
				'potential' => false,
				'franchise' => false,
				'material' => false,
				'stop' => false,
			);
		}
		if (empty($ids)) {
			return $facts;
		}
		$tagMap = self::loadTagsForRecords($db, $ids, 'Potentials');
		foreach ($tagMap as $id => $tags) {
			if (!isset($facts[$id])) {
				continue;
			}
			$facts[$id]['tags'] = $tags;
			$facts[$id]['show'] = self::hasAnyTag($tags, array('da_tg_free', 'da_tg_fb1', 'thu_3'));
			$facts[$id]['confirmed'] = self::hasAnyTag($tags, array('xac_nhan_tham_gia'));
			$facts[$id]['potential'] = self::hasAnyTag($tags, array('tiem_nang'));
			$facts[$id]['franchise'] = self::hasAnyTag($tags, array('nhuong_quyen', 'da_ky_quy'));
			$facts[$id]['material'] = self::hasAnyTag($tags, array('nguyen_lieu_chuoi'));
			$facts[$id]['stop'] = self::hasAnyTag($tags, array('ngung_mua', 'khong_mua', 'dung_cham_soc', 'ngung_cham_soc'));
			if (!$facts[$id]['closed']) {
				$facts[$id]['closed'] = self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'da_ky_quy', 'da_990k'));
			}
		}
		return $facts;
	}

	/** Bảng 1 — theo ngày tạo Lead. */
	protected static function buildDailySheet(array $range, array $leadFacts) {
		$rows = array();
		$cursor = strtotime($range['from']);
		$end = strtotime($range['to']);
		while ($cursor <= $end) {
			$day = date('Y-m-d', $cursor);
			$rows[$day] = array_merge(
				array('date' => $day, 'label' => date('d/m/Y', $cursor)),
				self::emptyDailyCounters()
			);
			$cursor = strtotime('+1 day', $cursor);
		}

		foreach ($leadFacts as $lead) {
			$day = $lead['created_day'];
			if (!isset($rows[$day])) {
				continue;
			}
			$rows[$day]['total_leads']++;
			if (!empty($lead['contacted'])) {
				$rows[$day]['contacted']++;
			}
			if ($lead['source_bucket'] === 'tiktok') {
				$rows[$day]['tiktok']++;
			} elseif ($lead['source_bucket'] === 'n_khoa') {
				$rows[$day]['n_khoa']++;
			}
			$r = $lead['region'];
			if ($r === 'KV1') {
				$rows[$day]['kv1']++;
			} elseif ($r === 'KV2') {
				$rows[$day]['kv2']++;
			} elseif ($r === 'KV3') {
				$rows[$day]['kv3']++;
			} else {
				$rows[$day]['region_unknown']++;
			}
			if (!empty($lead['consulting'])) {
				$rows[$day]['consulting']++;
			}
			if (!empty($lead['unreachable'])) {
				$rows[$day]['unreachable']++;
			}
			if (!empty($lead['invalid'])) {
				$rows[$day]['invalid']++;
			}
			if (!empty($lead['online_class'])) {
				$rows[$day]['online_class']++;
			}
			if (!empty($lead['offline'])) {
				$rows[$day]['consult_direct']++;
				$rows[$day]['meet_direct']++;
			}
			if (!empty($lead['online'])) {
				$rows[$day]['consult_online']++;
			}
			if (!empty($lead['new_contract'])) {
				$rows[$day]['guest_new']++;
			}
			if (!empty($lead['old_contract'])) {
				$rows[$day]['guest_old']++;
			}
			if (!empty($lead['potential'])) {
				$rows[$day]['potential']++;
			}
			if (!empty($lead['closed'])) {
				$rows[$day]['closed']++;
			}
		}

		$total = array_merge(
			array('date' => '', 'label' => 'TỔNG THÁNG ' . (int) date('n', strtotime($range['from']))),
			self::emptyDailyCounters()
		);
		foreach ($rows as $row) {
			foreach (self::emptyDailyCounters() as $k => $_) {
				$total[$k] += (int) $row[$k];
			}
		}

		return array(
			'rows' => array_values($rows),
			'total' => $total,
		);
	}

	/**
	 * Bảng 2 — theo ngày học (Meeting / ngày có lịch).
	 * Tổng Data Marketing của mỗi ngày học = lead tạo từ sau ngày học trước → ngày học hiện tại.
	 */
	protected static function buildClassDaySheet(array $range, array $leadFacts, array $apptFacts, array $oppFacts) {
		$classDates = self::detectClassDates($apptFacts, $leadFacts, $oppFacts, $range);
		if (empty($classDates)) {
			return array();
		}

		$leadsByDay = array();
		foreach ($leadFacts as $lead) {
			$d = $lead['created_day'];
			if ($d < $range['from'] || $d > $range['to']) {
				continue;
			}
			if (!isset($leadsByDay[$d])) {
				$leadsByDay[$d] = 0;
			}
			$leadsByDay[$d]++;
		}

		$prevExclusive = date('Y-m-d', strtotime($range['from'] . ' -1 day'));
		$out = array();
		foreach ($classDates as $day) {
			$row = array_merge(
				array(
					'date' => $day,
					'label' => date('d/m/Y', strtotime($day)),
					'weekday' => self::weekdayVi($day),
					'month_label' => 'THÁNG ' . (int) date('n', strtotime($day)),
					'is_summary' => false,
				),
				self::emptyClassDayCounters()
			);

			// Cohort marketing tới ngày học này
			$cursor = strtotime($prevExclusive . ' +1 day');
			$end = strtotime($day);
			while ($cursor <= $end) {
				$key = date('Y-m-d', $cursor);
				if (isset($leadsByDay[$key])) {
					$row['total_leads'] += (int) $leadsByDay[$key];
				}
				$cursor = strtotime('+1 day', $cursor);
			}
			$prevExclusive = $day;

			foreach ($apptFacts as $ap) {
				if ($ap['day'] !== $day) {
					continue;
				}
				if (!empty($ap['placeholder'])) {
					continue;
				}
				$row['appointments']++;
				$tags = self::tagsForAppointment($ap, $leadFacts, $oppFacts);
				if (!empty($ap['is_reschedule']) || self::hasAnyTag($tags, array('doi_lich'))) {
					$row['reschedule']++;
				}
				self::accumulateStatusFromTags($row, $tags, $ap, $leadFacts, $oppFacts);
			}

			// Fallback: nếu ngày học không có lịch nhưng có cohort lead → đếm lead cohort như "hẹn" ước lượng 0, vẫn hiện dòng
			if ($row['appointments'] <= 0 && $row['total_leads'] > 0) {
				// Giữ appointments = 0 nhưng dòng vẫn có total_leads — OK cho vận hành khi chưa gắn Calendar
			}

			$row['pct_potential'] = self::pct($row['potential'], $row['appointments']);
			$row['pct_show_appt'] = self::pct($row['show'], $row['appointments']);
			$row['pct_confirm_appt'] = self::pct($row['confirmed'], $row['appointments']);
			$row['pct_show_confirm'] = self::pct($row['show'], $row['confirmed'] > 0 ? $row['confirmed'] : $row['appointments']);
			$row['pct_close_appt'] = self::pct($row['closed'], $row['appointments']);
			$row['pct_close_total'] = $row['pct_close_appt'];
			$out[] = $row;
		}

		// Hàng tổng tháng (highlight) — chỉ thêm khi có ≥2 ngày học; nhãn = TỔNG (không trùng ngày)
		if (count($out) >= 2) {
			$sum = array_merge(
				array(
					'date' => '',
					'label' => 'TỔNG',
					'weekday' => '—',
					'month_label' => $out[0]['month_label'],
					'is_summary' => true,
				),
				self::emptyClassDayCounters()
			);
			foreach ($out as $r) {
				foreach (self::emptyClassDayCounters() as $k => $_) {
					$sum[$k] += (int) $r[$k];
				}
			}
			$sum['pct_potential'] = self::pct($sum['potential'], $sum['appointments']);
			$sum['pct_show_appt'] = self::pct($sum['show'], $sum['appointments']);
			$sum['pct_confirm_appt'] = self::pct($sum['confirmed'], $sum['appointments']);
			$sum['pct_show_confirm'] = self::pct($sum['show'], $sum['confirmed'] > 0 ? $sum['confirmed'] : $sum['appointments']);
			$sum['pct_close_appt'] = self::pct($sum['closed'], $sum['appointments']);
			$sum['pct_close_total'] = $sum['pct_close_appt'];
			array_unshift($out, $sum);
		} elseif (count($out) === 1) {
			// 1 ngày duy nhất: không nhân đôi hàng tổng
			$out[0]['is_summary'] = false;
		}

		return $out;
	}

	protected static function detectClassDates(array $apptFacts, array $leadFacts, array $oppFacts, array $range) {
		$counts = array();
		foreach ($apptFacts as $ap) {
			$day = $ap['day'];
			if ($day < $range['from'] || $day > $range['to']) {
				continue;
			}
			if (!isset($counts[$day])) {
				$counts[$day] = array('meeting' => 0, 'any' => 0, 'thu3' => 0);
			}
			$counts[$day]['any']++;
			if (!empty($ap['is_meeting'])) {
				$counts[$day]['meeting']++;
			}
			if (!empty($ap['placeholder'])) {
				continue;
			}
			$tags = self::tagsForAppointment($ap, $leadFacts, $oppFacts);
			if (self::hasAnyTag($tags, array('thu_3'))) {
				$counts[$day]['thu3']++;
			}
		}

		// Luôn thêm các Thứ Ba trong tháng (mẫu Excel); thêm Thứ Tư nếu đã có lịch/tag
		$cursor = strtotime($range['from']);
		$end = strtotime($range['to']);
		while ($cursor <= $end) {
			$w = (int) date('w', $cursor);
			$day = date('Y-m-d', $cursor);
			if ($w === 2) { // Tuesday
				if (!isset($counts[$day])) {
					$counts[$day] = array('meeting' => 0, 'any' => 0, 'thu3' => 1);
				}
			}
			$cursor = strtotime('+1 day', $cursor);
		}

		$dates = array();
		foreach ($counts as $day => $c) {
			if ($c['meeting'] > 0 || $c['thu3'] > 0 || $c['any'] > 0) {
				$dates[] = $day;
			}
		}
		$dates = array_values(array_unique($dates));
		sort($dates);
		if (count($dates) > 16) {
			$prio = array();
			$rest = array();
			foreach ($dates as $d) {
				if (!empty($counts[$d]['meeting']) || !empty($counts[$d]['thu3'])) {
					$prio[] = $d;
				} else {
					$rest[] = $d;
				}
			}
			$dates = array_slice(array_merge($prio, $rest), 0, 16);
			sort($dates);
		}
		return $dates;
	}

	protected static function tagsForAppointment(array $ap, array $leadFacts, array $oppFacts) {
		$tags = array();
		if (!empty($ap['lead_id']) && isset($leadFacts[$ap['lead_id']])) {
			$tags = array_merge($tags, $leadFacts[$ap['lead_id']]['tags']);
		}
		if (!empty($ap['opp_id']) && isset($oppFacts[$ap['opp_id']])) {
			$tags = array_merge($tags, $oppFacts[$ap['opp_id']]['tags']);
		}
		return $tags;
	}

	protected static function accumulateStatusFromTags(array &$row, array $tags, array $ap, array $leadFacts, array $oppFacts) {
		if (self::hasAnyTag($tags, array('khong_hoc'))) {
			$row['khong_hoc']++;
		}
		if (self::hasAnyTag($tags, array('xac_nhan_tham_gia'))) {
			$row['confirmed']++;
		}
		if (self::hasAnyTag($tags, array('da_tg_free', 'da_tg_fb1', 'thu_3'))) {
			$row['show']++;
		}
		if (self::hasAnyTag($tags, array('tiem_nang'))) {
			$row['potential']++;
		}
		if (self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'da_ky_quy', 'da_990k'))) {
			$row['closed']++;
		}
		if (self::hasAnyTag($tags, array('da_pcth')) && self::hasAnyTag($tags, array('da_mqbb', 'da_mqbb_da_pcth', 'da_mqbb_chua_pcth'))) {
			$row['pcth_mq']++;
		} elseif (self::hasAnyTag($tags, array('da_pcth', 'pcth'))) {
			$row['pcth']++;
		} elseif (self::hasAnyTag($tags, array('da_mqbb', 'chua_mqbb_chua_pcth', 'chua_mqbb_da_pcth', 'da_mqbb_chua_pcth', 'da_mqbb_da_pcth'))) {
			$row['mq']++;
		}
		if (self::hasAnyTag($tags, array('khong_nghe_may', 'thue_bao'))) {
			$row['knm_ban']++;
			$row['no_call']++;
		}
		if (self::hasAnyTag($tags, array('kh_can_nhac'))) {
			$row['phan_van']++;
			$row['fifty_fifty']++;
		}
		if (self::hasAnyTag($tags, array('moi_lai'))) {
			$row['moi_lai']++;
		}
		if (self::hasAnyTag($tags, array('dang_cham_soc', 'dang_tu_van'))) {
			$row['ngong_cho']++;
		}
		if (self::hasAnyTag($tags, array('chuan_bi_mo'))) {
			$row['chua_xac_dinh']++;
		}
		if (self::hasAnyTag($tags, array('khong_xac_nhan_tham_gia'))) {
			$row['hoc_cho_khac']++;
		}
		if (self::hasAnyTag($tags, array('nhuong_quyen', 'da_ky_quy'))) {
			$row['quan_tam_nq']++;
		}
		if (self::hasAnyTag($tags, array('nguyen_lieu_chuoi'))) {
			$row['quan_tam_nl']++;
		}
		if (self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh')) && self::hasAnyTag($tags, array('nguyen_lieu_chuoi'))) {
			$row['material_bought']++;
		} elseif (self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh'))) {
			// mua nguyên liệu / đăng ký — đã đếm closed
		}
		if (self::hasAnyTag($tags, array('dung_cham_soc', 'ngung_cham_soc', 'ngung_mua', 'khong_mua', 'gia_dinh', 'tham_khao'))) {
			$row['stop_care']++;
		}

		if (!empty($ap['opp_id']) && isset($oppFacts[$ap['opp_id']])) {
			$o = $oppFacts[$ap['opp_id']];
			if (!empty($o['show']) && !self::hasAnyTag($tags, array('da_tg_free', 'da_tg_fb1', 'thu_3'))) {
				$row['show']++;
			}
			if (!empty($o['confirmed']) && !self::hasAnyTag($tags, array('xac_nhan_tham_gia'))) {
				$row['confirmed']++;
			}
			if (!empty($o['closed']) && !self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'da_ky_quy', 'da_990k'))) {
				$row['closed']++;
			}
			if (!empty($o['potential']) && !self::hasAnyTag($tags, array('tiem_nang'))) {
				$row['potential']++;
			}
		}
		if (!empty($ap['lead_id']) && isset($leadFacts[$ap['lead_id']])) {
			$l = $leadFacts[$ap['lead_id']];
			if (empty($l['contacted']) && !self::hasAnyTag($tags, array('khong_nghe_may'))) {
				// không cộng thêm nếu đã knm
			}
		}
	}

	/** Bảng 3 — hàng = tình trạng, cột = ngày học (bỏ hàng summary). */
	protected static function buildStatusMatrix(array $classDays, array $apptFacts, array $leadFacts, array $oppFacts) {
		$dateCols = array();
		foreach ($classDays as $cd) {
			if (!empty($cd['is_summary'])) {
				continue;
			}
			$dateCols[] = array(
				'date' => $cd['date'],
				'label' => date('d/m/Y', strtotime($cd['date'])),
			);
		}

		$rowDefs = self::statusMatrixRowDefs();
		$rows = array();
		foreach ($rowDefs as $def) {
			$cells = array_fill(0, count($dateCols), 0);
			$rows[$def['key']] = array(
				'key' => $def['key'],
				'label' => $def['label'],
				'highlight' => isset($def['highlight']) ? $def['highlight'] : '',
				'cells' => $cells,
			);
		}

		$dateIndex = array();
		foreach ($dateCols as $i => $col) {
			$dateIndex[$col['date']] = $i;
		}

		foreach ($apptFacts as $ap) {
			$day = $ap['day'];
			if (!isset($dateIndex[$day])) {
				continue;
			}
			$idx = $dateIndex[$day];
			$tags = self::tagsForAppointment($ap, $leadFacts, $oppFacts);
			$bucket = self::emptyClassDayCounters();
			$bucket['appointments'] = 1;
			if (!empty($ap['is_reschedule']) || self::hasAnyTag($tags, array('doi_lich'))) {
				$bucket['reschedule'] = 1;
			}
			self::accumulateStatusFromTags($bucket, $tags, $ap, $leadFacts, $oppFacts);

			$map = array(
				'appointments' => 'appointments',
				'reschedule' => 'reschedule',
				'khong_hoc' => 'khong_hoc',
				'potential' => 'potential',
				'fifty_fifty' => 'fifty_fifty',
				'show' => 'show',
				'companion' => 'companion',
				'closed' => 'closed',
				'knm_ban' => 'knm_ban',
				'potential_care' => 'potential',
				'phan_van' => 'phan_van',
				'moi_lai' => 'moi_lai',
				'stop_care' => 'stop_care',
				'chua_xac_dinh' => 'chua_xac_dinh',
				'quan_tam_nq' => 'quan_tam_nq',
				'quan_tam_nl' => 'quan_tam_nl',
				'material_bought' => 'material_bought',
			);
			foreach ($map as $rowKey => $bucketKey) {
				if (!isset($rows[$rowKey])) {
					continue;
				}
				$add = (int) $bucket[$bucketKey];
				if ($add > 0) {
					$rows[$rowKey]['cells'][$idx] += $add;
				}
			}
		}

		return array(
			'columns' => $dateCols,
			'rows' => array_values($rows),
		);
	}

	protected static function statusMatrixRowDefs() {
		return array(
			array('key' => 'appointments', 'label' => 'Số lượng hẹn'),
			array('key' => 'reschedule', 'label' => 'Dời lịch'),
			array('key' => 'khong_hoc', 'label' => 'Không học'),
			array('key' => 'potential', 'label' => 'Khách tiềm năng dự đoán đi học'),
			array('key' => 'fifty_fifty', 'label' => 'Khách 50-50'),
			array('key' => 'show', 'label' => 'Thực tế tham gia', 'highlight' => 'yellow'),
			array('key' => 'companion', 'label' => 'Khách đi cùng', 'highlight' => 'yellow'),
			array('key' => 'closed', 'label' => 'Đã đăng ký học', 'highlight' => 'yellow'),
			array('key' => 'knm_ban', 'label' => 'KNM, Bận gọi sau'),
			array('key' => 'potential_care', 'label' => 'Tiềm năng'),
			array('key' => 'phan_van', 'label' => 'Phân vân'),
			array('key' => 'moi_lai', 'label' => 'Mời lại'),
			array('key' => 'stop_care', 'label' => 'Ngừng chăm sóc (học cho biết, tham khảo, gia đình)'),
			array('key' => 'chua_xac_dinh', 'label' => 'Chưa xác định / lâu mới mở quán'),
			array('key' => 'quan_tam_nq', 'label' => 'Quan tâm nhượng quyền'),
			array('key' => 'quan_tam_nl', 'label' => 'Quan tâm nguyên liệu'),
			array('key' => 'material_bought', 'label' => 'Khách đã mua nguyên liệu', 'highlight' => 'green'),
		);
	}

	protected static function buildSummary(array $range, array $daily, array $dailyTotal, array $classDays) {
		$appts = 0;
		$show = 0;
		$confirmed = 0;
		$closed = 0;
		$potential = 0;
		foreach ($classDays as $cd) {
			if (!empty($cd['is_summary'])) {
				$appts = (int) $cd['appointments'];
				$show = (int) $cd['show'];
				$confirmed = (int) $cd['confirmed'];
				$closed = (int) $cd['closed'];
				$potential = (int) $cd['potential'];
				break;
			}
		}
		$leads = (int) $dailyTotal['total_leads'];
		return array(
			'month_label' => 'THÁNG ' . (int) date('n', strtotime($range['from'])),
			'totals' => $dailyTotal,
			'pct_potential' => self::pct($potential, $leads),
			'pct_show_appt' => self::pct($show, $appts),
			'pct_confirm_appt' => self::pct($confirmed, $appts),
			'pct_close_confirm' => self::pct($closed, $confirmed > 0 ? $confirmed : $show),
			'pct_close_total' => self::pct($closed, $appts),
			'pct_close_lead' => self::pct($closed, $leads),
			'opp_counts' => array(),
		);
	}

	protected static function buildMonthlyRows(array $range, array $leadFacts, array $apptFacts, array $oppFacts) {
		$months = array();
		$cursor = strtotime(date('Y-m-01', strtotime($range['from'])));
		$end = strtotime(date('Y-m-01', strtotime($range['to'])));
		while ($cursor <= $end) {
			$key = date('Y-m', $cursor);
			$months[$key] = array(
				'month' => $key,
				'label' => 'T' . (int) date('n', $cursor) . '/' . date('Y', $cursor),
				'month_num' => (int) date('n', $cursor),
				'year' => (int) date('Y', $cursor),
				'total_leads' => 0,
				'contacted' => 0,
				'appointments' => 0,
				'show' => 0,
				'confirmed' => 0,
				'closed' => 0,
			);
			$cursor = strtotime('+1 month', $cursor);
		}
		foreach ($leadFacts as $lead) {
			$key = substr($lead['created_day'], 0, 7);
			if (!isset($months[$key])) {
				continue;
			}
			$months[$key]['total_leads']++;
			if (!empty($lead['contacted'])) {
				$months[$key]['contacted']++;
			}
			if (!empty($lead['closed'])) {
				$months[$key]['closed']++;
			}
		}
		foreach ($apptFacts as $ap) {
			$key = substr($ap['day'], 0, 7);
			if (!isset($months[$key])) {
				continue;
			}
			$months[$key]['appointments']++;
			$tags = self::tagsForAppointment($ap, $leadFacts, $oppFacts);
			if (self::hasAnyTag($tags, array('da_tg_free', 'da_tg_fb1', 'thu_3'))) {
				$months[$key]['show']++;
			}
			if (self::hasAnyTag($tags, array('xac_nhan_tham_gia'))) {
				$months[$key]['confirmed']++;
			}
			if (self::hasAnyTag($tags, array('mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'da_ky_quy'))) {
				$months[$key]['closed']++;
			}
		}
		$out = array();
		foreach ($months as $row) {
			$appts = (int) $row['appointments'];
			$row['pct_show_appt'] = self::pct($row['show'], $appts);
			$row['pct_confirm_appt'] = self::pct($row['confirmed'], $appts);
			$row['pct_close_appt'] = self::pct($row['closed'], $appts);
			$row['pct_close_lead'] = self::pct($row['closed'], $row['total_leads']);
			$out[] = $row;
		}
		return $out;
	}

	protected static function classifySource(array $tags, $leadsource) {
		$src = mb_strtolower(trim((string) $leadsource), 'UTF-8');
		if (self::hasAnyTag($tags, array('tiktok')) || strpos($src, 'tiktok') !== false) {
			return 'tiktok';
		}
		if (self::hasAnyTag($tags, array('facebook', 'ladipage_fb', 'website', 'zalo', 'hotline', 'other_source', 'other'))) {
			return 'n_khoa';
		}
		if ($src !== '') {
			if (strpos($src, 'tiktok') !== false) {
				return 'tiktok';
			}
			// Leadsource vtiger thường map Facebook/Web/Other → kênh N.Khoa
			return 'n_khoa';
		}
		return '';
	}

	protected static function weekdayVi($ymd) {
		$n = (int) date('w', strtotime($ymd));
		$map = array('Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy');
		return isset($map[$n]) ? $map[$n] : '';
	}

	protected static function loadTagsForRecords(PearDatabase $db, array $ids, $moduleName) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) {
			return array();
		}
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$params = $ids;
		$params[] = $moduleName;
		$sql = "SELECT fo.object_id, t.tag
			FROM vtiger_freetagged_objects fo
			INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			WHERE fo.object_id IN ($placeholders) AND fo.module = ?";
		$rs = $db->pquery($sql, $params);
		$map = array();
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$id = (int) $row['object_id'];
			$tag = self::normalizeTag((string) $row['tag']);
			if ($tag === '') {
				continue;
			}
			if (!isset($map[$id])) {
				$map[$id] = array();
			}
			$map[$id][] = $tag;
		}
		return $map;
	}

	protected static function normalizeTag($tag) {
		$s = trim(decode_html((string) $tag));
		if ($s === '') {
			return '';
		}
		if (isset($s[0]) && $s[0] === '#') {
			$s = substr($s, 1);
		}
		$s = mb_strtolower($s, 'UTF-8');
		$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
		if (function_exists('transliterator_transliterate')) {
			$s = @transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
		}
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		return trim($s, '_');
	}

	protected static function hasCallTag(array $tags) {
		foreach ($tags as $t) {
			if (strpos($t, 'goi_lan_') === 0) {
				return true;
			}
		}
		return false;
	}

	protected static function hasAnyTag(array $tags, array $needles) {
		$set = array_flip($tags);
		foreach ($needles as $n) {
			$n = self::normalizeTag($n);
			if ($n !== '' && isset($set[$n])) {
				return true;
			}
		}
		return false;
	}

	protected static function regionFromDistrict($district) {
		$d = trim(decode_html((string) $district));
		if ($d === '') {
			return '';
		}
		if (preg_match('/^khu\s*vực\s*([1234])$/iu', $d, $m) || preg_match('/^kv([1234])$/i', $d, $m)) {
			return 'KV' . $m[1];
		}
		return '';
	}

	protected static function regionFromTags(array $tags, $fallback) {
		foreach (array('kv1' => 'KV1', 'kv2' => 'KV2', 'kv3' => 'KV3', 'kv4' => 'KV4') as $key => $label) {
			if (in_array($key, $tags, true)) {
				return $label;
			}
		}
		return $fallback;
	}

	protected static function pct($num, $den) {
		$num = (float) $num;
		$den = (float) $den;
		if ($den <= 0) {
			return 0.0;
		}
		return round(($num / $den) * 100, 2);
	}

	protected static function decode($value) {
		$value = trim(decode_html((string) $value));
		if (function_exists('html_entity_decode')) {
			$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return $value;
	}
}
