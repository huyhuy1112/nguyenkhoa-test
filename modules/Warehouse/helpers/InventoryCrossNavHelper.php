<?php
/**
 * Cross-navigation between Inbound / Storage / Outbound detail pages.
 * Resolves linked record ids via product_key (same identity as stock save/issue save).
 */
class Inventory_CrossNav_Helper {

	public static function itemKey(array $item) {
		return self::stockStyleItemKey($item);
	}

	/**
	 * Same identity shape as GoodsReceipt save / vtiger_warehouse_stock.product_key.
	 */
	public static function stockStyleItemKey(array $item) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		if (!empty($item['productid'])) {
			$base = 'P:' . (int) $item['productid'];
		} else {
			$name = Warehouse_Stock_Helper::decodeDisplayText(isset($item['product_name']) ? $item['product_name'] : '');
			$name = trim($name);
			if ($name === '') {
				return '';
			}
			$base = 'N:' . mb_strtolower($name);
		}
		$serial = trim((string) (isset($item['serial_number']) ? $item['serial_number'] : ''));
		if ($serial !== '') {
			$base .= ':S:' . mb_strtolower($serial);
		}
		$exp = trim((string) (isset($item['expired_date']) ? $item['expired_date'] : ''));
		if ($exp !== '') {
			$base .= ':E:' . $exp;
		}
		return $base;
	}

	protected static function stockRowFromLineItem(array $item) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		return array(
			'product_key' => self::stockStyleItemKey($item),
			'productid' => !empty($item['productid']) ? (int) $item['productid'] : 0,
			'product_name' => Warehouse_Stock_Helper::decodeDisplayText(isset($item['product_name']) ? $item['product_name'] : ''),
		);
	}

	public static function productKeysFromItems(array $items) {
		$keys = array();
		foreach ($items as $item) {
			$key = self::itemKey($item);
			if ($key !== '') {
				$keys[$key] = true;
			}
		}
		return array_keys($keys);
	}

	/**
	 * Warehouse detail when line items map to exactly one stock row.
	 */
	public static function resolveStockId(PearDatabase $db, array $items) {
		$keyList = self::productKeysFromItems($items);
		if (empty($keyList)) {
			return 0;
		}
		$placeholders = implode(',', array_fill(0, count($keyList), '?'));
		$rs = $db->pquery(
			"SELECT stockid FROM vtiger_warehouse_stock WHERE product_key IN ($placeholders)",
			$keyList
		);
		$stockIds = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$sid = isset($row['stockid']) ? (int) $row['stockid'] : 0;
			if ($sid > 0) {
				$stockIds[$sid] = true;
			}
		}
		if (count($stockIds) !== 1) {
			return 0;
		}
		return (int) key($stockIds);
	}

	/**
	 * Storage detail from an inbound receipt (first matching stock row for its line items).
	 */
	public static function resolveStockIdForInboundReceipt(PearDatabase $db, $receiptId, array $items = array()) {
		$receiptId = (int) $receiptId;
		if ($receiptId > 0) {
			$rs = $db->pquery(
				"SELECT ws.stockid
				 FROM vtiger_goodsreceipt_items gri
				 INNER JOIN vtiger_warehouse_stock ws ON (
					(gri.productid IS NOT NULL AND gri.productid > 0 AND ws.product_key = CONCAT('P:', gri.productid))
					OR
					((gri.productid IS NULL OR gri.productid = 0)
						AND ws.product_key = CONCAT('N:', LOWER(TRIM(gri.product_name))))
				 )
				 WHERE gri.receiptid = ?
				 ORDER BY gri.itemid ASC
				 LIMIT 1",
				array($receiptId)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				$stockId = isset($row['stockid']) ? (int) $row['stockid'] : 0;
				if ($stockId > 0) {
					return $stockId;
				}
			}
		}

		$unique = self::resolveStockId($db, $items);
		if ($unique > 0) {
			return $unique;
		}

		foreach ($items as $item) {
			$stockId = self::findStockIdForLineItem($db, $item);
			if ($stockId > 0) {
				return $stockId;
			}
		}
		return 0;
	}

	protected static function findStockIdForLineItem(PearDatabase $db, array $item) {
		$key = self::itemKey($item);
		if ($key !== '') {
			$rs = $db->pquery(
				'SELECT stockid FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
				array($key)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				return isset($row['stockid']) ? (int) $row['stockid'] : 0;
			}
		}

		$productId = !empty($item['productid']) ? (int) $item['productid'] : 0;
		if ($productId > 0) {
			$rs = $db->pquery(
				'SELECT stockid FROM vtiger_warehouse_stock WHERE productid = ? ORDER BY updatedtime DESC, stockid DESC LIMIT 1',
				array($productId)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				return isset($row['stockid']) ? (int) $row['stockid'] : 0;
			}
		}

		$name = isset($item['product_name']) ? trim((string) $item['product_name']) : '';
		if ($name !== '') {
			$rs = $db->pquery(
				"SELECT stockid FROM vtiger_warehouse_stock
				 WHERE (productid IS NULL OR productid = 0)
				   AND LOWER(TRIM(product_name)) = LOWER(?)
				 ORDER BY updatedtime DESC, stockid DESC
				 LIMIT 1",
				array($name)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				return isset($row['stockid']) ? (int) $row['stockid'] : 0;
			}
		}

		return 0;
	}

	/**
	 * Inbound detail from line items (unique receipt or most recent match).
	 */
	public static function resolveInboundReceiptId(PearDatabase $db, array $items) {
		$keyList = self::productKeysFromItems($items);
		if (empty($keyList)) {
			return 0;
		}
		$clauses = array();
		$params = array();
		foreach ($keyList as $key) {
			if (strpos($key, 'P:') === 0) {
				$clauses[] = 'gri.productid = ?';
				$params[] = (int) substr($key, 2);
			} else {
				$clauses[] = '((gri.productid IS NULL OR gri.productid = 0) AND LOWER(TRIM(gri.product_name)) = ?)';
				$params[] = substr($key, 2);
			}
		}
		$where = implode(' OR ', $clauses);
		$rs = $db->pquery(
			"SELECT DISTINCT gr.receiptid, gr.received_date
			 FROM vtiger_goodsreceipt_items gri
			 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			 WHERE ($where)
			 ORDER BY gr.received_date DESC, gr.receiptid DESC",
			$params
		);
		return self::pickSingleOrMostRecentId($rs, 'receiptid');
	}

	/**
	 * Outbound detail from inbound/outbound line items (most recent match).
	 */
	public static function resolveOutboundIssueId(PearDatabase $db, array $items) {
		if (empty($items)) {
			return 0;
		}
		$bestId = 0;
		$bestTs = 0;
		foreach ($items as $item) {
			$issueId = self::resolveOutboundIssueIdForStock($db, self::stockRowFromLineItem($item));
			if ($issueId <= 0) {
				continue;
			}
			$tsRs = $db->pquery(
				'SELECT issued_date FROM vtiger_goodsissue WHERE issueid = ? AND deleted = 0 LIMIT 1',
				array($issueId)
			);
			$ts = 0;
			if ($db->num_rows($tsRs) > 0) {
				$tsRow = $db->fetchByAssoc($tsRs);
				$ts = strtotime((string) (isset($tsRow['issued_date']) ? $tsRow['issued_date'] : ''));
				if ($ts === false) {
					$ts = 0;
				}
			}
			if ($bestId === 0 || $ts >= $bestTs) {
				$bestId = $issueId;
				$bestTs = $ts;
			}
		}
		return $bestId;
	}

	/**
	 * Outbound detail from an inbound receipt (via linked stock row, then line items).
	 */
	public static function resolveOutboundIssueIdForInboundReceipt(PearDatabase $db, $receiptId, array $items = array()) {
		$stockId = self::resolveStockIdForInboundReceipt($db, $receiptId, $items);
		if ($stockId > 0) {
			require_once 'modules/Warehouse/helpers/StockHelper.php';
			$rs = $db->pquery(
				"SELECT ws.*, COALESCE(NULLIF(ws.product_type, ''), ps.item_type) AS raw_item_type
				 FROM vtiger_warehouse_stock ws
				 LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ws.productid AND ws.productid > 0
				 WHERE ws.stockid = ?",
				array($stockId)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				$row['product_name'] = Warehouse_Stock_Helper::decodeDisplayText(
					isset($row['product_name']) ? $row['product_name'] : ''
				);
				$issueId = self::resolveOutboundIssueIdForStock($db, $row);
				if ($issueId > 0) {
					return $issueId;
				}
			}
		}
		return self::resolveOutboundIssueId($db, $items);
	}

	/**
	 * Inbound detail for a storage row (most recent inbound for this product_key).
	 */
	public static function resolveInboundReceiptIdForStock(PearDatabase $db, array $stockRow) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$params = array();
		$match = Warehouse_Stock_Helper::inboundItemsMatchWhere($stockRow, $params);
		$rs = $db->pquery(
			"SELECT DISTINCT gr.receiptid, gr.received_date
			 FROM vtiger_goodsreceipt_items gri
			 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			 WHERE {$match}
			 ORDER BY gr.received_date DESC, gr.receiptid DESC",
			$params
		);
		return self::pickSingleOrMostRecentId($rs, 'receiptid');
	}

	/**
	 * Outbound detail for a storage row (most recent outbound for this product_key).
	 */
	public static function resolveOutboundIssueIdForStock(PearDatabase $db, array $stockRow) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$params = array();
		$match = Warehouse_Stock_Helper::outboundItemsMatchWhere($stockRow, $params);
		if ($match !== '1=0') {
			$rs = $db->pquery(
				"SELECT DISTINCT gi.issueid, gi.issued_date
				 FROM vtiger_goodsissue_items gii
				 INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
				 WHERE {$match}
				 ORDER BY gi.issued_date DESC, gi.issueid DESC",
				$params
			);
			$id = self::pickSingleOrMostRecentId($rs, 'issueid');
			if ($id > 0) {
				return $id;
			}
		}
		return self::resolveOutboundIssueIdForStockFallback($db, $stockRow);
	}

	/**
	 * Fallback when SQL name match fails (HTML entities, legacy keys with :S:/:E:).
	 */
	protected static function resolveOutboundIssueIdForStockFallback(PearDatabase $db, array $stockRow) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$pid = !empty($stockRow['productid']) ? (int) $stockRow['productid'] : 0;
		if ($pid <= 0) {
			$parsed = Warehouse_Stock_Helper::parseProductKey(isset($stockRow['product_key']) ? $stockRow['product_key'] : '');
			$pid = (int) $parsed['product_id'];
		}
		if ($pid > 0) {
			$rs = $db->pquery(
				"SELECT gi.issueid, gi.issued_date
				 FROM vtiger_goodsissue_items gii
				 INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
				 WHERE gii.productid = ?
				 ORDER BY gi.issued_date DESC, gi.issueid DESC
				 LIMIT 1",
				array($pid)
			);
			if ($db->num_rows($rs) > 0) {
				$row = $db->fetchByAssoc($rs);
				return isset($row['issueid']) ? (int) $row['issueid'] : 0;
			}
		}

		$target = Warehouse_Stock_Helper::legacyNameMatchKey($stockRow);
		if ($target === '') {
			return 0;
		}

		$rs = $db->pquery(
			"SELECT gi.issueid, gi.issued_date, gii.product_name
			 FROM vtiger_goodsissue_items gii
			 INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
			 WHERE (gii.productid IS NULL OR gii.productid = 0)
			 ORDER BY gi.issued_date DESC, gi.issueid DESC
			 LIMIT 300",
			array()
		);
		while ($row = $db->fetchByAssoc($rs)) {
			$name = mb_strtolower(trim(Warehouse_Stock_Helper::decodeDisplayText(
				isset($row['product_name']) ? $row['product_name'] : ''
			)));
			if ($name === $target) {
				return isset($row['issueid']) ? (int) $row['issueid'] : 0;
			}
		}
		return 0;
	}

	/**
	 * Inbound detail linked from outbound line items (source receipt match).
	 */
	public static function resolveInboundReceiptIdFromOutboundItems(PearDatabase $db, array $items) {
		if (empty($items)) {
			return 0;
		}
		$receiptIds = array();
		foreach ($items as $item) {
			$rid = self::findSourceReceiptIdForOutboundItem($db, $item);
			if ($rid > 0) {
				$receiptIds[$rid] = true;
			}
		}
		if (empty($receiptIds)) {
			return self::resolveInboundReceiptId($db, $items);
		}
		if (count($receiptIds) === 1) {
			return (int) key($receiptIds);
		}
		$idList = array_keys($receiptIds);
		$placeholders = implode(',', array_fill(0, count($idList), '?'));
		$rs = $db->pquery(
			"SELECT receiptid, received_date FROM vtiger_goodsreceipt
			 WHERE deleted = 0 AND receiptid IN ($placeholders)
			 ORDER BY received_date DESC, receiptid DESC
			 LIMIT 1",
			$idList
		);
		if ($db->num_rows($rs) <= 0) {
			return 0;
		}
		$row = $db->fetchByAssoc($rs);
		return isset($row['receiptid']) ? (int) $row['receiptid'] : 0;
	}

	protected static function findSourceReceiptIdForOutboundItem(PearDatabase $db, array $item) {
		$productId = !empty($item['productid']) ? (int) $item['productid'] : 0;
		$productName = isset($item['product_name']) ? trim((string) $item['product_name']) : '';
		$productType = isset($item['product_type']) ? trim((string) $item['product_type']) : '';
		$serial = isset($item['serial_number']) ? trim((string) $item['serial_number']) : '';

		if ($serial !== '') {
			$rs = $db->pquery(
				"SELECT gri.receiptid
				 FROM vtiger_goodsreceipt_items gri
				 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				 WHERE TRIM(gri.serial_number) <> '' AND gri.serial_number = ?
				   AND (
						(? > 0 AND gri.productid = ?)
						OR
						(? = 0 AND LOWER(TRIM(gri.product_name)) = LOWER(?))
				   )
				 ORDER BY gri.itemid DESC
				 LIMIT 1",
				array($serial, $productId, $productId, $productId, $productName)
			);
		} elseif ($productId > 0) {
			$rs = $db->pquery(
				"SELECT gri.receiptid
				 FROM vtiger_goodsreceipt_items gri
				 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				 WHERE gri.productid = ?
				 ORDER BY gri.itemid DESC
				 LIMIT 1",
				array($productId)
			);
		} else {
			$rs = $db->pquery(
				"SELECT gri.receiptid
				 FROM vtiger_goodsreceipt_items gri
				 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				 WHERE (gri.productid IS NULL OR gri.productid = 0)
				   AND LOWER(TRIM(gri.product_name)) = LOWER(?)
				   AND LOWER(TRIM(COALESCE(gri.product_type,''))) = LOWER(?)
				 ORDER BY gri.itemid DESC
				 LIMIT 1",
				array($productName, $productType)
			);
		}

		if ($db->num_rows($rs) <= 0) {
			return 0;
		}
		$row = $db->fetchByAssoc($rs);
		return isset($row['receiptid']) ? (int) $row['receiptid'] : 0;
	}

	protected static function pickSingleOrMostRecentId($rs, $idField) {
		$db = PearDatabase::getInstance();
		$firstId = 0;
		$ids = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$id = isset($row[$idField]) ? (int) $row[$idField] : 0;
			if ($id <= 0) {
				continue;
			}
			if ($firstId === 0) {
				$firstId = $id;
			}
			$ids[$id] = true;
		}
		if ($firstId === 0) {
			return 0;
		}
		return $firstId;
	}
}
