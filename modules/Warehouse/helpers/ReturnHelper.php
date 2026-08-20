<?php
/**
 * Warehouse recall / customer-return slips.
 * Stock always returns to the warehouse on confirm.
 * Refund (SO received) is optional — admin decides per slip.
 */
require_once 'modules/Warehouse/models/WhMgmtService.php';

class Warehouse_Return_Helper {

	public static function ensureSchema(PearDatabase $db = null) {
		if (!$db) {
			$db = PearDatabase::getInstance();
		}
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse_return (
				returnid INT(19) NOT NULL AUTO_INCREMENT,
				code VARCHAR(24) NOT NULL,
				warehouse_id VARCHAR(20) NOT NULL,
				doc_type VARCHAR(16) NOT NULL DEFAULT 'return',
				source_type VARCHAR(24) NOT NULL DEFAULT 'retail',
				source_label VARCHAR(255) DEFAULT NULL,
				salesorderid INT(19) DEFAULT NULL,
				servicecontractsid INT(19) DEFAULT NULL,
				refund TINYINT(1) NOT NULL DEFAULT 0,
				refund_amount DECIMAL(25,8) NOT NULL DEFAULT 0,
				status VARCHAR(32) NOT NULL DEFAULT 'draft',
				note TEXT,
				meta TEXT,
				createdby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (returnid),
				UNIQUE KEY vtiger_wh_return_code_uq (code),
				KEY vtiger_wh_return_wh_idx (warehouse_id),
				KEY vtiger_wh_return_so_idx (salesorderid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse_return_item (
				itemid INT(19) NOT NULL AUTO_INCREMENT,
				returnid INT(19) NOT NULL,
				productid INT(19) DEFAULT 0,
				sku VARCHAR(64) DEFAULT NULL,
				product_name VARCHAR(255) DEFAULT NULL,
				lot VARCHAR(64) DEFAULT NULL,
				qty DECIMAL(25,8) NOT NULL DEFAULT 0,
				expiry DATE DEFAULT NULL,
				mfg_date DATE DEFAULT NULL,
				price DECIMAL(25,8) NOT NULL DEFAULT 0,
				PRIMARY KEY (itemid),
				KEY vtiger_wh_return_item_ret_idx (returnid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
	}

	protected static function nextCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_seq
			 FROM vtiger_warehouse_return
			 WHERE code LIKE 'RCL-%'",
			array()
		);
		$max = 0;
		if ($rs && $db->num_rows($rs) > 0) {
			$max = (int) $db->query_result($rs, 0, 'max_seq');
		}
		return 'RCL-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
	}

	public static function listByWarehouse($warehouseCode) {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		$warehouseCode = trim((string) $warehouseCode);
		$rs = $db->pquery(
			'SELECT * FROM vtiger_warehouse_return
			 WHERE warehouse_id = ? AND deleted = 0
			 ORDER BY returnid DESC',
			array($warehouseCode)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$out[] = self::mapRow($db, $row);
		}
		return $out;
	}

	public static function getByCode($warehouseCode, $code) {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		$rs = $db->pquery(
			'SELECT * FROM vtiger_warehouse_return
			 WHERE warehouse_id = ? AND code = ? AND deleted = 0 LIMIT 1',
			array(trim((string) $warehouseCode), trim((string) $code))
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return null;
		}
		return self::mapRow($db, $db->fetchByAssoc($rs));
	}

	protected static function mapRow(PearDatabase $db, array $row) {
		$returnId = (int) $row['returnid'];
		$items = array();
		$irs = $db->pquery(
			'SELECT * FROM vtiger_warehouse_return_item WHERE returnid = ? ORDER BY itemid ASC',
			array($returnId)
		);
		while ($item = $db->fetchByAssoc($irs)) {
			$items[] = array(
				'sku' => (string) (isset($item['sku']) ? $item['sku'] : ''),
				'name' => Warehouse_WhMgmtService::publicDecode((string) (isset($item['product_name']) ? $item['product_name'] : '')),
				'lot' => (string) (isset($item['lot']) ? $item['lot'] : ''),
				'qty' => (float) $item['qty'],
				'expiry' => (string) (isset($item['expiry']) ? $item['expiry'] : ''),
				'mfg' => (string) (isset($item['mfg_date']) ? $item['mfg_date'] : ''),
				'price' => (float) (isset($item['price']) ? $item['price'] : 0),
				'product_id' => (int) (isset($item['productid']) ? $item['productid'] : 0),
			);
		}
		$created = isset($row['createdtime']) ? $row['createdtime'] : '';
		return array(
			'id' => (string) $row['code'],
			'code' => (string) $row['code'],
			'docType' => (string) $row['doc_type'],
			'sourceType' => (string) $row['source_type'],
			'sourceLabel' => Warehouse_WhMgmtService::publicDecode((string) (isset($row['source_label']) ? $row['source_label'] : '')),
			'salesorderId' => (int) (isset($row['salesorderid']) ? $row['salesorderid'] : 0),
			'servicecontractId' => (int) (isset($row['servicecontractsid']) ? $row['servicecontractsid'] : 0),
			'refund' => !empty($row['refund']),
			'refundAmount' => (float) $row['refund_amount'],
			'status' => (string) $row['status'],
			'note' => Warehouse_WhMgmtService::publicDecode((string) (isset($row['note']) ? $row['note'] : '')),
			'createdAt' => $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c'),
			'lines' => $items,
		);
	}

	/**
	 * Search retail SOs and franchise customers as recall/return sources.
	 */
	public static function searchSources($query) {
		$db = PearDatabase::getInstance();
		$q = trim((string) $query);
		$like = '%' . $q . '%';
		$out = array();

		$hasScCol = false;
		$cols = $db->pquery("SHOW COLUMNS FROM vtiger_salesorder LIKE 'mk_servicecontract_id'", array());
		if ($cols && $db->num_rows($cols) > 0) {
			$hasScCol = true;
		}

		$scSelect = $hasScCol ? ', so.mk_servicecontract_id' : ', 0 AS mk_servicecontract_id';
		$sql = 'SELECT so.salesorderid, so.salesorder_no, so.subject, so.total, so.received, so.accountid'
			. $scSelect . ', a.accountname
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 LEFT JOIN vtiger_account a ON a.accountid = so.accountid
			 WHERE 1=1';
		$params = array();
		if ($q !== '') {
			$sql .= ' AND (so.salesorder_no LIKE ? OR so.subject LIKE ? OR a.accountname LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		$sql .= ' ORDER BY so.salesorderid DESC LIMIT 30';
		$rs = $db->pquery($sql, $params);
		while ($row = $db->fetchByAssoc($rs)) {
			$scId = (int) (isset($row['mk_servicecontract_id']) ? $row['mk_servicecontract_id'] : 0);
			$name = trim((string) (isset($row['accountname']) ? $row['accountname'] : ''));
			$out[] = array(
				'kind' => $scId > 0 ? 'franchise' : 'retail',
				'salesorderId' => (int) $row['salesorderid'],
				'servicecontractId' => $scId,
				'label' => trim((string) $row['salesorder_no']) . ' — ' . ($name !== '' ? $name : (string) $row['subject']),
				'soNo' => (string) $row['salesorder_no'],
				'customer' => $name,
				'total' => (float) $row['total'],
				'received' => (float) $row['received'],
				'lines' => self::soLines((int) $row['salesorderid']),
			);
		}

		$scSql = 'SELECT sc.servicecontractsid, sc.subject, p.full_name, p.phone
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			 WHERE 1=1';
		$scParams = array();
		if ($q !== '') {
			$scSql .= ' AND (sc.subject LIKE ? OR p.full_name LIKE ? OR p.phone LIKE ?)';
			$scParams[] = $like;
			$scParams[] = $like;
			$scParams[] = $like;
		}
		$scSql .= ' ORDER BY sc.servicecontractsid DESC LIMIT 20';
		try {
			$scRs = $db->pquery($scSql, $scParams);
			while ($scRow = $db->fetchByAssoc($scRs)) {
				$scId = (int) $scRow['servicecontractsid'];
				$already = false;
				foreach ($out as $existing) {
					if ((int) $existing['servicecontractId'] === $scId) {
						$already = true;
						break;
					}
				}
				if ($already) {
					continue;
				}
				$name = trim((string) (isset($scRow['full_name']) ? $scRow['full_name'] : ''));
				if ($name === '') {
					$name = trim((string) $scRow['subject']);
				}
				$out[] = array(
					'kind' => 'franchise',
					'salesorderId' => 0,
					'servicecontractId' => $scId,
					'label' => 'NQ #' . $scId . ' — ' . $name,
					'soNo' => '',
					'customer' => $name,
					'total' => 0,
					'received' => 0,
					'lines' => array(),
				);
			}
		} catch (Exception $e) {
			/* profile table may be missing */
		}

		return $out;
	}

	protected static function soLines($soId) {
		$db = PearDatabase::getInstance();
		$soId = (int) $soId;
		if ($soId <= 0) {
			return array();
		}
		$rs = $db->pquery(
			'SELECT ip.productid, ip.quantity, ip.listprice, ip.comment, ip.productid,
			        ps.productsservicesname, ps.sku
			 FROM vtiger_inventoryproductrel ip
			 LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ip.productid
			 WHERE ip.id = ?
			 ORDER BY ip.sequence_no ASC',
			array($soId)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$name = trim((string) (isset($row['productsservicesname']) ? $row['productsservicesname'] : ''));
			$sku = trim((string) (isset($row['sku']) ? $row['sku'] : ''));
			$out[] = array(
				'product_id' => (int) $row['productid'],
				'name' => $name !== '' ? Warehouse_WhMgmtService::publicDecode($name) : ('SP #' . (int) $row['productid']),
				'sku' => $sku,
				'qty' => (float) $row['quantity'],
				'price' => (float) $row['listprice'],
				'lot' => '',
				'expiry' => '',
			);
		}
		return $out;
	}

	public static function save($warehouseCode, array $payload, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		Warehouse_WhMgmtService::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$wh = null;
		foreach (Warehouse_WhMgmtService::listWarehouses($db) as $w) {
			if ($w['id'] === $warehouseCode) {
				$wh = $w;
				break;
			}
		}
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}

		$docType = strtolower(trim((string) (isset($payload['docType']) ? $payload['docType'] : 'return')));
		if ($docType !== 'recall') {
			$docType = 'return';
		}
		$sourceType = strtolower(trim((string) (isset($payload['sourceType']) ? $payload['sourceType'] : 'retail')));
		if ($sourceType !== 'franchise') {
			$sourceType = 'retail';
		}
		$sourceLabel = trim((string) (isset($payload['sourceLabel']) ? $payload['sourceLabel'] : ''));
		$soId = (int) (isset($payload['salesorderId']) ? $payload['salesorderId'] : 0);
		$scId = (int) (isset($payload['servicecontractId']) ? $payload['servicecontractId'] : 0);
		$refund = !empty($payload['refund']);
		$note = trim((string) (isset($payload['note']) ? $payload['note'] : ''));
		$lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();
		$resolved = array();
		$lineTotal = 0.0;
		foreach ($lines as $line) {
			$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
			$sku = trim((string) (isset($line['sku']) ? $line['sku'] : ''));
			$lot = trim((string) (isset($line['lot']) ? $line['lot'] : ''));
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$price = (float) (isset($line['price']) ? $line['price'] : 0);
			$productId = (int) (isset($line['product_id']) ? $line['product_id'] : 0);
			if ($name === '' || $qty <= 0) {
				continue;
			}
			if ($lot === '') {
				$lot = 'RET-' . date('ymd');
			}
			$resolved[] = array(
				'product_id' => $productId,
				'name' => $name,
				'sku' => $sku,
				'lot' => $lot,
				'qty' => $qty,
				'price' => $price,
				'expiry' => isset($line['expiry']) ? $line['expiry'] : '',
				'mfg' => isset($line['mfg']) ? $line['mfg'] : '',
			);
			$lineTotal += $qty * $price;
		}
		if (empty($resolved)) {
			throw new Exception('Thiếu dòng hàng thu hồi / trả.');
		}
		if ($sourceLabel === '') {
			$sourceLabel = $soId > 0 ? ('ĐH #' . $soId) : ($scId > 0 ? ('NQ #' . $scId) : 'Khách lẻ');
		}

		$refundAmount = 0.0;
		if ($refund && $soId > 0) {
			$paidRs = $db->pquery('SELECT received FROM vtiger_salesorder WHERE salesorderid = ?', array($soId));
			$paid = 0.0;
			if ($paidRs && $db->num_rows($paidRs) > 0) {
				$paid = (float) $db->query_result($paidRs, 0, 'received');
			}
			$refundAmount = min($paid, $lineTotal);
			if ($refundAmount < 0) {
				$refundAmount = 0;
			}
		}

		$code = self::nextCode($db);
		$now = date('Y-m-d H:i:s');
		$db->pquery(
			'INSERT INTO vtiger_warehouse_return
			 (code, warehouse_id, doc_type, source_type, source_label, salesorderid, servicecontractsid,
			  refund, refund_amount, status, note, createdby, createdtime, updatedtime, deleted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)',
			array(
				$code, $warehouseCode, $docType, $sourceType, $sourceLabel,
				$soId > 0 ? $soId : null, $scId > 0 ? $scId : null,
				$refund ? 1 : 0, $refundAmount, 'draft', $note,
				(int) $userId, $now, $now,
			)
		);
		$returnId = (int) $db->getLastInsertID();
		if ($returnId <= 0) {
			$lookup = $db->pquery('SELECT returnid FROM vtiger_warehouse_return WHERE code = ? LIMIT 1', array($code));
			$returnId = $lookup ? (int) $db->query_result($lookup, 0, 'returnid') : 0;
		}
		foreach ($resolved as $line) {
			$db->pquery(
				'INSERT INTO vtiger_warehouse_return_item
				 (returnid, productid, sku, product_name, lot, qty, expiry, mfg_date, price)
				 VALUES (?,?,?,?,?,?,?,?,?)',
				array(
					$returnId,
					$line['product_id'],
					$line['sku'],
					$line['name'],
					$line['lot'],
					$line['qty'],
					$line['expiry'] !== '' ? $line['expiry'] : null,
					$line['mfg'] !== '' ? $line['mfg'] : null,
					$line['price'],
				)
			);
		}

		return array(
			'slip' => self::getByCode($warehouseCode, $code),
			'data' => Warehouse_WhMgmtService::getWarehouseData($db, $warehouseCode),
		);
	}

	public static function confirm($warehouseCode, $code, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		$slip = self::getByCode($warehouseCode, $code);
		if (!$slip) {
			throw new Exception('Không tìm thấy phiếu.');
		}
		if ($slip['status'] === 'confirmed') {
			return array(
				'slip' => $slip,
				'data' => Warehouse_WhMgmtService::getWarehouseData($db, $warehouseCode),
			);
		}
		if ($slip['status'] === 'cancelled') {
			throw new Exception('Phiếu đã hủy.');
		}

		$whName = '';
		foreach (Warehouse_WhMgmtService::listWarehouses($db) as $w) {
			if ($w['id'] === $warehouseCode) {
				$whName = $w['name'];
				break;
			}
		}
		foreach ($slip['lines'] as $line) {
			Warehouse_WhMgmtService::publicApplyInboundStockLine($db, $warehouseCode, $whName, array(
				'sku' => $line['sku'],
				'lot' => $line['lot'],
				'qty' => $line['qty'],
				'name' => $line['name'],
				'product_id' => $line['product_id'],
				'price' => $line['price'],
				'expiry' => $line['expiry'],
				'mfg' => $line['mfg'],
				'location' => 'Trả/Thu hồi',
			), $userId);
		}

		if (!empty($slip['refund']) && (int) $slip['salesorderId'] > 0 && (float) $slip['refundAmount'] > 0) {
			self::applySoRefund((int) $slip['salesorderId'], (float) $slip['refundAmount']);
		}

		$db->pquery(
			'UPDATE vtiger_warehouse_return SET status = ?, updatedtime = ? WHERE code = ? AND warehouse_id = ?',
			array('confirmed', date('Y-m-d H:i:s'), $code, $warehouseCode)
		);

		return array(
			'slip' => self::getByCode($warehouseCode, $code),
			'data' => Warehouse_WhMgmtService::getWarehouseData($db, $warehouseCode),
		);
	}

	public static function cancel($warehouseCode, $code) {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		$slip = self::getByCode($warehouseCode, $code);
		if (!$slip) {
			throw new Exception('Không tìm thấy phiếu.');
		}
		if ($slip['status'] === 'confirmed') {
			throw new Exception('Phiếu đã nhập kho, không hủy được.');
		}
		$db->pquery(
			'UPDATE vtiger_warehouse_return SET status = ?, updatedtime = ? WHERE code = ? AND warehouse_id = ?',
			array('cancelled', date('Y-m-d H:i:s'), $code, $warehouseCode)
		);
		return array(
			'slip' => self::getByCode($warehouseCode, $code),
			'data' => Warehouse_WhMgmtService::getWarehouseData($db, trim((string) $warehouseCode)),
		);
	}

	protected static function applySoRefund($soId, $amount) {
		$db = PearDatabase::getInstance();
		$soId = (int) $soId;
		$amount = (float) $amount;
		if ($soId <= 0 || $amount <= 0) {
			return;
		}
		$rs = $db->pquery(
			'SELECT total, received FROM vtiger_salesorder WHERE salesorderid = ?',
			array($soId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return;
		}
		$total = (float) $db->query_result($rs, 0, 'total');
		$paid = (float) $db->query_result($rs, 0, 'received');
		$newPaid = $paid - $amount;
		if ($newPaid < 0) {
			$newPaid = 0;
		}
		$balance = $total - $newPaid;
		if ($balance < 0) {
			$balance = 0;
		}
		$db->pquery(
			'UPDATE vtiger_salesorder SET received = ?, balance = ? WHERE salesorderid = ?',
			array($newPaid, $balance, $soId)
		);
	}

	public static function printPayload($warehouseCode, $code) {
		$slip = self::getByCode($warehouseCode, $code);
		if (!$slip) {
			throw new Exception('Không tìm thấy phiếu.');
		}
		$whName = $warehouseCode;
		foreach (Warehouse_WhMgmtService::listWarehouses() as $w) {
			if ($w['id'] === $warehouseCode) {
				$whName = $w['name'];
				break;
			}
		}
		return array(
			'warehouse' => $whName,
			'warehouseCode' => $warehouseCode,
			'slip' => $slip,
			'company' => 'Nguyên Khoa',
		);
	}
}
