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

class ProjectHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// Enhanced logging for debugging
			if ($log) {
				$log->debug("[ProjectHandler] Event received: $eventName");
			}
			
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				if ($log) {
					$log->debug("[ProjectHandler] Ignoring event: $eventName (expected vtiger.entity.aftersave.final)");
				}
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'Project') {
				if ($log) {
					$log->debug("[ProjectHandler] Ignoring module: $moduleName (expected Project)");
				}
				return;
			}
			
			if ($log) {
				$log->debug("[ProjectHandler] Processing Project module event");
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

			// Get Project name
			$projectName = $entityData->get('projectname');
			if (empty($projectName)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT projectname FROM vtiger_project WHERE projectid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$projectName = $adb->query_result($nameResult, 0, 'projectname');
				}
			}
			if (empty($projectName)) {
				$projectName = 'Project #' . $recordId;
			}

			// Check if owner changed using VTEntityDelta
			$delta = new VTEntityDelta();
			$changes = $delta->getEntityDelta('Project', $recordId);

			// If no change OR 'assigned_user_id' not in $changes, check if it's a new record
			$isNew = $entityData->isNew();
			$shouldNotify = false;

			// DEBUG: Log for troubleshooting
			if ($log) {
				$log->debug("[ProjectHandler] Record ID: $recordId, isNew: " . ($isNew ? 'true' : 'false') . ", newOwnerId: $newOwnerId, changes: " . (empty($changes) ? 'empty' : 'has changes'));
			}

			if ($isNew) {
				// New record - always notify
				$shouldNotify = true;
				if ($log) {
					$log->debug("[ProjectHandler] New record detected - will notify user $newOwnerId");
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
				$message = "Bạn được assign vào Project: " . $projectName;
				$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Project', ?, ?, NOW())";
				$insertResult = $adb->pquery($insertSql, array($newOwnerId, $recordId, $message));
				
				// DEBUG: Log insert result
				if ($log) {
					if ($insertResult) {
						$insertId = $adb->getLastInsertID();
						$log->debug("[ProjectHandler] Notification inserted successfully. ID: $insertId, User: $newOwnerId, Record: $recordId");
					} else {
						$log->error("[ProjectHandler] Failed to insert notification. User: $newOwnerId, Record: $recordId");
					}
				}
			} else {
				// DEBUG: Log why notification was not sent
				if ($log) {
					$log->debug("[ProjectHandler] Notification NOT sent. isNew: " . ($isNew ? 'true' : 'false') . ", shouldNotify: false, changes: " . (empty($changes) ? 'empty' : json_encode($changes)));
				}
			}

			// ALWAYS check deadline reminder (regardless of assign notification)
			// Query directly from database as entityData may not have the updated values
			$dateResult = $adb->pquery("SELECT targetenddate, actualenddate FROM vtiger_project WHERE projectid = ?", array($recordId));
			$targetEndDate = null;
			$actualEndDate = null;
			if ($adb->num_rows($dateResult) > 0) {
				$targetEndDate = $adb->query_result($dateResult, 0, 'targetenddate');
				$actualEndDate = $adb->query_result($dateResult, 0, 'actualenddate');
			}
			
			// Use targetenddate if available, otherwise actualenddate
			$endDate = !empty($targetEndDate) ? $targetEndDate : $actualEndDate;
			
			if (!empty($endDate)) {
				$today = date('Y-m-d');
				$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
				
				// Check if end date is within 7 days
				if ($endDate >= $today && $endDate <= $sevenDaysLater) {
					// Check if reminder already sent in the last 7 days
					$reminderCheck = $adb->pquery(
						"SELECT id FROM vtiger_notifications 
						 WHERE userid = ? AND module = 'Project' AND recordid = ? 
						 AND message LIKE '%sắp đến hạn%' 
						 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
						array($newOwnerId, $recordId)
					);
					
					if ($adb->num_rows($reminderCheck) == 0) {
						// Calculate days until deadline
						$daysUntilDeadline = (strtotime($endDate) - strtotime($today)) / 86400;
						$daysUntilDeadline = ceil($daysUntilDeadline);
						
						// Send reminder notification
						$reminderMessage = "Project \"$projectName\" sắp đến hạn trong $daysUntilDeadline ngày (Deadline: $endDate)";
						$reminderSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Project', ?, ?, NOW())";
						$adb->pquery($reminderSql, array($newOwnerId, $recordId, $reminderMessage));
						
						if ($log) {
							$log->debug("[ProjectHandler] Deadline reminder sent for Project $recordId to user $newOwnerId");
						}
					}
				}
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[ProjectHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

