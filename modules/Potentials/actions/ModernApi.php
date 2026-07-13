<?php
/*+***********************************************************************************
 * Modern Potentials API — list for SALES Lovable-style UI.
 *************************************************************************************/

require_once 'modules/Potentials/models/ModernService.php';

class Potentials_ModernApi_Action extends Vtiger_Action_Controller {

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
		if (in_array($mode, array('save_confirm_tag', 'delete'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$userId = (int)$current_user->id;

		try {
			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'opportunities' => Potentials_ModernService::listPotentials($userId),
						'assignable_users' => Potentials_ModernService::listAssignableUsers(),
					));
					break;
				case 'save_confirm_tag':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$confirm = $request->get('confirm');
					if ($confirm === null) {
						$confirm = $request->get('confirm_tag');
					}
					$result = Potentials_ModernService::setConfirmTag($recordId, $confirm);
					$response->setResult(array('success' => true) + $result);
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					Potentials_ModernService::deletePotential($recordId);
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
