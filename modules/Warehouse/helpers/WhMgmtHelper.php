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

		require_once 'modules/Warehouse/models/WhMgmtService.php';
		try {
			Warehouse_WhMgmtService::ensureInstalled();
			$state = Warehouse_WhMgmtService::getFullState();
			$viewer->assign('MK_WH_DB_STATE_JSON', json_encode($state, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
			$catalog = Warehouse_WhMgmtService::listProductCatalog();
			$viewer->assign('MK_WH_PRODUCT_CATALOG_JSON', json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
		} catch (Exception $e) {
			$viewer->assign('MK_WH_DB_STATE_JSON', '');
			$viewer->assign('MK_WH_PRODUCT_CATALOG_JSON', '[]');
		}

		self::assignUserAccessFlags($viewer);
	}

	/**
	 * Real CRM permissions for WhDetail UI (replaces prototype role picker).
	 */
	public static function assignUserAccessFlags($viewer) {
		$canWrite = false;
		try {
			$canWrite = Users_Privileges_Model::isPermitted('GoodsReceipt', 'CreateView')
				|| Users_Privileges_Model::isPermitted('GoodsIssue', 'CreateView')
				|| Users_Privileges_Model::isPermitted('Warehouse', 'CreateView')
				|| Users_Privileges_Model::isPermitted('Warehouse', 'EditView');
		} catch (Exception $e) {
			$canWrite = false;
		}
		$userName = '';
		try {
			$user = Users_Record_Model::getCurrentUserModel();
			if ($user) {
				$userName = trim((string) $user->getName());
				if ($userName === '') {
					$userName = trim((string) $user->get('user_name'));
				}
			}
		} catch (Exception $e) {
			$userName = '';
		}
		$viewer->assign('MK_WH_CAN_WRITE', $canWrite ? 1 : 0);
		// No separate QC profile yet — warehouse write roles also handle QC.
		$viewer->assign('MK_WH_CAN_QC', $canWrite ? 1 : 0);
		$viewer->assign('MK_WH_USER_NAME', $userName);
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
