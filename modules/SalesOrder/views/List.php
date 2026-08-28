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

	const SALES_STATUS_FIELD_CANDIDATES = array('sostatus', 'salesorder_status', 'invoicestatus', 'status');

	protected function isSalesListContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	protected function assignSalesAppCategory(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if ($this->isSalesListContext($request) && !$this->isToolsOrdersContext($request)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		}
	}

	protected function resolveSalesStatusFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (self::SALES_STATUS_FIELD_CANDIDATES as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return null;
	}

	protected function isSalesQuoteListField($fieldName) {
		$normalized = strtolower((string) $fieldName);
		if ($normalized === '' || $normalized === 'id' || $normalized === 'starred') {
			return false;
		}
		return (strpos($normalized, 'quote') !== false);
	}

	protected function getNormalizedListHeaders(Vtiger_Request $request) {
		$listHeaders = $request->get('list_headers', array());
		if (is_string($listHeaders) && $listHeaders !== '') {
			$decoded = json_decode($listHeaders, true);
			$listHeaders = is_array($decoded) ? $decoded : array();
		}
		if (!is_array($listHeaders)) {
			$listHeaders = array();
		}
		if (empty($listHeaders)) {
			$listHeaders = $this->getSalesListHeadersFromCustomView($request);
		}
		return array_values(array_filter($listHeaders, function ($fieldName) {
			return $fieldName !== '' && $fieldName !== 'id' && $fieldName !== 'starred';
		}));
	}

	protected function getSalesListHeadersFromCustomView(Vtiger_Request $request) {
		$customView = new CustomView();
		$viewId = $request->get('viewname');
		if (empty($viewId)) {
			$viewId = $customView->getViewIdByName('All', 'SalesOrder');
		}
		if (empty($viewId)) {
			return array();
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$queryGenerator = new EnhancedQueryGenerator('SalesOrder', $currentUser);
		$queryGenerator->initForCustomViewById($viewId);
		$fieldsList = $queryGenerator->getFields();
		if (!is_array($fieldsList)) {
			return array();
		}

		$headers = array();
		foreach ($fieldsList as $fieldName) {
			if ($fieldName === 'id' || $fieldName === 'starred') {
				continue;
			}
			$headers[] = $fieldName;
		}
		return $headers;
	}

	/**
	 * SALES list: replace quote_id with status field (same column position).
	 *
	 * @return string|null Detected status field name
	 */
	protected function applySalesListHeaders(Vtiger_Request $request) {
		if (!$this->isSalesListContext($request) || $this->isToolsOrdersContext($request)) {
			return null;
		}

		$moduleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if (!$moduleModel) {
			return null;
		}

		$statusField = $this->resolveSalesStatusFieldName($moduleModel);
		if (!$statusField) {
			return null;
		}

		$listHeaders = $this->getNormalizedListHeaders($request);
		if (empty($listHeaders)) {
			return $statusField;
		}

		if (in_array($statusField, $listHeaders, true)) {
			$listHeaders = array_values(array_filter($listHeaders, function ($fieldName) use ($statusField) {
				if ($fieldName === $statusField) {
					return true;
				}
				return !$this->isSalesQuoteListField($fieldName);
			}));
		} else {
			$quoteIndex = false;
			foreach ($listHeaders as $index => $fieldName) {
				if ($this->isSalesQuoteListField($fieldName)) {
					$quoteIndex = $index;
					break;
				}
			}
			if ($quoteIndex !== false) {
				$listHeaders[$quoteIndex] = $statusField;
			} else {
				$insertAt = count($listHeaders);
				foreach ($listHeaders as $index => $fieldName) {
					if ($fieldName === 'hdnGrandTotal' || $fieldName === 'total') {
						$insertAt = $index;
						break;
					}
				}
				array_splice($listHeaders, $insertAt, 0, array($statusField));
			}
			$listHeaders = array_values(array_filter($listHeaders, function ($fieldName) use ($statusField) {
				if ($fieldName === $statusField) {
					return true;
				}
				return !$this->isSalesQuoteListField($fieldName);
			}));
		}

		$request->set('list_headers', $listHeaders);
		$_REQUEST['list_headers'] = $listHeaders;
		return $statusField;
	}

	protected function assignSalesListTemplateVars(Vtiger_Viewer $viewer, $statusField) {
		if (empty($statusField)) {
			return;
		}
		$viewer->assign('LISTVIEW_HEADER_LABEL_OVERRIDES', array(
			$statusField => vtranslate('Status', 'SalesOrder'),
		));
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
			if (!$request->isAjax()) {
				$customView = new CustomView();
				$cvId = $request->get('viewname');
				if (empty($cvId)) {
					$cvId = $customView->getViewId('SalesOrder');
				}
				if (empty($cvId)) {
					$cvId = $customView->getViewIdByName('All', 'SalesOrder');
				}
				if (!empty($cvId)) {
					Vtiger_ListView_Model::deleteParamsSession('SalesOrder_' . $cvId, array('orderby', 'sortorder'));
				}
			}
			$defaultOrderBy = isset($availableFields['createdtime']) ? 'createdtime' : 'modifiedtime';
			$request->set('orderby', $defaultOrderBy);
			$request->set('sortorder', 'DESC');
			$_REQUEST['orderby'] = $defaultOrderBy;
			$_REQUEST['sortorder'] = 'DESC';
		}

	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->applyToolsOrdersDefaults($request);
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		$this->assignSalesAppCategory($request, $viewer);
		if ($this->isToolsOrdersContext($request)) {
			$this->assignMkToolsListHeaderVars($request, $viewer);
		}
		$this->applySalesListHeaders($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	protected function assignMkToolsListHeaderVars(Vtiger_Request $request, Vtiger_Viewer $viewer) {
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

	public function process(Vtiger_Request $request) {
		$this->applyToolsOrdersDefaults($request);
		$viewer = $this->getViewer($request);
		$this->assignSalesAppCategory($request, $viewer);
		$statusField = $this->applySalesListHeaders($request);
		$this->assignSalesListTemplateVars($viewer, $statusField);

		// Full page load: build list data once in process() with adjusted headers (not stale preProcess only).
		if (!$request->isAjax()) {
			$this->listviewinitcalled = false;
		}

		parent::process($request);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$this->applyToolsOrdersDefaults($request);
		$statusField = null;
		if ($this->isSalesListContext($request) && !$this->isToolsOrdersContext($request)) {
			$statusField = $this->applySalesListHeaders($request);
			$this->assignSalesListTemplateVars($viewer, $statusField);
		}
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
