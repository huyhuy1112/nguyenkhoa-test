<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SalesOrder_List_View extends Inventory_List_View {
	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	protected function applyToolsOrdersDefaults(Vtiger_Request $request) {
		if (!$this->isToolsOrdersContext($request)) {
			return;
		}

		$moduleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if (!$moduleModel) {
			return;
		}

		$availableFields = $moduleModel->getFields();
		$preferredHeaders = array(
			'subject',
			'team_group',
			'purpose',
			'internal_cost',
			'created_user_id',
			'internal_order_status',
			'approved_by',
			'needed_time',
			'createdtime',
		);

		$resolvedHeaders = array();
		foreach ($preferredHeaders as $fieldName) {
			if (isset($availableFields[$fieldName])) {
				$resolvedHeaders[] = $fieldName;
			}
		}
		if (!empty($resolvedHeaders)) {
			$request->set('list_headers', $resolvedHeaders);
			$_REQUEST['list_headers'] = $resolvedHeaders;
		}

		if (!$request->get('orderby')) {
			$defaultOrderBy = isset($availableFields['createdtime']) ? 'createdtime' : 'modifiedtime';
			$request->set('orderby', $defaultOrderBy);
			$request->set('sortorder', 'DESC');
			$_REQUEST['orderby'] = $defaultOrderBy;
			$_REQUEST['sortorder'] = 'DESC';
		}

	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->applyToolsOrdersDefaults($request);
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		$this->applyToolsOrdersDefaults($request);
		parent::process($request);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		parent::initializeListViewContents($request, $viewer);

		if (!$this->isToolsOrdersContext($request)) {
			return;
		}

		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Orders');
		$viewer->assign('LISTVIEW_ADD_RECORD_LABEL', 'Add Order');
		$viewer->assign('LISTVIEW_EMPTY_ENTITY_LABEL', 'Orders');
		$viewer->assign('LISTVIEW_HEADER_LABEL_OVERRIDES', array(
			'subject' => 'Order Name',
			'team_group' => 'Team Group',
			'purpose' => 'Purpose',
			'internal_cost' => 'Cost',
			'created_user_id' => 'Ordered By',
			'internal_order_status' => 'Status',
			'approved_by' => 'Approved By',
			'needed_time' => 'Needed Time',
			'createdtime' => 'Created Time',
		));
	}
}