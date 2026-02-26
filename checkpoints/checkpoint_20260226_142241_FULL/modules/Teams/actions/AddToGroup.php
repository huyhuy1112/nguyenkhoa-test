<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_AddToGroup_Action extends Vtiger_Action_Controller {

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
		Teams_Module_Model::ensureGroupSchema();
		$db = PearDatabase::getInstance();

		$userId = (int)$request->get('userid');
		$groupId = (int)$request->get('groupid');
		if ($userId <= 0 || $groupId <= 0) {
			throw new AppException('LBL_REQUIRED_FIELDS_MISSING');
		}

		// Do not allow assigning Owners via Teams actions
		$res = $db->pquery("SELECT is_admin FROM vtiger_users WHERE id=? AND deleted=0", array($userId));
		if ($res && $db->num_rows($res) > 0) {
			$isAdmin = $db->query_result($res, 0, 'is_admin');
			if ($isAdmin === 'on') {
				throw new AppException('LBL_PERMISSION_DENIED');
			}
		}

		$db->pquery(
			"INSERT INTO vtiger_team_group_users (groupid, userid) VALUES (?, ?) ON DUPLICATE KEY UPDATE groupid=VALUES(groupid)",
			array($groupId, $userId)
		);

		header('Location: index.php?module=Teams&view=People&app=Management&groupid='.$groupId);
	}
}
