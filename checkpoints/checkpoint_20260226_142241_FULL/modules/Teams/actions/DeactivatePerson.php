<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_DeactivatePerson_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Allow for now (permission logic can be added later if needed)
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// STRICT validation - CSRF must pass
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$userId = (int)$request->get('record') ?: (int)$request->get('userid');
		$status = trim($request->get('status')) ?: 'Inactive';

		if ($userId <= 0) {
			$response = new Vtiger_Response();
			$response->setError('Invalid user ID');
			$response->emit();
			return;
		}

		// Validate status
		if (!in_array($status, array('Active', 'Inactive'))) {
			$response = new Vtiger_Response();
			$response->setError('Invalid status');
			$response->emit();
			return;
		}

		// Check if user is admin/owner
		$res = $db->pquery("SELECT is_admin FROM vtiger_users WHERE id=? AND deleted=0", array($userId));
		if ($res && $db->num_rows($res) > 0) {
			$isAdmin = $db->query_result($res, 0, 'is_admin');
			if ($isAdmin === 'on' && $status === 'Inactive') {
				$response = new Vtiger_Response();
				$response->setError('Cannot deactivate admin user');
				$response->emit();
				return;
			}
		}

		// Update user status
		if ($status === 'Inactive') {
			// Use Users module deleteRecord behavior (marks status Inactive)
			$recordModel = Users_Record_Model::getInstanceById($userId, 'Users');
			$usersModuleModel = Users_Module_Model::getInstance('Users');
			$usersModuleModel->deleteRecord($recordModel);
		} else {
			// Reactivate user
			$db->pquery("UPDATE vtiger_users SET status = 'Active', deleted = 0 WHERE id = ?", array($userId));
		}

		// Return JSON response
		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'userid' => $userId,
			'message' => 'User status updated successfully',
			'status' => $status
		));
		$response->emit();
	}
}
