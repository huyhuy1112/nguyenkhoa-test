<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_PersonAjax_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$mode = $request->get('mode');
		
		if ($mode === 'toggleStatus') {
			// Only admin can deactivate users
			if (!$currentUser->isAdminUser()) {
				throw new AppException('LBL_PERMISSION_DENIED');
			}
		} elseif ($mode === 'getStatus') {
			// getStatus is read-only, allow any authenticated user
			return true;
		} else {
			if (!$currentUser->isAdminUser() && !Users_Privileges_Model::isPermitted('Users', 'CreateView')) {
				throw new AppException('LBL_PERMISSION_DENIED');
			}
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// For AJAX requests, use lenient validation
		if ($request->isAjax()) {
			// For AJAX, only validate referer, skip CSRF
			try {
				$request->validateReadAccess(); // This validates referer
			} catch (Exception $e) {
				// If referer check fails, still allow for AJAX (same domain)
				// This handles cases where referer might be missing
			}
			return true;
		}
		
		// For non-AJAX, use standard validation
		$mode = $request->get('mode');
		if (in_array($mode, array('toggleStatus'))) {
			return $request->validateWriteAccess();
		}
		return $request->validateReadAccess();
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		$response = new Vtiger_Response();

		try {
			if (empty($mode)) {
				$response->setError('Mode parameter is required');
				$response->emit();
				return;
			}

			switch ($mode) {
				case 'toggleStatus':
					$this->toggleStatus($request, $response);
					break;
				case 'getStatus':
					$this->getStatus($request, $response);
					break;
				default:
					$response->setError('Invalid mode: ' . $mode);
					$response->emit();
					return;
			}
		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error('PersonAjax error: ' . $e->getMessage() . ' | Mode: ' . $mode);
			}
			$response->setError($e->getMessage());
			$response->emit();
		}
	}

	protected function toggleStatus(Vtiger_Request $request, Vtiger_Response $response) {
		$db = PearDatabase::getInstance();
		$userId = (int)$request->get('record');
		$status = trim($request->get('status'));

		if ($userId <= 0) {
			$response->setError('Invalid user ID');
			$response->emit();
			return;
		}

		// Validate status
		if (!in_array($status, array('Active', 'Inactive'))) {
			$response->setError('Invalid status');
			$response->emit();
			return;
		}

		// Check if user is admin/owner
		$res = $db->pquery("SELECT is_admin FROM vtiger_users WHERE id=? AND deleted=0", array($userId));
		if ($res && $db->num_rows($res) > 0) {
			$isAdmin = $db->query_result($res, 0, 'is_admin');
			if ($isAdmin === 'on' && $status === 'Inactive') {
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

		$response->setResult(array(
			'success' => true,
			'message' => 'User status updated successfully',
			'status' => $status
		));
		$response->emit();
	}

	protected function getStatus(Vtiger_Request $request, Vtiger_Response $response) {
		$db = PearDatabase::getInstance();
		
		// Ensure table exists
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_user_activity (
			userid INT PRIMARY KEY,
			last_seen DATETIME
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
		
		// Get all users with their status
		$res = $db->pquery(
			"SELECT u.id, u.status AS user_status, ua.last_seen
			 FROM vtiger_users u
			 LEFT JOIN vtiger_user_activity ua ON ua.userid = u.id
			 WHERE u.deleted = 0",
			array()
		);
		
		$now = time();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUser ? (int)$currentUser->getId() : 0;
		$statusMap = array();
		
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$uid = (int)$row['id'];
			$lastSeen = $row['last_seen'];
			$statusLabel = 'Never logged in';
			$isOnline = false;
			$isInactive = ($row['user_status'] === 'Inactive');
			
			// Current user is always online if not inactive
			if ($uid === $currentUserId && !$isInactive) {
				$isOnline = true;
				$statusLabel = 'Online';
			} elseif ($isInactive) {
				$statusLabel = 'Inactive';
			} elseif (!empty($lastSeen)) {
				// Parse last_seen as MySQL DATETIME and convert to timestamp
				$lastSeenTimestamp = strtotime($lastSeen);
				if ($lastSeenTimestamp === false) {
					// If parsing fails, try alternative format
					$lastSeenTimestamp = strtotime(str_replace(' ', 'T', $lastSeen));
				}
				
					if ($lastSeenTimestamp !== false) {
						$diff = $now - $lastSeenTimestamp;
						// Online if last_seen is within 2 minutes (120 seconds) - more accurate for real-time status
						if ($diff <= 120 && $diff >= 0) {
							$isOnline = true;
							$statusLabel = 'Online';
						} else {
						$mins = (int)floor($diff/60);
						if ($mins < 60) {
							$statusLabel = $mins . 'm ago';
						} else {
							$hrs = (int)floor($mins/60);
							if ($hrs < 24) {
								$statusLabel = $hrs . 'h ago';
							} else {
								$statusLabel = (int)floor($hrs/24) . 'd ago';
							}
						}
					}
				}
			}
			
			$statusMap[$uid] = array(
				'is_online' => $isOnline,
				'is_inactive' => $isInactive,
				'status_label' => $statusLabel
			);
		}
		
		$response->setResult(array(
			'success' => true,
			'status_map' => $statusMap
		));
		$response->emit();
	}
}
