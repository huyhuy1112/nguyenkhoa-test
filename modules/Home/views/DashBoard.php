<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Home_DashBoard_View extends Vtiger_DashBoard_View {

	/**
	 * Menu = Main Page (MANAGEMENT, đẹp), CSS trang = theme gốc (PAGE_THEME_APP) để giữ giao diện cũ.
	 */
	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		// Giữ CSS cũ: theme mà parent vừa set (trước khi đổi sang MANAGEMENT)
		$pageThemeApp = $viewer->getTemplateVars('SELECTED_MENU_CATEGORY');
		if ($pageThemeApp === null || $pageThemeApp === '' || $pageThemeApp === false) {
			$pageThemeApp = 'MARKETING';
		}
		$viewer->assign('PAGE_THEME_APP', $pageThemeApp);
		// Menu = Main Page (MANAGEMENT)
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssScriptInstances = parent::getHeaderCss($request);
		$css = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Home/resources/HomeDashBoardCharts.css',
		);
		return array_merge($headerCssScriptInstances, $this->checkAndConvertCssStyles($css));
	}

}