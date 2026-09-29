<?php
/*+***********************************************************************************
 * Admin KPI Dashboard — aggregate queries for summary + detail panels.
 *************************************************************************************/

class Home_AdminKpiService {

	/**
	 * Doanh thu từ Đơn hàng: mọi SO còn hiệu lực (giống tổng list Đặt hàng).
	 * Chỉ loại đơn huỷ / từ chối.
	 */
	const SO_EXCLUDED_STATUSES = array(
		'Cancelled', 'Rejected', 'Đã hủy', 'Đã huỷ', 'Từ chối', 'cancelled', 'rejected',
	);

	/**
	 * Decode HTML entities from DB (tránh Kh&aacute;c trên UI).
	 * @param mixed $value
	 * @return string
	 */
	public static function decodeText($value) {
		$text = trim((string) $value);
		if ($text === '') {
			return '';
		}
		if (function_exists('decode_html')) {
			$text = decode_html($text);
		}
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		// Second pass if double-encoded
		if (strpos($text, '&') !== false) {
			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return trim($text);
	}

	/**
	 * Đưa trạng thái DB (EN / snake_case) sang nhãn tiếng Việt trên dashboard.
	 * @param mixed $value
	 * @param string $module SalesOrder|Quotes|Leads|ServiceContracts
	 * @return string
	 */
	public static function translateStatus($value, $module = 'SalesOrder') {
		$text = self::decodeText($value);
		if ($text === '') {
			return '—';
		}
		$map = self::statusLabelMap($module);
		if (isset($map[$text])) {
			return $map[$text];
		}
		$lower = strtolower($text);
		foreach ($map as $k => $v) {
			if (strtolower((string) $k) === $lower) {
				return $v;
			}
		}
		return $text;
	}

	/**
	 * @param string $module
	 * @return array<string,string>
	 */
	protected static function statusLabelMap($module) {
		static $maps = null;
		if ($maps === null) {
			$maps = array(
				'SalesOrder' => array(
					'Created' => 'Phiếu tạm',
					'Draft' => 'Nháp',
					'Approved' => 'Đã xác nhận',
					'Delivered' => 'Hoàn thành',
					'Cancelled' => 'Đã hủy',
					'Canceled' => 'Đã hủy',
					'Pending' => 'Đang chờ',
					'Paid' => 'Đã thanh toán',
					'Sent' => 'Đã gửi',
					'Rejected' => 'Từ chối',
					'waiting_print' => 'Chờ soạn',
					'picking' => 'Đang soạn',
					'packed' => 'Đã soạn',
					'shipped' => 'Đã giao',
					'rejected' => 'Từ chối',
					'Đã duyệt' => 'Đã xác nhận',
					'Đã tạo' => 'Phiếu tạm',
					'Đang chờ xử lý' => 'Đang chờ',
					'Đang giao hàng' => 'Đang giao hàng',
					'Hoàn thành' => 'Hoàn thành',
					'Đã gửi' => 'Đã gửi',
					'Đã thanh toán' => 'Đã thanh toán',
					'Đã hủy' => 'Đã hủy',
					'Đã huỷ' => 'Đã hủy',
					'Từ chối' => 'Từ chối',
					'Chờ soạn' => 'Chờ soạn',
					'Đang soạn' => 'Đang soạn',
					'Đã soạn' => 'Đã soạn',
					'Đã giao' => 'Đã giao',
					'Phiếu tạm' => 'Phiếu tạm',
					'Nháp' => 'Nháp',
					'Đã xác nhận' => 'Đã xác nhận',
				),
				'Quotes' => array(
					'Created' => 'Nháp',
					'Draft' => 'Nháp',
					'Nháp' => 'Nháp',
					'Đã tạo' => 'Nháp',
					'Sent' => 'Báo giá',
					'Delivered' => 'Báo giá',
					'Báo giá' => 'Báo giá',
					'Accepted' => 'Chấp nhận',
					'Confirmed' => 'Xác nhận',
					'Xác nhận' => 'Xác nhận',
					'Chấp nhận' => 'Chấp nhận',
					'Rejected' => 'Đã huỷ',
					'Cancelled' => 'Đã huỷ',
					'Canceled' => 'Đã huỷ',
					'Huỷ' => 'Đã huỷ',
					'Hủy' => 'Đã huỷ',
					'Đã từ chối' => 'Đã huỷ',
					'Đã huỷ' => 'Đã huỷ',
					'Đã hủy' => 'Đã huỷ',
				),
				'Leads' => array(
					'Cold' => 'Chưa quan tâm',
					'Warm' => 'Quan tâm',
					'Hot' => 'Rất quan tâm',
					'Contacted' => 'Đã liên hệ',
					'Not Contacted' => 'Không liên hệ được',
					'Junk Lead' => 'Không hợp lệ',
					'Lost Lead' => 'Đã mất',
					'Attempted to Contact' => 'Đã thử liên hệ',
					'Pre Qualified' => 'Đủ điều kiện sơ bộ',
					'Qualified' => 'Đủ điều kiện',
					'New' => 'Mới',
				),
				'ServiceContracts' => array(
					'In Progress' => 'Đang thực hiện',
					'Complete' => 'Hoàn thành',
					'Completed' => 'Hoàn thành',
					'Planned' => 'Đã lên kế hoạch',
					'On Hold' => 'Tạm dừng',
					'Archived' => 'Lưu trữ',
					'Inactive' => 'Ngưng hoạt động',
					'Active' => 'Đang hiệu lực',
				),
			);
		}
		return isset($maps[$module]) ? $maps[$module] : $maps['SalesOrder'];
	}

	protected static function soNotCancelledSql($alias = 'so') {
		$statuses = self::SO_EXCLUDED_STATUSES;
		$ph = implode(',', array_fill(0, count($statuses), '?'));
		return array(
			"($alias.sostatus IS NULL OR $alias.sostatus = '' OR $alias.sostatus NOT IN ($ph))",
			$statuses,
		);
	}

	/**
	 * Năm/tháng nghiệp vụ theo đơn hàng mới nhất (data test có thể lệch năm hệ thống).
	 * @return array{year:int,month:int,month_start:string,month_end:string,today_end:string}
	 */
	protected static function businessCalendar(PearDatabase $db) {
		$year = (int) date('Y');
		$month = (int) date('n');
		$r = $db->pquery(
			'SELECT MAX(ce.createdtime) AS mx FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0',
			array()
		);
		if ($r && $db->num_rows($r)) {
			$mx = (string) $db->query_result($r, 0, 'mx');
			if ($mx !== '' && $mx !== '0000-00-00 00:00:00') {
				$ts = strtotime($mx);
				if ($ts) {
					$year = (int) date('Y', $ts);
					$month = (int) date('n', $ts);
				}
			}
		}
		$monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
		$monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));
		return array(
			'year' => $year,
			'month' => $month,
			'month_start' => $monthStart,
			'month_end' => $monthEnd,
			'today_end' => $monthEnd,
		);
	}

	/**
	 * Six top-card numbers.
	 * @return array
	 */
	public static function getSummary() {
		$db = PearDatabase::getInstance();
		$cal = self::businessCalendar($db);
		$todayStart = date('Y-m-d 00:00:00');
		$todayEnd = date('Y-m-d 23:59:59');

		return array(
			'customers' => self::countContacts($db),
			'leads_today' => self::countLeadsCreatedBetween($db, $todayStart, $todayEnd),
			'revenue_month' => self::sumSoRevenueBetween($db, $cal['month_start'], $cal['month_end']),
			'quotes_pending' => self::countQuotesPending($db),
			'orders_processing' => self::countOrdersProcessing($db),
			'franchise_contracts' => self::countServiceContracts($db),
			'business_year' => $cal['year'],
			'business_month' => $cal['month'],
		);
	}

	/**
	 * @param string $section customers|leads|revenue|quotes|orders|franchise
	 * @param array $opts
	 * @return array
	 */
	public static function getDetail($section, array $opts = array()) {
		$section = strtolower(trim((string) $section));
		switch ($section) {
			case 'customers':
				return self::detailCustomers();
			case 'leads':
				return self::detailLeads();
			case 'revenue':
				return self::detailRevenue($opts);
			case 'quotes':
				return self::detailQuotes();
			case 'orders':
				return self::detailOrders();
			case 'franchise':
				return array(
					'ready' => false,
					'message' => 'Chi tiết nhượng quyền đang được cập nhật.',
					'total' => self::countServiceContracts(PearDatabase::getInstance()),
				);
			default:
				throw new Exception('Phần chi tiết không hợp lệ');
		}
	}

	// ─── Summary helpers ───────────────────────────────────────────────

	protected static function countContacts(PearDatabase $db) {
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0',
			array()
		);
		return (int) $db->query_result($r, 0, 'c');
	}

	protected static function countLeadsCreatedBetween(PearDatabase $db, $from, $to) {
		// Module Leads = "Khách hàng tiềm năng"; count all non-converted alive leads in range.
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 WHERE (ld.converted = 0 OR ld.converted IS NULL OR ld.converted = \'0\')
			 AND ce.createdtime >= ? AND ce.createdtime <= ?',
			array($from, $to)
		);
		return $r ? (int) $db->query_result($r, 0, 'c') : 0;
	}

	protected static function sumSoRevenueBetween(PearDatabase $db, $from, $to) {
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT COALESCE(SUM(so.total), 0) AS s FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?",
			$params
		);
		return $r ? (float) $db->query_result($r, 0, 's') : 0.0;
	}

	protected static function countQuotesPending(PearDatabase $db) {
		// Đang chờ = Nháp (chưa gửi / chưa huỷ)
		$pending = array('Nháp', 'Created', 'Draft', 'Đã tạo');
		$ph = implode(',', array_fill(0, count($pending), '?'));
		$r = $db->pquery(
			"SELECT COUNT(*) AS c FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
			 WHERE q.quotestage IN ($ph)",
			$pending
		);
		return $r ? (int) $db->query_result($r, 0, 'c') : 0;
	}

	protected static function countOrdersProcessing(PearDatabase $db) {
		$done = array('Delivered', 'Cancelled', 'Rejected', 'Hoàn thành', 'Đã hủy', 'Từ chối', 'shipped');
		$ph = implode(',', array_fill(0, count($done), '?'));
		$r = $db->pquery(
			"SELECT COUNT(*) AS c FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.sostatus NOT IN ($ph) OR so.sostatus IS NULL OR so.sostatus = ''",
			$done
		);
		return (int) $db->query_result($r, 0, 'c');
	}

	protected static function countServiceContracts(PearDatabase $db) {
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0',
			array()
		);
		return (int) $db->query_result($r, 0, 'c');
	}

	// ─── Customers detail ──────────────────────────────────────────────

	protected static function detailCustomers() {
		$db = PearDatabase::getInstance();
		$monthStart = date('Y-m-01 00:00:00');
		$now = date('Y-m-d 23:59:59');
		$total = self::countContacts($db);

		$rNew = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			 WHERE ce.createdtime >= ? AND ce.createdtime <= ?',
			array($monthStart, $now)
		);
		$newMonth = (int) $db->query_result($rNew, 0, 'c');

		// Active ≈ tagged đang chăm sóc OR mua ổn định OR modified in last 90 days
		$active = 0;
		$rAct = $db->pquery(
			"SELECT COUNT(DISTINCT cd.contactid) AS c FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			 LEFT JOIN vtiger_freetagged_objects fo ON fo.object_id = cd.contactid AND fo.module = 'Contacts'
			 LEFT JOIN vtiger_freetags ft ON ft.id = fo.tag_id
			 WHERE ce.modifiedtime >= DATE_SUB(NOW(), INTERVAL 90 DAY)
			    OR LOWER(REPLACE(REPLACE(ft.tag, ' ', '_'), 'đ', 'd')) IN ('dang_cham_soc', 'mua_on_dinh')",
			array()
		);
		if ($rAct) {
			$active = (int) $db->query_result($rAct, 0, 'c');
		}

		$tiers = self::countContactTiers($db);
		$top = self::topCustomersByRevenue($db, 5);

		return array(
			'total' => $total,
			'new_month' => $newMonth,
			'active' => $active,
			'tiers' => $tiers,
			'top_customers' => $top,
		);
	}

	protected static function countContactTiers(PearDatabase $db) {
		$out = array('gold' => 0, 'silver' => 0, 'bronze' => 0);
		$r = $db->pquery(
			"SELECT LOWER(ft.tag) AS tag, COUNT(DISTINCT fo.object_id) AS c
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags ft ON ft.id = fo.tag_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = fo.object_id AND ce.deleted = 0
			 WHERE fo.module = 'Contacts'
			 GROUP BY LOWER(ft.tag)",
			array()
		);
		if (!$r) {
			return $out;
		}
		while ($row = $db->fetchByAssoc($r)) {
			$key = self::normalizeTierTag((string) $row['tag']);
			if ($key && isset($out[$key])) {
				$out[$key] += (int) $row['c'];
			}
		}
		return $out;
	}

	protected static function normalizeTierTag($tag) {
		$t = strtolower(trim($tag));
		$t = str_replace(array(' ', '-'), '_', $t);
		$map = array(
			'vang' => 'gold', 'vàng' => 'gold', 'gold' => 'gold',
			'bac' => 'silver', 'bạc' => 'silver', 'silver' => 'silver',
			'dong' => 'bronze', 'đồng' => 'bronze', 'bronze' => 'bronze',
		);
		return isset($map[$t]) ? $map[$t] : null;
	}

	protected static function topCustomersByRevenue(PearDatabase $db, $limit = 10) {
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = $excluded;
		$params[] = (int) $limit;
		$r = $db->pquery(
			"SELECT cd.contactid AS id,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS name,
				COALESCE(SUM(so.total), 0) AS revenue
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 INNER JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 INNER JOIN vtiger_crmentity ce2 ON ce2.crmid = cd.contactid AND ce2.deleted = 0
			 WHERE $notCancelSql AND so.contactid > 0
			 GROUP BY cd.contactid, cd.firstname, cd.lastname
			 ORDER BY revenue DESC
			 LIMIT ?",
			$params
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$name = trim((string) $row['name']);
				$rows[] = array(
					'id' => (int) $row['id'],
					'name' => self::decodeText($name !== '' ? $name : ('#' . $row['id'])),
					'revenue' => (float) $row['revenue'],
				);
			}
		}
		return $rows;
	}

	// ─── Leads detail ──────────────────────────────────────────────────

	protected static function detailLeads() {
		$db = PearDatabase::getInstance();
		$todayStart = date('Y-m-d 00:00:00');
		$todayEnd = date('Y-m-d 23:59:59');
		$weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
		$monthStart = date('Y-m-01 00:00:00');

		$today = self::countLeadsCreatedBetween($db, $todayStart, $todayEnd);
		$week = self::countLeadsCreatedBetween($db, $weekStart, $todayEnd);
		$month = self::countLeadsCreatedBetween($db, $monthStart, $todayEnd);

		$sources = self::leadSourceBreakdown($db);
		$topSales = self::leadsByOwner($db, 8);
		$urgency = self::leadUrgency($db);

		return array(
			'today' => $today,
			'week' => $week,
			'month' => $month,
			'sources' => $sources,
			'top_sales' => $topSales,
			'not_contacted' => $urgency['not_contacted'],
			'over_24h' => $urgency['over_24h'],
			'over_72h' => $urgency['over_72h'],
		);
	}

	protected static function leadBaseFrom(PearDatabase $db) {
		$joinProfile = '';
		if (self::tableExists($db, 'bace_lead_profile')) {
			$joinProfile = ' LEFT JOIN bace_lead_profile p ON p.leadid = ld.leadid ';
		}
		return "FROM vtiger_leaddetails ld
			INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			$joinProfile
			WHERE (ld.converted = 0 OR ld.converted IS NULL OR ld.converted = '0')";
	}

	protected static function leadSourceBreakdown(PearDatabase $db) {
		$base = self::leadBaseFrom($db);
		$r = $db->pquery("SELECT COALESCE(NULLIF(TRIM(ld.leadsource), ''), 'Other') AS src, COUNT(*) AS c $base GROUP BY src", array());
		$buckets = array(
			'Facebook' => 0,
			'Website' => 0,
			'Zalo' => 0,
			'Tiktok' => 0,
			'Khác' => 0,
		);
		$total = 0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$src = (string) $row['src'];
				$c = (int) $row['c'];
				$total += $c;
				$key = self::mapLeadSourceBucket($src);
				$buckets[$key] += $c;
			}
		}
		$pct = array();
		foreach ($buckets as $k => $c) {
			$pct[] = array(
				'label' => $k,
				'count' => $c,
				'percent' => $total > 0 ? round(($c / $total) * 100, 1) : 0,
			);
		}
		return array('total' => $total, 'items' => $pct);
	}

	protected static function mapLeadSourceBucket($src) {
		$s = strtolower(trim($src));
		if (strpos($s, 'facebook') !== false || $s === 'fb') {
			return 'Facebook';
		}
		if (strpos($s, 'web') !== false || strpos($s, 'website') !== false) {
			return 'Website';
		}
		if (strpos($s, 'zalo') !== false) {
			return 'Zalo';
		}
		if (strpos($s, 'tiktok') !== false || strpos($s, 'tik tok') !== false) {
			return 'Tiktok';
		}
		return 'Khác';
	}

	protected static function leadsByOwner(PearDatabase $db, $limit = 8) {
		$base = self::leadBaseFrom($db);
		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COUNT(*) AS c
			 $base
			 GROUP BY ce.smownerid
			 ORDER BY c DESC
			 LIMIT " . (int) $limit,
			array()
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$uid = (int) $row['uid'];
				$rows[] = array(
					'id' => $uid,
					'name' => self::userDisplayName($db, $uid),
					'count' => (int) $row['c'],
				);
			}
		}
		return $rows;
	}

	protected static function leadUrgency(PearDatabase $db) {
		$out = array('not_contacted' => 0, 'over_24h' => 0, 'over_72h' => 0);
		$base = self::leadBaseFrom($db);
		$hasCalls = self::tableExists($db, 'bace_lead_last_touch_call');

		if ($hasCalls) {
			// Chưa liên hệ: lead chưa có cuộc gọi Last Touch nào
			$r = $db->pquery(
				"SELECT COUNT(*) AS c $base
				 AND NOT EXISTS (
					SELECT 1 FROM bace_lead_last_touch_call c
					WHERE c.leadid = ld.leadid
				 )",
				array()
			);
			$out['not_contacted'] = $r ? (int) $db->query_result($r, 0, 'c') : 0;

			// Cuộc gọi gần nhất = Không nghe máy, chưa gọi lại quá 24h / 72h
			$missedSql = "SELECT COUNT(*) AS c $base
				 AND EXISTS (
					SELECT 1 FROM bace_lead_last_touch_call lastc
					WHERE lastc.leadid = ld.leadid
					AND lastc.called_at = (
						SELECT MAX(c2.called_at) FROM bace_lead_last_touch_call c2 WHERE c2.leadid = ld.leadid
					)
					AND lastc.result_label = ?
					AND lastc.called_at IS NOT NULL
					AND lastc.called_at != ''
					AND lastc.called_at != '0000-00-00 00:00:00'
					AND lastc.called_at < DATE_SUB(NOW(), INTERVAL %d HOUR)
				 )";

			$r24 = $db->pquery(sprintf($missedSql, 24), array('Không nghe máy'));
			$out['over_24h'] = $r24 ? (int) $db->query_result($r24, 0, 'c') : 0;

			$r72 = $db->pquery(sprintf($missedSql, 72), array('Không nghe máy'));
			$out['over_72h'] = $r72 ? (int) $db->query_result($r72, 0, 'c') : 0;

			return $out;
		}

		// Fallback: không có bảng cuộc gọi → dựa last_touch / tuổi lead
		$hasProfile = self::tableExists($db, 'bace_lead_profile');
		if ($hasProfile) {
			$r = $db->pquery(
				"SELECT COUNT(*) AS c $base
				 AND (p.last_touch IS NULL OR p.last_touch = '' OR p.last_touch = '0000-00-00 00:00:00')",
				array()
			);
			$out['not_contacted'] = $r ? (int) $db->query_result($r, 0, 'c') : 0;

			$r24 = $db->pquery(
				"SELECT COUNT(*) AS c $base
				 AND p.last_touch IS NOT NULL AND p.last_touch != '' AND p.last_touch != '0000-00-00 00:00:00'
				 AND p.last_touch < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
				array()
			);
			$out['over_24h'] = $r24 ? (int) $db->query_result($r24, 0, 'c') : 0;

			$r72 = $db->pquery(
				"SELECT COUNT(*) AS c $base
				 AND p.last_touch IS NOT NULL AND p.last_touch != '' AND p.last_touch != '0000-00-00 00:00:00'
				 AND p.last_touch < DATE_SUB(NOW(), INTERVAL 72 HOUR)",
				array()
			);
			$out['over_72h'] = $r72 ? (int) $db->query_result($r72, 0, 'c') : 0;
		} else {
			$r = $db->pquery(
				"SELECT COUNT(*) AS c $base AND ce.createdtime < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
				array()
			);
			$out['not_contacted'] = $r ? (int) $db->query_result($r, 0, 'c') : 0;
			$out['over_24h'] = $out['not_contacted'];
		}
		return $out;
	}

	// ─── Revenue detail (3 branches) ───────────────────────────────────

	protected static function detailRevenue(array $opts) {
		$mode = isset($opts['mode']) ? strtolower((string) $opts['mode']) : 'total';
		$saleId = isset($opts['sale_id']) ? (int) $opts['sale_id'] : 0;
		$period = isset($opts['period']) ? strtolower((string) $opts['period']) : 'month';

		if ($mode === 'product') {
			return self::revenueByProduct($period);
		}
		if ($mode === 'sale') {
			if ($saleId > 0) {
				return self::revenueSaleDetail($saleId, $period);
			}
			$full = !empty($opts['full']);
			return self::revenueBySale($period, $full);
		}
		// total — 4 periods (tháng/năm theo lịch nghiệp vụ từ đơn hàng)
		$db = PearDatabase::getInstance();
		$cal = self::businessCalendar($db);
		$now = date('Y-m-d 23:59:59');
		return array(
			'mode' => 'total',
			'today' => self::sumSoRevenueBetween($db, date('Y-m-d 00:00:00'), $now),
			'week' => self::sumSoRevenueBetween($db, date('Y-m-d 00:00:00', strtotime('monday this week')), $now),
			'month' => self::sumSoRevenueBetween($db, $cal['month_start'], $cal['month_end']),
			'year' => self::sumSoRevenueBetween($db, $cal['year'] . '-01-01 00:00:00', $cal['year'] . '-12-31 23:59:59'),
		);
	}

	protected static function periodBounds($period) {
		$db = PearDatabase::getInstance();
		$cal = self::businessCalendar($db);
		$now = date('Y-m-d 23:59:59');
		switch ($period) {
			case 'today':
				return array(date('Y-m-d 00:00:00'), $now);
			case 'week':
				return array(date('Y-m-d 00:00:00', strtotime('monday this week')), $now);
			case 'year':
				return array($cal['year'] . '-01-01 00:00:00', $cal['year'] . '-12-31 23:59:59');
			case 'month':
			default:
				return array($cal['month_start'], $cal['month_end']);
		}
	}

	protected static function revenueByProduct($period = 'month') {
		$db = PearDatabase::getInstance();
		list($from, $to) = self::periodBounds($period);
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));

		$r = $db->pquery(
			"SELECT ip.productid,
				COALESCE(pr.productname, sv.servicename, '') AS pname,
				COALESCE(pr.productcategory, '') AS pcat,
				COALESCE(sv.servicecategory, '') AS scat,
				SUM(ip.quantity * ip.listprice) AS amount
			 FROM vtiger_inventoryproductrel ip
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = ip.id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_products pr ON pr.productid = ip.productid
			 LEFT JOIN vtiger_service sv ON sv.serviceid = ip.productid
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY ip.productid, COALESCE(pr.productname, sv.servicename, ''),
			          COALESCE(pr.productcategory, ''), COALESCE(sv.servicecategory, '')",
			$params
		);

		$groups = array('course' => 0.0, 'ingredient' => 0.0, 'franchise' => 0.0);
		$total = 0.0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$amt = (float) $row['amount'];
				$total += $amt;
				$bucket = self::classifyProductBucket(
					(string) $row['pname'],
					(string) $row['pcat'],
					(string) $row['scat']
				);
				$groups[$bucket] += $amt;
			}
		}

		$labels = array(
			'course' => 'Khóa học',
			'ingredient' => 'Nguyên liệu',
			'franchise' => 'Nhượng quyền',
		);
		$items = array();
		foreach ($labels as $k => $label) {
			$amt = $groups[$k];
			$items[] = array(
				'key' => $k,
				'label' => $label,
				'amount' => $amt,
				'percent' => $total > 0 ? round(($amt / $total) * 100, 1) : 0,
			);
		}
		return array(
			'mode' => 'product',
			'period' => $period,
			'total' => $total,
			'items' => $items,
		);
	}

	protected static function classifyProductBucket($name, $productCat, $serviceCat) {
		$blob = strtolower($name . ' ' . $productCat . ' ' . $serviceCat);
		if (preg_match('/nhượng|nhuong|franchise|quyền|quyen|aff/', $blob)) {
			return 'franchise';
		}
		if (preg_match('/khóa|khoa hoc|học|hoc |training|course|lớp|lop |pcth/', $blob)
			|| stripos($serviceCat, 'Training') !== false) {
			return 'course';
		}
		return 'ingredient';
	}

	protected static function revenueBySale($period = 'month', $full = false) {
		$db = PearDatabase::getInstance();
		list($from, $to) = self::periodBounds($period);
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$limitSql = $full ? '' : ' LIMIT 4';

		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COALESCE(SUM(so.total), 0) AS revenue, COUNT(*) AS orders
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY ce.smownerid
			 ORDER BY revenue DESC
			 $limitSql",
			$params
		);
		$items = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$uid = (int) $row['uid'];
				$items[] = array(
					'id' => $uid,
					'name' => self::userDisplayName($db, $uid),
					'revenue' => (float) $row['revenue'],
					'orders' => (int) $row['orders'],
				);
			}
		}
		return array(
			'mode' => 'sale',
			'period' => $period,
			'full' => (bool) $full,
			'items' => $items,
		);
	}

	protected static function revenueSaleDetail($saleId, $period = 'month') {
		$db = PearDatabase::getInstance();
		list($from, $to) = self::periodBounds($period);
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to, $saleId));

		$r = $db->pquery(
			"SELECT COALESCE(SUM(so.total), 0) AS revenue, COUNT(*) AS orders
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 AND ce.smownerid = ?",
			$params
		);
		$revenue = (float) $db->query_result($r, 0, 'revenue');
		$orders = (int) $db->query_result($r, 0, 'orders');

		$r2 = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus, ce.createdtime
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 AND ce.smownerid = ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 20",
			$params
		);
		$recent = array();
		if ($r2) {
			while ($row = $db->fetchByAssoc($r2)) {
				$recent[] = array(
					'id' => (int) $row['id'],
					'no' => (string) $row['no'],
					'total' => (float) $row['total'],
					'status' => self::translateStatus($row['sostatus'], 'SalesOrder'),
					'created' => (string) $row['createdtime'],
				);
			}
		}

		return array(
			'mode' => 'sale_detail',
			'period' => $period,
			'sale' => array(
				'id' => $saleId,
				'name' => self::userDisplayName($db, $saleId),
				'revenue' => $revenue,
				'orders' => $orders,
			),
			'recent_orders' => $recent,
		);
	}

	// ─── Quotes detail ─────────────────────────────────────────────────

	protected static function detailQuotes() {
		$db = PearDatabase::getInstance();
		// 3 trạng thái nghiệp vụ: Nháp | Báo giá | Đã huỷ
		$groups = array(
			'draft' => array('Nháp', 'Created', 'Draft', 'Đã tạo'),
			'quoted' => array('Sent', 'Báo giá', 'Delivered', 'Xác nhận', 'Accepted', 'Confirmed', 'Chấp nhận'),
			'cancelled' => array('Rejected', 'Huỷ', 'Hủy', 'Cancelled', 'Đã từ chối', 'Đã huỷ', 'Đã hủy'),
		);
		$counts = array();
		foreach ($groups as $key => $vals) {
			$ph = implode(',', array_fill(0, count($vals), '?'));
			$r = $db->pquery(
				"SELECT COUNT(*) AS c FROM vtiger_quotes q
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
				 WHERE q.quotestage IN ($ph)",
				$vals
			);
			$counts[$key] = $r ? (int) $db->query_result($r, 0, 'c') : 0;
		}

		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COUNT(*) AS c
			 FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
			 GROUP BY ce.smownerid
			 ORDER BY c DESC
			 LIMIT 20",
			array()
		);
		$bySale = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$uid = (int) $row['uid'];
				$bySale[] = array(
					'id' => $uid,
					'name' => self::userDisplayName($db, $uid),
					'count' => (int) $row['c'],
				);
			}
		}

		return array(
			'status' => array(
				array(
					'key' => 'draft',
					'label' => 'Nháp',
					'count' => $counts['draft'],
					'drill' => array('type' => 'quotes_status', 'key' => 'draft'),
				),
				array(
					'key' => 'quoted',
					'label' => 'Báo giá',
					'count' => $counts['quoted'],
					'drill' => array('type' => 'quotes_status', 'key' => 'quoted'),
				),
				array(
					'key' => 'cancelled',
					'label' => 'Đã huỷ',
					'count' => $counts['cancelled'],
					'drill' => array('type' => 'quotes_status', 'key' => 'cancelled'),
				),
			),
			'by_sale' => $bySale,
		);
	}

	// ─── Orders detail ─────────────────────────────────────────────────

	protected static function detailOrders() {
		$db = PearDatabase::getInstance();
		// Theo trạng thái (bán hàng): không lẫn trạng thái kho (chờ soạn / đã soạn)
		$statusMap = array(
			'draft' => array('Created', 'Nháp', 'Phiếu tạm', 'Draft'),
			'pending' => array('Pending', 'Sent', 'Chờ xác nhận', 'Đang chờ'),
			'delivered' => array('Delivered', 'Hoàn thành', 'shipped', 'Đã giao'),
		);
		$labels = array(
			'draft' => 'Nháp',
			'pending' => 'Chờ xác nhận',
			'delivered' => 'Đã giao',
		);
		$statusOut = array();
		foreach ($statusMap as $key => $vals) {
			$ph = implode(',', array_fill(0, count($vals), '?'));
			$r = $db->pquery(
				"SELECT COUNT(*) AS c FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE so.sostatus IN ($ph)",
				$vals
			);
			$statusOut[] = array(
				'key' => $key,
				'label' => $labels[$key],
				'count' => $r ? (int) $db->query_result($r, 0, 'c') : 0,
				'drill' => array('type' => 'orders_status', 'key' => $key),
			);
		}

		$warehouse = array();
		$whLabels = array(
			'waiting' => 'Đang chờ soạn',
			'packed' => 'Đã soạn',
			'delivered' => 'Đã giao',
		);
		if (self::tableExists($db, 'vtiger_goodsissue')) {
			$whMap = array(
				'waiting' => array('waiting_print', 'picking'),
				'packed' => array('packed', 'prepared'),
				'delivered' => array('shipped', 'completed'),
			);
			foreach ($whMap as $key => $vals) {
				$ph = implode(',', array_fill(0, count($vals), '?'));
				// Đếm theo đơn hàng có phiếu xuất (khớp danh sách drill), bỏ GI orphan
				$r = $db->pquery(
					"SELECT COUNT(DISTINCT gi.salesorder_id) AS c
					 FROM vtiger_goodsissue gi
					 INNER JOIN vtiger_salesorder so ON so.salesorderid = gi.salesorder_id
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
					 WHERE gi.deleted = 0
					   AND gi.salesorder_id IS NOT NULL AND gi.salesorder_id > 0
					   AND gi.status IN ($ph)",
					$vals
				);
				$warehouse[] = array(
					'key' => $key,
					'label' => $whLabels[$key],
					'count' => $r ? (int) $db->query_result($r, 0, 'c') : 0,
					'drill' => array('type' => 'orders_warehouse', 'key' => $key),
				);
			}
		} else {
			$whFromSo = array(
				'waiting' => array('waiting_print', 'picking', 'Approved', 'Đã xác nhận'),
				'packed' => array('packed', 'Đã soạn'),
				'delivered' => array('shipped', 'Delivered', 'Đã giao', 'Hoàn thành'),
			);
			foreach ($whFromSo as $key => $vals) {
				$ph = implode(',', array_fill(0, count($vals), '?'));
				$r = $db->pquery(
					"SELECT COUNT(*) AS c FROM vtiger_salesorder so
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
					 WHERE so.sostatus IN ($ph)",
					$vals
				);
				$warehouse[] = array(
					'key' => $key,
					'label' => $whLabels[$key],
					'count' => $r ? (int) $db->query_result($r, 0, 'c') : 0,
					'drill' => array('type' => 'orders_warehouse', 'key' => $key),
				);
			}
		}

		return array(
			'status' => $statusOut,
			'warehouse' => $warehouse,
		);
	}

	// ─── Stage 2–5 widgets ─────────────────────────────────────────────

	/**
	 * Bundle for dashboard stages below KPI cards.
	 * @param array $chartOpts
	 * @return array
	 */
	public static function getWidgets(array $chartOpts = array()) {
		return array(
			'funnel' => self::getSalesFunnel(),
			'revenue_chart' => self::getRevenueChart($chartOpts),
			'performance' => self::getPerformance(),
			'alerts' => self::getAlerts(),
			'offline_gd11' => self::getOfflineGd11(),
			'online_gd12' => self::getOnlineGd12(),
			'gd14' => self::getGd14(),
		);
	}

	/**
	 * GD 1.1: tỷ lệ và trạng thái của hồ sơ tạo trong tháng này. Bấm số ra đúng danh sách.
	 * @return array
	 */
	public static function getOfflineGd11() {
		$db = PearDatabase::getInstance();
		$soon = array(
			'Chốt sản phẩm có phí trong 30 ngày sau lớp',
			'Mua nguyên liệu trong 30 ngày sau lớp',
			'Độ khớp form với kết luận sau gọi',
		);
		if (!self::tableExists($db, 'bace_lead_profile')) {
			return self::emptyStageBoard($soon);
		}
		$rows = self::fetchMonthLeadRows($db, " AND p.offline_status IS NOT NULL AND p.offline_status <> ''");
		$labels = array(
			'offline_hen_goi_lai' => 'Hẹn gọi lại',
			'offline_khong_nghe_may' => 'Không nghe máy',
			'offline_sai_thong_tin' => 'Sai thông tin',
			'offline_chua_xac_nhan_lich' => 'Chưa xác nhận lịch',
			'offline_hen_lich_lai' => 'Hẹn lịch lại',
			'offline_da_xac_nhan_lich' => 'Đã xác nhận lịch',
			'offline_da_tham_gia' => 'Đã tham gia',
			'offline_khong_tham_gia' => 'Không tham gia',
			'offline_ngung_cskh' => 'Ngưng CSKH',
		);
		$miss = array('offline_hen_goi_lai' => 1, 'offline_khong_nghe_may' => 1, 'offline_sai_thong_tin' => 1);
		$counts = array();
		$levels = array('sieu_tiem_nang' => 0, 'tiem_nang' => 0, 'binh_thuong' => 0);
		$regions = array('kv1' => 0, 'kv2' => 0, 'kv3' => 0);
		$total = 0;
		$contacted = 0;
		$eligible = 0;
		$attended = 0;
		$dated = 0;
		foreach ($rows as $row) {
			$status = strtolower(trim((string) $row['offline_status']));
			$total++;
			if (!isset($counts[$status])) {
				$counts[$status] = 0;
			}
			$counts[$status]++;
			if (!isset($miss[$status])) {
				$contacted++;
				if ((string) $row['eligibility_result'] === 'du_dk') {
					$eligible++;
				}
			}
			if ($status === 'offline_da_tham_gia') {
				$attended++;
			}
			if (in_array($status, array('offline_da_xac_nhan_lich', 'offline_da_tham_gia', 'offline_khong_tham_gia'), true)) {
				$dated++;
			}
			$level = strtolower(trim((string) $row['potential_level']));
			if (isset($levels[$level])) {
				$levels[$level]++;
			}
			$region = self::stageRegionKey($row['area'], $row['district']);
			if (isset($regions[$region])) {
				$regions[$region]++;
			}
		}
		$colors = array(
			'offline_hen_goi_lai' => '#f59e0b',
			'offline_khong_nghe_may' => '#fb7185',
			'offline_sai_thong_tin' => '#e11d48',
			'offline_chua_xac_nhan_lich' => '#f59e0b',
			'offline_hen_lich_lai' => '#d97706',
			'offline_da_xac_nhan_lich' => '#06b6d4',
			'offline_da_tham_gia' => '#10b981',
			'offline_khong_tham_gia' => '#f43f5e',
			'offline_ngung_cskh' => '#64748b',
		);
		$stages = array(
			self::stageCountCard('Trong luồng Offline', $total, 'gd11:all', '#2563eb'),
		);
		foreach ($labels as $key => $label) {
			$stages[] = self::stageCountCard($label, isset($counts[$key]) ? $counts[$key] : 0, 'gd11:status:' . $key, $colors[$key]);
		}
		$levelLabels = array(
			'sieu_tiem_nang' => 'Siêu tiềm năng',
			'tiem_nang' => 'Tiềm năng',
			'binh_thuong' => 'Bình thường',
		);
		$levelItems = array();
		foreach ($levelLabels as $key => $label) {
			$levelItems[] = self::stageCountCard($label, $levels[$key], 'gd11:level:' . $key, '#2563eb');
		}
		$regionItems = array(
			self::stageCountCard('Khu vực 1', $regions['kv1'], 'gd11:region:kv1', '#2563eb'),
			self::stageCountCard('Khu vực 2', $regions['kv2'], 'gd11:region:kv2', '#06b6d4'),
			self::stageCountCard('Khu vực 3', $regions['kv3'], 'gd11:region:kv3', '#8b5cf6'),
		);
		return array(
			'period_label' => 'Tháng này · theo ngày tạo hồ sơ · SỐ TẠM',
			'rates' => array(
				self::stageRateCard('Liên hệ được', $contacted, $total, 'gd11:contacted'),
				self::stageRateCard('Giữ đủ điều kiện', $eligible, $contacted, 'gd11:eligible'),
				self::stageRateCard('Tham gia / đã chốt ngày', $attended, $dated, 'gd11:attended'),
			),
			'stages' => $stages,
			'splits' => array(
				array('title' => 'Theo mức tiềm năng', 'items' => $levelItems),
				array('title' => 'Theo khu vực', 'items' => $regionItems),
			),
			'soon' => $soon,
			'total' => $total,
			'attend_rate' => $dated > 0 ? round(($attended / $dated) * 100, 1) : 0,
		);
	}

	/**
	 * GD 1.2: phễu tháng này, kèm tiến độ EduBit. Bấm số ra danh sách.
	 * @return array
	 */
	public static function getOnlineGd12() {
		$db = PearDatabase::getInstance();
		$soon = array(
			'Chọn sản phẩm khác',
			'Hồ sơ đủ một trong bốn đầu ra',
			'Chốt sản phẩm có phí trong 30 ngày sau bàn giao',
			'Mua nguyên liệu trong 30 ngày',
			'Hiệu quả từng lần nhắc và gia hạn',
			'Số lượng theo khu vực ở bốn mốc',
		);
		if (!self::tableExists($db, 'bace_lead_profile')) {
			return self::emptyStageBoard($soon);
		}
		$rows = self::fetchMonthLeadRows($db, " AND p.online_status IS NOT NULL AND p.online_status <> ''");
		$total = 0;
		$pending = 0;
		$qualified = 0;
		$notQualified = 0;
		$stopped = 0;
		$activated = 0;
		$reached80 = 0;
		$complete = 0;
		foreach ($rows as $row) {
			$status = strtolower(trim((string) $row['online_status']));
			$total++;
			$progress = (float) $row['edubit_progress_pct'];
			$hasAccount = self::stageHasTimestamp($row['edubit_activated_at'])
				|| in_array($status, array('online_dang_hoc', 'online_dat_80'), true);
			$hit80 = $status === 'online_dat_80' || $progress >= 80;
			$hit100 = $progress >= 100;
			if ($status === 'online_chua_dien_form') {
				$pending++;
			} elseif ($status === 'online_khong_du_dk') {
				$notQualified++;
			} elseif ($status === 'online_ngung_cskh') {
				$stopped++;
			} elseif ($status === 'online_chua_dk_tk') {
				$qualified++;
			}
			if ($hasAccount) {
				$activated++;
			}
			if ($hit80) {
				$reached80++;
			}
			if ($hit100) {
				$complete++;
			}
		}
		$formFilled = max(0, $total - $pending);
		$passed = $qualified + $activated;
		$stages = array(
			self::stageCountCard('Vào Zalo OA', $total, 'gd12:all', '#2563eb'),
			self::stageCountCard('Chưa điền form', $pending, 'gd12:bucket:pending', '#f59e0b'),
			self::stageCountCard('Đã điền form', $formFilled, 'gd12:bucket:form', '#06b6d4'),
			self::stageCountCard('Đủ điều kiện, chờ tài khoản', $qualified, 'gd12:bucket:qualified', '#10b981'),
			self::stageCountCard('Không đủ điều kiện', $notQualified, 'gd12:bucket:blocked', '#f43f5e'),
			self::stageCountCard('Ngưng CSKH', $stopped, 'gd12:bucket:stopped', '#64748b'),
			self::stageCountCard('Đã kích hoạt EduBit', $activated, 'gd12:bucket:activated', '#8b5cf6'),
			self::stageCountCard('Đạt 80%', $reached80, 'gd12:bucket:p80', '#a855f7'),
			self::stageCountCard('Học hết 100%', $complete, 'gd12:bucket:p100', '#7c3aed'),
		);
		return array(
			'period_label' => 'Tháng này · theo ngày tạo hồ sơ · SỐ TẠM',
			'rates' => array(
				self::stageRateCard('Điền form', $formFilled, $total, 'gd12:bucket:form'),
				self::stageRateCard('Đủ điều kiện', $passed, $formFilled, 'gd12:bucket:passed'),
				self::stageRateCard('Kích hoạt', $activated, $passed, 'gd12:bucket:activated'),
				self::stageRateCard('Đạt 80%', $reached80, $activated, 'gd12:bucket:p80'),
				self::stageRateCard('Học hết 100%', $complete, $activated, 'gd12:bucket:p100'),
			),
			'stages' => $stages,
			'splits' => array(),
			'soon' => $soon,
			'total' => $total,
			'form_rate' => $total > 0 ? round(($formFilled / $total) * 100, 1) : 0,
			'qualify_rate' => $formFilled > 0 ? round(($passed / $formFilled) * 100, 1) : 0,
		);
	}

	/**
	 * GD 1.4: tag 990k, xác minh và khoá đã thanh toán trong tháng này.
	 * @return array
	 */
	public static function getGd14() {
		$soon = array(
			'Liên hệ đúng hạn 30 phút',
			'Tham gia lớp trong 30 / 60 / 90 ngày',
			'Doanh thu và giá trị đơn',
			'Tỷ lệ huỷ hoặc xin hoàn phí',
		);
		$db = PearDatabase::getInstance();
		if (!self::tableExists($db, 'bace_lead_profile') || !self::tableExists($db, 'vtiger_freetags')) {
			return self::emptyStageBoard($soon);
		}
		$catalog = array(
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
		$packed = self::fetchGd14MonthRows($db);
		$tagCounts = array();
		foreach (array_keys($catalog) as $slug) {
			$tagCounts[$slug] = 0;
		}
		$verified = 0;
		$invited = 0;
		$blocked = 0;
		$courses = array('lop_990k' => 0, 'pcth' => 0, 'mqbb' => 0, 'combo' => 0);
		foreach ($packed as $row) {
			$tag = $row['tag'];
			if (isset($tagCounts[$tag])) {
				$tagCounts[$tag]++;
			}
			if (!empty($row['verified'])) {
				$verified++;
			}
			if ($row['invite'] === 1) {
				$invited++;
			} elseif ($row['invite'] === 0) {
				$blocked++;
			}
			if ($row['course'] !== '' && isset($courses[$row['course']])) {
				$courses[$row['course']]++;
			}
		}
		$total = count($packed);
		$stages = array(self::stageCountCard('Hồ sơ 990k trong tháng', $total, 'gd14:all', '#2563eb'));
		foreach ($catalog as $slug => $label) {
			$stages[] = self::stageCountCard($label, $tagCounts[$slug], 'gd14:tag:' . $slug, '#0f766e');
		}
		$courseItems = array(
			self::stageCountCard('Lớp 990k đã thanh toán', $courses['lop_990k'], 'gd14:course:lop_990k', '#10b981'),
			self::stageCountCard('Pha chế tổng hợp', $courses['pcth'], 'gd14:course:pcth', '#2563eb'),
			self::stageCountCard('Mở quán bài bản', $courses['mqbb'], 'gd14:course:mqbb', '#7c3aed'),
			self::stageCountCard('Combo mở quán', $courses['combo'], 'gd14:course:combo', '#b45309'),
		);
		$classified = $invited + $blocked;
		return array(
			'period_label' => 'Tháng này · theo ngày tạo hồ sơ · SỐ TẠM',
			'rates' => array(
				self::stageRateCard('Đã xác minh', $verified, $total, 'gd14:verified'),
				self::stageRateCard('Được mời Combo / Mở quán', $invited, $classified, 'gd14:invited'),
				self::stageRateCard('Bị chặn Combo / Mở quán', $blocked, $classified, 'gd14:blocked'),
			),
			'stages' => $stages,
			'splits' => array(
				array('title' => 'Đã xác nhận thanh toán', 'items' => $courseItems),
			),
			'soon' => $soon,
			'total' => $total,
		);
	}

	protected static function emptyStageBoard(array $soon) {
		return array(
			'period_label' => 'Tháng này · theo ngày tạo hồ sơ · SỐ TẠM',
			'rates' => array(),
			'stages' => array(),
			'splits' => array(),
			'soon' => $soon,
			'total' => 0,
		);
	}

	protected static function stageCountCard($label, $count, $drillKey, $color) {
		return array(
			'label' => $label,
			'value' => (string) (int) $count,
			'count' => (int) $count,
			'color' => $color,
			'drill' => array('type' => 'stage_people', 'key' => $drillKey),
		);
	}

	protected static function stageRateCard($label, $num, $den, $drillKey) {
		$num = (int) $num;
		$den = (int) $den;
		$pct = $den > 0 ? round(($num / $den) * 100, 1) : 0;
		return array(
			'label' => $label . ' · ' . $num . '/' . $den,
			'value' => $pct . '%',
			'count' => $num,
			'color' => '#047857',
			'drill' => array('type' => 'stage_people', 'key' => $drillKey),
		);
	}

	protected static function stageMonthBounds() {
		return array(date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59'));
	}

	protected static function fetchMonthLeadRows(PearDatabase $db, $extraWhere) {
		list($from, $to) = self::stageMonthBounds();
		$r = $db->pquery(
			"SELECT ld.leadid AS id,
				TRIM(CONCAT(COALESCE(ld.firstname,''), ' ', COALESCE(ld.lastname,''))) AS name,
				COALESCE(NULLIF(la.mobile, ''), NULLIF(la.phone, ''), '') AS phone,
				p.offline_status, p.online_status, p.eligibility_result, p.potential_level,
				p.area, p.district, p.edubit_progress_pct, p.edubit_activated_at, p.verify_extra_json
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 LEFT JOIN bace_lead_profile p ON p.leadid = ld.leadid
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = ld.leadid
			 WHERE ce.createdtime >= ? AND ce.createdtime <= ?
			 {$extraWhere}",
			array($from, $to)
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	protected static function fetchGd14MonthRows(PearDatabase $db) {
		list($from, $to) = self::stageMonthBounds();
		$r = $db->pquery(
			"SELECT ld.leadid AS id,
				TRIM(CONCAT(COALESCE(ld.firstname,''), ' ', COALESCE(ld.lastname,''))) AS name,
				COALESCE(NULLIF(la.mobile, ''), NULLIF(la.phone, ''), '') AS phone,
				t.tag AS tag_name, p.verify_extra_json
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 INNER JOIN vtiger_freetagged_objects fo ON fo.object_id = ld.leadid AND fo.module = 'Leads'
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 LEFT JOIN bace_lead_profile p ON p.leadid = ld.leadid
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = ld.leadid
			 WHERE ce.createdtime >= ? AND ce.createdtime <= ?
			 AND (t.tag LIKE 'gd14\\_%' OR t.tag IN ('990k','990','gd14_990'))",
			array($from, $to)
		);
		$byId = array();
		$order = array_keys(array(
			'gd14_moi_dang_ky' => 1,
			'gd14_hen_goi_lai' => 1,
			'gd14_khong_nghe_may' => 1,
			'gd14_sai_thong_tin' => 1,
			'gd14_dang_can_nhac' => 1,
			'gd14_cho_thanh_toan' => 1,
			'gd14_chua_xep_buoi' => 1,
			'gd14_da_xac_nhan_lich' => 1,
			'gd14_khong_tham_gia' => 1,
			'gd14_da_tham_gia' => 1,
			'gd14_ngung_cham_soc' => 1,
		));
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$tag = self::normalizeGd14Tag($row['tag_name']);
				if ($tag === '') {
					continue;
				}
				$extra = self::decodeGd14Extra($row['verify_extra_json']);
				$rank = array_search($tag, $order, true);
				if (!isset($byId[$id]) || $rank > $byId[$id]['rank']) {
					$byId[$id] = array(
						'id' => $id,
						'name' => $row['name'],
						'phone' => $row['phone'],
						'tag' => $tag,
						'rank' => $rank === false ? -1 : $rank,
						'verified' => !empty($extra['gd14_verified']) ? 1 : 0,
						'invite' => self::gd14InviteFlag($extra),
						'course' => !empty($extra['gd14_paid_at']) ? strtolower(trim((string) (isset($extra['gd14_course']) ? $extra['gd14_course'] : ''))) : '',
						'status' => $tag,
					);
				}
			}
		}
		return array_values($byId);
	}

	protected static function normalizeGd14Tag($tag) {
		$tag = strtolower(trim((string) $tag));
		if ($tag === '990k' || $tag === '990' || $tag === 'gd14_990') {
			return 'gd14_moi_dang_ky';
		}
		if (strpos($tag, 'gd14_') === 0) {
			return $tag;
		}
		return '';
	}

	protected static function decodeGd14Extra($raw) {
		$data = json_decode((string) $raw, true);
		return is_array($data) ? $data : array();
	}

	protected static function gd14InviteFlag(array $extra) {
		if (empty($extra['gd14_result']) || !is_array($extra['gd14_result'])) {
			return null;
		}
		if (!isset($extra['gd14_result']['invited'])) {
			return null;
		}
		return !empty($extra['gd14_result']['invited']) ? 1 : 0;
	}

	protected static function stageRegionKey($area, $district) {
		$s = mb_strtolower(trim((string) $area . ' ' . (string) $district), 'UTF-8');
		if (strpos($s, 'kv1') !== false || strpos($s, 'khu vực 1') !== false || strpos($s, 'khu vuc 1') !== false) {
			return 'kv1';
		}
		if (strpos($s, 'kv2') !== false || strpos($s, 'khu vực 2') !== false || strpos($s, 'khu vuc 2') !== false) {
			return 'kv2';
		}
		if (strpos($s, 'kv3') !== false || strpos($s, 'khu vực 3') !== false || strpos($s, 'khu vuc 3') !== false) {
			return 'kv3';
		}
		return '';
	}

	protected static function stageHasTimestamp($value) {
		$value = trim((string) $value);
		return $value !== '' && $value !== '0000-00-00 00:00:00';
	}

	protected static function drillStagePeople(PearDatabase $db, $key) {
		$parts = explode(':', (string) $key);
		$stage = isset($parts[0]) ? $parts[0] : '';
		$kind = isset($parts[1]) ? $parts[1] : '';
		$arg = isset($parts[2]) ? $parts[2] : '';
		$rows = array();
		$title = 'Danh sách';
		if ($stage === 'gd11') {
			$all = self::fetchMonthLeadRows($db, " AND p.offline_status IS NOT NULL AND p.offline_status <> ''");
			$picked = array();
			foreach ($all as $row) {
				if (self::gd11RowMatches($row, $kind, $arg)) {
					$picked[] = $row;
				}
			}
			$title = self::gd11DrillTitle($kind, $arg);
			$rows = self::mapStagePeople($picked, 'offline_status');
		} elseif ($stage === 'gd12') {
			$all = self::fetchMonthLeadRows($db, " AND p.online_status IS NOT NULL AND p.online_status <> ''");
			$picked = array();
			foreach ($all as $row) {
				if (self::gd12RowMatches($row, $kind, $arg)) {
					$picked[] = $row;
				}
			}
			$title = 'Online 1.2 — ' . $arg;
			$rows = self::mapStagePeople($picked, 'online_status');
		} elseif ($stage === 'gd14') {
			$all = self::fetchGd14MonthRows($db);
			$picked = array();
			foreach ($all as $row) {
				if (self::gd14RowMatches($row, $kind, $arg)) {
					$picked[] = $row;
				}
			}
			$title = '990k — ' . ($arg !== '' ? $arg : $kind);
			$rows = self::mapStagePeople($picked, 'status');
		}
		if (count($rows) > 200) {
			$rows = array_slice($rows, 0, 200);
		}
		return array(
			'title' => $title . ' · tháng này (' . count($rows) . ')',
			'module' => 'StageRoster',
			'hint' => 'Bấm Chi tiết để mở hồ sơ. Đây là đúng những người tạo nên con số vừa bấm.',
			'columns' => array('name', 'phone', 'status', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function gd11RowMatches(array $row, $kind, $arg) {
		$status = strtolower(trim((string) $row['offline_status']));
		$miss = array('offline_hen_goi_lai', 'offline_khong_nghe_may', 'offline_sai_thong_tin');
		if ($kind === 'all') {
			return true;
		}
		if ($kind === 'status') {
			return $status === $arg;
		}
		if ($kind === 'contacted') {
			return !in_array($status, $miss, true);
		}
		if ($kind === 'eligible') {
			return !in_array($status, $miss, true) && (string) $row['eligibility_result'] === 'du_dk';
		}
		if ($kind === 'attended') {
			return $status === 'offline_da_tham_gia';
		}
		if ($kind === 'level') {
			return strtolower(trim((string) $row['potential_level'])) === $arg;
		}
		if ($kind === 'region') {
			return self::stageRegionKey($row['area'], $row['district']) === $arg;
		}
		return false;
	}

	protected static function gd11DrillTitle($kind, $arg) {
		$names = array(
			'all' => 'Offline 1.1 — trong luồng',
			'contacted' => 'Offline 1.1 — đã liên hệ được',
			'eligible' => 'Offline 1.1 — giữ đủ điều kiện',
			'attended' => 'Offline 1.1 — đã tham gia',
			'status' => 'Offline 1.1 — ' . $arg,
			'level' => 'Offline 1.1 — ' . $arg,
			'region' => 'Offline 1.1 — ' . $arg,
		);
		return isset($names[$kind]) ? $names[$kind] : 'Offline 1.1';
	}

	protected static function gd12RowMatches(array $row, $kind, $bucket) {
		if ($kind === 'all') {
			return true;
		}
		$status = strtolower(trim((string) $row['online_status']));
		$progress = (float) $row['edubit_progress_pct'];
		$activated = self::stageHasTimestamp($row['edubit_activated_at'])
			|| in_array($status, array('online_dang_hoc', 'online_dat_80'), true);
		if ($bucket === 'pending') {
			return $status === 'online_chua_dien_form';
		}
		if ($bucket === 'form') {
			return $status !== 'online_chua_dien_form';
		}
		if ($bucket === 'qualified') {
			return $status === 'online_chua_dk_tk';
		}
		if ($bucket === 'blocked') {
			return $status === 'online_khong_du_dk';
		}
		if ($bucket === 'stopped') {
			return $status === 'online_ngung_cskh';
		}
		if ($bucket === 'passed') {
			return $status === 'online_chua_dk_tk' || $activated;
		}
		if ($bucket === 'activated') {
			return $activated;
		}
		if ($bucket === 'p80') {
			return $status === 'online_dat_80' || $progress >= 80;
		}
		if ($bucket === 'p100') {
			return $progress >= 100;
		}
		return false;
	}

	protected static function gd14RowMatches(array $row, $kind, $arg) {
		if ($kind === 'all') {
			return true;
		}
		if ($kind === 'tag') {
			return $row['tag'] === $arg;
		}
		if ($kind === 'verified') {
			return !empty($row['verified']);
		}
		if ($kind === 'invited') {
			return $row['invite'] === 1;
		}
		if ($kind === 'blocked') {
			return $row['invite'] === 0;
		}
		if ($kind === 'course') {
			return $row['course'] === $arg;
		}
		return false;
	}

	protected static function mapStagePeople(array $rows, $statusField) {
		$out = array();
		foreach ($rows as $row) {
			$id = (int) $row['id'];
			$out[] = array(
				'id' => $id,
				'name' => self::decodeText(isset($row['name']) ? $row['name'] : '') ?: ('#' . $id),
				'phone' => self::decodeText(isset($row['phone']) ? $row['phone'] : ''),
				'status' => isset($row[$statusField]) ? (string) $row[$statusField] : '',
				'detail_url' => 'index.php?module=Leads&view=Detail&record=' . $id . '&app=SALES',
			);
		}
		return $out;
	}

	/** Stage 2 — Sales Funnel */
	public static function getSalesFunnel() {
		$db = PearDatabase::getInstance();
		$leads = 0;
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 WHERE (ld.converted = 0 OR ld.converted IS NULL OR ld.converted = \'0\')',
			array()
		);
		$leads = $r ? (int) $db->query_result($r, 0, 'c') : 0;

		// Align with SALES Potentials list (ModernService): exclude converted-to-customer.
		$opps = 0;
		if (self::tableExists($db, 'vtiger_potential')) {
			$oppsSql = 'SELECT COUNT(*) AS c FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0';
			if (self::tableExists($db, 'bace_potential_profile')) {
				$oppsSql .= ' LEFT JOIN bace_potential_profile pp ON pp.potentialid = p.potentialid
					 WHERE pp.converted_to_customer_at IS NULL';
			}
			$r = $db->pquery($oppsSql, array());
			$opps = $r ? (int) $db->query_result($r, 0, 'c') : 0;
		}

		$quotes = 0;
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0',
			array()
		);
		$quotes = $r ? (int) $db->query_result($r, 0, 'c') : 0;

		$orders = 0;
		$r = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0',
			array()
		);
		$orders = $r ? (int) $db->query_result($r, 0, 'c') : 0;

		$won = 0;
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$r = $db->pquery(
			"SELECT COUNT(*) AS c FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql",
			$excluded
		);
		$won = $r ? (int) $db->query_result($r, 0, 'c') : 0;

		// Always open new SALES list shells (Quotes/SO ListViewPreProcess needs app=SALES).
		$stages = array(
			array('key' => 'leads', 'label' => 'KH tiềm năng', 'count' => $leads, 'url' => 'index.php?module=Leads&view=List&app=SALES'),
			array('key' => 'opps', 'label' => 'Cơ hội', 'count' => $opps, 'url' => 'index.php?module=Potentials&view=List&app=SALES'),
			array('key' => 'quotes', 'label' => 'Báo giá', 'count' => $quotes, 'url' => 'index.php?module=Quotes&view=List&app=SALES'),
			array('key' => 'orders', 'label' => 'Đơn hàng', 'count' => $orders, 'url' => 'index.php?module=SalesOrder&view=List&app=SALES'),
			array('key' => 'won', 'label' => 'Đã chốt', 'count' => $won, 'url' => 'index.php?module=SalesOrder&view=List&app=SALES'),
		);
		$max = 1;
		foreach ($stages as $s) {
			if ($s['count'] > $max) {
				$max = $s['count'];
			}
		}
		foreach ($stages as &$s) {
			$s['percent'] = round(($s['count'] / $max) * 100, 1);
		}
		unset($s);
		return array('stages' => $stages, 'max' => $max);
	}

	/**
	 * Stage 3 — Revenue chart.
	 * @param array $opts group=month|quarter|year, dimension=none|sale|product|region, sale_id, year
	 */
	public static function getRevenueChart(array $opts = array()) {
		$db = PearDatabase::getInstance();
		$cal = self::businessCalendar($db);
		$group = isset($opts['group']) ? strtolower((string) $opts['group']) : 'month';
		$dimension = isset($opts['dimension']) ? strtolower((string) $opts['dimension']) : 'none';
		$saleId = isset($opts['sale_id']) ? (int) $opts['sale_id'] : 0;
		$year = isset($opts['year']) ? (int) $opts['year'] : 0;
		if ($year < 2000 || $year > 2100) {
			$year = $cal['year'];
		}

		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');

		if ($dimension === 'sale') {
			return self::revenueChartBySale($db, $year);
		}
		if ($dimension === 'product') {
			$from = $year . '-01-01 00:00:00';
			$to = $year . '-12-31 23:59:59';
			$product = self::revenueByProductBetween($db, $from, $to);
			return array(
				'group' => 'product',
				'dimension' => 'product',
				'year' => $year,
				'labels' => array_map(function ($i) { return $i['label']; }, $product['items']),
				'series' => array_map(function ($i) { return $i['amount']; }, $product['items']),
				'keys' => array_map(function ($i) { return $i['key']; }, $product['items']),
				'drill_type' => 'revenue_product',
				'total' => $product['total'],
			);
		}
		if ($dimension === 'region') {
			return self::revenueChartByRegion($db, $year);
		}

		// time series
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		$params = array_merge($excluded, array($from, $to));
		if ($saleId > 0) {
			$params[] = $saleId;
		}
		$ownerSql = $saleId > 0 ? ' AND ce.smownerid = ? ' : '';

		if ($group === 'quarter') {
			$r = $db->pquery(
				"SELECT QUARTER(ce.createdtime) AS bucket, COALESCE(SUM(so.total), 0) AS amount
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ? $ownerSql
				 GROUP BY QUARTER(ce.createdtime)
				 ORDER BY bucket",
				$params
			);
			$by = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
			if ($r) {
				while ($row = $db->fetchByAssoc($r)) {
					$by[(int) $row['bucket']] = (float) $row['amount'];
				}
			}
			$labels = array('Q1', 'Q2', 'Q3', 'Q4');
			$series = array($by[1], $by[2], $by[3], $by[4]);
			$keys = array('1', '2', '3', '4');
			$drillType = 'revenue_quarter';
		} elseif ($group === 'year') {
			$fromY = ($year - 4) . '-01-01 00:00:00';
			$paramsY = array_merge($excluded, array($fromY, $to));
			if ($saleId > 0) {
				$paramsY[] = $saleId;
			}
			$r = $db->pquery(
				"SELECT YEAR(ce.createdtime) AS bucket, COALESCE(SUM(so.total), 0) AS amount
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ? $ownerSql
				 GROUP BY YEAR(ce.createdtime)
				 ORDER BY bucket",
				$paramsY
			);
			$labels = array();
			$series = array();
			$keys = array();
			$map = array();
			if ($r) {
				while ($row = $db->fetchByAssoc($r)) {
					$map[(int) $row['bucket']] = (float) $row['amount'];
				}
			}
			for ($y = $year - 4; $y <= $year; $y++) {
				$labels[] = (string) $y;
				$series[] = isset($map[$y]) ? $map[$y] : 0;
				$keys[] = (string) $y;
			}
			$drillType = 'revenue_year';
		} else {
			// month — 12 months of selected year
			$r = $db->pquery(
				"SELECT MONTH(ce.createdtime) AS bucket, COALESCE(SUM(so.total), 0) AS amount
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ? $ownerSql
				 GROUP BY MONTH(ce.createdtime)
				 ORDER BY bucket",
				$params
			);
			$by = array();
			for ($m = 1; $m <= 12; $m++) {
				$by[$m] = 0;
			}
			if ($r) {
				while ($row = $db->fetchByAssoc($r)) {
					$by[(int) $row['bucket']] = (float) $row['amount'];
				}
			}
			$labels = array('T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12');
			$series = array_values($by);
			$keys = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
			$drillType = 'revenue_month';
		}

		$total = 0;
		foreach ($series as $v) {
			$total += $v;
		}
		return array(
			'group' => $group,
			'dimension' => $dimension,
			'year' => $year,
			'labels' => $labels,
			'series' => $series,
			'keys' => $keys,
			'drill_type' => $drillType,
			'total' => $total,
		);
	}

	protected static function revenueByProductBetween(PearDatabase $db, $from, $to) {
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT COALESCE(pr.productname, sv.servicename, '') AS pname,
				COALESCE(pr.productcategory, '') AS pcat,
				COALESCE(sv.servicecategory, '') AS scat,
				SUM(ip.quantity * ip.listprice) AS amount
			 FROM vtiger_inventoryproductrel ip
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = ip.id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_products pr ON pr.productid = ip.productid
			 LEFT JOIN vtiger_service sv ON sv.serviceid = ip.productid
			 WHERE $notCancelSql
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY COALESCE(pr.productname, sv.servicename, ''),
			          COALESCE(pr.productcategory, ''), COALESCE(sv.servicecategory, '')",
			$params
		);
		$groups = array('course' => 0.0, 'ingredient' => 0.0, 'franchise' => 0.0);
		$total = 0.0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$amt = (float) $row['amount'];
				$total += $amt;
				$bucket = self::classifyProductBucket(
					(string) $row['pname'],
					(string) $row['pcat'],
					(string) $row['scat']
				);
				$groups[$bucket] += $amt;
			}
		}
		$labels = array(
			'course' => 'Khóa học',
			'ingredient' => 'Nguyên liệu',
			'franchise' => 'Nhượng quyền',
		);
		$items = array();
		foreach ($labels as $k => $label) {
			$amt = $groups[$k];
			$items[] = array(
				'key' => $k,
				'label' => $label,
				'amount' => $amt,
				'percent' => $total > 0 ? round(($amt / $total) * 100, 1) : 0,
			);
		}
		return array('total' => $total, 'items' => $items);
	}

	protected static function revenueChartBySale(PearDatabase $db, $year) {
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COALESCE(SUM(so.total), 0) AS amount
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY ce.smownerid
			 ORDER BY amount DESC
			 LIMIT 12",
			$params
		);
		$labels = array();
		$series = array();
		$keys = array();
		$total = 0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$uid = (int) $row['uid'];
				$labels[] = self::userDisplayName($db, $uid);
				$amt = (float) $row['amount'];
				$series[] = $amt;
				$keys[] = (string) $uid;
				$total += $amt;
			}
		}
		return array(
			'group' => 'sale',
			'dimension' => 'sale',
			'year' => $year,
			'labels' => $labels,
			'series' => $series,
			'keys' => $keys,
			'drill_type' => 'revenue_sale',
			'total' => $total,
		);
	}

	/**
	 * Join + biểu thức khu vực từ Opp / Lead gắn đơn hàng.
	 * @return array{joins:string,expr:string}
	 */
	protected static function soKvRegionSqlParts(PearDatabase $db) {
		$joins = '';
		$parts = array();
		if (self::tableExists($db, 'bace_potential_profile')) {
			$joins .= ' LEFT JOIN bace_potential_profile pp ON pp.potentialid = so.potentialid AND so.potentialid > 0';
			$parts[] = "NULLIF(TRIM(pp.district), '')";
		}
		if (self::tableExists($db, 'bace_lead_profile')) {
			if (self::columnExists($db, 'bace_lead_profile', 'potential_id')) {
				$joins .= ' LEFT JOIN bace_lead_profile lp_pot ON lp_pot.potential_id = so.potentialid AND so.potentialid > 0';
				$parts[] = "NULLIF(TRIM(lp_pot.district), '')";
			}
			if (self::tableExists($db, 'vtiger_salesordercf')
				&& self::columnExists($db, 'vtiger_salesordercf', 'lead_id')) {
				$joins .= ' LEFT JOIN vtiger_salesordercf socf ON socf.salesorderid = so.salesorderid';
				$joins .= ' LEFT JOIN bace_lead_profile lp_cf ON lp_cf.leadid = socf.lead_id AND socf.lead_id > 0';
				$parts[] = "NULLIF(TRIM(lp_cf.district), '')";
			}
			if (self::columnExists($db, 'bace_lead_profile', 'contact_id')) {
				$joins .= ' LEFT JOIN bace_lead_profile lp_ct ON lp_ct.contact_id = so.contactid AND so.contactid > 0';
				$parts[] = "NULLIF(TRIM(lp_ct.district), '')";
			}
		}
		$expr = !empty($parts) ? ('COALESCE(' . implode(', ', $parts) . ", '')") : "''";
		return array('joins' => $joins, 'expr' => $expr);
	}

	protected static function revenueChartByRegion(PearDatabase $db, $year) {
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$kv = self::soKvRegionSqlParts($db);
		$joins = $kv['joins'];
		$regionExpr = $kv['expr'];

		$r = $db->pquery(
			"SELECT $regionExpr AS region_raw, COALESCE(SUM(so.total), 0) AS amount
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 $joins
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY region_raw",
			$params
		);

		$buckets = array(
			'Khu vực 1' => 0.0,
			'Khu vực 2' => 0.0,
			'Khu vực 3' => 0.0,
		);
		$other = 0.0;
		$total = 0.0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$amt = (float) $row['amount'];
				$total += $amt;
				$key = self::normalizeKvRegion($row['region_raw']);
				if ($key !== '' && isset($buckets[$key])) {
					$buckets[$key] += $amt;
				} else {
					$other += $amt;
				}
			}
		}

		$labels = array_keys($buckets);
		$series = array_values($buckets);
		$keys = array('1', '2', '3');
		if ($other > 0) {
			$labels[] = 'Khác';
			$series[] = $other;
			$keys[] = 'other';
		}

		return array(
			'group' => 'region',
			'dimension' => 'region',
			'year' => $year,
			'labels' => $labels,
			'series' => $series,
			'keys' => $keys,
			'drill_type' => 'revenue_region',
			'total' => $total,
		);
	}

	/**
	 * Chuẩn hoá nhãn khu vực về "Khu vực 1|2|3".
	 * @param mixed $raw
	 * @return string
	 */
	protected static function normalizeKvRegion($raw) {
		$text = self::decodeText($raw);
		if ($text === '') {
			return '';
		}
		if (preg_match('/khu\s*v[uự]c\s*([123])/iu', $text, $m)) {
			return 'Khu vực ' . $m[1];
		}
		if (preg_match('/\bkv\s*([123])\b/i', $text, $m)) {
			return 'Khu vực ' . $m[1];
		}
		if (preg_match('/^([123])$/', $text, $m)) {
			return 'Khu vực ' . $m[1];
		}
		return '';
	}

	/** Stage 4 — Employee performance */
	public static function getPerformance() {
		$db = PearDatabase::getInstance();
		$cal = self::businessCalendar($db);
		$sale = self::performanceByRevenue($db, $cal['month_start'], $cal['month_end'], 5);
		$pm = self::performanceByRole($db, array('pm', 'project manager', 'quản lý dự án', 'quan ly du an'), 'Project', 5);
		$support = self::performanceByRole($db, array('support', 'hỗ trợ', 'ho tro', 'cskh', 'helpdesk'), 'HelpDesk', 5);
		return array(
			'sale' => $sale,
			'pm' => $pm,
			'support' => $support,
		);
	}

	protected static function performanceByRevenue(PearDatabase $db, $from, $to, $limit) {
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COALESCE(SUM(so.total), 0) AS score
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 GROUP BY ce.smownerid
			 ORDER BY score DESC
			 LIMIT " . (int) $limit,
			$params
		);
		return self::normalizePerformanceRows($db, $r);
	}

	protected static function performanceByRole(PearDatabase $db, array $roleNeedles, $moduleFallback, $limit) {
		$users = self::usersMatchingRoles($db, $roleNeedles);
		if (empty($users)) {
			// Fallback: owners of open tickets / projects this month
			if ($moduleFallback === 'HelpDesk' && self::tableExists($db, 'vtiger_troubletickets')) {
				$r = $db->pquery(
					"SELECT ce.smownerid AS uid, COUNT(*) AS score
					 FROM vtiger_troubletickets t
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = t.ticketid AND ce.deleted = 0
					 WHERE ce.createdtime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
					 GROUP BY ce.smownerid
					 ORDER BY score DESC
					 LIMIT " . (int) $limit,
					array()
				);
				return self::normalizePerformanceRows($db, $r);
			}
			if ($moduleFallback === 'Project' && self::tableExists($db, 'vtiger_project')) {
				$r = $db->pquery(
					"SELECT ce.smownerid AS uid, COUNT(*) AS score
					 FROM vtiger_project p
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.projectid AND ce.deleted = 0
					 WHERE ce.deleted = 0
					 GROUP BY ce.smownerid
					 ORDER BY score DESC
					 LIMIT " . (int) $limit,
					array()
				);
				return self::normalizePerformanceRows($db, $r);
			}
			return array();
		}
		// Score = SO revenue this month for matching users (Sale-like) else activity count
		$ids = array_keys($users);
		$ph = implode(',', array_fill(0, count($ids), '?'));
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$cal = self::businessCalendar($db);
		$params = array_merge($excluded, array($cal['month_start'], $cal['month_end']), $ids);
		$r = $db->pquery(
			"SELECT ce.smownerid AS uid, COALESCE(SUM(so.total), 0) AS score
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 AND ce.smownerid IN ($ph)
			 GROUP BY ce.smownerid
			 ORDER BY score DESC
			 LIMIT " . (int) $limit,
			$params
		);
		$rows = self::normalizePerformanceRows($db, $r);
		if (!empty($rows)) {
			return $rows;
		}
		// Zero revenue — still list users with 0%
		$out = array();
		$i = 0;
		foreach ($users as $uid => $name) {
			if ($i >= $limit) {
				break;
			}
			$out[] = array('id' => $uid, 'name' => $name, 'score' => 0, 'percent' => 0);
			$i++;
		}
		return $out;
	}

	protected static function usersMatchingRoles(PearDatabase $db, array $needles) {
		$r = $db->pquery(
			"SELECT u.id, u.first_name, u.last_name, u.user_name, r.rolename
			 FROM vtiger_users u
			 INNER JOIN vtiger_user2role ur ON ur.userid = u.id
			 INNER JOIN vtiger_role r ON r.roleid = ur.roleid
			 WHERE u.status = 'Active'",
			array()
		);
		$out = array();
		if (!$r) {
			return $out;
		}
		while ($row = $db->fetchByAssoc($r)) {
			$role = strtolower((string) $row['rolename']);
			$hit = false;
			foreach ($needles as $n) {
				if ($n !== '' && strpos($role, $n) !== false) {
					$hit = true;
					break;
				}
			}
			if (!$hit) {
				continue;
			}
			$uid = (int) $row['id'];
			$name = trim($row['first_name'] . ' ' . $row['last_name']);
			if ($name === '') {
				$name = (string) $row['user_name'];
			}
			$out[$uid] = $name;
		}
		return $out;
	}

	protected static function normalizePerformanceRows(PearDatabase $db, $r) {
		$items = array();
		$sum = 0.0;
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$score = (float) $row['score'];
				$sum += $score;
				$items[] = array(
					'id' => (int) $row['uid'],
					'name' => self::userDisplayName($db, (int) $row['uid']),
					'score' => $score,
				);
			}
		}
		if ($sum <= 0) {
			$sum = 1;
		}
		foreach ($items as &$it) {
			// Phần trăm trên tổng (cho biểu đồ tròn)
			$it['percent'] = (int) round(($it['score'] / $sum) * 100);
		}
		unset($it);
		return $items;
	}

	/** Stage 5 — Alerts */
	public static function getAlerts() {
		$db = PearDatabase::getInstance();

		$urgency = self::leadUrgency($db);
		$nc = (int) $urgency['not_contacted'];

		$draftOrders = 0;
		$draftVals = self::orderStatusValues('draft');
		if (!empty($draftVals)) {
			$ph = implode(',', array_fill(0, count($draftVals), '?'));
			$r = $db->pquery(
				"SELECT COUNT(*) AS c FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE so.sostatus IN ($ph)",
				$draftVals
			);
			$draftOrders = $r ? (int) $db->query_result($r, 0, 'c') : 0;
		}

		$draftQuotes = 0;
		$qDraft = self::quoteStatusValues('draft');
		if (!empty($qDraft)) {
			$ph = implode(',', array_fill(0, count($qDraft), '?'));
			$r = $db->pquery(
				"SELECT COUNT(*) AS c FROM vtiger_quotes q
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
				 WHERE q.quotestage IN ($ph)",
				$qDraft
			);
			$draftQuotes = $r ? (int) $db->query_result($r, 0, 'c') : 0;
		}

		$franchiseMissed = self::countFranchiseMissedCalls($db);

		return array(
			'items' => array(
				array(
					'key' => 'leads_nocall',
					'level' => $nc > 0 ? 'warn' : 'muted',
					'count' => $nc,
					'label' => 'KH tiềm năng chưa gọi',
					'drill' => array('type' => 'leads_urgency', 'key' => 'not_contacted'),
				),
				array(
					'key' => 'orders_draft',
					'level' => $draftOrders > 0 ? 'warn' : 'muted',
					'count' => $draftOrders,
					'label' => 'Đơn hàng đang nháp',
					'drill' => array('type' => 'orders_status', 'key' => 'draft'),
				),
				array(
					'key' => 'quotes_draft',
					'level' => $draftQuotes > 0 ? 'warn' : 'muted',
					'count' => $draftQuotes,
					'label' => 'Báo giá đang nháp',
					'drill' => array('type' => 'quotes_status', 'key' => 'draft'),
				),
				array(
					'key' => 'franchise_missed',
					'level' => $franchiseMissed > 0 ? 'warn' : 'muted',
					'count' => $franchiseMissed,
					'label' => 'KH nhượng quyền chưa nghe máy',
					'drill' => array('type' => 'franchise_missed', 'key' => ''),
				),
			),
		);
	}

	/**
	 * HĐ nhượng quyền: chưa gọi lần nào HOẶC lần gọi gần nhất = Không nghe máy.
	 */
	protected static function countFranchiseMissedCalls(PearDatabase $db) {
		if (!self::tableExists($db, 'vtiger_servicecontracts')) {
			return 0;
		}
		$hasCalls = self::tableExists($db, 'bace_sc_last_touch_call');
		if (!$hasCalls) {
			$r = $db->pquery(
				'SELECT COUNT(*) AS c FROM vtiger_servicecontracts sc
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0',
				array()
			);
			return $r ? (int) $db->query_result($r, 0, 'c') : 0;
		}
		$r = $db->pquery(
			"SELECT COUNT(*) AS c
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 WHERE NOT EXISTS (
				SELECT 1 FROM bace_sc_last_touch_call c WHERE c.servicecontractsid = sc.servicecontractsid
			 )
			 OR EXISTS (
				SELECT 1 FROM bace_sc_last_touch_call lastc
				WHERE lastc.servicecontractsid = sc.servicecontractsid
				AND lastc.called_at = (
					SELECT MAX(c2.called_at) FROM bace_sc_last_touch_call c2
					WHERE c2.servicecontractsid = sc.servicecontractsid
				)
				AND lastc.result_label = ?
			 )",
			array('Không nghe máy')
		);
		return $r ? (int) $db->query_result($r, 0, 'c') : 0;
	}

	/**
	 * Drill-down: KPI / summary → data table rows (click → Detail / Print).
	 * @param string $type
	 * @param array $opts
	 * @return array
	 */
	public static function getDrilldown($type, array $opts = array()) {
		$type = strtolower(trim((string) $type));
		$key = isset($opts['key']) ? (string) $opts['key'] : '';
		$id = isset($opts['id']) ? (int) $opts['id'] : 0;
		$db = PearDatabase::getInstance();

		switch ($type) {
			case 'revenue_period':
				return self::drillSalesOrdersByPeriod($db, $key !== '' ? $key : 'month');
			case 'revenue_region':
				return self::drillSalesOrdersByRegion($db, $key !== '' ? $key : '1', isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'revenue_product':
				return self::drillSalesOrdersByProductBucket($db, $key !== '' ? $key : 'course', isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'revenue_sale':
				return self::drillSalesOrdersBySaleOwner($db, $id > 0 ? $id : (int) $key, isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'revenue_month':
				return self::drillSalesOrdersByChartBucket($db, 'month', $key, isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'revenue_quarter':
				return self::drillSalesOrdersByChartBucket($db, 'quarter', $key, isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'revenue_year':
				return self::drillSalesOrdersByChartBucket($db, 'year', $key, isset($opts['year']) ? (int) $opts['year'] : 0);
			case 'orders_status':
				return self::drillSalesOrdersByStatusKey($db, $key);
			case 'orders_warehouse':
				return self::drillSalesOrdersByWarehouseKey($db, $key);
			case 'orders_processing':
				return self::drillOrdersProcessing($db);
			case 'quotes_status':
				return self::drillQuotesByStatusKey($db, $key);
			case 'quotes_pending':
				return self::drillQuotesByStatusKey($db, 'draft'); // đang chờ ≈ Nháp (+ có thể mở rộng)
			case 'customer_orders':
				return self::drillSalesOrdersByContact($db, $id);
			case 'customers':
				return self::drillContacts($db, $key);
			case 'stage_people':
				return self::drillStagePeople($db, $key);
			case 'leads_period':
				return self::drillLeadsByPeriod($db, $key !== '' ? $key : 'today');
			case 'leads_urgency':
				return self::drillLeadsByUrgency($db, $key !== '' ? $key : 'not_contacted');
			case 'franchise':
				return self::drillServiceContracts($db);
			case 'franchise_missed':
				return self::drillFranchiseMissed($db);
			default:
				throw new Exception('Loại xem chi tiết không hợp lệ');
		}
	}

	protected static function soRowLinks($soId) {
		$soId = (int) $soId;
		return array(
			'detail_url' => 'index.php?module=SalesOrder&view=Detail&record=' . $soId . '&app=SALES',
			'print_url' => 'index.php?module=SalesOrder&view=Print&record=' . $soId . '&app=SALES',
		);
	}

	protected static function mapSalesOrderRows(PearDatabase $db, $r) {
		$rows = array();
		if (!$r) {
			return $rows;
		}
		while ($row = $db->fetchByAssoc($r)) {
			$id = (int) $row['id'];
			$links = self::soRowLinks($id);
			$contact = self::decodeText(trim((string) ($row['contact_name'] ?? '')));
			$rows[] = array(
				'id' => $id,
				'no' => self::decodeText($row['no'] ?? ''),
				'contact' => $contact !== '' ? $contact : '—',
				'status' => self::translateStatus($row['status'] ?? '', 'SalesOrder'),
				'total' => (float) ($row['total'] ?? 0),
				'created' => (string) ($row['created'] ?? ''),
				'detail_url' => $links['detail_url'],
				'print_url' => $links['print_url'],
			);
		}
		return $rows;
	}

	protected static function drillSalesOrdersByPeriod(PearDatabase $db, $period) {
		list($from, $to) = self::periodBounds($period);
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		$labels = array(
			'today' => 'Doanh thu hôm nay',
			'week' => 'Doanh thu tuần này',
			'month' => 'Doanh thu tháng này',
			'year' => 'Doanh thu năm nay',
		);
		return array(
			'title' => isset($labels[$period]) ? $labels[$period] : 'Đơn hàng',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function resolveChartYear(PearDatabase $db, $year) {
		$year = (int) $year;
		if ($year < 2000 || $year > 2100) {
			$cal = self::businessCalendar($db);
			$year = (int) $cal['year'];
		}
		return $year;
	}

	protected static function drillSalesOrdersByRegion(PearDatabase $db, $key, $year = 0) {
		$year = self::resolveChartYear($db, $year);
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$kv = self::soKvRegionSqlParts($db);
		$joins = $kv['joins'];
		$expr = $kv['expr'];
		$key = strtolower(trim((string) $key));
		if (preg_match('/khu\s*v[uự]c\s*([123])/u', $key, $m) || preg_match('/^([123])$/', $key, $m) || preg_match('/^kv([123])$/', $key, $m)) {
			$key = $m[1];
		}
		if ($key === 'khác' || $key === 'khac') {
			$key = 'other';
		}

		if ($key === 'other') {
			$regionFilter = " AND NOT (
				TRIM($expr) = 'Khu vực 1' OR TRIM($expr) LIKE 'Khu vực 1,%'
				OR TRIM($expr) = 'Khu vực 2' OR TRIM($expr) LIKE 'Khu vực 2,%'
				OR TRIM($expr) = 'Khu vực 3' OR TRIM($expr) LIKE 'Khu vực 3,%'
				OR LOWER(TRIM($expr)) IN ('kv1','kv2','kv3','1','2','3')
			)";
			$title = 'Doanh thu — Khác (' . $year . ')';
			$params = array_merge($excluded, array($from, $to));
		} else {
			$n = in_array($key, array('1', '2', '3'), true) ? $key : '1';
			$regionFilter = " AND (
				TRIM($expr) = ?
				OR TRIM($expr) LIKE ?
				OR LOWER(TRIM($expr)) IN (?, ?)
			)";
			$title = 'Doanh thu — Khu vực ' . $n . ' (' . $year . ')';
			$params = array_merge($excluded, array($from, $to, 'Khu vực ' . $n, 'Khu vực ' . $n . ',%', 'kv' . $n, $n));
		}

		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 $joins
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 $regionFilter
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		return array(
			'title' => $title,
			'module' => 'SalesOrder',
			'hint' => 'Bấm Chi tiết / In phiếu để mở đơn',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function drillSalesOrdersByProductBucket(PearDatabase $db, $bucket, $year = 0) {
		$year = self::resolveChartYear($db, $year);
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name,
				COALESCE(pr.productname, sv.servicename, '') AS pname,
				COALESCE(pr.productcategory, '') AS pcat,
				COALESCE(sv.servicecategory, '') AS scat
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 LEFT JOIN vtiger_inventoryproductrel ip ON ip.id = so.salesorderid
			 LEFT JOIN vtiger_products pr ON pr.productid = ip.productid
			 LEFT JOIN vtiger_service sv ON sv.serviceid = ip.productid
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 300",
			$params
		);
		$bucket = strtolower(trim((string) $bucket));
		$labels = array('course' => 'Khóa học', 'ingredient' => 'Nguyên liệu', 'franchise' => 'Nhượng quyền');
		$seen = array();
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				if (isset($seen[$id])) {
					continue;
				}
				$got = self::classifyProductBucket((string) $row['pname'], (string) $row['pcat'], (string) $row['scat']);
				if ($got !== $bucket) {
					continue;
				}
				$seen[$id] = true;
				$links = self::soRowLinks($id);
				$contact = self::decodeText(trim((string) ($row['contact_name'] ?? '')));
				$rows[] = array(
					'id' => $id,
					'no' => self::decodeText($row['no'] ?? ''),
					'contact' => $contact !== '' ? $contact : '—',
					'status' => self::translateStatus($row['status'] ?? '', 'SalesOrder'),
					'total' => (float) ($row['total'] ?? 0),
					'created' => (string) ($row['created'] ?? ''),
					'detail_url' => $links['detail_url'],
					'print_url' => $links['print_url'],
				);
				if (count($rows) >= 100) {
					break;
				}
			}
		}
		return array(
			'title' => 'Doanh thu — ' . (isset($labels[$bucket]) ? $labels[$bucket] : 'Sản phẩm') . ' (' . $year . ')',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function drillSalesOrdersBySaleOwner(PearDatabase $db, $ownerId, $year = 0) {
		$ownerId = (int) $ownerId;
		$year = self::resolveChartYear($db, $year);
		$from = $year . '-01-01 00:00:00';
		$to = $year . '-12-31 23:59:59';
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to, $ownerId));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ? AND ce.smownerid = ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		return array(
			'title' => 'Doanh thu — ' . self::userDisplayName($db, $ownerId) . ' (' . $year . ')',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function drillSalesOrdersByChartBucket(PearDatabase $db, $mode, $key, $year = 0) {
		$year = self::resolveChartYear($db, $year);
		$n = (int) $key;
		if ($mode === 'month') {
			if ($n < 1 || $n > 12) {
				$n = 1;
			}
			$from = sprintf('%04d-%02d-01 00:00:00', $year, $n);
			$to = date('Y-m-t 23:59:59', strtotime($from));
			$title = 'Doanh thu — Tháng ' . $n . '/' . $year;
		} elseif ($mode === 'quarter') {
			if ($n < 1 || $n > 4) {
				$n = 1;
			}
			$startMonth = ($n - 1) * 3 + 1;
			$from = sprintf('%04d-%02d-01 00:00:00', $year, $startMonth);
			$endMonth = $startMonth + 2;
			$to = date('Y-m-t 23:59:59', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
			$title = 'Doanh thu — Quý ' . $n . '/' . $year;
		} else {
			if ($n >= 2000 && $n <= 2100) {
				$year = $n;
			}
			$from = $year . '-01-01 00:00:00';
			$to = $year . '-12-31 23:59:59';
			$title = 'Doanh thu — Năm ' . $year;
		}
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($from, $to));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE $notCancelSql AND ce.createdtime >= ? AND ce.createdtime <= ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		return array(
			'title' => $title,
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function orderStatusValues($key) {
		$map = array(
			'draft' => array('Created', 'Nháp', 'Phiếu tạm', 'Draft'),
			'pending' => array('Pending', 'Sent', 'Chờ xác nhận', 'Đang chờ'),
			'delivered' => array('Delivered', 'Hoàn thành', 'shipped', 'Đã giao'),
		);
		return isset($map[$key]) ? $map[$key] : array();
	}

	protected static function drillSalesOrdersByStatusKey(PearDatabase $db, $key) {
		$vals = self::orderStatusValues($key);
		if (empty($vals)) {
			return array('title' => 'Đơn hàng', 'module' => 'SalesOrder', 'columns' => array(), 'rows' => array());
		}
		$ph = implode(',', array_fill(0, count($vals), '?'));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE so.sostatus IN ($ph)
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$vals
		);
		$titles = array(
			'draft' => 'Đơn Nháp',
			'pending' => 'Đơn chờ xác nhận',
			'delivered' => 'Đơn đã giao',
		);
		return array(
			'title' => isset($titles[$key]) ? $titles[$key] : 'Đơn hàng',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function drillSalesOrdersByWarehouseKey(PearDatabase $db, $key) {
		if (self::tableExists($db, 'vtiger_goodsissue')) {
			$whMap = array(
				'waiting' => array('waiting_print', 'picking'),
				'packed' => array('packed', 'prepared'),
				'delivered' => array('shipped', 'completed'),
			);
			$vals = isset($whMap[$key]) ? $whMap[$key] : array();
			if (empty($vals)) {
				return array('title' => 'Theo kho', 'module' => 'SalesOrder', 'rows' => array());
			}
			$ph = implode(',', array_fill(0, count($vals), '?'));
			// Hiển thị trạng thái phiếu xuất (kho), không lấy sostatus bán hàng
			$r = $db->pquery(
				"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, gi.status AS status, ce.createdtime AS created,
					TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
				 FROM vtiger_goodsissue gi
				 INNER JOIN vtiger_salesorder so ON so.salesorderid = gi.salesorder_id
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
				 WHERE gi.deleted = 0
				   AND gi.salesorder_id IS NOT NULL AND gi.salesorder_id > 0
				   AND gi.status IN ($ph)
				 GROUP BY so.salesorderid
				 ORDER BY ce.createdtime DESC
				 LIMIT 100",
				$vals
			);
		} else {
			$fallbackKey = ($key === 'waiting') ? 'pending' : 'delivered';
			if ($key === 'packed') {
				$vals = array('packed', 'Đã soạn');
				$ph = implode(',', array_fill(0, count($vals), '?'));
				$r = $db->pquery(
					"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
						TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
					 FROM vtiger_salesorder so
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
					 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
					 WHERE so.sostatus IN ($ph)
					 ORDER BY ce.createdtime DESC
					 LIMIT 100",
					$vals
				);
				return array(
					'title' => 'Đã soạn',
					'module' => 'SalesOrder',
					'columns' => array('no', 'contact', 'status', 'total', 'actions'),
					'rows' => self::mapSalesOrderRows($db, $r),
				);
			}
			return self::drillSalesOrdersByStatusKey($db, $fallbackKey);
		}
		$titles = array(
			'waiting' => 'Đang chờ soạn',
			'packed' => 'Đã soạn',
			'delivered' => 'Đã giao',
		);
		return array(
			'title' => isset($titles[$key]) ? $titles[$key] : 'Theo kho',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function drillOrdersProcessing(PearDatabase $db) {
		$done = array('Delivered', 'Cancelled', 'Rejected', 'Hoàn thành', 'Đã hủy', 'Từ chối', 'shipped');
		$ph = implode(',', array_fill(0, count($done), '?'));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE so.sostatus NOT IN ($ph) OR so.sostatus IS NULL OR so.sostatus = ''
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$done
		);
		return array(
			'title' => 'Đơn hàng đang xử lý',
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
		);
	}

	protected static function quoteStatusValues($key) {
		$map = array(
			'draft' => array('Nháp', 'Created', 'Draft', 'Đã tạo'),
			'quoted' => array('Sent', 'Báo giá', 'Delivered', 'Xác nhận', 'Accepted', 'Confirmed', 'Chấp nhận'),
			'cancelled' => array('Rejected', 'Huỷ', 'Hủy', 'Cancelled', 'Đã từ chối', 'Đã huỷ', 'Đã hủy'),
		);
		return isset($map[$key]) ? $map[$key] : array();
	}

	protected static function drillQuotesByStatusKey(PearDatabase $db, $key) {
		$vals = self::quoteStatusValues($key);
		if (empty($vals)) {
			return array('title' => 'Báo giá', 'module' => 'Quotes', 'rows' => array());
		}
		$ph = implode(',', array_fill(0, count($vals), '?'));
		$r = $db->pquery(
			"SELECT q.quoteid AS id, q.quote_no AS no, q.total, q.quotestage AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = q.contactid
			 WHERE q.quotestage IN ($ph)
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$vals
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'no' => self::decodeText($row['no']),
					'contact' => self::decodeText(trim($row['contact_name'])) ?: '—',
					'status' => self::translateStatus($row['status'], 'Quotes'),
					'print_url' => '',
				);
			}
		}
		$titles = array('draft' => 'Báo giá Nháp', 'quoted' => 'Báo giá', 'cancelled' => 'Báo giá đã huỷ');
		return array(
			'title' => isset($titles[$key]) ? $titles[$key] : 'Báo giá',
			'module' => 'Quotes',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function drillSalesOrdersByContact(PearDatabase $db, $contactId) {
		if ($contactId <= 0) {
			return array('title' => 'Đơn của khách', 'module' => 'SalesOrder', 'rows' => array());
		}
		list($notCancelSql, $excluded) = self::soNotCancelledSql('so');
		$params = array_merge($excluded, array($contactId));
		$r = $db->pquery(
			"SELECT so.salesorderid AS id, so.salesorder_no AS no, so.total, so.sostatus AS status, ce.createdtime AS created,
				TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS contact_name
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE $notCancelSql AND so.contactid = ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		$name = '';
		$rn = $db->pquery(
			"SELECT TRIM(CONCAT(COALESCE(firstname,''), ' ', COALESCE(lastname,''))) AS n FROM vtiger_contactdetails WHERE contactid = ?",
			array($contactId)
		);
		if ($rn && $db->num_rows($rn)) {
			$name = self::decodeText($db->query_result($rn, 0, 'n'));
		}
		return array(
			'title' => 'Đơn hàng — ' . ($name !== '' ? $name : ('#' . $contactId)),
			'module' => 'SalesOrder',
			'columns' => array('no', 'contact', 'status', 'total', 'actions'),
			'rows' => self::mapSalesOrderRows($db, $r),
			'hint' => 'Bấm In phiếu để mở PHIẾU ĐẶT HÀNG',
		);
	}

	protected static function drillContacts(PearDatabase $db, $key) {
		$sql = 'SELECT cd.contactid AS id,
				TRIM(CONCAT(COALESCE(cd.firstname,\'\'), \' \', COALESCE(cd.lastname,\'\'))) AS name,
				cd.phone, cd.mobile, ce.createdtime AS created
			 FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0';
		$params = array();
		$title = 'Khách hàng';
		if ($key === 'new_month') {
			$cal = self::businessCalendar($db);
			$sql .= ' WHERE ce.createdtime >= ? AND ce.createdtime <= ?';
			$params = array($cal['month_start'], $cal['month_end']);
			$title = 'Khách mới tháng này';
		} elseif ($key === 'active') {
			$sql .= " WHERE ce.modifiedtime >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
			$title = 'Khách đang hoạt động';
		} elseif (in_array($key, array('gold', 'silver', 'bronze'), true)) {
			$tags = array(
				'gold' => array('vang', 'vàng', 'gold'),
				'silver' => array('bac', 'bạc', 'silver'),
				'bronze' => array('dong', 'đồng', 'bronze'),
			);
			$tagList = $tags[$key];
			$ph = implode(',', array_fill(0, count($tagList), '?'));
			$sql = "SELECT DISTINCT cd.contactid AS id,
					TRIM(CONCAT(COALESCE(cd.firstname,''), ' ', COALESCE(cd.lastname,''))) AS name,
					cd.phone, cd.mobile, ce.createdtime AS created
				 FROM vtiger_contactdetails cd
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
				 INNER JOIN vtiger_freetagged_objects fo ON fo.object_id = cd.contactid AND fo.module = 'Contacts'
				 INNER JOIN vtiger_freetags ft ON ft.id = fo.tag_id
				 WHERE LOWER(ft.tag) IN ($ph)";
			$params = $tagList;
			$title = $key === 'gold' ? 'Khách Vàng' : ($key === 'silver' ? 'Khách Bạc' : 'Khách Đồng');
		}
		$sql .= ' ORDER BY ce.createdtime DESC LIMIT 100';
		$r = $db->pquery($sql, $params);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'name' => self::decodeText($row['name']) ?: ('#' . $id),
					'phone' => self::decodeText($row['phone'] ?: $row['mobile']),
					'created' => (string) $row['created'],
					'detail_url' => 'index.php?module=Contacts&view=Detail&record=' . $id . '&app=SALES',
					'orders_drill' => array('type' => 'customer_orders', 'id' => $id),
				);
			}
		}
		return array(
			'title' => $title,
			'module' => 'Contacts',
			'columns' => array('name', 'phone', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function drillLeadsByPeriod(PearDatabase $db, $period) {
		$todayEnd = date('Y-m-d 23:59:59');
		if ($period === 'week') {
			$from = date('Y-m-d 00:00:00', strtotime('monday this week'));
		} elseif ($period === 'month') {
			$from = date('Y-m-01 00:00:00');
		} else {
			$from = date('Y-m-d 00:00:00');
		}
		$r = $db->pquery(
			"SELECT ld.leadid AS id,
				TRIM(CONCAT(COALESCE(ld.firstname,''), ' ', COALESCE(ld.lastname,''))) AS name,
				ld.leadsource, ld.leadstatus, ce.createdtime AS created
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
			 WHERE (ld.converted = 0 OR ld.converted IS NULL OR ld.converted = '0')
			 AND ce.createdtime >= ? AND ce.createdtime <= ?
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			array($from, $todayEnd)
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'name' => self::decodeText($row['name']) ?: ('#' . $id),
					'source' => self::decodeText($row['leadsource']),
					'status' => self::translateStatus($row['leadstatus'], 'Leads'),
					'created' => (string) $row['created'],
					'detail_url' => 'index.php?module=Leads&view=Detail&record=' . $id . '&app=SALES',
				);
			}
		}
		$periodLabels = array(
			'today' => 'Hôm nay',
			'week' => 'Tuần này',
			'month' => 'Tháng này',
		);
		$periodLabel = isset($periodLabels[$period]) ? $periodLabels[$period] : 'Hôm nay';
		return array(
			'title' => 'KH tiềm năng — ' . $periodLabel,
			'module' => 'Leads',
			'columns' => array('name', 'source', 'status', 'actions'),
			'rows' => $rows,
		);
	}

	/**
	 * Danh sách lead theo độ ưu tiên liên hệ (đúng logic leadUrgency).
	 * not_contacted | over_24h | over_72h
	 */
	protected static function drillLeadsByUrgency(PearDatabase $db, $key) {
		$base = self::leadBaseFrom($db);
		$hasCalls = self::tableExists($db, 'bace_lead_last_touch_call');
		$params = array();
		$extra = '';
		$titles = array(
			'not_contacted' => 'Chưa liên hệ — chưa gọi',
			'over_24h' => 'Không nghe máy — quá 24 giờ',
			'over_72h' => 'Không nghe máy — quá 72 giờ',
		);
		$title = isset($titles[$key]) ? $titles[$key] : 'Độ ưu tiên liên hệ';

		if ($hasCalls) {
			if ($key === 'over_24h' || $key === 'over_72h') {
				$hours = $key === 'over_72h' ? 72 : 24;
				$extra = " AND EXISTS (
					SELECT 1 FROM bace_lead_last_touch_call lastc
					WHERE lastc.leadid = ld.leadid
					AND lastc.called_at = (
						SELECT MAX(c2.called_at) FROM bace_lead_last_touch_call c2 WHERE c2.leadid = ld.leadid
					)
					AND lastc.result_label = ?
					AND lastc.called_at IS NOT NULL
					AND lastc.called_at != ''
					AND lastc.called_at != '0000-00-00 00:00:00'
					AND lastc.called_at < DATE_SUB(NOW(), INTERVAL $hours HOUR)
				 )";
				$params[] = 'Không nghe máy';
			} else {
				$extra = " AND NOT EXISTS (
					SELECT 1 FROM bace_lead_last_touch_call c WHERE c.leadid = ld.leadid
				 )";
			}
		} else {
			$hasProfile = self::tableExists($db, 'bace_lead_profile');
			if ($hasProfile) {
				if ($key === 'over_24h' || $key === 'over_72h') {
					$hours = $key === 'over_72h' ? 72 : 24;
					$extra = " AND p.last_touch IS NOT NULL AND p.last_touch != '' AND p.last_touch != '0000-00-00 00:00:00'
						AND p.last_touch < DATE_SUB(NOW(), INTERVAL $hours HOUR)";
				} else {
					$extra = " AND (p.last_touch IS NULL OR p.last_touch = '' OR p.last_touch = '0000-00-00 00:00:00')";
				}
			} else {
				$extra = ' AND ce.createdtime < DATE_SUB(NOW(), INTERVAL 24 HOUR)';
			}
		}

		$r = $db->pquery(
			"SELECT ld.leadid AS id,
				TRIM(CONCAT(COALESCE(ld.firstname,''), ' ', COALESCE(ld.lastname,''))) AS name,
				ld.leadsource, ld.leadstatus, ce.createdtime AS created
			 $base
			 $extra
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'name' => self::decodeText($row['name']) ?: ('#' . $id),
					'source' => self::decodeText($row['leadsource']),
					'status' => self::translateStatus($row['leadstatus'], 'Leads'),
					'created' => (string) $row['created'],
					'detail_url' => 'index.php?module=Leads&view=Detail&record=' . $id . '&app=SALES',
				);
			}
		}
		return array(
			'title' => $title,
			'module' => 'Leads',
			'hint' => 'Bấm Chi tiết để mở lead',
			'columns' => array('name', 'source', 'status', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function drillServiceContracts(PearDatabase $db) {
		$r = $db->pquery(
			"SELECT sc.servicecontractsid AS id, sc.subject, sc.contract_no AS no, sc.contract_status AS status, ce.createdtime AS created
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			array()
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'no' => self::decodeText($row['no']),
					'name' => self::decodeText($row['subject']),
					'status' => self::translateStatus($row['status'], 'ServiceContracts'),
					'detail_url' => 'index.php?module=ServiceContracts&view=Detail&record=' . $id,
				);
			}
		}
		return array(
			'title' => 'Hợp đồng nhượng quyền',
			'module' => 'ServiceContracts',
			'columns' => array('no', 'name', 'status', 'actions'),
			'rows' => $rows,
		);
	}

	protected static function drillFranchiseMissed(PearDatabase $db) {
		$hasCalls = self::tableExists($db, 'bace_sc_last_touch_call');
		$extra = '';
		$params = array();
		if ($hasCalls) {
			$extra = " WHERE NOT EXISTS (
					SELECT 1 FROM bace_sc_last_touch_call c WHERE c.servicecontractsid = sc.servicecontractsid
				 )
				 OR EXISTS (
					SELECT 1 FROM bace_sc_last_touch_call lastc
					WHERE lastc.servicecontractsid = sc.servicecontractsid
					AND lastc.called_at = (
						SELECT MAX(c2.called_at) FROM bace_sc_last_touch_call c2
						WHERE c2.servicecontractsid = sc.servicecontractsid
					)
					AND lastc.result_label = ?
				 )";
			$params[] = 'Không nghe máy';
		}
		$r = $db->pquery(
			"SELECT sc.servicecontractsid AS id, sc.subject, sc.contract_no AS no, sc.contract_status AS status, ce.createdtime AS created
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 $extra
			 ORDER BY ce.createdtime DESC
			 LIMIT 100",
			$params
		);
		$rows = array();
		if ($r) {
			while ($row = $db->fetchByAssoc($r)) {
				$id = (int) $row['id'];
				$rows[] = array(
					'id' => $id,
					'no' => self::decodeText($row['no']),
					'name' => self::decodeText($row['subject']),
					'status' => self::translateStatus($row['status'], 'ServiceContracts'),
					'detail_url' => 'index.php?module=ServiceContracts&view=Detail&record=' . $id,
				);
			}
		}
		return array(
			'title' => 'KH nhượng quyền — chưa gọi / không nghe máy',
			'module' => 'ServiceContracts',
			'hint' => 'Bấm Chi tiết để mở hợp đồng và gọi lại',
			'columns' => array('no', 'name', 'status', 'actions'),
			'rows' => $rows,
		);
	}

	// ─── Utils ─────────────────────────────────────────────────────────

	protected static function userDisplayName(PearDatabase $db, $userId) {
		if ($userId <= 0) {
			return '—';
		}
		$r = $db->pquery(
			'SELECT first_name, last_name, user_name FROM vtiger_users WHERE id = ?',
			array($userId)
		);
		if ($db->num_rows($r)) {
			$name = trim($db->query_result($r, 0, 'first_name') . ' ' . $db->query_result($r, 0, 'last_name'));
			if ($name === '') {
				$name = (string) $db->query_result($r, 0, 'user_name');
			}
			$name = self::decodeText($name);
			return $name !== '' ? $name : ('#' . $userId);
		}
		return '#' . $userId;
	}

	protected static function tableExists(PearDatabase $db, $table) {
		static $cache = array();
		if (isset($cache[$table])) {
			return $cache[$table];
		}
		try {
			$r = $db->pquery('SHOW TABLES LIKE ?', array($table));
			$cache[$table] = ($r && $db->num_rows($r) > 0);
		} catch (Exception $e) {
			$cache[$table] = false;
		}
		return $cache[$table];
	}

	protected static function columnExists(PearDatabase $db, $table, $column) {
		static $cache = array();
		$key = $table . '.' . $column;
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		try {
			$r = $db->pquery('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?', array($column));
			$cache[$key] = ($r && $db->num_rows($r) > 0);
		} catch (Exception $e) {
			$cache[$key] = false;
		}
		return $cache[$key];
	}
}
