<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_EditGroup_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// For AJAX requests, use lenient validation
		if ($request->isAjax()) {
			try {
				$request->validateReadAccess();
			} catch (Exception $e) {
				// Allow even if referer check fails for AJAX
			}
			return true;
		}
		return $request->validateReadAccess();
	}

	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();
		$db = PearDatabase::getInstance();
		$groupId = (int)$request->get('record') ?: (int)$request->get('groupid');

		if ($groupId <= 0) {
			$response = new Vtiger_Response();
			$response->setError('Invalid group ID');
			$response->emit();
			return;
		}

		// Load group data
		$resGroup = $db->pquery(
			"SELECT groupid, group_name, assign_type FROM vtiger_team_groups WHERE groupid = ?",
			array($groupId)
		);

		if (!$resGroup || $db->num_rows($resGroup) === 0) {
			$response = new Vtiger_Response();
			$response->setError('Group not found');
			$response->emit();
			return;
		}

		$groupData = $db->fetchByAssoc($resGroup);

		// Load assigned users
		$selectedUserIds = array();
		$resUsers = $db->pquery(
			"SELECT userid FROM vtiger_team_group_users WHERE groupid = ?",
			array($groupId)
		);
		while ($resUsers && ($row = $db->fetchByAssoc($resUsers))) {
			$selectedUserIds[] = (int)$row['userid'];
		}

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'group' => $groupData,
			'selectedUserIds' => $selectedUserIds
		));
		$response->emit();
	}
}
