<?php
/*+***********************************************************************************
 * Ajax save for per-user notification channel preferences.
 *************************************************************************************/

require_once 'modules/Vtiger/models/NotificationService.php';

class Settings_Vtiger_NotificationPrefsAjax_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!$currentUserModel || !$currentUserModel->getId()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		$userId = (int) $user->getId();
		Vtiger_NotificationService::ensureInstalled();

		$mode = $request->get('mode');
		$response = new Vtiger_Response();
		try {
			if ($mode === 'saveChannels') {
				$raw = $request->get('channels');
				if (is_string($raw)) {
					$decoded = json_decode($raw, true);
					$channels = is_array($decoded) ? $decoded : array();
				} elseif (is_array($raw)) {
					$channels = $raw;
				} else {
					$channels = array();
				}
				$prefs = Vtiger_NotificationService::setChannelPrefs($userId, $channels);

				if ($request->has('sound_enabled')) {
					$en = $request->get('sound_enabled');
					$enabled = !($en === '0' || $en === 0 || $en === false || $en === 'false');
					$vol = $request->get('volume');
					$vol = ($vol === '' || $vol === null) ? null : (float) $vol;
					$sound = Vtiger_NotificationService::setSoundPref($userId, $enabled, $vol);
				} else {
					$sound = Vtiger_NotificationService::getSoundPref($userId);
				}

				$response->setResult(array(
					'success' => true,
					'channels' => $prefs,
					'sound_enabled' => $sound['enabled'],
					'volume' => $sound['volume'],
					'message' => 'Đã lưu tùy chọn thông báo',
				));
			} else {
				$response->setError(400, 'Unknown mode');
			}
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}
		$response->emit();
	}
}
