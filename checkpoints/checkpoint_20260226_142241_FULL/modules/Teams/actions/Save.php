<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_Save_Action extends Vtiger_Save_Action {

	/**
	 * Process the request
	 */
	public function process(Vtiger_Request $request) {
		$recordModel = $this->saveRecord($request);
		$recordId = $recordModel->getId();
		
		// Handle team members and groups after record is saved
		$userIds = $request->get('team_users');
		$groupIds = $request->get('team_groups');
		
		if (!is_array($userIds)) {
			$userIds = array();
		}
		if (!is_array($groupIds)) {
			$groupIds = array();
		}
		
		$recordModel->updateTeamMembers($recordId, $userIds, $groupIds);
		
		// Redirect back to Teams List (NOT Dashboard)
		if ($request->get('relationOperation')) {
			$this->saveRelatedModule($request);
		} else {
			header("Location: index.php?module=Teams&view=List&app=Management");
			exit();
		}
	}
}
