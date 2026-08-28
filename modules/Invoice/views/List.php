<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Invoice_List_View extends Inventory_List_View {

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		if ($this->isMkInvoiceListApp($appName)) {
			$this->assignMkListHeaderVars($request, $viewer);
		}
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	protected function isMkInvoiceListApp($appName) {
		$app = strtoupper((string) $appName);
		return ($app === 'SUPPORT' || $app === 'TOOLS');
	}

	protected function assignMkListHeaderVars(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if (!$moduleModel) {
			return;
		}

		$basicLinks = array();
		foreach ($moduleModel->getModuleBasicLinks() as $basicLink) {
			$basicLinks[] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}
		$viewer->assign('MODULE_BASIC_ACTIONS', $basicLinks);

		$settingLinks = array();
		foreach ($moduleModel->getSettingLinks() as $settingsLink) {
			$settingLinks[] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
		}
		$viewer->assign('MODULE_SETTING_ACTIONS', $settingLinks);

		$fieldsInfo = array();
		foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
			$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
		}
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
	}
}
