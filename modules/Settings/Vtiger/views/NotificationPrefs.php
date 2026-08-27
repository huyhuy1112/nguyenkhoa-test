<?php
/*+***********************************************************************************
 * Settings → Quản lý thông báo (mỗi user tự bật/tắt loại thông báo của mình).
 *************************************************************************************/

require_once 'modules/Vtiger/models/NotificationService.php';

class Settings_Vtiger_NotificationPrefs_View extends Settings_Vtiger_Index_View {

	/**
	 * Any logged-in user may manage their own notification channels.
	 */
	function checkPermission(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!$currentUserModel || !$currentUserModel->getId()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		return true;
	}

	public function preProcessSettings(Vtiger_Request $request, $display = true) {
		try {
			Vtiger_NotificationService::ensureInstalled();
		} catch (Exception $e) {
			// ignore
		}
		parent::preProcessSettings($request, $display);
	}

	public function process(Vtiger_Request $request) {
		Vtiger_NotificationService::ensureInstalled();
		$qualifiedName = $request->getModule(false);
		$user = Users_Record_Model::getCurrentUserModel();
		$userId = (int) $user->getId();
		$catalog = Vtiger_NotificationService::channelCatalog();
		$prefs = Vtiger_NotificationService::getChannelPrefs($userId);
		$sound = Vtiger_NotificationService::getSoundPref($userId);

		$groups = array();
		foreach ($catalog as $key => $meta) {
			$g = isset($meta['group']) ? $meta['group'] : 'Khác';
			if (!isset($groups[$g])) {
				$groups[$g] = array();
			}
			$groups[$g][] = array(
				'key' => $key,
				'label' => $meta['label'],
				'enabled' => !empty($prefs[$key]),
			);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', $user);
		$viewer->assign('NK_NOTIF_GROUPS', $groups);
		$viewer->assign('NK_NOTIF_SOUND', $sound);
		$viewer->view('NotificationPrefs.tpl', $qualifiedName);
	}

	function getPageTitle(Vtiger_Request $request) {
		return vtranslate('LBL_NK_NOTIFICATION_PREFS', $request->getModule(false));
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/NotificationPrefs.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/NotificationPrefs.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}
