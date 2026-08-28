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

class PotentialsHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'Potentials') {
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

			// Get Potential name
			$potentialName = $entityData->get('potentialname');
			if (empty($potentialName)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT potentialname FROM vtiger_potential WHERE potentialid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$potentialName = $adb->query_result($nameResult, 0, 'potentialname');
				}
			}
			if (empty($potentialName)) {
				$potentialName = 'Opportunity #' . $recordId;
			}

			// Check if owner changed using VTEntityDelta
			$delta = new VTEntityDelta();
			$changes = $delta->getEntityDelta('Potentials', $recordId);

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
				$message = "Bạn được assign vào Opportunity: " . $potentialName;
				$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Potentials', ?, ?, NOW())";
				$adb->pquery($insertSql, array($newOwnerId, $recordId, $message));
			}

			// ALWAYS check deadline reminder (regardless of assign notification)
			// Query directly from database as entityData may not have the updated values
			$dateResult = $adb->pquery("SELECT closingdate FROM vtiger_potential WHERE potentialid = ?", array($recordId));
			$closingDate = null;
			if ($adb->num_rows($dateResult) > 0) {
				$closingDate = $adb->query_result($dateResult, 0, 'closingdate');
			}
			
			if (!empty($closingDate)) {
				// Extract date part if datetime format
				if (strpos($closingDate, ' ') !== false) {
					$closingDateParts = explode(' ', $closingDate);
					$closingDate = $closingDateParts[0];
				}
				
				$today = date('Y-m-d');
				$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
				
				// Check if closing date is within 7 days
				if ($closingDate >= $today && $closingDate <= $sevenDaysLater) {
					// Check if reminder already sent in the last 7 days
					$reminderCheck = $adb->pquery(
						"SELECT id FROM vtiger_notifications 
						 WHERE userid = ? AND module = 'Potentials' AND recordid = ? 
						 AND message LIKE '%sắp đến hạn%' 
						 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
						array($newOwnerId, $recordId)
					);
					
					if ($adb->num_rows($reminderCheck) == 0) {
						// Calculate days until deadline
						$daysUntilDeadline = (strtotime($closingDate) - strtotime($today)) / 86400;
						$daysUntilDeadline = ceil($daysUntilDeadline);
						
						// Send reminder notification
						$reminderMessage = "Opportunity \"$potentialName\" sắp đến hạn trong $daysUntilDeadline ngày (Deadline: $closingDate)";
						$reminderSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Potentials', ?, ?, NOW())";
						$adb->pquery($reminderSql, array($newOwnerId, $recordId, $reminderMessage));
						
						if ($log) {
							$log->debug("[PotentialsHandler] Deadline reminder sent for Potentials $recordId to user $newOwnerId");
						}
					}
				}
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[PotentialsHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

