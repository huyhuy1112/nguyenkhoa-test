<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_People_View extends Vtiger_Index_View {

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Teams.resources.Group',
			'modules.Teams.resources.Person',
			'modules.Teams.resources.TeamsModal'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'ListView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if ($mode === 'modal') {
			$this->renderModal($request);
			return;
		}

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		$roles = array();
		$resRoles = $db->pquery("SELECT roleid, rolename FROM vtiger_role ORDER BY rolename", array());
		while ($resRoles && ($row = $db->fetchByAssoc($resRoles))) {
			$roles[] = $row;
		}

		$timezones = array();
		$resTZ = $db->pquery("SELECT time_zone FROM vtiger_time_zone ORDER BY time_zone", array());
		while ($resTZ && ($row = $db->fetchByAssoc($resTZ))) {
			$timezones[] = $row['time_zone'];
		}

		$existingGroups = array();
		$resGroups = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY createdtime DESC", array());
		while ($resGroups && ($row = $db->fetchByAssoc($resGroups))) {
			$existingGroups[] = $row;
		}

		$viewer->assign('TEAM_GROUPS_LIST', $existingGroups);
		$viewer->assign('ROLES', $roles);
		$viewer->assign('TIMEZONES', $timezones);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('AddPerson.tpl', $request->getModule());
	}

	protected function renderModal(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();

		$roles = array();
		$resRoles = $db->pquery("SELECT roleid, rolename FROM vtiger_role ORDER BY rolename", array());
		while ($resRoles && ($row = $db->fetchByAssoc($resRoles))) {
			$roles[] = $row;
		}

		$timezones = array();
		$resTZ = $db->pquery("SELECT time_zone FROM vtiger_time_zone ORDER BY time_zone", array());
		while ($resTZ && ($row = $db->fetchByAssoc($resTZ))) {
			$timezones[] = $row['time_zone'];
		}

		$existingGroups = array();
		$resGroups = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY createdtime DESC", array());
		while ($resGroups && ($row = $db->fetchByAssoc($resGroups))) {
			$existingGroups[] = $row;
		}

		// Projects (optional) - same logic as AddGroup
		$projects = array();
		$tbl = $db->pquery("SHOW TABLES LIKE ?", array("vtiger_project"));
		if ($tbl && $db->num_rows($tbl) > 0) {
			$resProj = $db->pquery("SELECT projectid, projectname FROM vtiger_project ORDER BY projectname", array());
			while ($resProj && ($row = $db->fetchByAssoc($resProj))) {
				$projects[] = $row;
			}
		}

		$viewer->assign('TEAM_GROUPS_LIST', $existingGroups);
		$viewer->assign('ROLES', $roles);
		$viewer->assign('TIMEZONES', $timezones);
		$viewer->assign('PROJECTS', $projects);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		echo $viewer->view('AddPersonModal.tpl', $request->getModule(), true);
	}
}
