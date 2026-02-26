<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_DeleteUser_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Allow for now (permission logic can be added later if needed)
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// STRICT validation - CSRF must pass
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {
			Teams_Module_Model::ensureGroupSchema();

			$db = PearDatabase::getInstance();
			$userId = (int)$request->get('record') ?: (int)$request->get('userid');

			if ($userId <= 0) {
				$response = new Vtiger_Response();
				$response->setError('Invalid user ID');
				$response->emit();
				return;
			}

			// Delete user from team groups (does NOT delete the user record)
			// Remove user from all team groups
			$db->pquery("DELETE FROM vtiger_team_group_users WHERE userid = ?", array($userId));

			// Also remove from team members if exists
			$tblCheck = $db->pquery("SHOW TABLES LIKE 'vtiger_team_members'", array());
			if ($tblCheck && $db->num_rows($tblCheck) > 0) {
				$db->pquery("DELETE FROM vtiger_team_members WHERE userid = ?", array($userId));
			}

			// Return JSON response
			$response = new Vtiger_Response();
			$response->setResult(array(
				'success' => true,
				'userid' => $userId,
				'message' => 'User deleted from team successfully'
			));
			$response->emit();
		} catch (Exception $e) {
			$response = new Vtiger_Response();
			$response->setError('Failed to delete user: ' . $e->getMessage());
			$response->emit();
		}
	}
}
