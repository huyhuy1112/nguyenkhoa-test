<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_Detail_View extends Vtiger_Detail_View {

	/**
	 * Pre-process - Set menu category for proper navigation
	 */
	public function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		if(!empty($appName)){
			$viewer->assign('SELECTED_MENU_CATEGORY',$appName);
		}
		if($display) {
			$this->preProcessDisplay($request);
		}
	}

	/**
	 * Process the request
	 */
	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();
		Teams_Module_Model::ensureUserActivitySchema();

		$viewer = $this->getViewer($request);
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Teams_Record_Model::getInstanceById($recordId);
		$recordModel->set('module', $moduleName);

		// Get team users and groups
		$viewer->assign('USERS_COUNT', $recordModel->getUsersCount());
		$viewer->assign('GROUPS_COUNT', $recordModel->getGroupsCount());
		$viewer->assign('TEAM_USERS', $recordModel->getTeamUsers());
		$viewer->assign('TEAM_GROUPS', $recordModel->getTeamGroups());

		// Groups management (Groups are the source of truth; do not filter by teamid)
		$db = PearDatabase::getInstance();
		$groups = array();
		$res = $db->pquery(
			"SELECT groupid, teamid, group_name, assign_type, projectid, createdtime
			 FROM vtiger_team_groups ORDER BY createdtime DESC",
			array()
		);
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$groups[] = $row;
		}
		$viewer->assign('TEAM_GROUPS_LIST', $groups);
		$viewer->assign('GROUPS_COUNT', count($groups));

		// Presence (online/offline) for members
		$presenceMap = array();
		$userIds = array();
		foreach ($recordModel->getTeamUsers() as $u) {
			if (!empty($u['id'])) $userIds[] = (int)$u['id'];
		}
		if (!empty($userIds)) {
			$resP = $db->pquery(
				"SELECT userid, last_seen FROM vtiger_user_activity WHERE userid IN (" . generateQuestionMarks($userIds) . ")",
				$userIds
			);
			while ($resP && ($row = $db->fetchByAssoc($resP))) {
				$presenceMap[(int)$row['userid']] = $row['last_seen'];
			}
		}
		$viewer->assign('PRESENCE_MAP', $presenceMap);

		// Permission for add group (Admin OR Users.Create)
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$canAddGroup = ($currentUser->isAdminUser() || Users_Privileges_Model::isPermitted('Users', 'CreateView'));
		$viewer->assign('CAN_ADD_GROUP', $canAddGroup);
		$viewer->assign('CAN_ADD_PERSON', ($currentUser->isAdminUser() || Users_Privileges_Model::isPermitted('Users', 'CreateView')));

		// Active tab selection (optional)
		$viewer->assign('ACTIVE_TAB', $request->get('tab'));

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->view('Detail.tpl', $moduleName);
	}
}
