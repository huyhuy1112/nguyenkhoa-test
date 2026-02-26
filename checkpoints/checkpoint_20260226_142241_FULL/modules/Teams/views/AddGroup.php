<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_AddGroup_View extends Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		if ($request->get('mode') === 'modal') {
			$this->renderModal($request);
			return;
		}
		Teams_Module_Model::ensureGroupSchema();

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		// Users list for Assign Type = USERS: load from vtiger_users only
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

		// Groups list for Assign Type = GROUPS
		$existingGroups = array();
		$resGroups = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY group_name", array());
		while ($resGroups && ($row = $db->fetchByAssoc($resGroups))) {
			$existingGroups[] = $row;
		}

		$viewer->assign('TEAM_MEMBERS', $members);
		$viewer->assign('EXISTING_GROUPS', $existingGroups);

		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->view('AddGroup.tpl', $request->getModule());
	}

	protected function renderModal(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		$groupId = (int)$request->get('groupid');
		$isEdit = ($groupId > 0);

		// Load existing group data for edit mode
		$groupData = null;
		$selectedUserIds = array();
		$selectedGroupIds = array();
		if ($isEdit) {
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
			}
		}

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

		// Groups list for Assign Type = GROUPS (exclude current group in edit mode)
		$existingGroups = array();
		$sqlGroups = "SELECT groupid, group_name FROM vtiger_team_groups";
		$paramsGroups = array();
		if ($isEdit) {
			$sqlGroups .= " WHERE groupid != ?";
			$paramsGroups[] = $groupId;
		}
		$sqlGroups .= " ORDER BY group_name";
		$resGroups = $db->pquery($sqlGroups, $paramsGroups);
		while ($resGroups && ($row = $db->fetchByAssoc($resGroups))) {
			$existingGroups[] = $row;
		}

		$viewer->assign('TEAM_MEMBERS', $members);
		$viewer->assign('EXISTING_GROUPS', $existingGroups);
		$viewer->assign('GROUP_DATA', $groupData);
		$viewer->assign('SELECTED_USER_IDS', $selectedUserIds);
		$viewer->assign('IS_EDIT', $isEdit);
		$viewer->assign('GROUP_ID', $groupId);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		echo $viewer->view('AddGroupModal.tpl', $request->getModule(), true);
	}
}

