<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_SuspendUser_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$userId = (int)$request->get('userid');
		if ($userId <= 0) {
			throw new AppException('LBL_REQUIRED_FIELDS_MISSING');
		}

		$res = $db->pquery("SELECT is_admin FROM vtiger_users WHERE id=? AND deleted=0", array($userId));
		if ($res && $db->num_rows($res) > 0) {
			$isAdmin = $db->query_result($res, 0, 'is_admin');
			if ($isAdmin === 'on') {
				// Owner cannot be suspended
				throw new AppException('LBL_PERMISSION_DENIED');
			}
		}

		// Reuse Users module deleteRecord behavior (marks status Inactive)
		$recordModel = Users_Record_Model::getInstanceById($userId, 'Users');
		$usersModuleModel = Users_Module_Model::getInstance('Users');
		$usersModuleModel->deleteRecord($recordModel);

		// Check if this is an AJAX request
		if ($request->isAjax()) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => true, 'message' => 'User deactivated successfully'));
			$response->emit();
			return;
		}

		// Fallback: redirect for non-AJAX requests
		header('Location: index.php?module=Teams&view=List&tab=people&app=Management');
	}
}
