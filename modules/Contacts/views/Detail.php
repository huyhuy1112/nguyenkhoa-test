<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

require_once 'modules/Leads/models/ConvertService.php';

class Contacts_Detail_View extends Accounts_Detail_View {

	function __construct() {
		parent::__construct();
	}

	/**
	 * Expandable list-row detail panel with pencil inline edit (Sales Mk list).
	 * Overrides Accounts parent which hardcodes Organizations fields.
	 */
	public function showListInlineDetail(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app !== 'SALES') {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			return '<div class="mk-so-inline-detail mk-so-inline-detail--error">Không tải được chi tiết.</div>';
		}

		require_once 'modules/Vtiger/helpers/MkSalesInlineDetailHelper.php';
		$moduleName = 'Contacts';
		try {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		} catch (Exception $e) {
			return '<div class="mk-so-inline-detail mk-so-inline-detail--error">Không tải được chi tiết khách hàng.</div>';
		}

		$title = trim((string) $recordModel->getName());
		if ($title === '') {
			$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('lastname')), ENT_QUOTES, 'UTF-8'));
		}
		$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('contact_no')), ENT_QUOTES, 'UTF-8'));
		require_once 'modules/Contacts/models/ModernService.php';
		try {
			Contacts_ModernService::ensureEventTimeColumns();
			// Reload module so newly registered fields are available for inline edit.
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		} catch (Exception $e) {
			// ignore — schema ensure is best-effort
		}

		$infoFields = Vtiger_MkSalesInlineDetailHelper::buildFields($moduleModel, $recordModel, array(
			array('phone', 'Điện thoại'),
			array('mobile', 'Di động'),
			array('email', 'Email'),
			array('mailingstreet', 'Địa chỉ'),
			array('title', 'Chức danh'),
			array('thoigian_dangky', 'Thời gian Đăng Ký'),
			array('thoigian_pcth', 'Thời gian tham gia PCTH'),
			array('thoigian_mqbb', 'Thời gian tham gia MQBB'),
			array('assigned_user_id', 'Phụ trách'),
		));

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
			require_once 'modules/Contacts/models/LastTouchCallService.php';
			$lastTouch = Contacts_LastTouchCallService::getSummary($recordId);
		} catch (Exception $e) {
			// keep defaults
		}

		$viewer = $this->getViewer($request);
		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, $app, $infoFields, $title, $subtitle);
		// Class-reg + bằng/tài khoản chỉ trên Detail — không hiện trong dropdown list.
		$viewer->assign('INLINE_SHOW_CLASS_REG', false);
		$viewer->assign('INLINE_LAST_TOUCH', $lastTouch);
		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	/**
	 * Sales + Marketing use the same modern Contacts detail shell (templates + ContactsDetail.css).
	 */
	protected function isModernContactDetailUi(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === '') {
			$app = strtoupper((string)$request->get('SELECTED_MENU_CATEGORY'));
		}
		return $app === 'SALES' || $app === 'MARKETING';
	}

	protected function assignModernContactDetailUi(Vtiger_Request $request) {
		if (!$this->isModernContactDetailUi($request)) {
			return;
		}
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		$viewer->assign('MK_CONTACT_MODERN_UI', true);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Contacts');
		$viewer->assign('VIEW', 'Detail');

		$recordId = (int)$request->get('record');
		if ($recordId > 0) {
			try {
				require_once 'modules/Contacts/models/ModernService.php';
				Contacts_ModernService::ensureEventTimeColumns();
				Contacts_ModernService::ensureCredentialFields();
				$viewer->assign('MK_CLASS_REG', Contacts_ModernService::getClassRegSummary($recordId));
				$viewer->assign('MK_CREDENTIALS', Contacts_ModernService::getCredentialState($recordId));
				$viewer->assign('MK_CONTACT_CCCD', Contacts_ModernService::getLeadCccdForContact($recordId));
			} catch (Exception $e) {
				$viewer->assign('MK_CLASS_REG', array(
					'logs' => array(),
					'hint' => 'Chọn lớp và ngày đăng ký. Mỗi lớp có Lần 1, Lần 2… riêng.',
					'can_add' => true,
					'rights_label' => '',
					'date_min' => '',
					'date_max' => '',
					'class_options' => Contacts_ModernService::getClassRegOptions(),
					'by_class' => array(),
				));
				$viewer->assign('MK_CREDENTIALS', array(
					'da_cap_bang' => 'Chưa cấp',
					'da_cap_tai_khoan' => 'Chưa cấp tài khoản',
					'bang_options' => array('Chưa cấp', 'Đã cấp'),
					'tk_options' => array('Chưa cấp tài khoản', 'Đã cấp tài khoản'),
				));
				$viewer->assign('MK_CONTACT_CCCD', '');
			}
		}
	}

	protected function assignFilteredContactTags(Vtiger_Request $request, $recordId) {
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		$recordId = (int)$recordId;
		if ($recordId <= 0) {
			return;
		}
		$viewer = $this->getViewer($request);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleName = 'Contacts';

		$allTags = Vtiger_Tag_Model::getAllAccessible($currentUserModel->getId(), $moduleName, $recordId);
		$filteredTags = array();
		foreach ($allTags as $tagId => $tagModel) {
			if (Contacts_ContactTagCatalog::isAllowed($tagModel->getName())) {
				$filteredTags[$tagId] = $tagModel;
			}
		}
		$viewer->assign('TAGS_LIST', $filteredTags);

		$allUserTags = Vtiger_Tag_Model::getAllUserTags($currentUserModel->getId());
		$filteredUserTags = array();
		foreach ($allUserTags as $tagModel) {
			if (Contacts_ContactTagCatalog::isAllowed($tagModel->getName())) {
				$filteredUserTags[] = $tagModel;
			}
		}
		$viewer->assign('ALL_USER_TAGS', $filteredUserTags);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignModernContactDetailUi($request);
		$recordId = (int)$request->get('record');
		if ($recordId > 0 && strtolower((string)$request->get('view')) === 'detail') {
			Leads_ConvertService::ensureContactTagsFromLead($recordId);
		}
		parent::preProcess($request, false);
		$this->assignModernContactDetailUi($request);
		if ($recordId > 0 && $this->isModernContactDetailUi($request)) {
			$this->assignFilteredContactTags($request, $recordId);
		}
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function process(Vtiger_Request $request) {
		$this->assignModernContactDetailUi($request);
		return parent::process($request);
	}

	public function showModuleBasicView(Vtiger_Request $request) {
		$this->assignModernContactDetailUi($request);
		return parent::showModuleBasicView($request);
	}

	public function showModuleSummaryView($request) {
		$this->assignModernContactDetailUi($request);
		return parent::showModuleSummaryView($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$this->assignModernContactDetailUi($request);
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$viewer = $this->getViewer($request);
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return parent::showModuleDetailView($request);
	}

	/**
	 * Calendar activities for Summary tab (same as Organizations detail).
	 */
	public function getActivities(Vtiger_Request $request) {
		$calendarModule = 'Calendar';
		$calendarModel = Vtiger_Module_Model::getInstance($calendarModule);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($calendarModel->getId())) {
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
