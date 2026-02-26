<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_DeleteGroup_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Allow for now (permission logic can be added later if needed)
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $adb;
		Teams_Module_Model::ensureGroupSchema();

		$id = (int)$request->get('record') ?: (int)$request->get('groupid');

		if ($id <= 0) {
			header('Location: index.php?module=Teams&view=List&tab=groups&app=Management');
			exit;
		}

		$adb->pquery('DELETE FROM vtiger_team_group_users WHERE groupid=?', array($id));
		$adb->pquery('DELETE FROM vtiger_team_groups WHERE groupid=?', array($id));

		header('Location: index.php?module=Teams&view=List&tab=groups&app=Management');
		exit;
	}
}
