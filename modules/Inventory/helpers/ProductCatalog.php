<?php
/**
 * Active Hàng hoá (ProductsServices) catalog for inventory line-item pickers.
 */
class Inventory_ProductCatalog_Helper {

	/**
	 * @return array<int, array{id:int,name:string,price:float,type:string,sku:string}>
	 */
	public static function listActiveProducts($limit = 5000) {
		$db = PearDatabase::getInstance();
		$limit = max(1, min(10000, (int) $limit));
		$rs = $db->pquery(
			'SELECT ps.productsservicesid, ps.productsservicesname, ps.price, ps.item_type, ps.sku
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 ORDER BY ps.productsservicesname ASC
			 LIMIT ?',
			array($limit)
		);
		$out = array();
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
			$out[] = array(
				'id' => $id,
				'name' => $name,
				'price' => (float) (isset($row['price']) ? $row['price'] : 0),
				'type' => (string) (isset($row['item_type']) ? $row['item_type'] : ''),
				'sku' => $sku,
			);
		}
		return $out;
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
