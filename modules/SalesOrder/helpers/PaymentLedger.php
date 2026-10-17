<?php
/*+***********************************************************************************
 * Per-order customer payment installments.
 * Khách đã trả stays the running total. Each increase is one history row.
 *************************************************************************************/

class SalesOrder_PaymentLedger_Helper {

	public static function ensureTable() {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'CREATE TABLE IF NOT EXISTS vtiger_mk_so_payment (
				paymentid INT NOT NULL AUTO_INCREMENT,
				salesorderid INT NOT NULL,
				amount DECIMAL(25,8) NOT NULL DEFAULT 0,
				createdtime DATETIME NOT NULL,
				userid INT NOT NULL DEFAULT 0,
				PRIMARY KEY (paymentid),
				KEY mk_so_payment_so (salesorderid, paymentid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
	}

	public static function readReceived($salesorderId) {
		$salesorderId = (int) $salesorderId;
		if ($salesorderId <= 0) {
			return 0.0;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT received, total FROM vtiger_salesorder WHERE salesorderid = ?',
			array($salesorderId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return 0.0;
		}
		$paid = (float) $db->query_result($rs, 0, 'received');
		return $paid > 0 ? $paid : 0.0;
	}

	/**
	 * Log a new installment when the stored paid total goes up.
	 * Existing paid amounts with no rows are kept as the first installment.
	 */
	public static function recordIncrease($salesorderId, $previousPaid) {
		$salesorderId = (int) $salesorderId;
		if ($salesorderId <= 0) {
			return;
		}
		self::ensureTable();
		$previousPaid = max(0.0, (float) $previousPaid);
		$current = self::readReceived($salesorderId);
		self::backfillIfEmpty($salesorderId, $previousPaid);
		$delta = $current - $previousPaid;
		if ($delta > 0.5) {
			self::insertRow($salesorderId, $delta, date('Y-m-d H:i:s'));
		}
	}

	public static function listForOrder($salesorderId) {
		$salesorderId = (int) $salesorderId;
		$out = array(
			'order_no' => '',
			'total' => 0.0,
			'rows' => array(),
		);
		if ($salesorderId <= 0) {
			return $out;
		}
		self::ensureTable();
		$db = PearDatabase::getInstance();
		$head = $db->pquery(
			'SELECT salesorder_no, total, received FROM vtiger_salesorder WHERE salesorderid = ?',
			array($salesorderId)
		);
		if (!$head || $db->num_rows($head) < 1) {
			return $out;
		}
		$total = (float) $db->query_result($head, 0, 'total');
		$received = (float) $db->query_result($head, 0, 'received');
		if ($received < 0) {
			$received = 0;
		}
		$out['order_no'] = decode_html((string) $db->query_result($head, 0, 'salesorder_no'));
		$out['total'] = $total;
		$out['total_display'] = CurrencyField::convertToUserFormat($total, null, true);
		$out['paid_display'] = CurrencyField::convertToUserFormat($received, null, true);
		self::backfillIfEmpty($salesorderId, $received);

		$rs = $db->pquery(
			'SELECT amount, createdtime FROM vtiger_mk_so_payment WHERE salesorderid = ? ORDER BY paymentid ASC',
			array($salesorderId)
		);
		$running = 0.0;
		$n = $rs ? $db->num_rows($rs) : 0;
		for ($i = 0; $i < $n; $i++) {
			$amount = (float) $db->query_result($rs, $i, 'amount');
			if ($amount < 0) {
				$amount = 0;
			}
			$running += $amount;
			$remaining = $total - $running;
			if ($remaining < 0) {
				$remaining = 0;
			}
			$created = (string) $db->query_result($rs, $i, 'createdtime');
			$when = $created;
			if ($created !== '') {
				$when = (new DateTimeField($created))->getDisplayDateTimeValue();
			}
			$amountDisplay = CurrencyField::convertToUserFormat($amount, null, true);
			$totalDisplay = CurrencyField::convertToUserFormat($total, null, true);
			$out['rows'][] = array(
				'seq' => $i + 1,
				'no' => $out['order_no'],
				'when' => $when,
				'total' => $totalDisplay,
				'amount' => $amountDisplay,
				'paid' => $amountDisplay,
				'remaining' => CurrencyField::convertToUserFormat($remaining, null, true),
			);
		}
		return $out;
	}

	protected static function backfillIfEmpty($salesorderId, $amount) {
		$amount = (float) $amount;
		if ($amount <= 0.5) {
			return;
		}
		$db = PearDatabase::getInstance();
		$cnt = $db->pquery(
			'SELECT COUNT(*) AS n FROM vtiger_mk_so_payment WHERE salesorderid = ?',
			array((int) $salesorderId)
		);
		$n = ($cnt && $db->num_rows($cnt) > 0) ? (int) $db->query_result($cnt, 0, 'n') : 0;
		if ($n > 0) {
			return;
		}
		$when = date('Y-m-d H:i:s');
		$timeRs = $db->pquery(
			'SELECT createdtime FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
			array((int) $salesorderId)
		);
		if ($timeRs && $db->num_rows($timeRs) > 0) {
			$created = trim((string) $db->query_result($timeRs, 0, 'createdtime'));
			if ($created !== '' && $created !== '0000-00-00 00:00:00') {
				$when = $created;
			}
		}
		self::insertRow($salesorderId, $amount, $when);
	}

	protected static function insertRow($salesorderId, $amount, $when) {
		global $current_user;
		$userId = (!empty($current_user) && !empty($current_user->id)) ? (int) $current_user->id : 0;
		$db = PearDatabase::getInstance();
		$db->pquery(
			'INSERT INTO vtiger_mk_so_payment (salesorderid, amount, createdtime, userid) VALUES (?, ?, ?, ?)',
			array((int) $salesorderId, (float) $amount, $when, $userId)
		);
	}
}
