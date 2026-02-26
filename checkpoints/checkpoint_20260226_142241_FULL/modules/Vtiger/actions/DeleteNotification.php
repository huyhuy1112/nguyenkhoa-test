<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 2.0
 * ("License.txt"); You may not use this file except in compliance with the License
 * The Original Code is: Vtiger CRM Open Source
 * The Initial Developer of the Original Code is Vtiger.
 * Portions created by Vtiger are Copyright (C) Vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Vtiger_DeleteNotification_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $adb, $current_user;

		header('Content-Type: application/json; charset=UTF-8');

		try {
			$userid = $current_user->id;
			if (empty($userid)) {
				throw new Exception('User not logged in');
			}

			$mode = $request->get('mode');
			$notificationIds = $request->get('notification_ids');

			if ($mode == 'deleteAll') {
				// Delete all notifications for current user
				$deleteSql = "DELETE FROM vtiger_notifications WHERE userid = ?";
				$adb->pquery($deleteSql, array($userid));
			} else if ($mode == 'deleteSelected' && !empty($notificationIds)) {
				// Delete selected notifications
				if (is_array($notificationIds)) {
					$placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
					$deleteSql = "DELETE FROM vtiger_notifications WHERE id IN ($placeholders) AND userid = ?";
					$params = $notificationIds;
					$params[] = $userid;
					$adb->pquery($deleteSql, $params);
				} else if (is_numeric($notificationIds)) {
					// Single notification ID
					$deleteSql = "DELETE FROM vtiger_notifications WHERE id = ? AND userid = ?";
					$adb->pquery($deleteSql, array($notificationIds, $userid));
				}
			} else {
				throw new Exception('Invalid parameters');
			}

			// Get remaining unread count
			$countSql = "SELECT COUNT(*) as unread_count FROM vtiger_notifications WHERE userid = ? AND is_read = 0";
			$countResult = $adb->pquery($countSql, array($userid));
			$unreadCount = 0;
			if ($adb->num_rows($countResult) > 0) {
				$unreadCount = (int)$adb->query_result($countResult, 0, 'unread_count');
			}

			$response = array(
				'success' => true,
				'unreadCount' => $unreadCount
			);
			echo json_encode($response, JSON_UNESCAPED_UNICODE);

		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error("[DeleteNotification] Error: " . $e->getMessage());
			}
			$errorResponse = array(
				'success' => false,
				'error' => $e->getMessage(),
				'unreadCount' => 0
			);
			echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
		}
	}
}

