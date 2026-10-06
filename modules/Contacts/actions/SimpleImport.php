<?php
/*+***********************************************************************************
 * Contacts one-step Excel import (KiotViet Khách lẻ / Miutea) — like Warehouse stock import.
 *************************************************************************************/

require_once 'modules/Contacts/helpers/KiotSimpleImport.php';

class Contacts_SimpleImport_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'Import')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);

		try {
			if ($request->getModule() !== 'Contacts') {
				throw new Exception('Invalid module');
			}
			if (empty($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
				throw new Exception('Chưa chọn file Excel (.xlsx).');
			}
			$user = Users_Record_Model::getCurrentUserModel();
			$result = Contacts_KiotSimpleImport_Helper::importUploadedFile(
				$_FILES['import_file'],
				(int) $user->getId()
			);
			$response->setResult($result);
		} catch (Throwable $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}
}
