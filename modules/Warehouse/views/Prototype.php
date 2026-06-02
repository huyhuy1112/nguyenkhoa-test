<?php
/*+***********************************************************************************
 * Warehouse Prototype (Inventory app): UI-only demo page (no DB).
 *************************************************************************************/

class Warehouse_Prototype_View extends Vtiger_Index_View {

	protected function isInventoryApp(Vtiger_Request $request) {
		$appName = $request->get('app');
		return ($appName === 'INVENTORY' || $appName === '');
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		if ($this->isInventoryApp($request)) {
			return 'PrototypeViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	protected function assignInventoryContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'INVENTORY');
		$viewer->assign('VIEW', 'Prototype');
		// Avoid highlighting Storage (Warehouse List) in sidebar.
		$viewer->assign('MENU_SELECTED_MODULENAME', 'WarehousePrototype');
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignInventoryContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isInventoryApp($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('PrototypeViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			$viewer->view('PrototypeViewScripts.tpl', $request->getModule());
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$this->assignInventoryContext($request);
		$viewer = $this->getViewer($request);
		$viewer->view('Prototype.tpl', $request->getModule());
	}
}

