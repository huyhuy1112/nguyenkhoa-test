<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'data/VTEntityDelta.php';

class ProjectTaskHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'ProjectTask') {
				return;
			}

			$recordId = $entityData->getId();
			if (empty($recordId)) {
				return;
			}

			// Get owner from vtiger_crmentity (after commit, data is committed)
			$ownerResult = $adb->pquery("SELECT smownerid FROM vtiger_crmentity WHERE crmid = ?", array($recordId));
			if ($adb->num_rows($ownerResult) == 0) {
				return;
			}
			
			$newOwnerId = $adb->query_result($ownerResult, 0, 'smownerid');
			if (empty($newOwnerId)) {
				return;
			}

			// Verify owner is USER (not GROUP)
			$userCheck = $adb->pquery("SELECT id FROM vtiger_users WHERE id = ?", array($newOwnerId));
			if ($adb->num_rows($userCheck) == 0) {
				// Owner is GROUP, not USER - exit
				return;
			}

			// Get ProjectTask name
			$taskName = $entityData->get('projecttaskname');
			if (empty($taskName)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT projecttaskname FROM vtiger_projecttask WHERE projecttaskid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$taskName = $adb->query_result($nameResult, 0, 'projecttaskname');
				}
			}
			if (empty($taskName)) {
				$taskName = 'Project Task #' . $recordId;
			}

			// Check if owner changed using VTEntityDelta
			$delta = new VTEntityDelta();
			$changes = $delta->getEntityDelta('ProjectTask', $recordId);

			// If no change OR 'assigned_user_id' not in $changes, check if it's a new record
			$isNew = $entityData->isNew();
			$shouldNotify = false;

			// DEBUG: Log for troubleshooting
			if ($log) {
				$log->debug("[ProjectTaskHandler] Record ID: $recordId, isNew: " . ($isNew ? 'true' : 'false') . ", newOwnerId: $newOwnerId, changes: " . (empty($changes) ? 'empty' : 'has changes'));
			}

			if ($isNew) {
				// New record - always notify
				$shouldNotify = true;
				if ($log) {
					$log->debug("[ProjectTaskHandler] New record detected - will notify user $newOwnerId");
				}
			} else if (!empty($changes) && isset($changes['assigned_user_id'])) {
				// Existing record - check if assigned user changed
				$oldOwnerId = isset($changes['assigned_user_id']['oldValue']) ? $changes['assigned_user_id']['oldValue'] : null;
				$newOwnerIdFromDelta = isset($changes['assigned_user_id']['currentValue']) ? $changes['assigned_user_id']['currentValue'] : null;

				// Parse webservice ID format if needed (e.g., "19x123")
				if (!empty($newOwnerIdFromDelta) && strpos($newOwnerIdFromDelta, 'x') !== false) {
					$newOwnerIdParts = explode('x', $newOwnerIdFromDelta);
					$newOwnerIdFromDelta = isset($newOwnerIdParts[1]) ? $newOwnerIdParts[1] : $newOwnerIdParts[0];
				}

				if ($oldOwnerId != $newOwnerIdFromDelta && !empty($newOwnerIdFromDelta)) {
					$shouldNotify = true;
					$newOwnerId = $newOwnerIdFromDelta;
				}
			}

			// Send assign notification if owner changed or new record
			if ($shouldNotify) {
				$message = "Bạn được assign vào Project Task: " . $taskName;
				$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'ProjectTask', ?, ?, NOW())";
				$insertResult = $adb->pquery($insertSql, array($newOwnerId, $recordId, $message));
				
				// DEBUG: Log insert result
				if ($log) {
					if ($insertResult) {
						$insertId = $adb->getLastInsertID();
						$log->debug("[ProjectTaskHandler] Notification inserted successfully. ID: $insertId, User: $newOwnerId, Record: $recordId");
					} else {
						$log->error("[ProjectTaskHandler] Failed to insert notification. User: $newOwnerId, Record: $recordId");
					}
				}
			} else {
				// DEBUG: Log why notification was not sent
				if ($log) {
					$log->debug("[ProjectTaskHandler] Notification NOT sent. isNew: " . ($isNew ? 'true' : 'false') . ", shouldNotify: false, changes: " . (empty($changes) ? 'empty' : json_encode($changes)));
				}
			}

			// ALWAYS check deadline reminder (regardless of assign notification)
			// Query directly from database as entityData may not have the updated values
			$dateResult = $adb->pquery("SELECT enddate FROM vtiger_projecttask WHERE projecttaskid = ?", array($recordId));
			$endDate = null;
			if ($adb->num_rows($dateResult) > 0) {
				$endDate = $adb->query_result($dateResult, 0, 'enddate');
			}
			
			if (!empty($endDate)) {
				$today = date('Y-m-d');
				$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
				
				// Check if end date is within 7 days
				if ($endDate >= $today && $endDate <= $sevenDaysLater) {
					// Check if reminder already sent in the last 7 days
					$reminderCheck = $adb->pquery(
						"SELECT id FROM vtiger_notifications 
						 WHERE userid = ? AND module = 'ProjectTask' AND recordid = ? 
						 AND message LIKE '%sắp đến hạn%' 
						 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
						array($newOwnerId, $recordId)
					);
					
					if ($adb->num_rows($reminderCheck) == 0) {
						// Calculate days until deadline
						$daysUntilDeadline = (strtotime($endDate) - strtotime($today)) / 86400;
						$daysUntilDeadline = ceil($daysUntilDeadline);
						
						// Send reminder notification
						$reminderMessage = "Project Task \"$taskName\" sắp đến hạn trong $daysUntilDeadline ngày (Deadline: $endDate)";
						$reminderSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'ProjectTask', ?, ?, NOW())";
						$adb->pquery($reminderSql, array($newOwnerId, $recordId, $reminderMessage));
						
						if ($log) {
							$log->debug("[ProjectTaskHandler] Deadline reminder sent for ProjectTask $recordId to user $newOwnerId");
						}
					}
				}
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[ProjectTaskHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

