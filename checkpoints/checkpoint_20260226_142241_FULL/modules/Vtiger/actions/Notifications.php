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

			$response = array(
				'success' => true,
				'type' => $type,
				'count' => count($list),
				'list' => $list
			);

			// Detect if request is from browser (not AJAX/API)
			$isBrowserRequest = !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
								strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest';
			
			if ($isBrowserRequest) {
				// Browser request - show HTML with back button
				header('Content-Type: text/html; charset=UTF-8');
				echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Thông báo</title>';
				echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}';
				echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:5px;}';
				echo '.back-btn{display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;margin-bottom:20px;}';
				echo '.back-btn:hover{background:#45a049;}';
				echo 'pre{background:#f4f4f4;padding:15px;border-radius:3px;overflow:auto;}</style>';
				echo '</head><body><div class="container">';
				echo '<a href="index.php" class="back-btn">← Quay về trang chính</a>';
				echo '<h2>Thông báo (' . htmlspecialchars($type) . ')</h2>';
				echo '<pre>' . htmlspecialchars(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre>';
				echo '</div></body></html>';
			} else {
				// AJAX/API request - return JSON only
				header('Content-Type: application/json; charset=UTF-8');
				echo json_encode($response, JSON_UNESCAPED_UNICODE);
			}
			
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

