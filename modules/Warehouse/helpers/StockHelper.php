<?php
/**
 * Presentation / query helpers for aggregated warehouse stock (Storage).
 * Stock identity follows GoodsReceipt_Save_Action::itemKey() + vtiger_warehouse_stock.product_key.
 */
class Warehouse_Stock_Helper {
	/** Available qty below this (and > 0) is flagged as low stock on Storage list. */
	const LOW_STOCK_THRESHOLD = 5;

	/**
	 * Decode HTML entities stored by legacy vtiger purify paths (e.g. Tr&agrave; → Trà).
	 */
	public static function decodeDisplayText($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	public static function normalizeDisplayName($name) {
		$name = self::decodeDisplayText($name);
		if ($name === '') {
			return '';
		}
		$name = preg_replace('/\s+/', ' ', $name);
		return ucwords(mb_strtolower($name, 'UTF-8'));
	}

	public static function formatNumber($value, $decimals = 2) {
		return number_format((float) $value, $decimals, '.', ',');
	}

	/**
	 * VND display (thousand separator ".") for Storage KPIs.
	 */
	public static function formatVnd($value) {
		return number_format((float) $value, 0, ',', '.') . ' ₫';
	}

	/**
	 * Aggregate KPIs for Storage list (same filters as list query).
	 *
	 * @return array sku_in_stock, inventory_value, low_stock_count, outbound_movements
	 */
	public static function computeStorageListStats(PearDatabase $db, array $where, array $params) {
		$fromWhere = 'FROM vtiger_warehouse_stock ws
			LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ws.productid AND ws.productid > 0
			WHERE ' . implode(' AND ', $where);

		$threshold = (float) self::LOW_STOCK_THRESHOLD;
		$aggSql = 'SELECT
			COUNT(CASE WHEN GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) > 0 THEN 1 END) AS sku_in_stock,
			COALESCE(SUM(
				GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) * COALESCE(ws.last_price, 0)
			), 0) AS inventory_value,
			COUNT(CASE
				WHEN GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) > 0
					AND GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) < ?
				THEN 1 END) AS low_stock_count
			' . $fromWhere;

		$aggParams = array_merge(array($threshold), $params);
		$rs = $db->pquery($aggSql, $aggParams);
		$row = $db->fetchByAssoc($rs);

		$outRs = $db->pquery('SELECT COUNT(*) AS c FROM vtiger_goodsissue WHERE deleted = 0', array());
		$outRow = $db->fetchByAssoc($outRs);

		return array(
			'sku_in_stock' => (int) (isset($row['sku_in_stock']) ? $row['sku_in_stock'] : 0),
			'inventory_value' => (float) (isset($row['inventory_value']) ? $row['inventory_value'] : 0),
			'low_stock_count' => (int) (isset($row['low_stock_count']) ? $row['low_stock_count'] : 0),
			'outbound_movements' => (int) (isset($outRow['c']) ? $outRow['c'] : 0),
		);
	}

	/**
	 * Legacy identity: name-based stock key (N:*) from older inbound lines without catalog id.
	 */
	public static function isLegacyNameKey(array $stockRow) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		return $key !== '' && strpos($key, 'N:') === 0;
	}

	/**
	 * Map ProductsServices.item_type to inbound line / storage display type (aligned with GoodsReceipt save).
	 */
	public static function mapCatalogItemTypeToLabel($itemType) {
		$t = strtolower(trim((string) $itemType));
		if ($t === 'product' || $t === 'products') {
			return 'Hardware';
		}
		if ($t === 'software') {
			return 'Software';
		}
		if ($t === 'service' || $t === 'services') {
			return 'Service';
		}
		if ($t === '') {
			return 'Other';
		}
		return 'Other';
	}

	/**
	 * Format datetime for list/detail (display only).
	 */
	public static function formatDateTimeDisplay($value) {
		if ($value === null || $value === '') {
			return '';
		}
		$ts = strtotime((string) $value);
		if ($ts === false) {
			return (string) $value;
		}
		return date('Y-m-d H:i', $ts);
	}

	public static function availableQty($quantity, $shrinkage) {
		$q = (float) $quantity;
		$s = (float) $shrinkage;
		$a = $q - $s;
		return $a > 0 ? $a : 0.0;
	}

	/**
	 * Sum available quantity for a catalog product at a named warehouse.
	 * Falls back to all warehouses when warehouse_name is unset on stock rows.
	 */
	public static function sumAvailableQtyForProductAtWarehouse(PearDatabase $db, $productId, $productName, $warehouseName) {
		$productId = (int) $productId;
		$warehouseName = trim((string) $warehouseName);
		$total = 0.0;

		if ($productId > 0) {
			$params = array($productId);
			$where = 'productid = ?';
			if ($warehouseName !== '') {
				$where .= ' AND (warehouse_name = ? OR warehouse_name IS NULL OR warehouse_name = \'\')';
				$params[] = $warehouseName;
			}
			$rs = $db->pquery(
				"SELECT quantity, shrinkage_qty FROM vtiger_warehouse_stock WHERE {$where}",
				$params
			);
			while ($row = $db->fetchByAssoc($rs)) {
				$total += self::availableQty($row['quantity'], isset($row['shrinkage_qty']) ? $row['shrinkage_qty'] : 0);
			}
			if ($total > 0 || $warehouseName === '') {
				return $total;
			}
		}

		if ($productName !== '') {
			$name = trim(self::decodeDisplayText($productName));
			$params = array(mb_strtolower($name), $name);
			$where = '(LOWER(TRIM(product_name)) = ? OR TRIM(product_name) = ?)';
			if ($warehouseName !== '') {
				$where .= ' AND (warehouse_name = ? OR warehouse_name IS NULL OR warehouse_name = \'\')';
				$params[] = $warehouseName;
			}
			$rs = $db->pquery(
				"SELECT quantity, shrinkage_qty FROM vtiger_warehouse_stock WHERE {$where}",
				$params
			);
			while ($row = $db->fetchByAssoc($rs)) {
				$total += self::availableQty($row['quantity'], isset($row['shrinkage_qty']) ? $row['shrinkage_qty'] : 0);
			}
		}

		if ($total <= 0 && $productId > 0) {
			$row = self::findStockRowByProductKey($db, 'P:' . $productId);
			if ($row) {
				return self::availableQty($row['quantity'], isset($row['shrinkage_qty']) ? $row['shrinkage_qty'] : 0);
			}
		}

		return $total;
	}

	/**
	 * Build WHERE clause matching goods receipt line items to this stock row.
	 *
	 * @param array $stockRow Row from vtiger_warehouse_stock
	 * @param array $params Output bind parameters
	 * @return string SQL fragment (without AND prefix)
	 */
	/**
	 * Parse warehouse product_key (P:/N: with optional :S: / :E: suffixes from inbound save).
	 *
	 * @return array{type: string, product_id: int, name_key: string, serial_key: string}
	 */
	public static function parseProductKey($productKey) {
		$key = trim((string) $productKey);
		$parsed = array(
			'type' => '',
			'product_id' => 0,
			'name_key' => '',
			'serial_key' => '',
		);
		if ($key === '') {
			return $parsed;
		}
		if (preg_match('/^P:(\d+)/', $key, $m)) {
			$parsed['type'] = 'P';
			$parsed['product_id'] = (int) $m[1];
		} elseif (preg_match('/^N:([^:]+)/u', $key, $m)) {
			$parsed['type'] = 'N';
			$parsed['name_key'] = mb_strtolower(trim($m[1]));
		}
		if (preg_match('/:S:([^:]+)/u', $key, $sm)) {
			$parsed['serial_key'] = mb_strtolower(trim($sm[1]));
		}
		return $parsed;
	}

	/**
	 * Expiry date embedded in product_key (:E:YYYY-MM-DD from inbound save).
	 */
	public static function extractExpiryFromProductKey($productKey) {
		$key = trim((string) $productKey);
		if ($key === '') {
			return '';
		}
		if (preg_match('/:E:(\d{4}-\d{2}-\d{2})/', $key, $m)) {
			return $m[1];
		}
		return '';
	}

	/**
	 * Resolve expiry (Y-m-d) for an inventory line item (stock row, key suffix, or inbound line).
	 */
	public static function resolveExpiredDateForIdentityItem(PearDatabase $db, array $item) {
		require_once 'modules/Warehouse/helpers/InventoryCrossNavHelper.php';
		$key = Inventory_CrossNav_Helper::stockStyleItemKey($item);
		if ($key !== '') {
			$stock = self::findStockRowByProductKey($db, $key);
			if ($stock) {
				$exp = isset($stock['expired_date']) ? trim((string) $stock['expired_date']) : '';
				if ($exp !== '') {
					return $exp;
				}
				$pk = isset($stock['product_key']) ? (string) $stock['product_key'] : $key;
				$fromKey = self::extractExpiryFromProductKey($pk);
				if ($fromKey !== '') {
					return $fromKey;
				}
			}
			$fromKey = self::extractExpiryFromProductKey($key);
			if ($fromKey !== '') {
				return $fromKey;
			}
		}
		return self::fetchInboundExpiredDateForItem($db, $item);
	}

	/**
	 * Earliest expiry across line items (for receipt/issue info header).
	 *
	 * @param array<int, array> $items
	 */
	public static function resolveEarliestExpiredDate(PearDatabase $db, array $items) {
		$earliest = '';
		foreach ($items as $item) {
			$exp = self::resolveExpiredDateForIdentityItem($db, $item);
			if ($exp !== '' && ($earliest === '' || $exp < $earliest)) {
				$earliest = $exp;
			}
		}
		return $earliest;
	}

	protected static function fetchInboundExpiredDateForItem(PearDatabase $db, array $item) {
		$productId = !empty($item['productid']) ? (int) $item['productid'] : 0;
		$name = self::decodeDisplayText(isset($item['product_name']) ? $item['product_name'] : '');
		$nameKey = mb_strtolower(trim($name));
		$serial = trim((string) (isset($item['serial_number']) ? $item['serial_number'] : ''));

		if ($serial !== '') {
			$params = array($serial);
			$match = 'TRIM(gri.serial_number) <> \'\' AND gri.serial_number = ?';
			if ($productId > 0) {
				$match .= ' AND gri.productid = ?';
				$params[] = $productId;
			} elseif ($nameKey !== '') {
				$match .= ' AND (gri.productid IS NULL OR gri.productid = 0) AND LOWER(TRIM(gri.product_name)) = ?';
				$params[] = $nameKey;
			} else {
				return '';
			}
		} elseif ($productId > 0) {
			$params = array($productId);
			$match = 'gri.productid = ?';
		} elseif ($nameKey !== '') {
			$params = array($nameKey);
			$match = '(gri.productid IS NULL OR gri.productid = 0) AND LOWER(TRIM(gri.product_name)) = ?';
		} else {
			return '';
		}

		$rs = $db->pquery(
			"SELECT gri.expired_date
			 FROM vtiger_goodsreceipt_items gri
			 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			 WHERE {$match} AND gri.expired_date IS NOT NULL AND TRIM(gri.expired_date) <> ''
			 ORDER BY gri.expired_date ASC, gri.itemid DESC
			 LIMIT 1",
			$params
		);
		if ($db->num_rows($rs) > 0) {
			return trim((string) $db->query_result($rs, 0, 'expired_date'));
		}

		if ($nameKey === '') {
			return '';
		}
		$rs = $db->pquery(
			"SELECT gri.expired_date, gri.product_name
			 FROM vtiger_goodsreceipt_items gri
			 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			 WHERE (gri.productid IS NULL OR gri.productid = 0)
			   AND gri.expired_date IS NOT NULL AND TRIM(gri.expired_date) <> ''
			 ORDER BY gri.expired_date ASC, gri.itemid DESC
			 LIMIT 200",
			array()
		);
		while ($row = $db->fetchByAssoc($rs)) {
			$rowName = mb_strtolower(trim(self::decodeDisplayText(isset($row['product_name']) ? $row['product_name'] : '')));
			if ($rowName === $nameKey) {
				return trim((string) $row['expired_date']);
			}
		}
		return '';
	}

	public static function legacyNameMatchKey(array $row) {
		$key = isset($row['product_key']) ? (string) $row['product_key'] : '';
		$parsed = self::parseProductKey($key);
		if ($parsed['name_key'] !== '') {
			return $parsed['name_key'];
		}
		return mb_strtolower(trim(self::decodeDisplayText(isset($row['product_name']) ? $row['product_name'] : '')));
	}

	public static function inboundItemsMatchWhere(array $stockRow, array &$params) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		$parsed = self::parseProductKey($key);
		if ($parsed['type'] === 'P' && $parsed['product_id'] > 0) {
			$params[] = $parsed['product_id'];
			return 'gri.productid = ?';
		}
		$nameKey = self::legacyNameMatchKey($stockRow);
		if ($nameKey === '') {
			return '1=0';
		}
		$rawName = trim(self::decodeDisplayText(isset($stockRow['product_name']) ? $stockRow['product_name'] : ''));
		$params[] = $nameKey;
		$params[] = mb_strtolower($rawName);
		$params[] = $rawName;
		return '(gri.productid IS NULL OR gri.productid = 0) AND (
			LOWER(TRIM(gri.product_name)) = ?
			OR LOWER(TRIM(gri.product_name)) = ?
			OR TRIM(gri.product_name) = ?
		)';
	}

	/**
	 * Load inbound history rows for a stock row (SQL match + decoded-name fallback).
	 *
	 * @return array<int, array>
	 */
	public static function fetchInboundHistoryRows(PearDatabase $db, array $stockRow) {
		$params = array();
		$match = self::inboundItemsMatchWhere($stockRow, $params);
		$sql = "SELECT gr.receiptid, gr.code, gr.subject, gr.received_date, gr.storage_location, gr.note,
				gr.createdtime AS receipt_createdtime, gr.updatedtime AS receipt_updatedtime,
				gri.quantity, gri.unit_price, gri.product_name, gri.product_type, gri.itemid, gri.serial_number, gri.description
			FROM vtiger_goodsreceipt_items gri
			INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			WHERE {$match}
			ORDER BY gr.received_date DESC, gri.itemid DESC";
		$rows = self::fetchAssocRows($db, $sql, $params);
		if (!empty($rows)) {
			return $rows;
		}
		return self::fetchInboundHistoryRowsFallback($db, $stockRow);
	}

	/**
	 * Load outbound history rows for a stock row (SQL match + decoded-name fallback).
	 *
	 * @return array<int, array>
	 */
	public static function fetchOutboundHistoryRows(PearDatabase $db, array $stockRow) {
		$params = array();
		$match = self::outboundItemsMatchWhere($stockRow, $params);
		$sql = "SELECT gi.issueid, gi.code, gi.subject, gi.issued_date, gi.destination, gi.storage_location,
				gi.createdtime AS issue_createdtime, gi.updatedtime AS issue_updatedtime,
				gii.productid, gii.quantity, gii.unit_price, gii.product_name, gii.product_type, gii.itemid, gii.serial_number, gii.description,
				(
					SELECT gri.description
					FROM vtiger_goodsreceipt_items gri
					INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
					WHERE
						(
							TRIM(gii.serial_number) <> ''
							AND TRIM(gri.serial_number) <> ''
							AND gri.serial_number = gii.serial_number
							AND (
								(gii.productid IS NOT NULL AND gii.productid > 0 AND gri.productid = gii.productid)
								OR
								((gii.productid IS NULL OR gii.productid = 0) AND LOWER(TRIM(gri.product_name)) = LOWER(TRIM(gii.product_name)))
							)
						)
						OR
						(
							(TRIM(gii.serial_number) = '' OR gii.serial_number IS NULL)
							AND gii.productid IS NOT NULL AND gii.productid > 0
							AND gri.productid = gii.productid
						)
						OR
						(
							(TRIM(gii.serial_number) = '' OR gii.serial_number IS NULL)
							AND (gii.productid IS NULL OR gii.productid = 0)
							AND LOWER(TRIM(gri.product_name)) = LOWER(TRIM(gii.product_name))
							AND LOWER(TRIM(COALESCE(gri.product_type,''))) = LOWER(TRIM(COALESCE(gii.product_type,'')))
						)
					ORDER BY gri.itemid DESC
					LIMIT 1
				) AS source_description
			FROM vtiger_goodsissue_items gii
			INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
			WHERE {$match}
			ORDER BY gi.issued_date DESC, gii.itemid DESC";
		$rows = self::fetchAssocRows($db, $sql, $params);
		if (!empty($rows)) {
			return $rows;
		}
		return self::fetchOutboundHistoryRowsFallback($db, $stockRow);
	}

	protected static function fetchAssocRows(PearDatabase $db, $sql, array $params) {
		$rows = array();
		$rs = $db->pquery($sql, $params);
		if (!$rs) {
			return $rows;
		}
		while ($row = $db->fetchByAssoc($rs)) {
			$rows[] = $row;
		}
		return $rows;
	}

	protected static function fetchInboundHistoryRowsFallback(PearDatabase $db, array $stockRow) {
		$parsed = self::parseProductKey(isset($stockRow['product_key']) ? $stockRow['product_key'] : '');
		$target = self::legacyNameMatchKey($stockRow);
		if ($parsed['type'] === 'P' && $parsed['product_id'] > 0) {
			$sql = "SELECT gr.receiptid, gr.code, gr.subject, gr.received_date, gr.storage_location, gr.note,
					gr.createdtime AS receipt_createdtime, gr.updatedtime AS receipt_updatedtime,
					gri.quantity, gri.unit_price, gri.product_name, gri.product_type, gri.itemid, gri.serial_number, gri.description
				FROM vtiger_goodsreceipt_items gri
				INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				WHERE gri.productid = ?
				ORDER BY gr.received_date DESC, gri.itemid DESC";
			return self::fetchAssocRows($db, $sql, array($parsed['product_id']));
		}
		if ($target === '') {
			return array();
		}
		$sql = "SELECT gr.receiptid, gr.code, gr.subject, gr.received_date, gr.storage_location, gr.note,
				gr.createdtime AS receipt_createdtime, gr.updatedtime AS receipt_updatedtime,
				gri.quantity, gri.unit_price, gri.product_name, gri.product_type, gri.itemid, gri.serial_number, gri.description
			FROM vtiger_goodsreceipt_items gri
			INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			WHERE (gri.productid IS NULL OR gri.productid = 0)
			ORDER BY gr.received_date DESC, gri.itemid DESC
			LIMIT 500";
		$rs = $db->pquery($sql, array());
		$rows = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$name = mb_strtolower(trim(self::decodeDisplayText(isset($row['product_name']) ? $row['product_name'] : '')));
			if ($name === $target) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	protected static function fetchOutboundHistoryRowsFallback(PearDatabase $db, array $stockRow) {
		$parsed = self::parseProductKey(isset($stockRow['product_key']) ? $stockRow['product_key'] : '');
		$target = self::legacyNameMatchKey($stockRow);
		if ($parsed['type'] === 'P' && $parsed['product_id'] > 0) {
			$sql = "SELECT gi.issueid, gi.code, gi.subject, gi.issued_date, gi.destination, gi.storage_location,
					gi.createdtime AS issue_createdtime, gi.updatedtime AS issue_updatedtime,
					gii.productid, gii.quantity, gii.unit_price, gii.product_name, gii.product_type, gii.itemid, gii.serial_number, gii.description,
					'' AS source_description
				FROM vtiger_goodsissue_items gii
				INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
				WHERE gii.productid = ?
				ORDER BY gi.issued_date DESC, gii.itemid DESC";
			return self::fetchAssocRows($db, $sql, array($parsed['product_id']));
		}
		if ($target === '') {
			return array();
		}
		$sql = "SELECT gi.issueid, gi.code, gi.subject, gi.issued_date, gi.destination, gi.storage_location,
				gi.createdtime AS issue_createdtime, gi.updatedtime AS issue_updatedtime,
				gii.productid, gii.quantity, gii.unit_price, gii.product_name, gii.product_type, gii.itemid, gii.serial_number, gii.description,
				'' AS source_description
			FROM vtiger_goodsissue_items gii
			INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
			WHERE (gii.productid IS NULL OR gii.productid = 0)
			ORDER BY gi.issued_date DESC, gii.itemid DESC
			LIMIT 500";
		$rs = $db->pquery($sql, array());
		$rows = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$name = mb_strtolower(trim(self::decodeDisplayText(isset($row['product_name']) ? $row['product_name'] : '')));
			if ($name === $target) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Resolve warehouse stock row for outbound save / lookups (exact key, prefix, or decoded name).
	 *
	 * @return array|null stockid, product_key, quantity, shrinkage_qty
	 */
	public static function findStockRowByProductKey(PearDatabase $db, $requestedKey) {
		$key = trim((string) $requestedKey);
		if ($key === '') {
			return null;
		}
		$cols = 'stockid, product_key, productid, product_name, quantity, shrinkage_qty, expired_date';
		$rs = $db->pquery(
			"SELECT {$cols} FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1",
			array($key)
		);
		if ($db->num_rows($rs) > 0) {
			return $db->fetchByAssoc($rs);
		}
		$parsed = self::parseProductKey($key);
		if ($parsed['type'] === 'P' && $parsed['product_id'] > 0) {
			$rs = $db->pquery(
				"SELECT {$cols} FROM vtiger_warehouse_stock WHERE productid = ? ORDER BY updatedtime DESC, stockid DESC LIMIT 1",
				array($parsed['product_id'])
			);
			if ($db->num_rows($rs) > 0) {
				return $db->fetchByAssoc($rs);
			}
		}
		if ($parsed['name_key'] !== '') {
			$prefix = 'N:' . $parsed['name_key'];
			$rs = $db->pquery(
				"SELECT {$cols} FROM vtiger_warehouse_stock
				 WHERE product_key = ? OR product_key LIKE ?
				 ORDER BY updatedtime DESC, stockid DESC
				 LIMIT 1",
				array($prefix, $prefix . ':%')
			);
			if ($db->num_rows($rs) > 0) {
				return $db->fetchByAssoc($rs);
			}
			$rs = $db->pquery("SELECT {$cols} FROM vtiger_warehouse_stock", array());
			while ($row = $db->fetchByAssoc($rs)) {
				if (self::legacyNameMatchKey($row) === $parsed['name_key']) {
					return $row;
				}
			}
		}
		return null;
	}

	/**
	 * Build WHERE clause matching goods issue line items (Outbound) to this stock row.
	 */
	public static function outboundItemsMatchWhere(array $stockRow, array &$params) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		$parsed = self::parseProductKey($key);
		if ($parsed['type'] === 'P' && $parsed['product_id'] > 0) {
			$params[] = $parsed['product_id'];
			return 'gii.productid = ?';
		}
		$nameKey = self::legacyNameMatchKey($stockRow);
		if ($nameKey === '') {
			return '1=0';
		}
		$rawName = trim(self::decodeDisplayText(isset($stockRow['product_name']) ? $stockRow['product_name'] : ''));
		$params[] = $nameKey;
		$params[] = mb_strtolower($rawName);
		$params[] = $rawName;
		return '(gii.productid IS NULL OR gii.productid = 0) AND (
			LOWER(TRIM(gii.product_name)) = ?
			OR LOWER(TRIM(gii.product_name)) = ?
			OR TRIM(gii.product_name) = ?
		)';
	}

	/**
	 * Human-readable product key for display (not a second source of truth).
	 */
	public static function formatProductKeyDisplay(array $stockRow) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		if (strpos($key, 'P:') === 0) {
			return 'P:' . (int) substr($key, 2);
		}
		return $key !== '' ? $key : '—';
	}

	/**
	 * Map ProductsServices.item_type to a short label, or null if unknown.
	 */
	public static function formatProductTypeLabel($itemType) {
		if ($itemType === null || $itemType === '') {
			return null;
		}
		$t = strtolower(trim((string) $itemType));
		if ($t === 'hardware') {
			return 'Hardware';
		}
		if ($t === 'software') {
			return 'Software';
		}
		if ($t === 'product' || $t === 'products') {
			return 'Hardware';
		}
		if ($t === 'service' || $t === 'services') {
			return 'Service';
		}
		if ($t === 'other') {
			return 'Other';
		}
		return (string) $itemType;
	}

	/**
	 * Bulk-load inbound serials (non-deleted receipts) indexed for Storage list/detail.
	 * One query; map in PHP — avoids N+1.
	 *
	 * @return array{by_pid: array<int,array<int,string>>, by_name: array<string,array<int,string>>, by_name_type: array<string,array<int,string>>}
	 */
	public static function fetchInboundSerialIndexes(PearDatabase $db) {
		$sql = "SELECT gri.productid, gri.product_name, gri.product_type, gri.serial_number
			FROM vtiger_goodsreceipt_items gri
			INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			WHERE gri.serial_number IS NOT NULL AND TRIM(gri.serial_number) <> ''";
		$rs = $db->pquery($sql, array());
		$byPid = array();
		$byName = array();
		$byNameType = array();
		if ($rs) {
			while ($row = $db->fetchByAssoc($rs)) {
				$sn = trim((string) $row['serial_number']);
				if ($sn === '') {
					continue;
				}
				$pid = (int) $row['productid'];
				if ($pid > 0) {
					if (!isset($byPid[$pid])) {
						$byPid[$pid] = array();
					}
					$byPid[$pid][$sn] = true;
				}
				$nn = mb_strtolower(trim((string) $row['product_name']));
				if ($nn !== '') {
					if (!isset($byName[$nn])) {
						$byName[$nn] = array();
					}
					$byName[$nn][$sn] = true;
				}
				$nt = mb_strtolower(trim((string) (isset($row['product_type']) ? $row['product_type'] : '')));
				if ($nn !== '' && $nt !== '') {
					$k = $nn . "\0" . $nt;
					if (!isset($byNameType[$k])) {
						$byNameType[$k] = array();
					}
					$byNameType[$k][$sn] = true;
				}
			}
		}
		$sortKeys = function (array $set) {
			$list = array_keys($set);
			sort($list);
			return $list;
		};
		foreach ($byPid as $p => $set) {
			$byPid[$p] = $sortKeys($set);
		}
		foreach ($byName as $n => $set) {
			$byName[$n] = $sortKeys($set);
		}
		foreach ($byNameType as $k => $set) {
			$byNameType[$k] = $sortKeys($set);
		}
		return array(
			'by_pid' => $byPid,
			'by_name' => $byName,
			'by_name_type' => $byNameType,
		);
	}

	/**
	 * Resolve serial list for a vtiger_warehouse_stock row (catalog first, then name/type, then N: key).
	 *
	 * @param array $stockRow Must include productid, product_name, product_key, raw_item_type (as on list/detail).
	 * @param array $indexes From fetchInboundSerialIndexes()
	 * @return array list of serial strings sorted unique
	 */
	public static function resolveInboundSerialsForStockRow(array $stockRow, array $indexes) {
		$byPid = isset($indexes['by_pid']) ? $indexes['by_pid'] : array();
		$byName = isset($indexes['by_name']) ? $indexes['by_name'] : array();
		$byNameType = isset($indexes['by_name_type']) ? $indexes['by_name_type'] : array();

		$pid = !empty($stockRow['productid']) ? (int) $stockRow['productid'] : 0;
		if ($pid > 0 && !empty($byPid[$pid])) {
			return $byPid[$pid];
		}

		$merged = array();
		$nn = mb_strtolower(trim((string) (isset($stockRow['product_name']) ? $stockRow['product_name'] : '')));
		$nt = mb_strtolower(trim((string) (isset($stockRow['raw_item_type']) ? $stockRow['raw_item_type'] : '')));
		if ($nn !== '' && $nt !== '' && !empty($byNameType[$nn . "\0" . $nt])) {
			foreach ($byNameType[$nn . "\0" . $nt] as $s) {
				$merged[$s] = true;
			}
		} elseif ($nn !== '' && !empty($byName[$nn])) {
			foreach ($byName[$nn] as $s) {
				$merged[$s] = true;
			}
		}

		$key = isset($stockRow['product_key']) ? trim((string) $stockRow['product_key']) : '';
		if (empty($merged) && strpos($key, 'N:') === 0) {
			$nk = trim(substr($key, 2));
			if ($nk !== '' && !empty($byName[$nk])) {
				foreach ($byName[$nk] as $s) {
					$merged[$s] = true;
				}
			}
		}

		$list = array_keys($merged);
		sort($list);
		return $list;
	}

	/**
	 * @param array $serials
	 * @return array array(display_string, full_string_for_title)
	 */
	public static function formatSerialDisplayList(array $serials) {
		$serials = array_values(array_filter($serials, function ($s) {
			return $s !== null && trim((string) $s) !== '';
		}));
		$n = count($serials);
		if ($n === 0) {
			return array('', '');
		}
		$full = implode(', ', $serials);
		if ($n <= 3) {
			return array($full, $full);
		}
		$first = array_slice($serials, 0, 3);
		$more = $n - 3;
		return array(implode(', ', $first) . ' (+' . $more . ' more)', $full);
	}
}
