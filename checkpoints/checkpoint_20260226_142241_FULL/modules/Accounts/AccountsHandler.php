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

class AccountsHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'Accounts') {
				return;
			}

			if ($log) {
				$log->debug("[AccountsHandler] Event triggered for Accounts module");
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

			// Check if owner changed using VTEntityDelta
			$delta = new VTEntityDelta();
			$changes = $delta->getEntityDelta('Accounts', $recordId);

			// If no change OR 'assigned_user_id' not in $changes, check if it's a new record
			$isNew = $entityData->isNew();
			$shouldNotify = false;

			if ($isNew) {
				// New record - always notify
				$shouldNotify = true;
				if ($log) {
					$log->debug("[AccountsHandler] New record detected, will notify user $newOwnerId");
				}
			} else if (!empty($changes) && isset($changes['assigned_user_id'])) {
				// Existing record - check if assigned user changed
				$oldOwnerId = isset($changes['assigned_user_id']['oldValue']) ? $changes['assigned_user_id']['oldValue'] : null;
				$newOwnerIdFromDelta = isset($changes['assigned_user_id']['currentValue']) ? $changes['assigned_user_id']['currentValue'] : null;

				if ($log) {
					$log->debug("[AccountsHandler] Owner change detected: old=$oldOwnerId, new=$newOwnerIdFromDelta");
				}

				// Parse webservice ID format if needed (e.g., "19x123")
				if (!empty($newOwnerIdFromDelta) && strpos($newOwnerIdFromDelta, 'x') !== false) {
					$newOwnerIdParts = explode('x', $newOwnerIdFromDelta);
					$newOwnerIdFromDelta = isset($newOwnerIdParts[1]) ? $newOwnerIdParts[1] : $newOwnerIdParts[0];
				}

				if ($oldOwnerId != $newOwnerIdFromDelta && !empty($newOwnerIdFromDelta)) {
					$shouldNotify = true;
					$newOwnerId = $newOwnerIdFromDelta;
					if ($log) {
						$log->debug("[AccountsHandler] Owner changed, will notify user $newOwnerId");
					}
				} else {
					if ($log) {
						$log->debug("[AccountsHandler] Owner unchanged, skipping notification");
					}
				}
			} else {
				if ($log) {
					$log->debug("[AccountsHandler] No owner change detected, skipping notification");
				}
			}

			if (!$shouldNotify) {
				return;
			}

			// Verify new owner is USER (not GROUP)
			$userCheck = $adb->pquery("SELECT id FROM vtiger_users WHERE id = ?", array($newOwnerId));
			if ($adb->num_rows($userCheck) == 0) {
				// Owner is GROUP, not USER - exit
				return;
			}

			// Get Account name
			$accountName = $entityData->get('accountname');
			if (empty($accountName)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT accountname FROM vtiger_account WHERE accountid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$accountName = $adb->query_result($nameResult, 0, 'accountname');
				}
			}
			if (empty($accountName)) {
				$accountName = 'Organization #' . $recordId;
			}

			// Insert notification (after commit, so it won't be rolled back)
			$message = "Bạn được assign vào Organization: " . $accountName;
			$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Accounts', ?, ?, NOW())";
			$adb->pquery($insertSql, array($newOwnerId, $recordId, $message));

			if ($log) {
				$log->debug("[AccountsHandler] Notification created for user $newOwnerId, record $recordId: $message");
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[AccountsHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

