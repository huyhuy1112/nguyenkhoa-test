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
		$phone = isset($franchise['phone']) ? (string) $franchise['phone'] : '';
		$biz = isset($franchise['business_note']) ? (string) $franchise['business_note'] : '';
		$im = isset($franchise['interaction_materials']) ? (string) $franchise['interaction_materials'] : '';
		$affCode = isset($franchise['affiliate_code']) ? trim((string) $franchise['affiliate_code']) : '';
		$refCode = isset($franchise['referral_code']) ? strtoupper(trim((string) $franchise['referral_code'])) : '';
		$referrer = isset($franchise['referrer']) ? trim((string) $franchise['referrer']) : '';

		// BA: bỏ "Nguồn data" khỏi panel (auto set khi nhập mã GT).
		// Khi chưa có mã GT → cho nhập; đã có → khóa cứng + hiện thông tin người GT.
		$refLocked = ($refCode !== '');
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
				'name' => 'phone',
				'label' => 'SĐT',
				'value' => $phone !== '' ? $phone : '—',
				'raw_value' => $phone,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'business_note',
				'label' => 'Địa chỉ kinh doanh',
				'value' => $biz !== '' ? $biz : '—',
				'raw_value' => $biz,
				'data_type' => 'text',
				'editable' => true,
				'picklist_values' => array(),
			),
			array(
				'name' => 'referral_code',
				'label' => 'Mã người giới thiệu',
				'value' => $refCode !== '' ? $refCode : '—',
				'raw_value' => $refCode,
				'data_type' => 'string',
				'editable' => !$refLocked,
				'readonly_locked' => $refLocked,
				'placeholder' => 'AFF-######',
				'picklist_values' => array(),
			),
			array(
				'name' => 'referrer',
				'label' => 'Thông tin người giới thiệu',
				'value' => $referrer !== '' ? $referrer : ($refCode !== '' ? $refCode : '—'),
				'raw_value' => $referrer,
				'data_type' => 'string',
				'editable' => false,
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
		);
		// BA: interactions 1/2/3 merged into Last Touch Call (Tương tác gần đây).
		$materialsField = array(
			'name' => 'interaction_materials',
			'label' => 'TƯƠNG TÁC TỰ MỞ NGUYÊN LIỆU MÁY MÓC',
			'value' => $im !== '' ? $im : '—',
			'raw_value' => $im,
			'data_type' => 'text',
			'editable' => true,
			'picklist_values' => array(),
		);
		$ownerField = Vtiger_MkSalesInlineDetailHelper::buildFieldEntry(
			$moduleModel,
			$recordModel,
			'assigned_user_id',
			'Phụ trách'
		);

		$lastTouch = array(
			'can_add' => true,
			'next_n' => 1,
			'count' => 0,
			'max_calls' => 3,
			'hint' => '',
			'reminder_at_label' => '',
			'calls' => array(),
		);
		try {
			require_once 'modules/ServiceContracts/models/LastTouchCallService.php';
			$lastTouch = ServiceContracts_LastTouchCallService::getSummary((int) $recordId);
		} catch (Exception $e) {
			// keep defaults
		}

		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, 'SALES', $infoFields, $title, $subtitle);
		$viewer->assign('INLINE_HIDE_TAGS', true);
		$viewer->assign('INLINE_LAST_TOUCH', $lastTouch);
		// BA: gộp Tương tác lần 1/2/3 → chỉ hiện Last Touch Call (Tương tác gần đây).
		$viewer->assign('INLINE_SC_INTERACTIONS', array());
		$viewer->assign('INLINE_SC_MATERIALS', $materialsField);
		$viewer->assign('INLINE_SC_OWNER', $ownerField ? $ownerField : null);
		$affVisible = ($affCode !== '' && !empty($franchise['affiliate_visible']));
		$viewer->assign('INLINE_SC_AFFILIATE_CODE', $affCode);
		$viewer->assign('INLINE_SC_AFF_VISIBLE', $affVisible ? 1 : 0);
		$viewer->assign('INLINE_SC_CAN_CREATE_AFF', false);

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
