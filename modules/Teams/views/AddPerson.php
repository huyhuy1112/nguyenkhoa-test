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
		Teams_Module_Model::ensureDateJoinedCompanyColumn();

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
		if (empty($timezones)) {
			$timezones = array('Asia/Ho_Chi_Minh', 'UTC');
		}

		$viewer->assign('ROLES', $roles);
		$viewer->assign('TIMEZONES', $timezones);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('APP', $request->get('app'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('TEAMID', 0);

		$viewer->view('AddPerson.tpl', $request->getModule());
	}
}
