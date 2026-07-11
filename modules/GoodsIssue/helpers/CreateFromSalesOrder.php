<?php
/**
 * Create outbound slip (phiếu xuất) from Sales Order — status "waiting_print", no stock movement.
 */
class GoodsIssue_CreateFromSalesOrder_Helper {

	const STATUS_WAITING_PRINT = 'waiting_print';

	public static function findBySalesOrderId($salesOrderId) {
		$salesOrderId = (int) $salesOrderId;
		if ($salesOrderId <= 0) {
			return 0;
		}
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT issueid FROM vtiger_goodsissue WHERE salesorder_id = ? AND deleted = 0 LIMIT 1',
			array($salesOrderId)
		);
		if ($rs && $db->num_rows($rs) > 0) {
			return (int) $db->query_result($rs, 0, 'issueid');
		}
		return 0;
	}

	public static function loadSalesOrderLines($salesOrderId) {
		$salesOrderId = (int) $salesOrderId;
		if ($salesOrderId <= 0) {
			return array();
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT ipr.productid, ipr.quantity, ipr.listprice, ipr.comment,
			        COALESCE(ps.productsservicesname, p.productname, s.servicename, ipr.comment) AS product_name
			 FROM vtiger_inventoryproductrel ipr
			 LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ipr.productid
			 LEFT JOIN vtiger_products p ON p.productid = ipr.productid
			 LEFT JOIN vtiger_service s ON s.serviceid = ipr.productid
			 WHERE ipr.id = ?
			 ORDER BY ipr.sequence_no ASC',
			array($salesOrderId)
		);
		$lines = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$qty = (float) $row['quantity'];
			if ($qty <= 0) {
				continue;
			}
			$lines[] = array(
				'productid' => (int) $row['productid'],
				'product_name' => trim((string) $row['product_name']),
				'quantity' => $qty,
				'unit_price' => (float) $row['listprice'],
				'comment' => trim((string) $row['comment']),
			);
		}
		return $lines;
	}

	/**
	 * @return array List of Vietnamese error messages; empty when valid.
	 */
	public static function validateWarehouseStock($lines, $warehouseName) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$db = PearDatabase::getInstance();
		$errors = array();
		foreach ($lines as $line) {
			$productId = (int) $line['productid'];
			$qty = (float) $line['quantity'];
			$name = (string) $line['product_name'];
			if ($qty <= 0) {
				continue;
			}
			$available = Warehouse_Stock_Helper::sumAvailableQtyForProductAtWarehouse(
				$db,
				$productId,
				$name,
				$warehouseName
			);
			if ($available < $qty) {
				$errors[] = sprintf(
					'%s: tồn kho %s chỉ còn %s, không đủ cho số lượng đặt %s.',
					$name !== '' ? $name : ('SP #' . $productId),
					$warehouseName !== '' ? $warehouseName : 'tổng',
					Warehouse_Stock_Helper::formatNumber($available, 2),
					Warehouse_Stock_Helper::formatNumber($qty, 2)
				);
			}
		}
		return $errors;
	}

	/**
	 * @param bool $requireStock When false (default for waiting_print), skip stock gate —
	 *                           stock is only reserved/deducted when the slip is printed/confirmed.
	 */
	public static function createFromSalesOrder($salesOrderId, $warehouseId, $warehouseName, $userId, $requireStock = false) {
		$salesOrderId = (int) $salesOrderId;
		$userId = (int) $userId;
		if ($salesOrderId <= 0) {
			return 0;
		}

		$existing = self::findBySalesOrderId($salesOrderId);
		if ($existing > 0) {
			return $existing;
		}

		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$lines = self::loadSalesOrderLines($salesOrderId);
		if (empty($lines)) {
			return 0;
		}

		if ($requireStock) {
			$stockErrors = self::validateWarehouseStock($lines, $warehouseName);
			if (!empty($stockErrors)) {
				throw new Exception(implode(' ', $stockErrors));
			}
		}

		$db = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$issuedDate = date('Y-m-d');

		$soSubject = '';
		$soRef = '';
		$destination = '';
		$rsSo = $db->pquery(
			'SELECT so.subject, so.salesorder_no, so.contactid, so.potentialid,
			        COALESCE(acc.accountname, \'\') AS organization,
			        TRIM(CONCAT(IFNULL(cd.firstname, \'\'), \' \', IFNULL(cd.lastname, \'\'))) AS contact_name
			 FROM vtiger_salesorder so
			 LEFT JOIN vtiger_account acc ON acc.accountid = so.accountid
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE so.salesorderid = ? LIMIT 1',
			array($salesOrderId)
		);
		if ($rsSo && $db->num_rows($rsSo) > 0) {
			$soSubject = trim((string) $db->query_result($rsSo, 0, 'subject'));
			$soRef = trim((string) $db->query_result($rsSo, 0, 'salesorder_no'));
			$destination = trim((string) $db->query_result($rsSo, 0, 'contact_name'));
			$organization = trim((string) $db->query_result($rsSo, 0, 'organization'));
			$contactId = (int) $db->query_result($rsSo, 0, 'contactid');
			$potentialId = (int) $db->query_result($rsSo, 0, 'potentialid');
			if ($destination === '' && $contactId <= 0 && $potentialId > 0) {
				require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
				$potContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
				if ($potContactId > 0) {
					$destination = Vtiger_MkSalesCustomerName_Helper::readContactNameById($potContactId);
				}
			}
			if ($destination === '') {
				$destination = $organization;
			}
		}
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$destination = Warehouse_Stock_Helper::decodeDisplayText($destination);
		$destination = Warehouse_Stock_Helper::decodeDisplayText($destination);
		$soSubject = Warehouse_Stock_Helper::decodeDisplayText($soSubject);
		$soRef = Warehouse_Stock_Helper::decodeDisplayText($soRef);
		if ($soRef === '' && $soSubject !== '') {
			$soRef = $soSubject;
		}

		$dayKey = str_replace('-', '', $issuedDate);
		$prefix = 'OUT-' . $dayKey . '-';
		$rsMax = $db->pquery(
			'SELECT MAX(CAST(SUBSTRING(code, 14) AS UNSIGNED)) AS max_seq
			 FROM vtiger_goodsissue
			 WHERE issued_date = ? AND code LIKE ?',
			array($issuedDate, $prefix . '%')
		);
		$nextSeq = (int) $db->query_result($rsMax, 0, 'max_seq') + 1;
		$code = $prefix . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);

		$issuedBy = '';
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			if ($user) {
				$issuedBy = trim((string) $user->get('user_name'));
			}
		} catch (Exception $e) {
			$issuedBy = '';
		}

		$subject = $soSubject !== '' ? $soSubject : ('Xuất kho cho SO #' . $salesOrderId);
		$note = 'Tự động tạo từ Đơn hàng #' . $salesOrderId;
		$mkMeta = array(
			'outboundType' => 'sale',
			'soRef' => $soRef,
			'createdBy' => $issuedBy,
			'timeline' => array(
				array(
					'at' => gmdate('c'),
					'by' => $issuedBy,
					'action' => 'Chờ in phiếu',
					'note' => $note,
				),
			),
		);
		$mkMetaJson = json_encode($mkMeta, JSON_UNESCAPED_UNICODE);

		$issueId = (int) $db->getUniqueID('vtiger_goodsissue');
		$db->pquery(
			'INSERT INTO vtiger_goodsissue(
				issueid, code, subject, issued_by, issued_date, destination, storage_location,
				note, status, salesorder_id, warehouse_id, mk_meta_json,
				createdby, updatedby, createdtime, updatedtime, deleted
			) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)',
			array(
				$issueId,
				$code,
				$subject,
				$issuedBy,
				$issuedDate,
				$destination,
				$warehouseName,
				$note,
				self::STATUS_WAITING_PRINT,
				$salesOrderId,
				$warehouseId,
				$mkMetaJson,
				$userId,
				$userId,
				$now,
				$now,
			)
		);

		foreach ($lines as $line) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsissue_items');
			$db->pquery(
				'INSERT INTO vtiger_goodsissue_items(
					itemid, issueid, productid, product_name, product_type, quantity, unit_price,
					discount_percent, serial_number, description, line_note
				) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
				array(
					$itemId,
					$issueId,
					(int) $line['productid'] > 0 ? (int) $line['productid'] : null,
					(string) $line['product_name'],
					'Other',
					(float) $line['quantity'],
					(float) $line['unit_price'],
					0,
					'',
					(string) $line['comment'],
					'',
				)
			);
		}

		return $issueId;
	}
}

?>
