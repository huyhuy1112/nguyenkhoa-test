<?php
/**
 * Shared helpers for Warehouse Management UI (localStorage prototype, no DB).
 */
class Warehouse_WhMgmt_Helper {

	public static function isInventoryApp(Vtiger_Request $request) {
		$appName = $request->get('app');
		return ($appName === 'INVENTORY' || $appName === '');
	}

	public static function assignInventoryContext(Vtiger_Index_View $view, Vtiger_Request $request, $viewName, $menuKey) {
		$viewer = $view->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'INVENTORY');
		$viewer->assign('VIEW', $viewName);
		$viewer->assign('MENU_SELECTED_MODULENAME', $menuKey);
	}

	public static function preProcessTplName(Vtiger_Request $request) {
		if (self::isInventoryApp($request)) {
			return 'WhMgmtViewPreProcess.tpl';
		}
		return 'IndexViewPreProcess.tpl';
	}

	public static function postProcessInventory(Vtiger_Index_View $view, Vtiger_Request $request) {
		$viewer = $view->getViewer($request);
		$viewer->view('WhMgmtViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
		$viewer->view('WhMgmtViewScripts.tpl', $request->getModule());
	}
}
