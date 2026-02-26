<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_GetGroupUsers_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateReadAccess();
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$users = array();

		$type = $request->get('type');
		
		if ($type === 'all') {
			// Fetch ALL active users
			$res = $db->pquery(
				"SELECT u.id, u.user_name, u.first_name, u.last_name, u.email1 AS email
				 FROM vtiger_users u
				 WHERE u.deleted = 0 AND u.status = 'Active'
				 ORDER BY u.last_name, u.first_name",
				array()
			);
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$users[] = array(
					'id' => (int)$row['id'],
					'user_name' => $row['user_name'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'email' => $row['email'],
					'full_name' => trim($row['first_name'] . ' ' . $row['last_name'])
				);
			}
		} elseif ($type === 'users') {
			// Fetch ALL users (for manual selection - none checked initially)
			$res = $db->pquery(
				"SELECT u.id, u.user_name, u.first_name, u.last_name, u.email1 AS email
				 FROM vtiger_users u
				 WHERE u.deleted = 0
				 ORDER BY u.last_name, u.first_name",
				array()
			);
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$users[] = array(
					'id' => (int)$row['id'],
					'user_name' => $row['user_name'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'email' => $row['email'],
					'full_name' => trim($row['first_name'] . ' ' . $row['last_name'])
				);
			}
		} elseif ($type === 'groups') {
			// Fetch users from selected groups
			$groupIds = $request->get('groupids');
			if (is_array($groupIds) && !empty($groupIds)) {
				$groupIds = array_map('intval', $groupIds);
				$groupIds = array_filter($groupIds, function($id) { return $id > 0; });
				
				if (!empty($groupIds)) {
					$res = $db->pquery(
						"SELECT DISTINCT u.id, u.user_name, u.first_name, u.last_name, u.email1 AS email
						 FROM vtiger_users u
						 INNER JOIN vtiger_team_group_users tgu ON tgu.userid = u.id
						 WHERE tgu.groupid IN (" . generateQuestionMarks($groupIds) . ")
						   AND u.deleted = 0
						 ORDER BY u.last_name, u.first_name",
						$groupIds
					);
					$userMap = array();
					while ($res && ($row = $db->fetchByAssoc($res))) {
						$uid = (int)$row['id'];
						// Deduplicate
						if (!isset($userMap[$uid])) {
							$userMap[$uid] = array(
								'id' => $uid,
								'user_name' => $row['user_name'],
								'first_name' => $row['first_name'],
								'last_name' => $row['last_name'],
								'email' => $row['email'],
								'full_name' => trim($row['first_name'] . ' ' . $row['last_name'])
							);
						}
					}
					$users = array_values($userMap);
				}
			}
		}

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('success' => true, 'users' => $users, 'count' => count($users)));
	}
}
