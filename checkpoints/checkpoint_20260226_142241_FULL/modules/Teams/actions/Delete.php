<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_Delete_Action extends Vtiger_Delete_Action {

	/**
	 * Process the request - Archive team (soft delete)
	 */
	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		
		$recordModel = Teams_Record_Model::getInstanceById($recordId);
		
		// Archive team (soft delete)
		$db = PearDatabase::getInstance();
		$db->pquery(
			"UPDATE vtiger_teams SET status = 'Archived', deleted = 1 WHERE teamid = ?",
			array($recordId)
		);
		
		// Update vtiger_crmentity
		$db->pquery(
			"UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?",
			array($recordId)
		);
		
		// Redirect back to Teams List
		header("Location: index.php?module=Teams&view=List&app=Management");
		exit();
	}
}
