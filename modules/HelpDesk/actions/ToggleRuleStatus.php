<?php
/*+***********************************************************************************
 * HelpDesk_ToggleRuleStatus_Action
 *
 * Enable / disable a Support Rule.
 ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_ToggleRuleStatus_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$ruleId = (int)$request->get('rule_id');
		$active = (int)$request->get('is_active') === 1;

		if ($ruleId <= 0) {
			header('Location: index.php?module=HelpDesk&view=Rules');
			return;
		}

		$service = HelpDesk_SupportRulesService::getInstance();
		$service->setRuleActive($ruleId, $active);

		header('Location: index.php?module=HelpDesk&view=Rules');
	}
}

