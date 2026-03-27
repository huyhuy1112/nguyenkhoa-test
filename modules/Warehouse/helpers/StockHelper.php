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
}
