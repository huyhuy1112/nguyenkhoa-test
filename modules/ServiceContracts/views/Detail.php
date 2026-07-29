<?php
/* +***********************************************************************************
 * ServiceContracts Detail: calendar activities + POS list inline dropdown.
 * *********************************************************************************** */

class ServiceContracts_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	public function showListInlineDetail(Vtiger_Request $request) {
		if (strtoupper((string) $request->get('app')) !== 'SALES') {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		require_once 'modules/Vtiger/helpers/MkSalesInlineDetailHelper.php';
		require_once 'modules/ServiceContracts/models/ModernService.php';
		$moduleName = 'ServiceContracts';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);

		$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('subject')), ENT_QUOTES, 'UTF-8'));
		if ($title === '') {
			$title = trim((string) $recordModel->getName());
		}
		$franchise = array();
		try {
			$franchise = ServiceContracts_ModernService::getFranchise((int) $recordId);
		} catch (Exception $e) {
			$franchise = array();
		}
		$subtitle = '';
		if (!empty($franchise['affiliate_code'])) {
			$subtitle = (string) $franchise['affiliate_code'];
			if (!empty($franchise['affiliate_tier_name'])) {
				$subtitle .= ' · ' . $franchise['affiliate_tier_name'];
			}
		} else {
			$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('contract_no')), ENT_QUOTES, 'UTF-8'));
		}

		$pick = ServiceContracts_ModernService::franchisePicklists();
		$statusOpts = array('' => '—');
		foreach ($pick['franchise_status'] as $opt) {
			$statusOpts[$opt] = $opt;
		}
		$contactOpts = array('' => '—');
		foreach ($pick['contact_status'] as $opt) {
			$contactOpts[$opt] = $opt;
		}
		$fs = isset($franchise['franchise_status']) ? (string) $franchise['franchise_status'] : '';
		$cs = isset($franchise['contact_status']) ? (string) $franchise['contact_status'] : '';
		$ref = isset($franchise['referrer']) ? (string) $franchise['referrer'] : '';
		$i1 = isset($franchise['interaction_1']) ? (string) $franchise['interaction_1'] : '';
		$i2 = isset($franchise['interaction_2']) ? (string) $franchise['interaction_2'] : '';
		$i3 = isset($franchise['interaction_3']) ? (string) $franchise['interaction_3'] : '';
		$im = isset($franchise['interaction_materials']) ? (string) $franchise['interaction_materials'] : '';
		$infoFields = array(
			array(
				'name' => 'franchise_status',
				'label' => 'Trạng thái',
				'value' => $fs !== '' ? $fs : '—',
				'raw_value' => $fs,
				'data_type' => 'picklist',
				'editable' => true,
				'picklist_values' => $statusOpts,
			),
			array(
				'name' => 'referrer',
				'label' => 'Liên quan tới',
				'value' => $ref !== '' ? $ref : '—',
				'raw_value' => $ref,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'contact_status',
				'label' => 'Liên hệ',
				'value' => $cs !== '' ? $cs : '—',
				'raw_value' => $cs,
				'data_type' => 'picklist',
				'editable' => true,
				'picklist_values' => $contactOpts,
			),
			array(
				'name' => 'interaction_1',
				'label' => 'Tương tác lần 1',
				'value' => $i1 !== '' ? $i1 : '—',
				'raw_value' => $i1,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'interaction_2',
				'label' => 'Tương tác lần 2',
				'value' => $i2 !== '' ? $i2 : '—',
				'raw_value' => $i2,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'interaction_3',
				'label' => 'Tương tác lần 3',
				'value' => $i3 !== '' ? $i3 : '—',
				'raw_value' => $i3,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'interaction_materials',
				'label' => 'Tương tác NL máy móc',
				'value' => $im !== '' ? $im : '—',
				'raw_value' => $im,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
		);
		$ownerField = Vtiger_MkSalesInlineDetailHelper::buildFieldEntry(
			$moduleModel,
			$recordModel,
			'assigned_user_id',
			'Phụ trách'
		);
		if ($ownerField) {
			$infoFields[] = $ownerField;
		}

		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, 'SALES', $infoFields, $title, $subtitle);
		$viewer->assign('INLINE_HIDE_TAGS', true);

		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	/**
	 * @param Vtiger_Request $request
	 * @return string
	 */
	public function getActivities(Vtiger_Request $request) {
		$moduleName = 'Calendar';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
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
