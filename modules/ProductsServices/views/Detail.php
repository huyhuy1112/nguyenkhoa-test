<?php

class ProductsServices_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	/**
	 * Expandable list-row detail panel (Leads / Accounts style) for Kho + Sales lists.
	 */
	public function showListInlineDetail(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app === '') {
			$app = 'INVENTORY';
		}
		if (!in_array($app, array('INVENTORY', 'SALES'), true)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			return '';
		}

		require_once 'modules/Vtiger/helpers/MkSalesInlineDetailHelper.php';
		$moduleName = 'ProductsServices';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$title = trim((string) $recordModel->getName());
		if ($title === '') {
			$title = trim(html_entity_decode(
				strip_tags((string) $recordModel->getDisplayValue('productsservicesname')),
				ENT_QUOTES,
				'UTF-8'
			));
		}
		$subtitle = trim(html_entity_decode(
			strip_tags((string) $recordModel->getDisplayValue('item_type')),
			ENT_QUOTES,
			'UTF-8'
		));

		$infoFields = Vtiger_MkSalesInlineDetailHelper::buildFields($moduleModel, $recordModel, array(
			array('sku', 'SKU'),
			array('item_type', 'Loại'),
			array('price', 'Giá'),
			array('wholesale_price', 'Giá sỉ'),
			array('needs_qc', 'Cần QC'),
			array('unit', 'Đơn vị'),
			array('specification', 'Diễn giải'),
			array('assigned_user_id', 'Phụ trách'),
			array('createdtime', 'Ngày tạo'),
		));

		$viewer = $this->getViewer($request);
		Vtiger_MkSalesInlineDetailHelper::assignCommon(
			$viewer,
			$recordModel,
			$moduleName,
			$app,
			$infoFields,
			$title,
			$subtitle
		);
		// Products have no care-tags / last-touch workflow — keep panel lean (Leads-like layout).
		$viewer->assign('INLINE_HIDE_TAGS', true);
		$viewer->assign('INLINE_SHOW_NEXT_ACTION', false);

		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	/**
	 * Summary tab: stock Calendar activities (Add Task / Add Event) for this record.
	 */
	public function getActivities(Vtiger_Request $request) {
		$calendarModuleName = 'Calendar';
		$calendarModuleModel = Vtiger_Module_Model::getInstance($calendarModuleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($calendarModuleModel->getId())) {
			return '';
		}

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$pageNumber = $request->get('page');
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('limit', 10);

		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$moduleModel = $recordModel->getModule();

		$relatedActivities = $moduleModel->getCalendarActivities('', $pagingModel, 'all', $recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ACTIVITIES', $relatedActivities);

		return $viewer->view('RelatedActivities.tpl', $moduleName, true);
	}
}
