<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_SavePerson_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		Teams_Module_Model::ensureDateJoinedCompanyColumn();

		$first = trim($request->get('first_name'));
		$last = trim($request->get('last_name'));
		$email = trim($request->get('email'));
		$title = trim($request->get('title'));
		$roleId = trim($request->get('roleid'));
		$timeZone = trim($request->get('time_zone'));
		$password = $request->getRaw('password');
		$dateJoinedCompany = trim($request->get('date_joined_company'));

		if ($timeZone === '') {
			$timeZone = 'Asia/Ho_Chi_Minh';
		}

		if (empty($first) || empty($last) || empty($email) || empty($title) || empty($roleId) || empty($timeZone) || empty($password)) {
			throw new AppException('LBL_REQUIRED_FIELDS_MISSING');
		}

		$baseUser = preg_replace('/[^a-z0-9_\\.\\-]/i', '', strtolower(strtok($email, '@')));
		if (empty($baseUser)) {
			$baseUser = strtolower($first . '.' . $last);
		}
		$userName = $baseUser;
		$i = 1;
		while (true) {
			$chk = $db->pquery("SELECT 1 FROM vtiger_users WHERE user_name=? AND deleted=0", array($userName));
			if (!$chk || $db->num_rows($chk) === 0) {
				break;
			}
			$userName = $baseUser . $i;
			$i++;
		}

		$recordModel = Vtiger_Record_Model::getCleanInstance('Users');
		$recordModel->set('mode', '');
		$recordModel->set('first_name', $first);
		$recordModel->set('last_name', $last);
		$recordModel->set('email1', $email);
		$recordModel->set('title', $title);
		$recordModel->set('user_name', $userName);
		$recordModel->set('user_password', $password);
		$recordModel->set('confirm_password', $password);
		$recordModel->set('time_zone', $timeZone);
		$recordModel->set('is_admin', 'off');
		$recordModel->set('assigned_user_id', $currentUser->getId());
		$recordModel->set('roleid', $roleId);

		$moduleModel = Users_Module_Model::getCleanInstance('Users');
		$moduleModel->saveRecord($recordModel);
		$newUserId = (int)$recordModel->getId();

		$db->pquery("DELETE FROM vtiger_user2role WHERE userid=?", array($newUserId));
		$db->pquery("INSERT INTO vtiger_user2role (userid, roleid) VALUES (?,?)", array($newUserId, $roleId));

		if ($dateJoinedCompany !== '') {
			$db->pquery(
				"UPDATE vtiger_users SET date_joined_company = ? WHERE id = ?",
				array($dateJoinedCompany, $newUserId)
			);
		}

		require_once('modules/Users/CreateUserPrivilegeFile.php');
		createUserPrivilegesfile($newUserId);
		createUserSharingPrivilegesfile($newUserId);

		if ($request->isAjax()) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => true, 'userid' => $newUserId));
			$response->emit();
			return;
		}

		header('Location: index.php?module=Teams&view=List&tab=people&app=Management');
		exit;
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}
}
