<?php
/*+***********************************************************************************
 * Home DashBoard — Admin/CEO KPI dashboard (replaces classic Gridster for allowed users).
 *************************************************************************************/

require_once 'modules/Home/helpers/AdminKpiAccess.php';

class Home_DashBoard_View extends Vtiger_DashBoard_View {

	/**
	 * KPI shell does not use classic Dashboard widgets — do not require Dashboard module.
	 * Parent Vtiger_DashBoard_View would otherwise block Sale/Ke toan/Kho when Dashboard tab is denied.
	 */
	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser || !$currentUser->getId()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	/**
	 * Menu = Main Page (MANAGEMENT), CSS trang = theme gốc (PAGE_THEME_APP).
	 */
	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$pageThemeApp = $viewer->getTemplateVars('SELECTED_MENU_CATEGORY');
		if ($pageThemeApp === null || $pageThemeApp === '' || $pageThemeApp === false) {
			$pageThemeApp = 'MARKETING';
		}
		$viewer->assign('PAGE_THEME_APP', $pageThemeApp);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('MK_ADMIN_KPI_ALLOWED', Home_AdminKpiAccess_Helper::isAllowed($currentUser) ? 1 : 0);
		$viewer->assign('CURRENT_USER', $currentUser);

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$allowed = Home_AdminKpiAccess_Helper::isAllowed($currentUser);

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('MK_ADMIN_KPI_ALLOWED', $allowed ? 1 : 0);

		if ($allowed) {
			$viewer->view('AdminKpiDashboard.tpl', $moduleName);
			return;
		}

		$viewer->view('AdminKpiDenied.tpl', $moduleName);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = Vtiger_Index_View::getHeaderScripts($request);
		$layout = Vtiger_Viewer::getDefaultLayoutName();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$allowed = Home_AdminKpiAccess_Helper::isAllowed($currentUser);

		$jsFileNames = array(
			'~layouts/' . $layout . '/modules/Vtiger/resources/DashboardSidebarNav.js',
		);
		if ($allowed) {
			$jsFileNames[] = '~layouts/' . $layout . '/modules/Home/resources/AdminKpiDashboard.js';
		}

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssScriptInstances = Vtiger_Index_View::getHeaderCss($request);
		$layout = Vtiger_Viewer::getDefaultLayoutName();

		$css = array(
			'~layouts/' . $layout . '/modules/Vtiger/resources/DashBoard.css',
			'~layouts/' . $layout . '/modules/Home/resources/AdminKpiDashboard.css',
		);
		return array_merge($headerCssScriptInstances, $this->checkAndConvertCssStyles($css));
	}
}
