<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Documents_Detail_View extends Vtiger_Detail_View {

	function preProcess(Vtiger_Request $request, $display=true) {
		$viewer = $this->getViewer($request);
		$viewer->assign('NO_SUMMARY', true);
		$recordId = $request->get('record');
		if ($recordId && (class_exists('Documents_History_Helper') || file_exists('modules/Documents/DocumentHistory.php'))) {
			require_once 'modules/Documents/DocumentHistory.php';
			$viewer->assign('DOCUMENT_HISTORY', Documents_History_Helper::getHistory($recordId));
		} else {
			$viewer->assign('DOCUMENT_HISTORY', array());
		}

		$parentApp = $request->get('app');
		if (empty($parentApp)) {
			$parentApp = 'MANAGEMENT';
			$request->set('app', $parentApp);
		}
		if ($parentApp === 'MANAGEMENT') {
			$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
			$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
			$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
			if (isset($menuGroupedByParent['MANAGEMENT'])) {
				$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
			}
		}

		parent::preProcess($request, $display);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($request->get('app') !== 'MANAGEMENT') {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Documents/resources/DocumentsMkDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	/**
	 * Function to get Ajax is enabled or not
	 * @param Vtiger_Record_Model record model
	 * @return <boolean> true/false
	 */
	public function isAjaxEnabled($recordModel) {
		return true;
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	function showModuleBasicView($request) {
		return $this->showModuleDetailView($request);
	}

}
