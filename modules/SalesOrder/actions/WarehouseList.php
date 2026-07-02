<?php
class SalesOrder_WarehouseList_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
		$response = new Vtiger_Response();
		$response->setResult(array(
			'warehouses' => Warehouse_Registry::getAll(),
		));
		$response->emit();
	}
}

?>
