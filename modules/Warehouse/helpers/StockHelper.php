<?php
/**
 * Presentation / query helpers for aggregated warehouse stock (Storage).
 * Stock identity follows GoodsReceipt_Save_Action::itemKey() + vtiger_warehouse_stock.product_key.
 */
class Warehouse_Stock_Helper {
	public static function normalizeDisplayName($name) {
		$name = trim((string) $name);
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
	 * Build WHERE clause matching goods receipt line items to this stock row.
	 *
	 * @param array $stockRow Row from vtiger_warehouse_stock
	 * @param array $params Output bind parameters
	 * @return string SQL fragment (without AND prefix)
	 */
	public static function inboundItemsMatchWhere(array $stockRow, array &$params) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		if (strpos($key, 'P:') === 0) {
			$pid = (int) substr($key, 2);
			$params[] = $pid;
			return 'gri.productid = ?';
		}
		$params[] = isset($stockRow['product_name']) ? (string) $stockRow['product_name'] : '';
		return '(gri.productid IS NULL OR gri.productid = 0) AND gri.product_name = ?';
	}

	/**
	 * Build WHERE clause matching goods issue line items (Outbound) to this stock row.
	 */
	public static function outboundItemsMatchWhere(array $stockRow, array &$params) {
		$key = isset($stockRow['product_key']) ? (string) $stockRow['product_key'] : '';
		if (strpos($key, 'P:') === 0) {
			$pid = (int) substr($key, 2);
			$params[] = $pid;
			return 'gii.productid = ?';
		}
		$params[] = isset($stockRow['product_name']) ? (string) $stockRow['product_name'] : '';
		return '(gii.productid IS NULL OR gii.productid = 0) AND gii.product_name = ?';
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
