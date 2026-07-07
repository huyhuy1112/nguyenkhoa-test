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

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	protected function isSalesListInlineContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

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

	public function showListInlineDetail(Vtiger_Request $request) {
		if (!$this->isSalesListInlineContext($request)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		$this->showLineItemDetails($request);

		$moduleName = 'SalesOrder';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Inventory_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('INLINE_INFO_FIELDS', $this->getInlineInfoFields($moduleModel, $recordModel));
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('INLINE_ASSIGNED_USERS', $currentUser->getAccessibleUsersForModule($moduleName));
		$viewer->assign('INLINE_BRANCH_LABEL', $this->resolveInlineBranchLabel($recordModel, $moduleModel));
		$viewer->assign('INLINE_PAID_FIELD', $this->resolveInlinePaidFieldName($moduleModel));
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_PRINT_URL', 'index.php?module=SalesOrder&action=ExportPDF&record=' . (int) $recordId . '&preview=1');
		$viewer->assign('INLINE_PRINT_DOWNLOAD_URL', 'index.php?module=SalesOrder&action=ExportPDF&record=' . (int) $recordId);

		return $viewer->view('partials/ListInlineDetail.tpl', $moduleName, true);
	}

	protected function resolveInlinePaidFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('received', 'paid_amount', 'amount_paid') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return '';
	}

	protected function resolveInlineBranchLabel(Vtiger_Record_Model $recordModel, Vtiger_Module_Model $moduleModel) {
		foreach (array('branch', 'mk_branch', 'store_location', 'location') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				$value = trim((string) $recordModel->getDisplayValue($fieldName));
				if ($value !== '') {
					return $value;
				}
			}
		}
		return '';
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
			'Đã duyệt' => 'Đã xác nhận',
			'Đã tạo' => 'Phiếu tạm',
			'Đang chờ xử lý' => 'Đang chờ',
			'Đang giao hàng' => 'Đang giao hàng',
			'Hoàn thành' => 'Hoàn thành',
			'Đã gửi' => 'Đã gửi',
			'Đã thanh toán' => 'Đã thanh toán',
			'Đã hủy' => 'Đã hủy',
			'Từ chối' => 'Từ chối',
		);
	}

	protected function resolvePosStatusLabel($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		$map = $this->getPosStatusLabelMap();
		if (isset($map[$value])) {
			return $map[$value];
		}
		foreach ($map as $key => $label) {
			if (strcasecmp($key, $value) === 0 || strcasecmp($label, $value) === 0) {
				return $label;
			}
		}
		return $value;
	}

	protected function getPosStatusOptions(Vtiger_Module_Model $moduleModel, $fieldName) {
		$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
		if (!$fieldModel || $fieldModel->getFieldDataType() !== 'picklist') {
			return array();
		}
		$values = $fieldModel->getPicklistValues();
		if (!is_array($values)) {
			return array();
		}
		$options = array();
		foreach ($values as $key => $label) {
			$options[$key] = $this->resolvePosStatusLabel($key);
			if ($options[$key] === $key) {
				$options[$key] = $this->resolvePosStatusLabel($label);
			}
			if ($options[$key] === $key) {
				$options[$key] = $label;
			}
		}
		return $options;
	}

	protected function isPosStatusFieldName($fieldName) {
		return in_array($fieldName, array('sostatus', 'salesorder_status', 'invoicestatus', 'status'), true);
	}

	protected function resolveInlineFieldModel(Vtiger_Module_Model $moduleModel, array $fieldNames, array $labelHints = array()) {
		foreach ($fieldNames as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldModel;
			}
		}
		if (empty($labelHints)) {
			return null;
		}
		foreach ($moduleModel->getFields() as $fieldModel) {
			if (!$fieldModel || !$fieldModel->isViewable()) {
				continue;
			}
			$label = trim((string) $fieldModel->get('label'));
			$translated = vtranslate($label, $moduleModel->getName());
			foreach ($labelHints as $hint) {
				if (strcasecmp($label, $hint) === 0 || strcasecmp($translated, $hint) === 0) {
					return $fieldModel;
				}
			}
		}
		return null;
	}

	protected function resolveInlineCreatorField(Vtiger_Record_Model $recordModel) {
		foreach (array('smcreatorid', 'created_user_id') as $fieldName) {
			$rawValue = $recordModel->get($fieldName);
			if ($rawValue !== '' && $rawValue !== null) {
				$displayValue = trim((string) $recordModel->getDisplayValue($fieldName));
				if ($displayValue !== '') {
					return array(
						'name' => $fieldName,
						'display' => $displayValue,
						'raw' => $rawValue,
					);
				}
			}
		}
		global $adb;
		$result = $adb->pquery('SELECT smcreatorid FROM vtiger_crmentity WHERE crmid = ?', array($recordModel->getId()));
		if ($result && $adb->num_rows($result)) {
			$creatorId = $adb->query_result($result, 0, 'smcreatorid');
			if (!empty($creatorId)) {
				return array(
					'name' => 'smcreatorid',
					'display' => getUserFullName($creatorId),
					'raw' => $creatorId,
				);
			}
		}
		return array(
			'name' => 'smcreatorid',
			'display' => '',
			'raw' => '',
		);
	}

	protected function buildInlineInfoFieldEntry(
		Vtiger_Module_Model $moduleModel,
		Vtiger_Record_Model $recordModel,
		Vtiger_Field_Model $fieldModel,
		$label
	) {
		$fieldName = $fieldModel->getName();
		$value = trim((string) $recordModel->getDisplayValue($fieldName));
		if ($this->isPosStatusFieldName($fieldName)) {
			$value = $this->resolvePosStatusLabel($value);
		}
		if ($value === '') {
			$value = '—';
		}

		$dataType = $fieldModel->getFieldDataType();
		$rawValue = $recordModel->get($fieldName);
		$editValue = $rawValue;
		if ($dataType === 'date' || $dataType === 'datetime') {
			$editValue = $fieldModel->getUITypeModel()->getDisplayValue($rawValue);
		}
		$picklistValues = array();
		if ($dataType === 'picklist') {
			if ($this->isPosStatusFieldName($fieldName)) {
				$picklistValues = $this->getPosStatusOptions($moduleModel, $fieldName);
			} else {
				$picklistValues = $fieldModel->getPicklistValues();
			}
		}
		$readOnlyFields = array('smcreatorid', 'created_user_id', 'createdtime', 'modifiedtime', 'modifiedby');
		return array(
			'name' => $fieldName,
			'label' => $label,
			'value' => $value,
			'raw_value' => $editValue,
			'data_type' => $dataType,
			'editable' => $fieldModel->isEditable() && !in_array($fieldName, $readOnlyFields, true),
			'picklist_values' => $picklistValues,
		);
	}

	protected function getInlineInfoFields(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel) {
		$candidates = array(
			array(
				'names' => array('smcreatorid', 'created_user_id'),
				'label' => 'Người tạo',
				'label_hints' => array('Người tạo', 'Creator', 'Created By'),
				'virtual' => 'creator',
			),
			array(
				'names' => array('leadsource'),
				'label' => 'Kênh bán',
				'label_hints' => array('Kênh bán', 'Lead Source', 'Source', 'Funnel'),
			),
			array(
				'names' => array('sostatus', 'salesorder_status', 'invoicestatus'),
				'label' => 'Trạng thái',
				'label_hints' => array('Trạng thái', 'Status', 'Invoice Status'),
			),
			array(
				'names' => array('assigned_user_id'),
				'label' => 'Người nhận đặt',
				'label_hints' => array('Người nhận đặt', 'Assigned To', 'Ordered By'),
			),
			array(
				'names' => array('pricebook_id'),
				'label' => 'Bảng giá',
				'label_hints' => array('Bảng giá', 'Price Book', 'List Price', 'PriceBooks'),
			),
			array(
				'names' => array('createdtime'),
				'label' => 'Ngày đặt',
				'label_hints' => array('Ngày đặt', 'Created Time', 'Order Date'),
			),
			array(
				'names' => array('duedate'),
				'label' => 'Dự kiến giao',
				'label_hints' => array('Dự kiến giao', 'Due Date', 'Ngày bàn giao', 'Expected Delivery'),
			),
		);

		$fields = array();
		$seenLabels = array();
		foreach ($candidates as $candidate) {
			$label = $candidate['label'];
			if (isset($seenLabels[$label])) {
				continue;
			}

			if (!empty($candidate['virtual']) && $candidate['virtual'] === 'creator') {
				$creator = $this->resolveInlineCreatorField($recordModel);
				$fieldModel = $this->resolveInlineFieldModel(
					$moduleModel,
					(array) $candidate['names'],
					(array) $candidate['label_hints']
				);
				$displayValue = $creator['display'] !== '' ? $creator['display'] : '—';
				$dataType = $fieldModel ? $fieldModel->getFieldDataType() : 'string';
				$fields[] = array(
					'name' => $creator['name'],
					'label' => $label,
					'value' => $displayValue,
					'raw_value' => $creator['raw'],
					'data_type' => $dataType,
					'editable' => false,
					'picklist_values' => array(),
				);
				$seenLabels[$label] = true;
				continue;
			}

			$fieldModel = $this->resolveInlineFieldModel(
				$moduleModel,
				(array) $candidate['names'],
				(array) $candidate['label_hints']
			);
			if (!$fieldModel) {
				continue;
			}
			$fields[] = $this->buildInlineInfoFieldEntry($moduleModel, $recordModel, $fieldModel, $label);
			$seenLabels[$label] = true;
		}
		return $fields;
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
