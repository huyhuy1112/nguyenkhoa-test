<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'data/VTEntityDelta.php';

class ContactsHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave.final (after commit)
			if ($eventName !== 'vtiger.entity.aftersave.final') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'Contacts') {
				return;
			}

			if ($log) {
				$log->debug("[ContactsHandler] Event triggered for Contacts module");
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
			$changes = $delta->getEntityDelta('Contacts', $recordId);

			// If no change OR 'assigned_user_id' not in $changes, check if it's a new record
			$isNew = $entityData->isNew();
			$shouldNotify = false;

			if ($isNew) {
				// New record - always notify
				$shouldNotify = true;
				if ($log) {
					$log->debug("[ContactsHandler] New record detected, will notify user $newOwnerId");
				}
			} else if (!empty($changes) && isset($changes['assigned_user_id'])) {
				// Existing record - check if assigned user changed
				$oldOwnerId = isset($changes['assigned_user_id']['oldValue']) ? $changes['assigned_user_id']['oldValue'] : null;
				$newOwnerIdFromDelta = isset($changes['assigned_user_id']['currentValue']) ? $changes['assigned_user_id']['currentValue'] : null;

				if ($log) {
					$log->debug("[ContactsHandler] Owner change detected: old=$oldOwnerId, new=$newOwnerIdFromDelta");
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
						$log->debug("[ContactsHandler] Owner changed, will notify user $newOwnerId");
					}
				} else {
					if ($log) {
						$log->debug("[ContactsHandler] Owner unchanged, skipping notification");
					}
				}
			} else {
				if ($log) {
					$log->debug("[ContactsHandler] No owner change detected, skipping notification");
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

			// Get Contact name (firstname + lastname)
			$firstName = $entityData->get('firstname');
			$lastName = $entityData->get('lastname');
			
			if (empty($firstName) && empty($lastName)) {
				// Fallback: get from database
				$nameResult = $adb->pquery("SELECT firstname, lastname FROM vtiger_contactdetails WHERE contactid = ?", array($recordId));
				if ($adb->num_rows($nameResult) > 0) {
					$firstName = $adb->query_result($nameResult, 0, 'firstname');
					$lastName = $adb->query_result($nameResult, 0, 'lastname');
				}
			}
			
			// Build contact name (at least one of firstname or lastname should exist)
			$contactName = trim(($firstName ? $firstName . ' ' : '') . ($lastName ? $lastName : ''));
			if (empty($contactName)) {
				$contactName = 'Contact #' . $recordId;
			}

			// Insert notification (after commit, so it won't be rolled back)
			$message = "Bạn được assign vào Contact: " . $contactName;
			$insertSql = "INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at) VALUES (?, 'Contacts', ?, ?, NOW())";
			$adb->pquery($insertSql, array($newOwnerId, $recordId, $message));

			if ($log) {
				$log->debug("[ContactsHandler] Notification created for user $newOwnerId, record $recordId: $message");
			}

		} catch (Exception $e) {
			if ($log) {
				$log->error("[ContactsHandler] Error creating notification: " . $e->getMessage());
			}
		}
	}
}

function Contacts_sendCustomerPortalLoginDetails($entityData){
	$adb = PearDatabase::getInstance();
	$moduleName = $entityData->getModuleName();
	$wsId = $entityData->getId();
	$parts = explode('x', $wsId);
	$entityId = $parts[1];
	$entityDelta = new VTEntityDelta();
	$email = $entityData->get('email');

	$isEmailChanged = $entityDelta->hasChanged($moduleName, $entityId, 'email') && $email;//changed and not empty
	$isPortalEnabled = $entityData->get('portal') == 'on' || $entityData->get('portal') == '1';

	if ($isPortalEnabled) {
		//If portal enabled / disabled, then trigger following actions
		$sql = "SELECT id, user_name, user_password, isactive FROM vtiger_portalinfo WHERE id=?";
		$result = $adb->pquery($sql, array($entityId));

		$insert = true; $update = false;
		if ($adb->num_rows($result)) {
			$insert = false;
			$dbusername = $adb->query_result($result,0,'user_name');
			$isactive = $adb->query_result($result,0,'isactive');
			if($email == $dbusername && $isactive == 1 && !$entityData->isNew()){
				$update = false;
			} else if($isPortalEnabled) {
				$sql = "UPDATE vtiger_portalinfo SET user_name=?, isactive=? WHERE id=?";
				$adb->pquery($sql, array($email, 1, $entityId));
				$update = true;
			} else {
				$sql = "UPDATE vtiger_portalinfo SET user_name=?, isactive=? WHERE id=?";
				$adb->pquery($sql, array($email, 0, $entityId));
				$update = false;
			}
		}

		//generate new password
		$password = makeRandomPassword();
		$enc_password = Vtiger_Functions::generateEncryptedPassword($password);

		//create new portal user
		$sendEmail = false;
		if ($insert) {
			$sql = "INSERT INTO vtiger_portalinfo(id,user_name,user_password,cryptmode,type,isactive) VALUES(?,?,?,?,?,?)";
			$params = array($entityId, $email, $enc_password, 'CRYPT', 'C', 1);
			$adb->pquery($sql, $params);
			$sendEmail = true;
		}

		//update existing portal user password
		if ($update && $isEmailChanged) {
			$sql = "UPDATE vtiger_portalinfo SET user_password=?, cryptmode=? WHERE id=?";
			$params = array($enc_password, 'CRYPT', $entityId);
			$adb->pquery($sql, $params);
			$sendEmail = true;
		}

		//trigger send email
		if ($sendEmail && $entityData->get('emailoptout') == 0) {
			global $current_user,$HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;
			require_once("modules/Emails/mail.php");
			$emailData = Contacts::getPortalEmailContents($entityData,$password,'LoginDetails');
			$subject = $emailData['subject'];
			if(empty($subject)) {
				$subject = 'Customer Portal Login Details';
			}

			$contents = $emailData['body'];
			$contents= decode_html(getMergedDescription($contents, $entityId, 'Contacts'));
			if(empty($contents)) {
				require_once 'config.inc.php';
				global $PORTAL_URL;
				$contents = 'LoginDetails';
				$contents .= "<br><br> User ID : $email";
				$contents .= "<br> Password: ".$password;
				$portalURL = vtranslate('Please ',$moduleName).'<a href="'.$PORTAL_URL.'" style="font-family:Arial, Helvetica, sans-serif;font-size:13px;">'. vtranslate('click here', $moduleName).'</a>';
				$contents .= "<br>".$portalURL;
			}
			$subject = decode_html(getMergedDescription($subject, $entityId,'Contacts'));
			send_mail('Contacts', $email, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $contents,'','','','','',true);
		}
	} else {
		$sql = "UPDATE vtiger_portalinfo SET user_name=?,isactive=0 WHERE id=?";
		$adb->pquery($sql, array($email, $entityId));
	}
}

?>
