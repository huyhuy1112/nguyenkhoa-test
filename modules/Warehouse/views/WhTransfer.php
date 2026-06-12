<?php
require_once 'modules/Warehouse/helpers/WhMgmtHelper.php';

class Warehouse_WhTransfer_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return Warehouse_WhMgmt_Helper::preProcessTplName($request);
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		Warehouse_WhMgmt_Helper::assignInventoryContext($this, $request, 'WhTransfer', 'WarehouseWhTransfer');
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		if (Warehouse_WhMgmt_Helper::isInventoryApp($request)) {
			Warehouse_WhMgmt_Helper::postProcessInventory($this, $request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		Warehouse_WhMgmt_Helper::assignInventoryContext($this, $request, 'WhTransfer', 'WarehouseWhTransfer');
		$viewer = $this->getViewer($request);
		$viewer->view('WhTransfer.tpl', $request->getModule());
	}
}
