<?php
/*+***********************************************************************************
 * Xóa đơn nghỉ phép. Chỉ Admin/CEO được phép xóa.
 *************************************************************************************/

class Calendar_DeleteLeaveRequest_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		if (!$user) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$recordId = (int) $request->get('leaverequestid');
		if (!$recordId) {
			$response->setError(vtranslate('LBL_RECORD_NOT_FOUND', 'Calendar'));
			$response->emit();
			return;
		}

		if (!Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
			$response->setError(vtranslate('LBL_TABLE_NOT_FOUND', 'Calendar'));
			$response->emit();
			return;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$isAdmin = $currentUser->isAdminUser();
		$isCEO = $this->isUserCEO($currentUser);
		if (!($isAdmin || $isCEO)) {
			$response->setError(vtranslate('LBL_PERMISSION_DENIED'));
			$response->emit();
			return;
		}

		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT leaverequestid FROM vtiger_leaverequest WHERE leaverequestid = ?", array($recordId));
		if (!$adb->num_rows($res)) {
			$response->setError(vtranslate('LBL_RECORD_NOT_FOUND', 'Calendar'));
			$response->emit();
			return;
		}

		$adb->pquery("DELETE FROM vtiger_leaverequest WHERE leaverequestid = ?", array($recordId));
		$response->setResult(array('success' => true, 'id' => $recordId));
		$response->emit();
	}

	protected function isUserCEO($userModel) {
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) return false;
		$adb = PearDatabase::getInstance();
		$r = $adb->pquery("SELECT rolename FROM vtiger_role WHERE roleid = ?", array($roleId));
		if ($adb->num_rows($r)) {
			$name = strtolower(trim($adb->query_result($r, 0, 'rolename')));
			return ($name === 'ceo' || preg_match('/\bceo\b/', $name));
		}
		return false;
	}
}
