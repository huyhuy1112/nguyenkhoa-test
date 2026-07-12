<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Accounts_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	/**
	 * Sales + Marketing use the same modern Organizations detail shell.
	 */
	protected function isModernAccountsDetailUi(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === '') {
			$app = strtoupper((string)$request->get('SELECTED_MENU_CATEGORY'));
		}
		return in_array($app, array('SALES', 'MARKETING', 'SUPPORT'), true);
	}

	public function showListInlineDetail(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if (!in_array($app, array('SALES', 'SUPPORT'), true)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		require_once 'modules/Vtiger/helpers/MkSalesInlineDetailHelper.php';
		$moduleName = 'Accounts';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);

		$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('accountname')), ENT_QUOTES, 'UTF-8'));
		if ($title === '') {
			$title = trim((string) $recordModel->getName());
		}
		$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('account_no')), ENT_QUOTES, 'UTF-8'));
		$infoFields = Vtiger_MkSalesInlineDetailHelper::buildFields($moduleModel, $recordModel, array(
			array('phone', 'SĐT'),
			array('email1', 'Email'),
			array('website', 'Website'),
			array('bill_city', 'Thành phố'),
			array('assigned_user_id', 'Phụ trách'),
			array('createdtime', 'Ngày tạo'),
		));
		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, $app, $infoFields, $title, $subtitle);

		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	protected function assignModernAccountsDetailUi(Vtiger_Request $request) {
		if (!$this->isModernAccountsDetailUi($request)) {
			return;
		}
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		$viewer->assign('MK_ACCOUNTS_MODERN_UI', true);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Accounts');
		$viewer->assign('VIEW', 'Detail');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignModernAccountsDetailUi($request);
		parent::preProcess($request, false);
		$this->assignModernAccountsDetailUi($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	/**
	 * Enforce record-level Tag ACL on DetailView for non-privileged users.
	 */
	public function process(Vtiger_Request $request) {
		$this->assignModernAccountsDetailUi($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		require_once 'modules/Contacts/models/TagAccessHelper.php';

		$recordId = (int)$request->get('record');
		$moduleName = (string)$request->getModule();

		$isPrivileged = TagAccessHelper::isPrivilegedUser($currentUser);
		if ($isPrivileged || $recordId <= 0) {
			if ($isPrivileged) {
				error_log('[TagACL][Detail] user=' . $currentUser->getId() . ' record=' . $recordId . ' allowed=1');
			}
			return parent::process($request);
		}

		$roleName = (string)$currentUser->getUserRoleName();
		$mappedTags = TagAccessHelper::getUserAccessTags($currentUser);
		$isAdmin = $currentUser->isAdminUser() ? '1' : '0';

		$allowed = $this->isAllowedByTags($recordId, $moduleName, $mappedTags);
		error_log('[TagACL][Detail] user=' . $currentUser->getId() .
			' record=' . $recordId .
			' module=' . $moduleName .
			' role=' . $roleName .
			' mappedTags=' . json_encode($mappedTags, JSON_UNESCAPED_UNICODE) .
			' isAdmin=' . $isAdmin .
			' isPrivileged=' . ($isPrivileged ? '1' : '0') .
			' allowed=' . ($allowed ? '1' : '0'));

		if (!$allowed) {
			throw new AppException('Permission denied');
		}

		return parent::process($request);
	}

	/**
	 * Check if record has at least one allowed tag using REAL tag tables.
	 * - vtiger_freetagged_objects (tag_id, object_id, module)
	 * - vtiger_freetags (id, tag)
	 */
	protected function isAllowedByTags($recordId, $moduleName, array $allowedTags) {
		if ($recordId <= 0) {
			return false;
		}
		if (empty($allowedTags)) {
			// secure default: show nothing
			return false;
		}

		$placeholders = implode(',', array_fill(0, count($allowedTags), '?'));
		$params = array_merge(array($recordId, $moduleName), array_values($allowedTags));

		global $adb;
		$sql = "SELECT 1
			FROM vtiger_freetagged_objects fto
			INNER JOIN vtiger_freetags ft ON ft.id = fto.tag_id
			WHERE fto.object_id = ?
			  AND fto.module = ?
			  AND ft.tag IN ($placeholders)
			LIMIT 1";
		$res = $adb->pquery($sql, $params);
		return ($res && $adb->num_rows($res) > 0);
	}

	/**
	 * Function to get activities
	 * @param Vtiger_Request $request
	 * @return <List of activity models>
	 */
	public function getActivities(Vtiger_Request $request) {
		$moduleName = 'Calendar';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if($currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			$moduleName = $request->getModule();
			$recordId = $request->get('record');

			$pageNumber = $request->get('page');
			if(empty ($pageNumber)) {
				$pageNumber = 1;
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', $pageNumber);
			$pagingModel->set('limit', 10);

			if(!$this->record) {
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

	public function showModuleBasicView(Vtiger_Request $request) {
		$this->assignModernAccountsDetailUi($request);
		return parent::showModuleBasicView($request);
	}

	public function showModuleSummaryView($request) {
		$this->assignModernAccountsDetailUi($request);
		return parent::showModuleSummaryView($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$this->assignModernAccountsDetailUi($request);
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		// Getting model to reuse it in parent 
		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$viewer = $this->getViewer($request);
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return parent::showModuleDetailView($request);
	}

}
