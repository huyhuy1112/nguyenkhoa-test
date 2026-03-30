<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Quotes_QuoteItemTypeHelper {

	/**
	 * Classify a Quote by its saved line-item types.
	 *
	 * @param int $quoteId
	 * @return string One of: product_only, service_only, mixed, empty
	 */
	public static function classifyQuoteByLineItems($quoteId) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return 'empty';
		}

		global $adb;
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}

		// We intentionally avoid using any export/total math here.
		// Classification is derived solely from how inventory line items reference Products / Services / ProductsServices.
		$result = $adb->pquery(
			"SELECT
				vtiger_inventoryproductrel.productid,
				CASE
					WHEN vtiger_productsservices.productsservicesid IS NOT NULL AND vtiger_productsservices.productsservicesid != '' THEN 'ProductsServices'
					WHEN vtiger_products.productid IS NOT NULL AND vtiger_products.productid != '' THEN 'Products'
					WHEN vtiger_service.serviceid IS NOT NULL AND vtiger_service.serviceid != '' THEN 'Services'
					ELSE ''
				END AS entitytype,
				vtiger_productsservices.item_type AS productsservices_item_type
			FROM vtiger_inventoryproductrel
				LEFT JOIN vtiger_products
					ON vtiger_products.productid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_service
					ON vtiger_service.serviceid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_productsservices
					ON vtiger_productsservices.productsservicesid = vtiger_inventoryproductrel.productid
			WHERE vtiger_inventoryproductrel.id = ?
			ORDER BY vtiger_inventoryproductrel.sequence_no",
			array($quoteId)
		);

		$numRows = $adb->num_rows($result);
		if ($numRows <= 0) {
			return 'empty';
		}

		$productSeen = false;
		$serviceSeen = false;
		$unknownSeen = false;

		for ($i = 0; $i < $numRows; $i++) {
			$entityType = strtolower(trim((string) $adb->query_result($result, $i, 'entitytype')));

			if ($entityType === 'products') {
				$productSeen = true;
				continue;
			}

			if ($entityType === 'services') {
				$serviceSeen = true;
				continue;
			}

			// ProductsServices: map its item_type (picklist: Product/Service)
			if ($entityType === 'productsservices') {
				$itemType = strtolower(trim((string) $adb->query_result($result, $i, 'productsservices_item_type')));
				if ($itemType === 'product' || $itemType === 'products') {
					$productSeen = true;
				} elseif ($itemType === 'service' || $itemType === 'services') {
					$serviceSeen = true;
				} else {
					$unknownSeen = true;
				}
				continue;
			}

			if ($entityType === '') {
				$unknownSeen = true;
			}
		}

		if ($productSeen && $serviceSeen) {
			return 'mixed';
		}
		if ($productSeen) {
			return 'product_only';
		}
		if ($serviceSeen) {
			return 'service_only';
		}

		// If we have line items but can't determine types reliably, treat as mixed for safety.
		if ($unknownSeen) {
			return 'mixed';
		}

		return 'empty';
	}
}

