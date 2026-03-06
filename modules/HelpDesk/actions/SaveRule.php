<?php
/*+***********************************************************************************
 * HelpDesk_SaveRule_Action – create/update Support Rule (minutes only).
 *
 * URL: index.php?module=HelpDesk&action=SaveRule
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_SaveRule_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName      = $request->getModule();
		$moduleModel     = Vtiger_Module_Model::getInstance($moduleName);
		$privilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		if (!$privilegesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$service = HelpDesk_SupportRulesService::getInstance();

		$id = (int)$request->get('rule_id');

		$data = [
			'id'                   => $id ?: null,
			'rule_name'            => $request->get('rule_name'),
			'rule_type'            => $request->get('rule_type'),
			'description'          => $request->get('description'),
			'is_active'            => $request->get('is_active'),
			'level_1_time_minutes' => $request->get('level_1_time_minutes'),
			'level_2_time_minutes' => $request->get('level_2_time_minutes'),
			'level_3_time_minutes' => $request->get('level_3_time_minutes'),
		];

		try {
			$service->saveRule($data);
		} catch (Exception $e) {
			// Could log error; for now just redirect
		}

		header('Location: index.php?module=HelpDesk&view=Rules');
		exit;
	}
}

