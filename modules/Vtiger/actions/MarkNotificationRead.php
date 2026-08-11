<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 2.0
 * ("License.txt"); You may not use this file except in compliance with the License
 * The Original Code is: Vtiger CRM Open Source
 * The Initial Developer of the Original Code is Vtiger.
 * Portions created by Vtiger are Copyright (C) Vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

require_once 'modules/Vtiger/models/NotificationService.php';

class Vtiger_MarkNotificationRead_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $current_user;

		header('Content-Type: application/json; charset=UTF-8');

		try {
			$userid = $current_user->id;
			if (empty($userid)) {
				throw new Exception('User not logged in');
			}

			$mode = $request->get('mode');
			$notificationId = $request->get('notification_id');

			if ($mode == 'markAll') {
				Vtiger_NotificationService::markAllRead($userid);
			} else if (!empty($notificationId)) {
				Vtiger_NotificationService::markRead($userid, $notificationId);
			} else {
				throw new Exception('Invalid parameters');
			}

			$response = array(
				'success' => true,
				'unreadCount' => Vtiger_NotificationService::countUnread($userid),
			);
			echo json_encode($response, JSON_UNESCAPED_UNICODE);

		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error("[MarkNotificationRead] Error: " . $e->getMessage());
			}
			echo json_encode(array(
				'success' => false,
				'error' => $e->getMessage(),
				'unreadCount' => 0,
			), JSON_UNESCAPED_UNICODE);
		}
	}
}
