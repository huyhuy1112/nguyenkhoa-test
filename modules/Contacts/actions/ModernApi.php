<?php
/*+***********************************************************************************
 * Modern Contacts API — list for SALES Lovable-style UI.
 *************************************************************************************/

require_once 'modules/Contacts/models/ModernService.php';

class Contacts_ModernApi_Action extends Vtiger_Action_Controller {

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
		if (in_array($mode, array('delete', 'class_reg_add', 'credential_save'), true)) {
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
						'contacts' => Contacts_ModernService::listContacts($userId),
						'assignable_users' => Contacts_ModernService::listAssignableUsers(),
					));
					break;
				case 'class_reg_list':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'class_reg' => Contacts_ModernService::getClassRegSummary($recordId),
					));
					break;
				case 'class_reg_add':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$date = $request->get('registered_on');
					if ($date === null || $date === '') {
						$date = $request->get('date');
					}
					$kind = $request->get('entry_kind');
					if ($kind === null || $kind === '') {
						$kind = $request->get('kind');
					}
					if ($kind === null || $kind === '') {
						$kind = 'register';
					}
					$response->setResult(array(
						'success' => true,
						'class_reg' => Contacts_ModernService::addClassRegLog($recordId, $date, $userId, $kind),
					));
					break;
				case 'credential_get':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'credentials' => Contacts_ModernService::getCredentialState($recordId),
					));
					break;
				case 'credential_save':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'credentials' => Contacts_ModernService::saveCredentialFields(
							$recordId,
							$request->get('da_cap_bang'),
							$request->get('da_cap_tai_khoan')
						),
					));
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					Contacts_ModernService::deleteContact($recordId);
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
