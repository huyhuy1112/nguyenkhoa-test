<?php
/*+***********************************************************************************
 * Zalo OA OAuth callback (Settings → Tích hợp hệ thống).
 * Zalo redirects here with ?code=&oa_id=&state=
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_ZaloOaOAuth_Action extends Settings_Vtiger_Basic_Action {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('callback');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if ($mode === 'callback' || $mode === '') {
			$this->callback($request);
			return;
		}
		$this->invokeExposedMethod($mode, $request);
	}

	/**
	 * Skip CSRF — Zalo GET redirect cannot send vtiger CSRF token.
	 */
	public function validateRequest(Vtiger_Request $request) {
		$mode = (string) $request->getMode();
		if ($mode === 'callback' || $mode === '') {
			return;
		}
		$request->validateWriteAccess();
	}

	public function callback(Vtiger_Request $request) {
		$integrationsUrl = 'index.php?module=Vtiger&parent=Settings&view=Integrations#code=zalo_oa';
		try {
			$error = trim((string) $request->get('error'));
			if ($error !== '') {
				$desc = trim((string) $request->get('error_description'));
				throw new Exception($desc !== '' ? $desc : ('Zalo OAuth lỗi: ' . $error));
			}
			$code = trim((string) $request->get('code'));
			$state = trim((string) $request->get('state'));
			$oaId = trim((string) $request->get('oa_id'));
			$userId = 0;
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if ($currentUser) {
				$userId = (int) $currentUser->getId();
			}
			/** @var NkApi_ZaloOa_Adapter $adapter */
			$adapter = NkApiConnection::adapter('zalo_oa');
			$adapter->completeOAuth($code, $state, $oaId, $userId);
			header('Location: ' . $integrationsUrl . '&zalo_oauth=1');
			exit;
		} catch (Exception $e) {
			$msg = rawurlencode($e->getMessage());
			header('Location: ' . $integrationsUrl . '&zalo_oauth=0&zalo_err=' . $msg);
			exit;
		}
	}
}
