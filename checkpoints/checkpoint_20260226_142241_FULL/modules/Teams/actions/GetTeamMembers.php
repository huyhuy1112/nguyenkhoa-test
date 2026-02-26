<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_GetTeamMembers_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();

		$members = array();
		$res = $db->pquery(
			"SELECT DISTINCT u.id, u.user_name, u.first_name, u.last_name
			 FROM vtiger_users u
			 INNER JOIN vtiger_team_group_users tgu ON tgu.userid = u.id
			 WHERE u.deleted = 0
			 ORDER BY u.last_name, u.first_name",
			array()
		);
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$members[] = $row;
		}

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('success' => true, 'members' => $members));
	}
}

