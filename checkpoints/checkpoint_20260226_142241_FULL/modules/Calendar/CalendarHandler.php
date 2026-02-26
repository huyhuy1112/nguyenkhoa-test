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

class CalendarHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			// Handle both Calendar and Events modules
			if ($moduleName !== 'Calendar' && $moduleName !== 'Events') {
				return;
			}

			$recordId = $entityData->getId();
			if (empty($recordId)) {
				return;
			}

			// Get activity type (Task, Call, Meeting, etc.)
			$activityType = $entityData->get('activitytype');
			if (empty($activityType)) {
				// Fallback: check from database
				$typeResult = $adb->pquery("SELECT activitytype FROM vtiger_activity WHERE activityid = ?", array($recordId));
				if ($adb->num_rows($typeResult) > 0) {
					$activityType = $adb->query_result($typeResult, 0, 'activitytype');
				}
			}
			
			// Only handle Tasks and Events (Call, Meeting), skip other types
			// Task = 'Task', Events = 'Call', 'Meeting', or other non-Task types
			if (empty($activityType)) {
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

			// Get activity subject
			$activitySubject = $entityData->get('subject');
			if (empty($activitySubject)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT subject FROM vtiger_activity WHERE activityid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$activitySubject = $adb->query_result($nameResult, 0, 'subject');
				}
			}
			if (empty($activitySubject)) {
				$activitySubject = 'Activity #' . $recordId;
			}

			// Check if owner changed using VTEntityDelta
			$delta = new VTEntityDelta();
			$changes = $delta->getEntityDelta($moduleName, $recordId);

			// If no change OR 'assigned_user_id' not in $changes, check if it's a new record
			$isNew = $entityData->isNew();
			$shouldNotify = false;

			if ($isNew) {
				// New record - always notify
				$shouldNotify = true;
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
				// Determine message based on activity type
				if ($activityType === 'Task') {
					$message = "Bạn được assign vào Task: " . $activitySubject;
				} else {
					// Event types: Call, Meeting, etc.
					$message = "Bạn được assign vào Event: " . $activitySubject;
				}
				$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Calendar', ?, ?, NOW())";
				$adb->pquery($insertSql, array($newOwnerId, $recordId, $message));
			}

			// ALWAYS check deadline reminder (regardless of assign notification)
			// Query directly from database as entityData may not have the updated values
			$dateResult = $adb->pquery("SELECT due_date FROM vtiger_activity WHERE activityid = ?", array($recordId));
			$dueDate = null;
			if ($adb->num_rows($dateResult) > 0) {
				$dueDate = $adb->query_result($dateResult, 0, 'due_date');
			}
			
			if (!empty($dueDate)) {
				// Extract date part if datetime format
				if (strpos($dueDate, ' ') !== false) {
					$dueDateParts = explode(' ', $dueDate);
					$dueDate = $dueDateParts[0];
				}
				
				$today = date('Y-m-d');
				$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
				
				// Check if due date is within 7 days
				if ($dueDate >= $today && $dueDate <= $sevenDaysLater) {
					// Check if reminder already sent in the last 7 days
					$reminderCheck = $adb->pquery(
						"SELECT id FROM vtiger_notifications 
						 WHERE userid = ? AND module = 'Calendar' AND recordid = ? 
						 AND message LIKE '%sắp đến hạn%' 
						 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
						array($newOwnerId, $recordId)
					);
					
					if ($adb->num_rows($reminderCheck) == 0) {
						// Calculate days until deadline
						$daysUntilDeadline = (strtotime($dueDate) - strtotime($today)) / 86400;
						$daysUntilDeadline = ceil($daysUntilDeadline);
						
						// Determine activity type label
						$activityLabel = ($activityType === 'Task') ? 'Task' : 'Event';
						
						// Send reminder notification
						$reminderMessage = "$activityLabel \"$activitySubject\" sắp đến hạn trong $daysUntilDeadline ngày (Deadline: $dueDate)";
						$reminderSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Calendar', ?, ?, NOW())";
						$adb->pquery($reminderSql, array($newOwnerId, $recordId, $reminderMessage));
						
						if ($log) {
							$log->debug("[CalendarHandler] Deadline reminder sent for Calendar $recordId to user $newOwnerId");
						}
					}
				}
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[CalendarHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

