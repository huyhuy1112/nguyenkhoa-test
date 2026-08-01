<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_SuspendUser_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$userId = (int)$request->get('userid') ?: (int)$request->get('record');
		if ($userId <= 0) {
			throw new AppException('LBL_REQUIRED_FIELDS_MISSING');
		}

		// Account deactivation only via Settings → Users
		if ($request->isAjax()) {
			$response = new Vtiger_Response();
			$response->setError('Không thể xoá/vô hiệu hoá tài khoản từ Teams. Vào Cài đặt → Người sử dụng.');
			$response->emit();
			return;
		}
		throw new AppException('Không thể xoá/vô hiệu hoá tài khoản từ Teams. Vào Cài đặt → Người sử dụng.');
	}
}
