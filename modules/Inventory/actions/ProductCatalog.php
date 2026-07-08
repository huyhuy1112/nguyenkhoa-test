<?php
/*+***********************************************************************************
 * Inventory line-item product catalog (Hàng hoá / ProductsServices).
 *************************************************************************************/

class Inventory_ProductCatalog_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/Inventory/helpers/ProductCatalog.php';
		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'products' => Inventory_ProductCatalog_Helper::listActiveProducts(),
		));
		$response->emit();
	}
}
