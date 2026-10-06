<?php
/*+***********************************************************************************
 * Modern Accounts API — Leads-like list + tags for KH NQ tiềm năng.
 *************************************************************************************/

require_once 'modules/Accounts/models/ModernService.php';

class Accounts_ModernApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted($request->getModule(), 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$mode = strtolower((string) $request->get('mode'));
		if (in_array($mode, array('save_tags'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		if (session_status() === PHP_SESSION_ACTIVE) {
			@session_write_close();
		}
		$response = new Vtiger_Response();
		$mode = strtolower((string) $request->get('mode'));
		$userId = (int) $current_user->id;

		try {
			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'accounts' => Accounts_ModernService::listAccounts($userId),
						'assignable_users' => Accounts_ModernService::listAssignableUsers(),
					));
					break;

				case 'get':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$account = Accounts_ModernService::getAccount($id, $userId);
					if (!$account) {
						throw new Exception('Account not found.');
					}
					$response->setResult(array('success' => true, 'account' => $account));
					break;

				case 'save_tags':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$payload = $this->decodePayload($request);
					$tags = array();
					if (isset($payload['tags']) && is_array($payload['tags'])) {
						$tags = $payload['tags'];
					} elseif ($request->get('tags')) {
						$raw = $request->get('tags');
						$tags = is_array($raw) ? $raw : array();
					}
					$result = Accounts_ModernService::saveTags((int) $id, $tags, $userId);
					$response->setResult($result);
					break;

				default:
					throw new Exception('Unknown mode: ' . $mode);
			}
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}
		$response->emit();
	}

	protected function decodePayload(Vtiger_Request $request) {
		$raw = $request->get('payload');
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		return array();
	}
}
