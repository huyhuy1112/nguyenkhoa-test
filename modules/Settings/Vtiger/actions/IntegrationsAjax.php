<?php
/*+***********************************************************************************
 * Save / test adapters for Settings → Tích hợp hệ thống.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_IntegrationsAjax_Action extends Settings_Vtiger_Basic_Action {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('test');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$response = new Vtiger_Response();
		$response->setError('Missing mode');
		$response->emit();
	}

	public function save(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			NkApiConnection::ensureInstalled();
			$code = trim((string) $request->get('code'));
			$adapter = NkApiConnection::adapter($code);
			$payload = $this->decodePayload($request);
			$userId = 0;
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if ($currentUser) {
				$userId = (int) $currentUser->getId();
			}
			$config = $adapter->save($payload, $userId);
			$response->setResult(array(
				'success' => true,
				'message' => vtranslate('LBL_NK_INTEG_SAVED', $request->getModule(false)),
				'connection' => $config,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function test(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			NkApiConnection::ensureInstalled();
			$code = trim((string) $request->get('code'));
			$adapter = NkApiConnection::adapter($code);
			$result = $adapter->test();
			$result['connection'] = $adapter->getConfigForAdmin();
			$response->setResult($result);
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	protected function decodePayload(Vtiger_Request $request) {
		// Prefer raw JSON so SA PEM / secrets are not corrupted by vtlib_purify.
		$raw = $request->getRaw('payload');
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$maybe = $request->get('payload');
		if (is_array($maybe)) {
			return $maybe;
		}
		$payload = array();
		foreach (array('enabled', 'base_url', 'api_key', 'username', 'password', 'spreadsheet_id', 'sheet_range', 'service_account_json', 'column_map') as $key) {
			$val = $request->getRaw($key);
			if ($val !== '' && $val !== null) {
				$payload[$key] = $val;
			}
		}
		return $payload;
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
