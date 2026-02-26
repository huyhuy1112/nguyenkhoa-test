<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_AddToGroup_View extends Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);

		$userId = (int)$request->get('userid');
		$selectedGroupId = (int)$request->get('groupid');
		$teamId = (int)$request->get('teamid');

		// Load groups
		$groups = array();
		$resG = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY group_name", array());
		while ($resG && ($row = $db->fetchByAssoc($resG))) {
			$groups[] = $row;
		}

		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->assign('USERID', $userId);
		$viewer->assign('GROUPS', $groups);
		$viewer->assign('SELECTED_GROUPID', $selectedGroupId);
		$viewer->assign('TEAMID', $teamId);
		$viewer->view('AddToGroup.tpl', $request->getModule());
	}
}
