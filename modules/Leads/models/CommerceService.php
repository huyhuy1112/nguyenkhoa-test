<?php
/*+***********************************************************************************
 * Modern Leads Phase 2 — live commerce + calendar from vtiger CRM modules.
 *************************************************************************************/

class Leads_CommerceService {

	const MODULE = 'Leads';

	public static function getPurchasesForLeadIds(array $leadIds) {
		if (empty($leadIds)) {
			return array();
		}
		$leadIds = array_values(array_unique(array_map('intval', $leadIds)));
		$live = self::fetchLivePurchases($leadIds);
		$mock = self::fetchMockPurchases($leadIds);
		$out = array();
		foreach ($leadIds as $leadId) {
			$out[$leadId] = !empty($live[$leadId]) ? $live[$leadId] : ($mock[$leadId] ?? array());
		}
		return $out;
	}

	public static function getCalendarTasksForLeadIds(array $leadIds) {
		if (empty($leadIds)) {
			return array();
		}
		$leadIds = array_values(array_unique(array_map('intval', $leadIds)));
		$live = self::fetchLiveCalendarTasks($leadIds);
		$mock = self::fetchMockCalendarTasks($leadIds);
		$out = array();
		foreach ($leadIds as $leadId) {
			$liveItems = $live[$leadId] ?? array();
			$mockItems = $mock[$leadId] ?? array();
			if (empty($liveItems)) {
				$out[$leadId] = $mockItems;
			} elseif (empty($mockItems)) {
				$out[$leadId] = $liveItems;
			} else {
				$out[$leadId] = self::mergeCalendarTaskLists($liveItems, $mockItems);
			}
		}
		return $out;
	}

	public static function linkActivityToLead($leadId, $activityId) {
		$leadId = (int)self::resolveLeadIdSimple($leadId);
		$activityId = (int)$activityId;
		if ($leadId <= 0 || $activityId <= 0) {
			throw new Exception('Invalid lead or activity id.');
		}
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			'SELECT 1 FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ?',
			array($leadId, $activityId)
		);
		if (!$exists || $adb->num_rows($exists) < 1) {
			$adb->pquery(
				'INSERT INTO vtiger_seactivityrel (crmid, activityid) VALUES (?, ?)',
				array($leadId, $activityId)
			);
		}
		return true;
	}

	protected static function mergeCalendarTaskLists(array $primary, array $secondary) {
		$seen = array();
		$out = array();
		foreach (array_merge($primary, $secondary) as $task) {
			if (!is_array($task)) {
				continue;
			}
			$key = !empty($task['id']) ? 'id:' . (int)$task['id'] : 's:' . strtolower(trim((string)($task['subject'] ?? '')));
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$out[] = $task;
		}
		return $out;
	}

	public static function relateSalesOrder($leadId, $salesOrderId) {
		return self::linkSalesOrderToLead($leadId, $salesOrderId);
	}

	public static function linkSalesOrderToLead($leadId, $salesOrderId) {
		$leadId = (int)self::resolveLeadIdSimple($leadId);
		$salesOrderId = (int)$salesOrderId;
		if ($leadId <= 0 || $salesOrderId <= 0) {
			throw new Exception('Invalid lead or sales order id.');
		}
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			"SELECT 1 FROM vtiger_crmentityrel
			 WHERE (crmid = ? AND module = ? AND relcrmid = ? AND relmodule = 'SalesOrder')
			    OR (relcrmid = ? AND relmodule = ? AND crmid = ? AND module = 'SalesOrder')",
			array($leadId, self::MODULE, $salesOrderId, $leadId, self::MODULE, $salesOrderId)
		);
		if (!$exists || $adb->num_rows($exists) < 1) {
			$adb->pquery(
				"INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?,?,?,?)",
				array($leadId, self::MODULE, $salesOrderId, 'SalesOrder')
			);
		}
		if (self::hasSalesOrderLeadField()) {
			$cf = $adb->pquery("SELECT salesorderid FROM vtiger_salesordercf WHERE salesorderid = ?", array($salesOrderId));
			if ($cf && $adb->num_rows($cf) > 0) {
				$adb->pquery("UPDATE vtiger_salesordercf SET lead_id = ? WHERE salesorderid = ?", array($leadId, $salesOrderId));
			} else {
				$adb->pquery("INSERT INTO vtiger_salesordercf(salesorderid, lead_id) VALUES(?,?)", array($salesOrderId, $leadId));
			}
		}
		return true;
	}

	public static function searchSalesOrders($query, $limit = 20) {
		$adb = PearDatabase::getInstance();
		$query = trim((string)$query);
		$params = array();
		$where = 'ce.deleted = 0';
		if ($query !== '') {
			$where .= ' AND (so.salesorder_no LIKE ? OR so.subject LIKE ?)';
			$like = '%' . $query . '%';
			$params[] = $like;
			$params[] = $like;
		}
		$res = $adb->pquery(
			"SELECT so.salesorderid, so.salesorder_no, so.subject, so.total, ce.createdtime
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid
			 WHERE {$where}
			 ORDER BY ce.modifiedtime DESC
			 LIMIT " . (int)$limit,
			$params
		);
		$rows = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			$no = self::decodeText($adb->query_result($res, $i, 'salesorder_no'));
			$subject = self::decodeText($adb->query_result($res, $i, 'subject'));
			$rows[] = array(
				'id' => $soId,
				'orderNo' => $no,
				'subject' => $subject,
				'total' => (float)$adb->query_result($res, $i, 'total'),
				'label' => trim($no . ' — ' . $subject),
			);
		}
		return $rows;
	}

	protected static function resolveLeadIdSimple($idOrCacheId) {
		if ($idOrCacheId === null || $idOrCacheId === '') {
			return 0;
		}
		if (is_numeric($idOrCacheId)) {
			return (int)$idOrCacheId;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE mk_cache_id = ?", array($idOrCacheId));
		if ($res && $adb->num_rows($res) > 0) {
			return (int)$adb->query_result($res, 0, 'leadid');
		}
		return 0;
	}

	protected static function hasSalesOrderLeadField() {
		static $has = null;
		if ($has !== null) {
			return $has;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SHOW COLUMNS FROM vtiger_salesordercf LIKE 'lead_id'", array());
		$has = ($res && $adb->num_rows($res) > 0);
		return $has;
	}

	protected static function fetchLivePurchases(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$orderIdsByLead = self::resolveSalesOrderIdsByLead($leadIds);
		$allOrderIds = array();
		foreach ($orderIdsByLead as $ids) {
			foreach ($ids as $id) {
				$allOrderIds[(int)$id] = true;
			}
		}
		if (empty($allOrderIds)) {
			return array();
		}
		$orderIdList = array_keys($allOrderIds);
		$linesByOrder = self::fetchSalesOrderLineItems($orderIdList);
		$metaByOrder = self::fetchSalesOrderMeta($orderIdList);
		$out = array();
		foreach ($orderIdsByLead as $leadId => $soIds) {
			$rows = array();
			foreach ($soIds as $soId) {
				$soId = (int)$soId;
				$meta = $metaByOrder[$soId] ?? array();
				$lines = $linesByOrder[$soId] ?? array();
				if (empty($lines) && !empty($meta)) {
					$rows[] = array(
						'orderId' => $meta['orderId'],
						'orderName' => $meta['orderName'],
						'product' => $meta['orderName'],
						'qty' => 1,
						'value' => (float)$meta['orderTotal'],
						'date' => $meta['date'],
						'source' => 'salesorder',
						'crmid' => $soId,
					);
					continue;
				}
				foreach ($lines as $line) {
					$rows[] = array(
						'orderId' => $meta['orderId'] ?: (string)$soId,
						'orderName' => $meta['orderName'] ?: ($meta['orderId'] ?: ('SO #' . $soId)),
						'product' => $line['product'],
						'qty' => (int)$line['qty'],
						'value' => (float)$line['value'],
						'date' => $meta['date'],
						'source' => 'salesorder',
						'crmid' => $soId,
					);
				}
			}
			if (!empty($rows)) {
				$out[(int)$leadId] = $rows;
			}
		}
		return $out;
	}

	protected static function resolveSalesOrderIdsByLead(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$out = array();
		foreach ($leadIds as $leadId) {
			$out[(int)$leadId] = array();
		}

		if (self::hasSalesOrderLeadField()) {
			$res = $adb->pquery(
				"SELECT cf.lead_id AS leadid, so.salesorderid
				 FROM vtiger_salesordercf cf
				 INNER JOIN vtiger_salesorder so ON so.salesorderid = cf.salesorderid
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE cf.lead_id IN (" . generateQuestionMarks($leadIds) . ")",
				$leadIds
			);
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$leadId = (int)$adb->query_result($res, $i, 'leadid');
				$soId = (int)$adb->query_result($res, $i, 'salesorderid');
				if (isset($out[$leadId]) && $soId > 0) {
					$out[$leadId][$soId] = $soId;
				}
			}
		}

		$res = $adb->pquery(
			"SELECT crmid AS leadid, relcrmid AS salesorderid
			 FROM vtiger_crmentityrel
			 WHERE module = ? AND relmodule = 'SalesOrder' AND crmid IN (" . generateQuestionMarks($leadIds) . ")
			 UNION
			 SELECT relcrmid AS leadid, crmid AS salesorderid
			 FROM vtiger_crmentityrel
			 WHERE relmodule = ? AND module = 'SalesOrder' AND relcrmid IN (" . generateQuestionMarks($leadIds) . ")",
			array_merge(array(self::MODULE), $leadIds, array(self::MODULE), $leadIds)
		);
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int)$adb->query_result($res, $i, 'leadid');
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			if (isset($out[$leadId]) && $soId > 0) {
				$out[$leadId][$soId] = $soId;
			}
		}

		$contactMap = self::resolveContactIdsByLead($leadIds);
		$contactIds = array();
		foreach ($contactMap as $ids) {
			foreach ($ids as $cid) {
				$contactIds[(int)$cid] = true;
			}
		}
		if (!empty($contactIds)) {
			$cidList = array_keys($contactIds);
			$soRes = $adb->pquery(
				"SELECT so.salesorderid, so.contactid
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE so.contactid IN (" . generateQuestionMarks($cidList) . ")",
				$cidList
			);
			for ($i = 0; $i < $adb->num_rows($soRes); $i++) {
				$soId = (int)$adb->query_result($soRes, $i, 'salesorderid');
				$contactId = (int)$adb->query_result($soRes, $i, 'contactid');
				foreach ($contactMap as $leadId => $cids) {
					if (isset($cids[$contactId])) {
						$out[(int)$leadId][$soId] = $soId;
					}
				}
			}
		}

		foreach ($out as $leadId => $ids) {
			$out[$leadId] = array_values($ids);
		}
		return $out;
	}

	protected static function resolveContactIdsByLead(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$out = array();
		$phonesByLead = array();
		$emailsByLead = array();

		$res = $adb->pquery(
			"SELECT ld.leadid, ld.email, la.phone, la.mobile
			 FROM vtiger_leaddetails ld
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = ld.leadid
			 WHERE ld.leadid IN (" . generateQuestionMarks($leadIds) . ")",
			$leadIds
		);
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int)$adb->query_result($res, $i, 'leadid');
			$phonesByLead[$leadId] = array();
			$emailsByLead[$leadId] = array();
			foreach (array('phone', 'mobile') as $col) {
				$norm = self::normalizePhone($adb->query_result($res, $i, $col));
				if ($norm !== '') {
					$phonesByLead[$leadId][$norm] = true;
				}
			}
			$email = strtolower(trim((string)$adb->query_result($res, $i, 'email')));
			if ($email !== '' && $email !== '--') {
				$emailsByLead[$leadId][$email] = true;
			}
			$out[$leadId] = array();
		}

		$allPhones = array();
		foreach ($phonesByLead as $norms) {
			foreach (array_keys($norms) as $p) {
				$allPhones[$p] = true;
			}
		}
		$allEmails = array();
		foreach ($emailsByLead as $emails) {
			foreach (array_keys($emails) as $e) {
				$allEmails[$e] = true;
			}
		}

		$contactIds = array();
		if (!empty($allPhones)) {
			foreach (array_keys($allPhones) as $norm) {
				$suffix = strlen($norm) >= 9 ? substr($norm, -9) : $norm;
				if ($suffix === '') {
					continue;
				}
				$like = '%' . $suffix;
				$phoneRes = $adb->pquery(
					"SELECT contactid, phone, mobile FROM vtiger_contactdetails
					 WHERE contactid > 0 AND (
						REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'.',''),'+','') LIKE ?
						OR REPLACE(REPLACE(REPLACE(REPLACE(mobile,' ',''),'-',''),'.',''),'+','') LIKE ?
					 )",
					array($like, $like)
				);
				for ($i = 0; $i < $adb->num_rows($phoneRes); $i++) {
					$contactId = (int)$adb->query_result($phoneRes, $i, 'contactid');
					foreach ($phonesByLead as $leadId => $norms) {
						foreach (array_keys($norms) as $leadNorm) {
							$leadSuffix = strlen($leadNorm) >= 9 ? substr($leadNorm, -9) : $leadNorm;
							$contactNorm = self::normalizePhone($adb->query_result($phoneRes, $i, 'phone'));
							$mobileNorm = self::normalizePhone($adb->query_result($phoneRes, $i, 'mobile'));
							if ($leadSuffix !== '' && ($leadSuffix === substr($contactNorm, -strlen($leadSuffix)) || $leadSuffix === substr($mobileNorm, -strlen($leadSuffix)))) {
								$out[(int)$leadId][$contactId] = $contactId;
							}
						}
					}
				}
			}
		}
		if (!empty($allEmails)) {
			$emailRes = $adb->pquery(
				"SELECT contactid, email FROM vtiger_contactdetails
				 WHERE contactid > 0 AND email IN (" . generateQuestionMarks(array_keys($allEmails)) . ")",
				array_keys($allEmails)
			);
			for ($i = 0; $i < $adb->num_rows($emailRes); $i++) {
				$contactId = (int)$adb->query_result($emailRes, $i, 'contactid');
				$email = strtolower(trim((string)$adb->query_result($emailRes, $i, 'email')));
				foreach ($emailsByLead as $leadId => $emails) {
					if (isset($emails[$email])) {
						$out[(int)$leadId][$contactId] = $contactId;
					}
				}
			}
		}

		return $out;
	}

	protected static function fetchSalesOrderMeta(array $orderIds) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT so.salesorderid, so.salesorder_no, so.subject, so.total, ce.createdtime
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.salesorderid IN (" . generateQuestionMarks($orderIds) . ")",
			$orderIds
		);
		$out = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			$created = $adb->query_result($res, $i, 'createdtime');
			$orderNo = self::decodeText($adb->query_result($res, $i, 'salesorder_no'));
			$subject = self::decodeText($adb->query_result($res, $i, 'subject'));
			$out[$soId] = array(
				'orderId' => $orderNo !== '' ? $orderNo : (string)$soId,
				'orderName' => $subject !== '' ? $subject : ($orderNo !== '' ? $orderNo : ('SO #' . $soId)),
				'orderTotal' => (float)$adb->query_result($res, $i, 'total'),
				'date' => self::formatPurchaseDate($created),
			);
		}
		return $out;
	}

	protected static function fetchSalesOrderLineItems(array $orderIds) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT ipr.id AS salesorderid, ipr.productid, ipr.quantity, ipr.listprice, ipr.discount_amount,
				COALESCE(p.productname, s.servicename, CONCAT('SP #', ipr.productid)) AS productname
			 FROM vtiger_inventoryproductrel ipr
			 LEFT JOIN vtiger_products p ON p.productid = ipr.productid
			 LEFT JOIN vtiger_service s ON s.serviceid = ipr.productid
			 WHERE ipr.id IN (" . generateQuestionMarks($orderIds) . ")
			 ORDER BY ipr.id ASC, ipr.sequence_no ASC, ipr.lineitem_id ASC",
			$orderIds
		);
		$out = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			$qty = (float)$adb->query_result($res, $i, 'quantity');
			$listPrice = (float)$adb->query_result($res, $i, 'listprice');
			$discount = (float)$adb->query_result($res, $i, 'discount_amount');
			$value = max(0, ($qty * $listPrice) - $discount);
			if (!isset($out[$soId])) {
				$out[$soId] = array();
			}
			$out[$soId][] = array(
				'product' => self::decodeText($adb->query_result($res, $i, 'productname')),
				'qty' => (int)round($qty),
				'value' => $value,
			);
		}
		return $out;
	}

	protected static function fetchLiveCalendarTasks(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT rel.crmid AS leadid, a.activityid, a.activitytype, a.subject, a.status, a.eventstatus,
				a.date_start, a.time_start, a.due_date, a.time_end
			 FROM vtiger_seactivityrel rel
			 INNER JOIN vtiger_activity a ON a.activityid = rel.activityid
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			 WHERE rel.crmid IN (" . generateQuestionMarks($leadIds) . ")
			   AND a.activitytype NOT IN ('Emails')
			 ORDER BY rel.crmid ASC, a.due_date ASC, a.date_start ASC, a.activityid ASC",
			$leadIds
		);
		$out = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int)$adb->query_result($res, $i, 'leadid');
			$row = self::mapActivityRow($adb, $res, $i);
			if (!$row) {
				continue;
			}
			if (!isset($out[$leadId])) {
				$out[$leadId] = array();
			}
			$out[$leadId][] = $row;
		}
		return $out;
	}

	protected static function mapActivityRow(PearDatabase $adb, $res, $i) {
		$activityType = self::decodeText($adb->query_result($res, $i, 'activitytype'));
		$uiType = self::mapActivityType($activityType);
		if ($uiType === null) {
			return null;
		}
		$statusRaw = self::decodeText($adb->query_result($res, $i, 'status'));
		$eventStatus = self::decodeText($adb->query_result($res, $i, 'eventstatus'));
		$isClosed = self::isActivityClosed($activityType, $statusRaw, $eventStatus);
		$dueAt = self::activityDueAt(
			$activityType,
			$adb->query_result($res, $i, 'due_date'),
			$adb->query_result($res, $i, 'time_end'),
			$adb->query_result($res, $i, 'date_start'),
			$adb->query_result($res, $i, 'time_start')
		);
		return array(
			'id' => (int)$adb->query_result($res, $i, 'activityid'),
			'type' => $uiType,
			'subject' => self::decodeText($adb->query_result($res, $i, 'subject')),
			'status' => $isClosed ? 'completed' : 'open',
			'dueAt' => $dueAt,
			'dueLabel' => self::dueLabel($dueAt),
			'source' => 'calendar',
		);
	}

	protected static function fetchMockPurchases(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT leadid, order_id, order_name, product, qty, value, purchase_date
			 FROM bace_lead_purchases WHERE leadid IN (" . generateQuestionMarks($leadIds) . ")
			 ORDER BY sort_order ASC, id ASC",
			$leadIds
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$leadId = (int)$row['leadid'];
			if (!isset($map[$leadId])) {
				$map[$leadId] = array();
			}
			$map[$leadId][] = array(
				'orderId' => self::decodeText($row['order_id']),
				'orderName' => self::decodeText($row['order_name']),
				'product' => self::decodeText($row['product']),
				'qty' => (int)$row['qty'],
				'value' => (float)$row['value'],
				'date' => self::decodeText($row['purchase_date']),
				'source' => 'mock',
			);
		}
		return $map;
	}

	protected static function fetchMockCalendarTasks(array $leadIds) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT leadid, task_type, subject, status, due_at, due_label
			 FROM bace_lead_calendar_tasks WHERE leadid IN (" . generateQuestionMarks($leadIds) . ")
			 ORDER BY sort_order ASC, id ASC",
			$leadIds
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$leadId = (int)$row['leadid'];
			if (!isset($map[$leadId])) {
				$map[$leadId] = array();
			}
			$dueAt = !empty($row['due_at']) ? date('c', strtotime($row['due_at'])) : null;
			$map[$leadId][] = array(
				'type' => self::decodeText($row['task_type']),
				'subject' => self::decodeText($row['subject']),
				'status' => self::decodeText($row['status']),
				'dueAt' => $dueAt,
				'dueLabel' => self::decodeText($row['due_label']),
				'source' => 'mock',
			);
		}
		return $map;
	}

	protected static function mapActivityType($activityType) {
		$key = strtolower(trim((string)$activityType));
		if ($key === '' || $key === '--none--') {
			return 'task';
		}
		if ($key === 'task') {
			return 'task';
		}
		if (strpos($key, 'call') !== false) {
			return 'call';
		}
		if ($key === 'meeting' || $key === 'events' || $key === 'event') {
			return 'meeting';
		}
		return 'task';
	}

	protected static function isActivityClosed($activityType, $status, $eventStatus) {
		$key = strtolower(trim((string)$activityType));
		if ($key === 'task') {
			$statusKey = strtolower(trim((string)$status));
			return in_array($statusKey, array('completed', 'deferred'), true);
		}
		$eventKey = strtolower(trim((string)$eventStatus));
		return in_array($eventKey, array('held', 'completed'), true);
	}

	protected static function activityDueAt($activityType, $dueDate, $timeEnd, $dateStart, $timeStart) {
		$key = strtolower(trim((string)$activityType));
		if ($key === 'task') {
			return self::combineDateTime($dueDate, $timeEnd ?: '09:00:00');
		}
		return self::combineDateTime($dateStart, $timeStart ?: '09:00:00');
	}

	protected static function combineDateTime($date, $time) {
		$date = trim((string)$date);
		if ($date === '' || $date === '0000-00-00') {
			return null;
		}
		$time = trim((string)$time);
		if ($time === '') {
			$time = '09:00:00';
		}
		if (strlen($time) === 5) {
			$time .= ':00';
		}
		$ts = strtotime($date . ' ' . $time);
		return ($ts === false) ? null : date('c', $ts);
	}

	protected static function dueLabel($dueAtIso) {
		if (!$dueAtIso) {
			return '';
		}
		$ts = strtotime($dueAtIso);
		if ($ts === false) {
			return '';
		}
		$days = (int)floor((time() - $ts) / 86400);
		if ($days <= 0) {
			return 'Today';
		}
		return $days . 'd ago';
	}

	protected static function formatPurchaseDate($value) {
		if (empty($value)) {
			return '';
		}
		$ts = strtotime($value);
		if ($ts === false) {
			return '';
		}
		return date('d/m/Y', $ts);
	}

	protected static function normalizePhone($phone) {
		return preg_replace('/\D+/', '', (string)$phone);
	}

	protected static function decodeText($value) {
		if ($value === null || $value === '') {
			return '';
		}
		if (!is_string($value)) {
			return $value;
		}
		return decode_html($value);
	}

	/* ---- Potentials (Opportunity) commerce ---- */

	const POTENTIAL_MODULE = 'Potentials';

	public static function getPurchasesForPotentialIds(array $potentialIds) {
		if (empty($potentialIds)) {
			return array();
		}
		$potentialIds = array_values(array_unique(array_map('intval', $potentialIds)));
		$orderIdsByPotential = self::resolveSalesOrderIdsByPotential($potentialIds);
		$allOrderIds = array();
		foreach ($orderIdsByPotential as $ids) {
			foreach ($ids as $id) {
				$allOrderIds[(int)$id] = true;
			}
		}
		if (empty($allOrderIds)) {
			$out = array();
			foreach ($potentialIds as $potentialId) {
				$out[(int)$potentialId] = array();
			}
			return $out;
		}
		$orderIdList = array_keys($allOrderIds);
		$linesByOrder = self::fetchSalesOrderLineItems($orderIdList);
		$metaByOrder = self::fetchSalesOrderMeta($orderIdList);
		$out = array();
		foreach ($potentialIds as $potentialId) {
			$out[(int)$potentialId] = array();
		}
		foreach ($orderIdsByPotential as $potentialId => $soIds) {
			$rows = array();
			foreach ($soIds as $soId) {
				$soId = (int)$soId;
				$meta = $metaByOrder[$soId] ?? array();
				$lines = $linesByOrder[$soId] ?? array();
				if (empty($lines) && !empty($meta)) {
					$rows[] = array(
						'orderId' => $meta['orderId'],
						'orderName' => $meta['orderName'],
						'product' => $meta['orderName'],
						'qty' => 1,
						'value' => (float)$meta['orderTotal'],
						'date' => $meta['date'],
						'source' => 'salesorder',
						'crmid' => $soId,
					);
					continue;
				}
				foreach ($lines as $line) {
					$rows[] = array(
						'orderId' => $meta['orderId'] ?: (string)$soId,
						'orderName' => $meta['orderName'] ?: ($meta['orderId'] ?: ('SO #' . $soId)),
						'product' => $line['product'],
						'qty' => (int)$line['qty'],
						'value' => (float)$line['value'],
						'date' => $meta['date'],
						'source' => 'salesorder',
						'crmid' => $soId,
					);
				}
			}
			$out[(int)$potentialId] = $rows;
		}
		return $out;
	}

	public static function linkSalesOrderToPotential($potentialId, $salesOrderId) {
		$potentialId = (int)$potentialId;
		$salesOrderId = (int)$salesOrderId;
		if ($potentialId <= 0 || $salesOrderId <= 0) {
			throw new Exception('Invalid opportunity or sales order id.');
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE vtiger_salesorder SET potentialid = ? WHERE salesorderid = ?',
			array($potentialId, $salesOrderId)
		);
		$exists = $adb->pquery(
			"SELECT 1 FROM vtiger_crmentityrel
			 WHERE (crmid = ? AND module = ? AND relcrmid = ? AND relmodule = 'SalesOrder')
			    OR (relcrmid = ? AND relmodule = ? AND crmid = ? AND module = 'SalesOrder')",
			array($potentialId, self::POTENTIAL_MODULE, $salesOrderId, $potentialId, self::POTENTIAL_MODULE, $salesOrderId)
		);
		if (!$exists || $adb->num_rows($exists) < 1) {
			$adb->pquery(
				'INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?,?,?,?)',
				array($potentialId, self::POTENTIAL_MODULE, $salesOrderId, 'SalesOrder')
			);
		}
		return true;
	}

	protected static function resolveSalesOrderIdsByPotential(array $potentialIds) {
		$adb = PearDatabase::getInstance();
		$out = array();
		foreach ($potentialIds as $potentialId) {
			$out[(int)$potentialId] = array();
		}

		$res = $adb->pquery(
			'SELECT so.potentialid, so.salesorderid
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.potentialid IN (' . generateQuestionMarks($potentialIds) . ')',
			$potentialIds
		);
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$potentialId = (int)$adb->query_result($res, $i, 'potentialid');
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			if (isset($out[$potentialId]) && $soId > 0) {
				$out[$potentialId][$soId] = $soId;
			}
		}

		$res = $adb->pquery(
			"SELECT crmid AS potentialid, relcrmid AS salesorderid
			 FROM vtiger_crmentityrel
			 WHERE module = ? AND relmodule = 'SalesOrder' AND crmid IN (" . generateQuestionMarks($potentialIds) . ")
			 UNION
			 SELECT relcrmid AS potentialid, crmid AS salesorderid
			 FROM vtiger_crmentityrel
			 WHERE relmodule = ? AND module = 'SalesOrder' AND relcrmid IN (" . generateQuestionMarks($potentialIds) . ')',
			array_merge(array(self::POTENTIAL_MODULE), $potentialIds, array(self::POTENTIAL_MODULE), $potentialIds)
		);
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$potentialId = (int)$adb->query_result($res, $i, 'potentialid');
			$soId = (int)$adb->query_result($res, $i, 'salesorderid');
			if (isset($out[$potentialId]) && $soId > 0) {
				$out[$potentialId][$soId] = $soId;
			}
		}

		return $out;
	}
}
