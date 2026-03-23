<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class SalesOrder_Edit_View extends Inventory_Edit_View {
	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	protected function getToolsOrderFieldModels($moduleModel) {
		$fieldNames = array(
			'subject',
			'team_group',
			'purpose',
			'internal_cost',
			'needed_time',
			'internal_order_status',
			'approved_by',
			'approval_note',
			'created_user_id',
		);
		$result = array();
		foreach ($fieldNames as $fieldName) {
			$fieldModel = $moduleModel->getField($fieldName);
			if ($fieldModel) {
				$result[$fieldName] = $fieldModel;
			}
		}
		return $result;
	}

	public function process(Vtiger_Request $request) {
		if (!$this->isToolsOrdersContext($request)) {
			parent::process($request);
			return;
		}

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$viewer->assign('MODE', 'edit');
			$viewer->assign('RECORD_ID', $recordId);
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
		}

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);
		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if ($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('SELECTED_MENU_CATEGORY', $request->get('app'));
		$viewer->assign('TOOLS_ORDERS_MODE', true);
		$fieldsMap = $this->getToolsOrderFieldModels($moduleModel);
		// UI type templates read from FIELD_MODEL->get('fieldvalue'), so we must populate it.
		foreach ($fieldsMap as $fieldName => $fieldModel) {
			$fieldModel->set('fieldvalue', $recordModel->get($fieldName));
		}
		$viewer->assign('FIELDS_MAP', $fieldsMap);
		$viewer->assign('IS_RELATION_OPERATION', $request->get('relationOperation'));
		$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
		$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		$viewer->assign('TOOLS_VALIDATION_ERROR', $request->get('validation_error'));

		if ($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}

		$viewer->view('ToolsOrdersEditView.tpl', $moduleName);
	}
}