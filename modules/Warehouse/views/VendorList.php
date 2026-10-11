<?php
require_once 'modules/Warehouse/helpers/WhMgmtHelper.php';

/** Nhà cung cấp — list UI kiểu Kiot (trong app INVENTORY). */
class Warehouse_VendorList_View extends Vtiger_Index_View {

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
		Warehouse_WhMgmt_Helper::assignInventoryContext($this, $request, 'VendorList', 'WarehouseVendorList');
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
		Warehouse_WhMgmt_Helper::assignInventoryContext($this, $request, 'VendorList', 'WarehouseVendorList');
		$viewer = $this->getViewer($request);
		$viewer->view('VendorList.tpl', $request->getModule());
	}
}
