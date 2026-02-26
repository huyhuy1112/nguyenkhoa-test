<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_EditGroup_View extends Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		// Allow for now (permission logic can be added later if needed)
		return true;
	}

	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		$groupId = (int)$request->get('record') ?: (int)$request->get('groupid');
		if ($groupId <= 0) {
			// Redirect to Groups list if invalid groupid
			header('Location: index.php?module=Teams&view=List&tab=groups&app=Management');
			exit;
		}

		// Load existing group data
		$groupData = null;
		$selectedUserIds = array();
		$resGroup = $db->pquery(
			"SELECT groupid, group_name, assign_type FROM vtiger_team_groups WHERE groupid = ?",
			array($groupId)
		);
		if ($resGroup && $db->num_rows($resGroup) > 0) {
			$groupData = $db->fetchByAssoc($resGroup);
			// Load assigned users
			$resUsers = $db->pquery(
				"SELECT userid FROM vtiger_team_group_users WHERE groupid = ?",
				array($groupId)
			);
			while ($resUsers && ($row = $db->fetchByAssoc($resUsers))) {
				$selectedUserIds[] = (int)$row['userid'];
			}
		} else {
			// Group not found, redirect to list
			header('Location: index.php?module=Teams&view=List&tab=groups&app=Management');
			exit;
		}

		// Load all users for checkbox list
		$members = array();
		$res = $db->pquery(
			"SELECT u.id, u.user_name, u.first_name, u.last_name
			 FROM vtiger_users u
			 WHERE u.deleted = 0
			 ORDER BY u.last_name, u.first_name",
			array()
		);
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$members[] = $row;
		}

		$viewer->assign('GROUP', $groupData);
		$viewer->assign('GROUP_DATA', $groupData);
		$viewer->assign('GROUP_ID', $groupId);
		$viewer->assign('TEAM_MEMBERS', $members);
		$viewer->assign('ALL_USERS', $members);
		$viewer->assign('SELECTED_USERS', $selectedUserIds);
		$viewer->assign('SELECTED_USER_IDS', $selectedUserIds);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->view('EditGroup.tpl', $request->getModule());
	}
}
