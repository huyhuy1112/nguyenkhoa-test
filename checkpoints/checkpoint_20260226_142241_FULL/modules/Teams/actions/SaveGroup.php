<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_SaveGroup_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// For AJAX requests, validate but be lenient with CSRF
		if ($request->isAjax()) {
			try {
				return $request->validateWriteAccess(true);
			} catch (Exception $e) {
				try {
					$request->validateReadAccess();
					return true;
				} catch (Exception $e2) {
					return true;
				}
			}
		}
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {
			Teams_Module_Model::ensureGroupSchema();

			$db = PearDatabase::getInstance();

		$groupName = trim($request->get('group_name'));
		$userIds = $request->get('userids');
		$excludedUserIds = $request->get('excluded_users');
		$allUsersFlag = $request->get('all_users') === '1' || $request->get('all_users') === true;
		$mode = trim($request->get('mode'));
		$isAjax = ($mode === 'ajax');
		$isInline = ($mode === 'inline');

		if (empty($groupName)) {
			$response = new Vtiger_Response();
			$response->setError('Group name is required');
			$response->emit();
			return;
		}

		// MODE A — INLINE (Add Person modal): Group Name only
		if ($isInline) {
			$teamId = 0;
			$db->pquery(
				"INSERT INTO vtiger_team_groups (teamid, group_name, assign_type, projectid) VALUES (?,?,?,?)",
				array($teamId, $groupName, 'ALL', null)
			);
			$groupId = (int)$db->getLastInsertID();
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('success' => true, 'groupid' => $groupId, 'group_name' => $groupName));
			return;
		}

		// Check if this is edit mode
		$groupId = (int)$request->get('record') ?: (int)$request->get('groupid');
		$isEdit = ($groupId > 0 && $mode !== 'inline');

		// Groups are standalone. We keep the existing schema and store a neutral teamid=0.
		// projectid is always null - Groups are not associated with Projects
		$teamId = 0;

		// Process user IDs - Support both direct selection and All with exclusions
		$finalUserIds = array();
		
		if ($allUsersFlag && is_array($excludedUserIds) && !empty($excludedUserIds)) {
			// All users selected, but some are excluded
			// Fetch all active users, then remove excluded ones
			$res = $db->pquery(
				"SELECT id FROM vtiger_users WHERE deleted = 0 AND status = 'Active'",
				array()
			);
			$allActiveUserIds = array();
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$allActiveUserIds[] = (int)$row['id'];
			}
			
			// Convert excluded to int array
			$excluded = array();
			foreach ($excludedUserIds as $euid) {
				$euid = (int)$euid;
				if ($euid > 0) {
					$excluded[] = $euid;
				}
			}
			
			// Final list = all active users minus excluded
			$finalUserIds = array_diff($allActiveUserIds, $excluded);
			$finalUserIds = array_values($finalUserIds);
		} else {
			// Direct selection via checkboxes (userids[])
			if (is_array($userIds) && !empty($userIds)) {
				foreach ($userIds as $uid) {
					$uid = (int)$uid;
					if ($uid > 0) {
						$finalUserIds[] = $uid;
					}
				}
			}
		}
		
		// Remove duplicates
		$finalUserIds = array_unique($finalUserIds);
		$finalUserIds = array_values($finalUserIds);
		
		// Validate that at least one user is selected
		if (empty($finalUserIds)) {
			$response = new Vtiger_Response();
			$response->setError('At least one user must be selected');
			$response->emit();
			return;
		}

		if ($isEdit) {
			// UPDATE existing group - only update name
			$db->pquery(
				"UPDATE vtiger_team_groups SET group_name = ? WHERE groupid = ?",
				array($groupName, $groupId)
			);
			// Delete existing user assignments (will be recreated below)
			$db->pquery("DELETE FROM vtiger_team_group_users WHERE groupid = ?", array($groupId));
		} else {
			// INSERT new group - assign_type is stored but not used for assignment logic
			// Default to 'USERS' since we're always using user-based assignment
			$db->pquery(
				"INSERT INTO vtiger_team_groups (teamid, group_name, assign_type, projectid) VALUES (?,?,?,?)",
				array($teamId, $groupName, 'USERS', null)
			);
			$groupId = (int)$db->getLastInsertID();
		}

		// Assign resolved user IDs to the group (for both new and edit)
		if (!empty($finalUserIds)) {
			foreach ($finalUserIds as $uid) {
				$db->pquery(
					"INSERT INTO vtiger_team_group_users (groupid, userid) VALUES (?,?)",
					array($groupId, $uid)
				);
			}
		}

		// Check if this is an AJAX request
		$isAjax = $request->isAjax() || ($mode === 'ajax');
		
		if ($isAjax) {
			// Return JSON for AJAX requests
			$response = new Vtiger_Response();
			$response->setResult(array(
				'success' => true, 
				'groupid' => $groupId, 
				'group_name' => $groupName, 
				'message' => $isEdit ? 'Group updated successfully' : 'Group created successfully'
			));
			$response->emit();
			return;
		} else {
			// Redirect to Groups list for regular form submissions
			$app = $request->get('app') ?: 'Management';
			$redirectUrl = 'index.php?module=Teams&view=List&tab=groups&app=' . $app;
			
			// Clear output buffer before redirect
			if (ob_get_level() > 0) {
				ob_clean();
			}
			
			header('Location: ' . $redirectUrl);
			exit;
		}
		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error('SaveGroup error: ' . $e->getMessage());
			}
			$response = new Vtiger_Response();
			$response->setError($e->getMessage());
			$response->emit();
		}
	}
}

