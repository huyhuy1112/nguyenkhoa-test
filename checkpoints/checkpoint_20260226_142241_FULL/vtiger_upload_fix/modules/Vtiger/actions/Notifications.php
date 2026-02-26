<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 2.0
 * ("License.txt"); You may not use this file except in compliance with the License
 * The Original Code is: Vtiger CRM Open Source
 * The Initial Developer of the Original Code is Vtiger.
 * Portions created by Vtiger are Copyright (C) Vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Vtiger_Notifications_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $adb, $current_user;

		try {
			$userid = $current_user->id;
			$type = $request->get('type');
			
			if (empty($type) || $type !== 'read') {
				$type = 'unread';
			}

			$isRead = ($type === 'read') ? 1 : 0;

			$sql = "SELECT id, module, recordid, message, created_at
					FROM vtiger_notifications
					WHERE userid = ? AND is_read = ?
					ORDER BY created_at DESC
					LIMIT 20";

			$result = $adb->pquery($sql, array($userid, $isRead));

			$list = array();
			while ($row = $adb->fetchByAssoc($result)) {
				$list[] = $row;
			}

			header('Content-Type: application/json; charset=UTF-8');
			$response = array(
				'success' => true,
				'type' => $type,
				'count' => count($list),
				'list' => $list
			);
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			
		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error("[ModernNotifications] Error: " . $e->getMessage());
			}
			header('Content-Type: application/json; charset=UTF-8');
			$errorResponse = array(
				'success' => false,
				'error' => $e->getMessage(),
				'type' => 'unread',
				'count' => 0,
				'list' => array()
			);
			echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
		}
	}
}

