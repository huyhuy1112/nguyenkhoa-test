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
		$groupId = (int)$request->get('team_groupid');
		$projectId = $request->get('projectid');
		$timeZone = trim($request->get('time_zone'));
		$password = $request->getRaw('password');
		$dateJoinedCompany = trim($request->get('date_joined_company'));

		if (empty($first) || empty($last) || empty($email) || empty($title) || empty($roleId) || empty($groupId) || empty($timeZone) || empty($password)) {
			throw new AppException('LBL_REQUIRED_FIELDS_MISSING');
		}

		// Build a unique username from email prefix
		$baseUser = preg_replace('/[^a-z0-9_\\.\\-]/i', '', strtolower(strtok($email, '@')));
		if (empty($baseUser)) {
			$baseUser = strtolower($first . '.' . $last);
		}
		$userName = $baseUser;
		$i = 1;
		while (true) {
			$chk = $db->pquery("SELECT 1 FROM vtiger_users WHERE user_name=? AND deleted=0", array($userName));
			if (!$chk || $db->num_rows($chk) === 0) break;
			$userName = $baseUser . $i;
			$i++;
		}

		// Create user record using Users module models
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

		// Ensure role mapping exists (some installs use vtiger_user2role)
		$db->pquery("DELETE FROM vtiger_user2role WHERE userid=?", array($newUserId));
		$db->pquery("INSERT INTO vtiger_user2role (userid, roleid) VALUES (?,?)", array($newUserId, $roleId));

		// Link user to selected group (group is the only organizational unit)
		$db->pquery(
			"INSERT INTO vtiger_team_group_users (groupid, userid) VALUES (?,?)",
			array($groupId, $newUserId)
		);

		// Save ngày vào công ty (date joined company) if provided
		if ($dateJoinedCompany !== '') {
			$db->pquery(
				"UPDATE vtiger_users SET date_joined_company = ? WHERE id = ?",
				array($dateJoinedCompany, $newUserId)
			);
		}

		// Regenerate privilege files for this user
		require_once('modules/Users/CreateUserPrivilegeFile.php');
		createUserPrivilegesfile($newUserId);
		createUserSharingPrivilegesfile($newUserId);

		// Return JSON for AJAX requests (modal)
		if ($request->isAjax()) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => true, 'userid' => $newUserId));
			$response->emit();
			return;
		}

		// Redirect for non-AJAX requests: về lại Teams tab People
		$teamId = (int)$request->get('teamid');
		if ($teamId > 0) {
			header('Location: index.php?module=Teams&view=Detail&record='.$teamId.'&tab=groups&app=Management');
		} else {
			header('Location: index.php?module=Teams&view=List&tab=people&app=Management');
		}
		exit;
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}
}

