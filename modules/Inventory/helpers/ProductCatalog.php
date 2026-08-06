<?php
/**
 * Active Hàng hoá (ProductsServices) catalog for inventory line-item pickers.
 * Includes warehouse stock + open SO/Quote qty + open PO qty for KiotViet-style search.
 */
class Inventory_ProductCatalog_Helper {

	/**
	 * @return array<int, array{id:int,name:string,price:float,type:string,sku:string,unit:string,stock:float,qty_po:float,qty_so:float}>
	 */
	public static function listActiveProducts($limit = 5000) {
		$db = PearDatabase::getInstance();
		$limit = max(1, min(10000, (int) $limit));

		$hasUnit = self::columnExists($db, 'vtiger_productsservices', 'unit');
		$hasNeedsQc = self::columnExists($db, 'vtiger_productsservices', 'needs_qc');
		$unitCol = $hasUnit ? ', ps.unit' : '';
		$needsQcCol = $hasNeedsQc ? ', ps.needs_qc' : '';
		$tierCols = array();
		foreach (array('price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m') as $col) {
			if (self::columnExists($db, 'vtiger_productsservices', $col)) {
				$tierCols[] = 'ps.' . $col;
			}
		}
		$tierSelect = $tierCols ? (', ' . implode(', ', $tierCols)) : '';

		$rs = $db->pquery(
			"SELECT ps.productsservicesid, ps.productsservicesname, ps.price, ps.item_type, ps.sku{$unitCol}{$needsQcCol}{$tierSelect}
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 ORDER BY ps.productsservicesname ASC
			 LIMIT ?",
			array($limit)
		);
		$out = array();
		$ids = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$id = (int) $row['productsservicesid'];
			$name = trim((string) $row['productsservicesname']);
			if ($name !== '' && is_file('modules/Warehouse/helpers/StockHelper.php')) {
				require_once 'modules/Warehouse/helpers/StockHelper.php';
				$name = Warehouse_Stock_Helper::decodeDisplayText($name);
			}
			if ($name === '') {
				continue;
			}
			$sku = trim((string) (isset($row['sku']) ? $row['sku'] : ''));
			$unit = $hasUnit ? trim((string) (isset($row['unit']) ? $row['unit'] : '')) : '';
			if ($unit !== '' && is_file('modules/Warehouse/helpers/StockHelper.php')) {
				require_once 'modules/Warehouse/helpers/StockHelper.php';
				$unit = Warehouse_Stock_Helper::decodeDisplayText($unit);
			}
			$ids[] = $id;
			$needsQcRaw = isset($row['needs_qc']) ? $row['needs_qc'] : 0;
			$needsQc = ($needsQcRaw === 1 || $needsQcRaw === '1' || $needsQcRaw === true || $needsQcRaw === 'on');
			$item = array(
				'id' => $id,
				'name' => $name,
				'price' => (float) (isset($row['price']) ? $row['price'] : 0),
				'type' => (string) (isset($row['item_type']) ? $row['item_type'] : ''),
				'sku' => $sku,
				'unit' => $unit,
				'needsQc' => $needsQc,
				'stock' => 0.0,
				'qty_po' => 0.0,
				'qty_so' => 0.0,
				'price_lt_1m' => isset($row['price_lt_1m']) ? (float) $row['price_lt_1m'] : null,
				'price_gte_1m' => isset($row['price_gte_1m']) ? (float) $row['price_gte_1m'] : null,
				'price_gte_3m' => isset($row['price_gte_3m']) ? (float) $row['price_gte_3m'] : null,
				'price_gte_5m' => isset($row['price_gte_5m']) ? (float) $row['price_gte_5m'] : null,
				'price_gte_7m' => isset($row['price_gte_7m']) ? (float) $row['price_gte_7m'] : null,
			);
			$out[] = $item;
		}

		$stockMap = self::mapWarehouseStock($db, $ids);
		$soMap = self::mapOpenCustomerDemand($db, $ids);
		$poMap = self::mapOpenPurchaseDemand($db, $ids);
		foreach ($out as &$item) {
			$pid = $item['id'];
			if (isset($stockMap[$pid])) {
				$item['stock'] = (float) $stockMap[$pid];
			}
			if (isset($soMap[$pid])) {
				$item['qty_so'] = (float) $soMap[$pid];
			}
			if (isset($poMap[$pid])) {
				$item['qty_po'] = (float) $poMap[$pid];
			}
		}
		unset($item);

		return $out;
	}

	protected static function columnExists(PearDatabase $db, $table, $column) {
		static $cache = array();
		$key = $table . '.' . $column;
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		$res = $db->pquery('SHOW COLUMNS FROM `' . $table . '` LIKE ?', array($column));
		$cache[$key] = ($res && $db->num_rows($res) > 0);
		return $cache[$key];
	}

	protected static function tableExists(PearDatabase $db, $table) {
		static $cache = array();
		if (isset($cache[$table])) {
			return $cache[$table];
		}
		$res = $db->pquery('SHOW TABLES LIKE ?', array($table));
		$cache[$table] = ($res && $db->num_rows($res) > 0);
		return $cache[$table];
	}

	/**
	 * Tồn kho: sum (quantity - shrinkage) across warehouses.
	 */
	protected static function mapWarehouseStock(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds) || !self::tableExists($db, 'vtiger_warehouse_stock')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT productid,
					SUM(GREATEST(IFNULL(quantity,0) - IFNULL(shrinkage_qty,0), 0)) AS stock_qty
				 FROM vtiger_warehouse_stock
				 WHERE productid IN ({$marks})
				 GROUP BY productid",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$map[(int) $row['productid']] = (float) $row['stock_qty'];
			}
		}
		return $map;
	}

	/**
	 * KH đặt: qty trên SalesOrder + Quotes còn mở (chưa hủy/giao xong).
	 */
	protected static function mapOpenCustomerDemand(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds) || !self::tableExists($db, 'vtiger_inventoryproductrel')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);

			if (self::tableExists($db, 'vtiger_salesorder')) {
				$rs = $db->pquery(
					"SELECT ip.productid, SUM(ip.quantity) AS qty
					 FROM vtiger_inventoryproductrel ip
					 INNER JOIN vtiger_salesorder so ON so.salesorderid = ip.id
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
					 WHERE ip.productid IN ({$marks})
					   AND (so.sostatus IS NULL OR so.sostatus NOT IN ('Cancelled','Delivered','Completed','Rejected'))
					 GROUP BY ip.productid",
					$chunk
				);
				if ($rs) {
					while ($row = $db->fetchByAssoc($rs)) {
						$pid = (int) $row['productid'];
						$map[$pid] = (isset($map[$pid]) ? $map[$pid] : 0) + (float) $row['qty'];
					}
				}
			}

			if (self::tableExists($db, 'vtiger_quotes')) {
				$rs = $db->pquery(
					"SELECT ip.productid, SUM(ip.quantity) AS qty
					 FROM vtiger_inventoryproductrel ip
					 INNER JOIN vtiger_quotes q ON q.quoteid = ip.id
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
					 WHERE ip.productid IN ({$marks})
					   AND (q.quotestage IS NULL OR q.quotestage NOT IN ('Rejected','Cancelled','Accepted'))
					 GROUP BY ip.productid",
					$chunk
				);
				if ($rs) {
					while ($row = $db->fetchByAssoc($rs)) {
						$pid = (int) $row['productid'];
						$map[$pid] = (isset($map[$pid]) ? $map[$pid] : 0) + (float) $row['qty'];
					}
				}
			}
		}
		return $map;
	}

	/**
	 * Đặt NCC: qty trên PurchaseOrder còn mở.
	 */
	protected static function mapOpenPurchaseDemand(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds)
			|| !self::tableExists($db, 'vtiger_inventoryproductrel')
			|| !self::tableExists($db, 'vtiger_purchaseorder')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT ip.productid, SUM(ip.quantity) AS qty
				 FROM vtiger_inventoryproductrel ip
				 INNER JOIN vtiger_purchaseorder po ON po.purchaseorderid = ip.id
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = po.purchaseorderid AND ce.deleted = 0
				 WHERE ip.productid IN ({$marks})
				   AND (po.postatus IS NULL OR po.postatus NOT IN ('Cancelled','Received Shipment','Completed','Rejected'))
				 GROUP BY ip.productid",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$map[(int) $row['productid']] = (float) $row['qty'];
			}
		}
		return $map;
	}

	public static function toJson() {
		return json_encode(self::listActiveProducts(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
	}

	public static function assignToViewer(Vtiger_Viewer $viewer) {
		try {
			$viewer->assign('MK_PRODUCT_CATALOG_JSON', self::toJson());
		} catch (Exception $e) {
			$viewer->assign('MK_PRODUCT_CATALOG_JSON', '[]');
		}
	}
}
