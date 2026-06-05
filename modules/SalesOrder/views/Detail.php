<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SalesOrder_Detail_View extends Inventory_Detail_View {

	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isToolsOrdersContext($request)) {
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'TOOLS');
			$viewer->assign('MK_SO_TOOLS_DETAIL', true);
		}
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isToolsOrdersContext($request)) {
			echo $this->showModuleDetailView($request);
			return;
		}
		parent::process($request);
	}

	public function showModuleBasicView($request) {
		if ($this->isToolsOrdersContext($request)) {
			echo $this->showModuleDetailView($request);
			return;
		}
		parent::showModuleBasicView($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		if (!$this->isToolsOrdersContext($request)) {
			return parent::showModuleDetailView($request);
		}

		$recordId = $request->get('record');
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'SalesOrder');
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', 'SalesOrder');
		$viewer->assign('MODULE', 'SalesOrder');
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance('SalesOrder'));
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		return $viewer->view('ToolsOrdersDetailView.tpl', 'SalesOrder', true);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isToolsOrdersContext($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/SalesOrder/resources/SalesOrderToolsDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}
