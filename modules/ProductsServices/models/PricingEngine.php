<?php
/*+***********************************************************************************
 * Pricing engine: quantity-based price for use in Quotes, SalesOrder, Invoice.
 * DO NOT modify Vtiger core.
 *************************************************************************************/

class ProductsServices_PricingEngine_Model {

	/**
	 * Get unit price for a given quantity.
	 * IF quantity >= minimum_qty_bulk   -> bulk_price
	 * ELSE IF quantity >= minimum_qty_wholesale -> wholesale_price
	 * ELSE -> retail_price
	 *
	 * @param int|float $quantity
	 * @param array $prices [ 'retail_price' => n, 'wholesale_price' => n, 'bulk_price' => n, 'minimum_qty_wholesale' => n, 'minimum_qty_bulk' => n ]
	 * @return float|null Unit price or null if no price defined
	 */
	public static function getPriceByQuantity($quantity, $prices) {
		$qty = (float) $quantity;
		$retail = isset($prices['retail_price']) ? (float) $prices['retail_price'] : null;
		$wholesale = isset($prices['wholesale_price']) ? (float) $prices['wholesale_price'] : null;
		$bulk = isset($prices['bulk_price']) ? (float) $prices['bulk_price'] : null;
		$minWholesale = isset($prices['minimum_qty_wholesale']) ? (float) $prices['minimum_qty_wholesale'] : 0;
		$minBulk = isset($prices['minimum_qty_bulk']) ? (float) $prices['minimum_qty_bulk'] : 0;

		if ($minBulk > 0 && $qty >= $minBulk && $bulk !== null) {
			return $bulk;
		}
		if ($minWholesale > 0 && $qty >= $minWholesale && $wholesale !== null) {
			return $wholesale;
		}
		return $retail;
	}

	/**
	 * Get price from a ProductsServices record (by record id or array of field values).
	 * @param int $recordId ProductsServices record id
	 * @param int|float $quantity
	 * @return float|null
	 */
	public static function getPriceForRecord($recordId, $quantity) {
		$db = PearDatabase::getInstance();
		$row = $db->pquery(
			"SELECT retail_price, wholesale_price, bulk_price, minimum_qty_wholesale, minimum_qty_bulk FROM vtiger_productsservices WHERE productsservicesid = ?",
			array($recordId)
		);
		if (!$row || $db->num_rows($row) === 0) {
			return null;
		}
		$r = $db->fetchByAssoc($row, 0);
		return self::getPriceByQuantity($quantity, $r);
	}
}
