<?php
/**
 * ProductsServices — lightweight catalog for Leads-style client search.
 * Does NOT use Vtiger list search_params / ListView paging.
 */
class ProductsServices_GetCatalog_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$db = PearDatabase::getInstance();
			$cols = array(
				'ps.productsservicesid AS id',
				'ps.productsservicesname AS name',
				'IFNULL(ps.sku, \'\') AS sku',
				'IFNULL(ps.product_group, \'\') AS product_group',
				'IFNULL(ps.item_type, \'\') AS item_type',
				'IFNULL(ps.price, 0) AS price',
				'IFNULL(ps.price_tuibao, 0) AS price_tuibao',
				'IFNULL(ps.unit, \'\') AS unit',
			);
			$sql = 'SELECT ' . implode(', ', $cols) . '
				FROM vtiger_productsservices ps
				INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
				ORDER BY ps.productsservicesname ASC, ps.productsservicesid ASC';
			$rs = $db->pquery($sql, array());
			$items = array();
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				$items[] = array(
					'id' => (int) $row['id'],
					'name' => decode_html((string) $row['name']),
					'sku' => decode_html((string) $row['sku']),
					'product_group' => decode_html((string) $row['product_group']),
					'item_type' => decode_html((string) $row['item_type']),
					'price' => (float) $row['price'],
					'price_tuibao' => (float) $row['price_tuibao'],
					'unit' => decode_html((string) $row['unit']),
				);
			}
			$response->setResult(array(
				'items' => $items,
				'count' => php7_count($items),
			));
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateReadAccess();
	}
}
