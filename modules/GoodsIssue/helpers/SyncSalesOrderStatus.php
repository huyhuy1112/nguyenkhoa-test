<?php
/**
 * Keep SalesOrder.sostatus aligned with linked GoodsIssue.status (xuất bán).
 */
class GoodsIssue_SyncSalesOrderStatus_Helper {

	/**
	 * Map GI DB status → SO sostatus (same codes so list labels stay in sync).
	 *
	 * @param string $issueStatus
	 * @return string
	 */
	public static function mapIssueStatusToSoStatus($issueStatus) {
		$s = strtolower(trim((string) $issueStatus));
		if ($s === '' ) {
			return '';
		}
		if (in_array($s, array('waiting_print', 'draft', 'pending_approval'), true)) {
			return 'waiting_print';
		}
		if ($s === 'picking') {
			return 'picking';
		}
		if (in_array($s, array('packed', 'prepared', 'approved'), true)) {
			return 'packed';
		}
		if (in_array($s, array('shipped', 'completed'), true)) {
			return 'shipped';
		}
		if (in_array($s, array('rejected', 'cancelled', 'canceled'), true)) {
			return 'rejected';
		}
		return $s;
	}

	/**
	 * @param int $issueId
	 * @param string|null $issueStatus If null, read from DB
	 * @return bool True when SO was updated
	 */
	public static function syncFromIssueId($issueId, $issueStatus = null) {
		$issueId = (int) $issueId;
		if ($issueId <= 0) {
			return false;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT salesorder_id, status FROM vtiger_goodsissue WHERE issueid = ? AND deleted = 0 LIMIT 1',
			array($issueId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return false;
		}
		$salesOrderId = (int) $db->query_result($rs, 0, 'salesorder_id');
		if ($salesOrderId <= 0) {
			return false;
		}
		if ($issueStatus === null || $issueStatus === '') {
			$issueStatus = (string) $db->query_result($rs, 0, 'status');
		}
		return self::syncFromSalesOrderId($salesOrderId, $issueStatus);
	}

	/**
	 * @param int $salesOrderId
	 * @param string $issueStatus
	 * @return bool
	 */
	public static function syncFromSalesOrderId($salesOrderId, $issueStatus) {
		$salesOrderId = (int) $salesOrderId;
		$soStatus = self::mapIssueStatusToSoStatus($issueStatus);
		if ($salesOrderId <= 0 || $soStatus === '') {
			return false;
		}
		if (getSalesEntityType($salesOrderId) !== 'SalesOrder') {
			return false;
		}

		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT sostatus FROM vtiger_salesorder WHERE salesorderid = ? LIMIT 1',
			array($salesOrderId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return false;
		}
		$current = trim((string) $db->query_result($rs, 0, 'sostatus'));
		if ($current === $soStatus) {
			return false;
		}

		$db->pquery(
			'UPDATE vtiger_salesorder SET sostatus = ? WHERE salesorderid = ?',
			array($soStatus, $salesOrderId)
		);

		// Best-effort status history (same table SO module uses).
		try {
			$accountName = '';
			$total = 0;
			$infoRs = $db->pquery(
				'SELECT so.total, COALESCE(acc.accountname, \'\') AS accountname
				 FROM vtiger_salesorder so
				 LEFT JOIN vtiger_account acc ON acc.accountid = so.accountid
				 WHERE so.salesorderid = ? LIMIT 1',
				array($salesOrderId)
			);
			if ($infoRs && $db->num_rows($infoRs) > 0) {
				$accountName = (string) $db->query_result($infoRs, 0, 'accountname');
				$total = (float) $db->query_result($infoRs, 0, 'total');
			}
			$historyId = (int) $db->getUniqueID('vtiger_sostatushistory');
			$db->pquery(
				'INSERT INTO vtiger_sostatushistory(historyid, salesorderid, accountname, total, sostatus, lastmodified)
				 VALUES(?,?,?,?,?,?)',
				array($historyId, $salesOrderId, $accountName, $total, $soStatus, date('Y-m-d H:i:s'))
			);
		} catch (Exception $ignore) {
			// History table/columns may differ — status update above is enough.
		}

		return true;
	}
}
