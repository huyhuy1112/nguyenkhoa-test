<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_AddPerson_View extends Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();
		Teams_Module_Model::ensureDateJoinedCompanyColumn();

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		// Roles
		$roles = array();
		$resRoles = $db->pquery("SELECT roleid, rolename FROM vtiger_role ORDER BY rolename", array());
		while ($resRoles && ($row = $db->fetchByAssoc($resRoles))) {
			$roles[] = $row;
		}

		// Timezones
		$timezones = array();
		$resTZ = $db->pquery("SELECT time_zone FROM vtiger_time_zone ORDER BY time_zone", array());
		while ($resTZ && ($row = $db->fetchByAssoc($resTZ))) {
			$timezones[] = $row['time_zone'];
		}

		// Projects (optional)
		$projects = array();
		$tbl = $db->pquery("SHOW TABLES LIKE ?", array("vtiger_project"));
		if ($tbl && $db->num_rows($tbl) > 0) {
			$resProj = $db->pquery("SELECT projectid, projectname FROM vtiger_project ORDER BY projectname", array());
			while ($resProj && ($row = $db->fetchByAssoc($resProj))) {
				$projects[] = $row;
			}
		}

		// Groups for selection (standalone; no team filtering)
		$existingGroups = array();
		$resGroups = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY createdtime DESC", array());
		while ($resGroups && ($row = $db->fetchByAssoc($resGroups))) {
			$existingGroups[] = $row;
		}
		$viewer->assign('TEAM_GROUPS_LIST', $existingGroups);
		$viewer->assign('ROLES', $roles);
		$viewer->assign('TIMEZONES', $timezones);
		$viewer->assign('PROJECTS', $projects);

		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		// Context team record for redirect back to Detail->Groups (optional)
		$teamId = (int)$request->get('teamid');
		if (empty($teamId)) {
			$teamId = (int)$request->get('record');
		}
		$viewer->assign('TEAMID', $teamId);

		$viewer->view('AddPerson.tpl', $request->getModule());
	}
}

