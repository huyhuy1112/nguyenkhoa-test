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
			try {
				require_once 'modules/Vtiger/models/NotificationSchedule.php';
				Vtiger_NotificationSchedule::flushDue(50);
			} catch (Exception $flushEx) {
				// ignore
			}
			try {
				Vtiger_NotificationService::ensureInstalled();
			} catch (Exception $instEx) {
				// ignore
			}

			$userid = $current_user->id;
			$mode = $request->get('mode');

			if ($mode === 'setSoundPref') {
				$enabled = $request->get('sound_enabled');
				$enabled = !($enabled === '0' || $enabled === 0 || $enabled === false || $enabled === 'false');
				$volume = $request->get('volume');
				$vol = ($volume === '' || $volume === null) ? null : (float)$volume;
				$pref = Vtiger_NotificationService::setSoundPref($userid, $enabled, $vol);
				header('Content-Type: application/json; charset=UTF-8');
				echo json_encode(array(
					'success' => true,
					'sound_enabled' => $pref['enabled'],
					'volume' => $pref['volume'],
				), JSON_UNESCAPED_UNICODE);
				return;
			}

			$type = $request->get('type');
			if (empty($type)) {
				$type = 'unread';
			}
			if ($type !== 'read' && $type !== 'unread' && $type !== 'all') {
				$type = 'unread';
			}

			$filter = $request->get('filter');
			if ($filter !== 'cskh' && $filter !== 'new') {
				$filter = 'all';
			}

			$list = Vtiger_NotificationService::getMergedList($userid, $type, 40, $filter);
			$unreadCount = Vtiger_NotificationService::countUnread($userid);
			$pref = Vtiger_NotificationService::getSoundPref($userid);

			$response = array(
				'success' => true,
				'type' => $type,
				'filter' => $filter,
				'count' => count($list),
				'unreadCount' => $unreadCount,
				'list' => $list,
				'sound_enabled' => $pref['enabled'],
				'volume' => $pref['volume'],
			);

			$isBrowserRequest = !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest';

			if ($isBrowserRequest) {
				header('Content-Type: text/html; charset=UTF-8');
				echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Thông báo</title>';
				echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}';
				echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:5px;}';
				echo 'pre{background:#f4f4f4;padding:15px;border-radius:3px;overflow:auto;}</style>';
				echo '</head><body><div class="container">';
				echo '<h2>Thông báo</h2>';
				echo '<pre>' . htmlspecialchars(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre>';
				echo '</div></body></html>';
			} else {
				header('Content-Type: application/json; charset=UTF-8');
				echo json_encode($response, JSON_UNESCAPED_UNICODE);
			}

		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error("[ModernNotifications] Error: " . $e->getMessage());
			}
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array(
				'success' => false,
				'error' => $e->getMessage(),
				'type' => 'unread',
				'count' => 0,
				'list' => array(),
			), JSON_UNESCAPED_UNICODE);
		}
	}
}
