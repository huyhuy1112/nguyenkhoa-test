<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Contacts_Detail_View extends Accounts_Detail_View {

	function __construct() {
		parent::__construct();
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
		if ($this->isModernContactDetailUi($request)) {
			$this->getViewer($request)->assign('MK_CONTACT_MODERN_UI', true);
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignModernContactDetailUi($request);
		return parent::preProcess($request, $display);
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
