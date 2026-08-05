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

	const SALES_STATUS_FIELD_CANDIDATES = array('invoicestatus', 'status', 'sostatus');

	protected function isSalesListContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

	protected function isMkInvoiceListApp($appName) {
		$app = strtoupper((string) $appName);
		return ($app === 'SUPPORT' || $app === 'TOOLS' || $app === 'SALES');
	}

	protected function resolveInvoiceStatusFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (self::SALES_STATUS_FIELD_CANDIDATES as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return null;
	}

	protected function resolveInvoiceNumberFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('invoice_no', 'subject') as $fieldName) {
			if ($this->isInvoiceListFieldAvailable($moduleModel, $fieldName)) {
				return $fieldName;
			}
		}
		return null;
	}

	protected function resolveInvoicePaidFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('received', 'paid', 'mk_customer_paid') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return null;
	}

	protected function isInvoiceListFieldAvailable(Vtiger_Module_Model $moduleModel, $fieldName) {
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

	protected function clearInvoicePosListHeadersSession(Vtiger_Request $request) {
		$customView = new CustomView();
		$cvId = $request->get('viewname');
		if (empty($cvId)) {
			$cvId = $customView->getViewId('Invoice');
		}
		if (!empty($cvId)) {
			Vtiger_ListView_Model::deleteParamsSession('Invoice_' . $cvId, array('list_headers'));
		}
	}

	protected function resetInvoicePosListViewState() {
		$this->listViewModel = false;
		$this->listViewHeaders = false;
		$this->listViewEntries = false;
		$this->listviewinitcalled = false;
	}

	protected function applyInvoiceSalesListPosDefaults(Vtiger_Request $request) {
		if (!$this->isSalesListContext($request)) {
			return null;
		}

		$moduleModel = Vtiger_Module_Model::getInstance('Invoice');
		if (!$moduleModel) {
			return null;
		}

		$statusField = $this->resolveInvoiceStatusFieldName($moduleModel);
		$paidField = $this->resolveInvoicePaidFieldName($moduleModel);
		$numberField = $this->resolveInvoiceNumberFieldName($moduleModel);

		$preferredHeaders = array();
		if ($numberField) {
			$preferredHeaders[] = $numberField;
		}
		if ($this->isInvoiceListFieldAvailable($moduleModel, 'createdtime')) {
			$preferredHeaders[] = 'createdtime';
		}
		if ($this->isInvoiceListFieldAvailable($moduleModel, 'account_id')) {
			$preferredHeaders[] = 'account_id';
		} elseif ($this->isInvoiceListFieldAvailable($moduleModel, 'contact_id')) {
			$preferredHeaders[] = 'contact_id';
		}
		if ($paidField) {
			$preferredHeaders[] = $paidField;
		}
		if ($statusField) {
			$preferredHeaders[] = $statusField;
		}
		if ($this->isInvoiceListFieldAvailable($moduleModel, 'hdnGrandTotal')) {
			$preferredHeaders[] = 'hdnGrandTotal';
		} elseif ($this->isInvoiceListFieldAvailable($moduleModel, 'total')) {
			$preferredHeaders[] = 'total';
		}

		$resolvedHeaders = array();
		foreach ($preferredHeaders as $fieldName) {
			if ($this->isInvoiceListFieldAvailable($moduleModel, $fieldName)) {
				$resolvedHeaders[] = $fieldName;
			}
		}
		if (!empty($resolvedHeaders)) {
			$this->clearInvoicePosListHeadersSession($request);
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
			'numberField' => $numberField,
		);
	}

	protected function assignInvoiceSalesListPosTemplateVars(Vtiger_Viewer $viewer, $posMeta) {
		if (empty($posMeta) || !is_array($posMeta)) {
			return;
		}
		$statusField = !empty($posMeta['statusField']) ? $posMeta['statusField'] : 'invoicestatus';
		$paidField = !empty($posMeta['paidField']) ? $posMeta['paidField'] : 'received';
		$numberField = !empty($posMeta['numberField']) ? $posMeta['numberField'] : 'invoice_no';

		$labelOverrides = array(
			'invoice_no' => 'Mã hóa đơn',
			'subject' => 'Mã hóa đơn',
			'createdtime' => 'Thời gian',
			'account_id' => 'Người liên hệ',
			'contact_id' => 'Người liên hệ',
			'hdnGrandTotal' => 'Tổng cộng',
			'total' => 'Tổng cộng',
			$paidField => 'Khách đã trả',
			$statusField => 'Trạng thái',
			$numberField => 'Mã hóa đơn',
		);

		$viewer->assign('LISTVIEW_HEADER_LABEL_OVERRIDES', $labelOverrides);
		$viewer->assign('MK_INV_POS_PAID_FIELD', $paidField);
		$viewer->assign('MK_INV_POS_STATUS_FIELD', $statusField);
		$viewer->assign('MK_INV_POS_LIST', true);
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

	public function preProcess(Vtiger_Request $request, $display = true) {
		$posMeta = null;
		if ($this->isSalesListContext($request)) {
			$this->clearInvoicePosListHeadersSession($request);
			$posMeta = $this->applyInvoiceSalesListPosDefaults($request);
		}

		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}

		if ($this->isSalesListContext($request)) {
			$this->assignMkListHeaderVars($request, $viewer);
			$this->assignInvoiceSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetInvoicePosListViewState();
			$this->applyInvoiceSalesListPosDefaults($request);
			$this->initializeListViewContents($request, $viewer);
		} elseif ($this->isMkInvoiceListApp($appName)) {
			$this->assignMkListHeaderVars($request, $viewer);
		}

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if ($this->isSalesListContext($request)) {
			$posMeta = $this->applyInvoiceSalesListPosDefaults($request);
			$this->assignInvoiceSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetInvoicePosListViewState();
		}
		parent::process($request);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if ($this->isSalesListContext($request)) {
			$posMeta = $this->applyInvoiceSalesListPosDefaults($request);
			$this->assignInvoiceSalesListPosTemplateVars($viewer, $posMeta);
			$this->resetInvoicePosListViewState();
		}
		parent::initializeListViewContents($request, $viewer);
	}
}
