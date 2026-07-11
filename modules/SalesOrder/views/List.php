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

	protected function resolveSalesPaidFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('received', 'paid', 'mk_customer_paid') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return null;
	}

	protected function isSalesListFieldAvailable(Vtiger_Module_Model $moduleModel, $fieldName) {
		if (!$moduleModel || $fieldName === '') {
			return false;
		}
		$fields = $moduleModel->getFields();
		if (is_array($fields) && isset($fields[$fieldName])) {
			return true;
		}
		$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
		return ($fieldModel && $fieldModel->isViewable());
	}

	protected function clearSalesPosListHeadersSession(Vtiger_Request $request) {
		$customView = new CustomView();
		$cvId = $request->get('viewname');
		if (empty($cvId)) {
			$cvId = $customView->getViewId('SalesOrder');
		}
		if (!empty($cvId)) {
			Vtiger_ListView_Model::deleteParamsSession('SalesOrder_' . $cvId, array('list_headers'));
		}
	}

	protected function resetSalesPosListViewState() {
		$this->listViewModel = false;
		$this->listViewHeaders = false;
		$this->listViewEntries = false;
		$this->listviewinitcalled = false;
	}

	protected function applySalesListPosDefaults(Vtiger_Request $request) {
		if (!$this->isSalesListContext($request) || $this->isToolsOrdersContext($request)) {
			return null;
		}

		$moduleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if (!$moduleModel) {
			return null;
		}

		$statusField = $this->resolveSalesStatusFieldName($moduleModel);
		$paidField = $this->resolveSalesPaidFieldName($moduleModel);

		$preferredHeaders = array(
			'salesorder_no',
			'createdtime',
			'account_id',
			'total',
			'hdnGrandTotal',
		);
		if ($paidField) {
			$preferredHeaders[] = $paidField;
		}
		// Status stays available via filter chips; default columns prefer paid over status.

		$resolvedHeaders = array();
		foreach ($preferredHeaders as $fieldName) {
			if ($this->isSalesListFieldAvailable($moduleModel, $fieldName)) {
				$resolvedHeaders[] = $fieldName;
			}
		}
		if (!empty($resolvedHeaders)) {
			$this->clearSalesPosListHeadersSession($request);
			$request->set('list_headers', $resolvedHeaders);
			$_REQUEST['list_headers'] = $resolvedHeaders;
		}

		if (!$request->get('orderby') && !$request->isAjax()) {
			$request->set('orderby', 'createdtime');
			$request->set('sortorder', 'DESC');
			$_REQUEST['orderby'] = 'createdtime';
			$_REQUEST['sortorder'] = 'DESC';
		}

		return array(
			'statusField' => $statusField,
			'paidField' => $paidField,
		);
	}

	protected function getPosStatusLabelMap() {
		return array(
			'Created' => 'Phiếu tạm',
			'Approved' => 'Đã xác nhận',
			'Delivered' => 'Hoàn thành',
			'Cancelled' => 'Đã hủy',
			'Pending' => 'Đang chờ',
			'Paid' => 'Đã thanh toán',
			'Sent' => 'Đã gửi',
			'Rejected' => 'Từ chối',
			'waiting_print' => 'Chờ in phiếu',
			'picking' => 'Đang soạn',
			'packed' => 'Đã soạn',
			'shipped' => 'Đã giao',
			'rejected' => 'Từ chối',
			'Đã duyệt' => 'Đã xác nhận',
			'Đã tạo' => 'Phiếu tạm',
			'Đang chờ xử lý' => 'Đang chờ',
			'Đang giao hàng' => 'Đang giao hàng',
			'Hoàn thành' => 'Hoàn thành',
			'Đã gửi' => 'Đã gửi',
			'Đã thanh toán' => 'Đã thanh toán',
			'Đã hủy' => 'Đã hủy',
			'Từ chối' => 'Từ chối',
			'Chờ in phiếu' => 'Chờ in phiếu',
			'Đang soạn' => 'Đang soạn',
			'Đã soạn' => 'Đã soạn',
			'Đã giao' => 'Đã giao',
		);
	}

	protected function mapPosStatusFilterOptions(array $options) {
		$map = $this->getPosStatusLabelMap();
		$mapped = array();
		foreach ($options as $key => $label) {
			if (isset($map[$key])) {
				$mapped[$key] = $map[$key];
			} elseif (isset($map[$label])) {
				$mapped[$key] = $map[$label];
			} else {
				$mapped[$key] = $label;
			}
		}
		return $mapped;
	}

	protected function assignSalesListPosTemplateVars(Vtiger_Viewer $viewer, $posMeta) {
		if (empty($posMeta) || !is_array($posMeta)) {
			return;
		}
		$statusField = !empty($posMeta['statusField']) ? $posMeta['statusField'] : 'sostatus';
		$paidField = !empty($posMeta['paidField']) ? $posMeta['paidField'] : 'received';

		$labelOverrides = array(
			'salesorder_no' => 'Mã đặt hàng',
			'createdtime' => 'Thời gian',
			'account_id' => 'Người liên hệ',
			'hdnGrandTotal' => 'Tổng cộng',
			'total' => 'Tổng cộng',
			$paidField => 'Khách đã trả',
			$statusField => 'Trạng thái',
		);
		// Prefer grand-total column label even when both total + hdnGrandTotal exist.
		$viewer->assign('LISTVIEW_HEADER_LABEL_OVERRIDES', $labelOverrides);
		$viewer->assign('MK_SO_POS_PAID_FIELD', $paidField);
		$viewer->assign('MK_SO_POS_STATUS_FIELD', $statusField);
		$viewer->assign('MK_SO_STATUS_FIELD', $statusField);
		$viewer->assign('MK_SO_POS_LIST', true);
		$this->assignSalesListPosFilterVars($viewer, $posMeta, $statusField);
	}

	protected function assignSalesListPosFilterVars(Vtiger_Viewer $viewer, $posMeta, $statusField) {
		$moduleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if (!$moduleModel) {
			return;
		}

		$picklistOptions = function ($fieldName) use ($moduleModel) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if (!$fieldModel || !$fieldModel->isViewable()) {
				return array();
			}
			$values = $fieldModel->getPicklistValues();
			return is_array($values) ? $values : array();
		};

		$paymentField = '';
		foreach (array('mk_payment_terms', 'payment_duration') as $candidate) {
			$fieldModel = Vtiger_Field_Model::getInstance($candidate, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				$paymentField = $candidate;
				break;
			}
		}

		$filterMeta = array(
			'statusField' => $statusField,
			'carrierField' => $this->isSalesListFieldAvailable($moduleModel, 'carrier') ? 'carrier' : '',
			'dueDateField' => $this->isSalesListFieldAvailable($moduleModel, 'duedate') ? 'duedate' : '',
			'createdTimeField' => 'createdtime',
			'shipCityField' => $this->isSalesListFieldAvailable($moduleModel, 'ship_city') ? 'ship_city' : '',
			'paymentField' => $paymentField,
		);

		$viewer->assign('MK_SO_POS_FILTER_META', $filterMeta);
		$viewer->assign('MK_SO_POS_FILTER_STATUS_OPTIONS', $this->mapPosStatusFilterOptions($picklistOptions($statusField)));
		$viewer->assign('MK_SO_POS_FILTER_CARRIER_OPTIONS', $picklistOptions('carrier'));
		$paymentOptions = array(
			'Tiền mặt' => 'Tiền mặt',
			'Chuyển khoản' => 'Chuyển khoản',
			'Thẻ' => 'Thẻ',
			'Ví' => 'Ví',
		);
		$viewer->assign('MK_SO_POS_FILTER_PAYMENT_OPTIONS', $paymentField ? $paymentOptions : array());
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
		$posMeta = null;
		if ($this->isSalesListContext($request) && !$this->isToolsOrdersContext($request)) {
			$this->clearSalesPosListHeadersSession($request);
			$posMeta = $this->applySalesListPosDefaults($request);
		}
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		$this->assignSalesAppCategory($request, $viewer);
		if ($this->isToolsOrdersContext($request)) {
			$this->assignMkToolsListHeaderVars($request, $viewer);
		} elseif ($this->isSalesListContext($request)) {
			$this->assignMkToolsListHeaderVars($request, $viewer);
			$this->assignSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetSalesPosListViewState();
			$this->applySalesListPosDefaults($request);
			$this->initializeListViewContents($request, $viewer);
		} else {
			$this->applySalesListHeaders($request);
		}
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
		$posMeta = null;
		if ($this->isSalesListContext($request) && !$this->isToolsOrdersContext($request)) {
			$posMeta = $this->applySalesListPosDefaults($request);
			$this->assignSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetSalesPosListViewState();
		} else {
			$this->applySalesListHeaders($request);
		}

		parent::process($request);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$this->applyToolsOrdersDefaults($request);
		$posMeta = null;
		if ($this->isSalesListContext($request) && !$this->isToolsOrdersContext($request)) {
			$posMeta = $this->applySalesListPosDefaults($request);
			$this->assignSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetSalesPosListViewState();
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
