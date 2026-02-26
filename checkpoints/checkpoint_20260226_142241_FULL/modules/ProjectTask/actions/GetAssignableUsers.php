<?php
/*+***********************************************************************************
 * Return assignable users for task owner dropdown (same as Project TaskBoard)
 *************************************************************************************/

class ProjectTask_GetAssignableUsers_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleModel = Vtiger_Module_Model::getInstance('ProjectTask');
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$moduleModel || !$userPrivilegesModel->hasModuleActionPermission($moduleModel->getId(), 'DetailView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$users = Users_Record_Model::getAll(true);
		$userOptions = array();
		foreach ($users as $userId => $userModel) {
			$userOptions[$userId] = $userModel->getName();
		}
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array('users' => $userOptions));
		$response->emit();
	}
}
