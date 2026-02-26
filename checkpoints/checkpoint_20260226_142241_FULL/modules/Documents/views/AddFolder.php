<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Documents_AddFolder_View extends Vtiger_IndexAjax_View {

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}


	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	/**
	 * Khi mở trực tiếp qua URL (full page): dùng layout đầy đủ để load custom.css.
	 * Khi mở qua Ajax (modal): chỉ trả fragment, không cần header.
	 */
	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($request->isAjax()) {
			return;
		}
		Vtiger_Index_View::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($request->isAjax()) {
			return;
		}
		Vtiger_Index_View::postProcess($request);
	}

	public function process (Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$viewer->assign('FOLDER_ID', null);
		$viewer->assign('FOLDER_NAME', '');
		$viewer->assign('FOLDER_DESC', '');

		$sharedUserIds = array();
		$sharedGroupIds = array();
		if ($request->has('folderid') && $request->get('mode') == 'edit') {
			$folderId = $request->get('folderid');
			$folderModel = Documents_Folder_Model::getInstanceById($folderId);

			$viewer->assign('FOLDER_ID', $folderId);
			$viewer->assign('SAVE_MODE', $request->get('mode'));
			$viewer->assign('FOLDER_NAME', $folderModel->getName());
			$viewer->assign('FOLDER_DESC', $folderModel->getDescription());
			$sharing = $folderModel->getSharing();
			$sharedUserIds = $sharing['users'];
			$sharedGroupIds = $sharing['groups'];
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('ACCESSIBLE_USERS', $currentUser->getAccessibleUsers());
		$viewer->assign('ACCESSIBLE_GROUPS', $currentUser->getAccessibleGroups());
		$viewer->assign('SHARED_USER_IDS', $sharedUserIds);
		$viewer->assign('SHARED_GROUP_IDS', $sharedGroupIds);
		$returnUrl = $request->get('return_url');
		if (empty($returnUrl)) {
			$returnUrl = 'index.php?module=Documents&view=List';
		}
		$viewer->assign('RETURN_URL', $returnUrl);
		$viewer->assign('IS_FULL_PAGE', !$request->isAjax());
		$viewer->assign('MODULE',$moduleName);
		$viewer->view('AddFolder.tpl', $moduleName);
	}
}