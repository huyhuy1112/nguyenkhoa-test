<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_Heartbeat_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Any authenticated user can heartbeat.
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();

		// Auto-create schema if missing
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_user_activity (
			userid INT PRIMARY KEY,
			last_seen DATETIME
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		// Get current user from session (this is the ONLY source of truth)
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$uid = (int)$currentUser->getId();
		
		// Validate: Ensure we have a valid user ID
		if ($uid <= 0) {
			global $log;
			if ($log) {
				$log->error("[Users_Heartbeat] Invalid user ID: $uid");
			}
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('success' => false, 'error' => 'Invalid user ID'));
			return;
		}
		
		// Optional: Check if user ID from request matches session (if provided)
		$requestUserId = $request->get('userid');
		if (!empty($requestUserId)) {
			$requestUserId = (int)$requestUserId;
			if ($requestUserId !== $uid) {
				global $log;
				if ($log) {
					$log->warn("[Users_Heartbeat] User ID mismatch: session=$uid, request=$requestUserId");
				}
				// Use session user ID (more secure)
			}
		}

		// Update last_seen ONLY for the current user from session
		$db->pquery(
			"INSERT INTO vtiger_user_activity (userid, last_seen) VALUES (?, NOW())
			 ON DUPLICATE KEY UPDATE last_seen = NOW()",
			array($uid)
		);
		
		global $log;
		if ($log) {
			$log->debug("[Users_Heartbeat] Updated last_seen for user ID: $uid");
		}

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('success' => true, 'userid' => $uid));
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateReadAccess();
	}
}

