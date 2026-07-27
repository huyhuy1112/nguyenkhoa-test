<?php
/*+***********************************************************************************
 * Modern ServiceContracts API — list for SALES Lovable-style UI.
 *************************************************************************************/

require_once 'modules/ServiceContracts/models/ModernService.php';

class ServiceContracts_ModernApi_Action extends Vtiger_Action_Controller {

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
		if (in_array($mode, array('delete', 'save_tags', 'save_next_action'), true)) {
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
						'contracts' => ServiceContracts_ModernService::listContracts($userId),
						'assignable_users' => ServiceContracts_ModernService::listAssignableUsers(),
					));
					break;
				case 'save_next_action':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$nextAction = $request->get('next_action');
					$saved = ServiceContracts_ModernService::saveNextAction($recordId, $nextAction);
					$response->setResult(array('success' => true, 'next_action' => $saved));
					break;
				case 'save_tags':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$tagsRaw = $request->get('tags');
					if (is_string($tagsRaw)) {
						$decoded = json_decode($tagsRaw, true);
						$tagsRaw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $tagsRaw);
					}
					if (!is_array($tagsRaw)) {
						$tagsRaw = array();
					}
					$response->setResult(ServiceContracts_ModernService::saveTags($recordId, $tagsRaw, $userId));
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					ServiceContracts_ModernService::deleteContract($recordId);
					$response->setResult(array('success' => true));
					break;
				default:
					throw new Exception('Unsupported mode.');
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
