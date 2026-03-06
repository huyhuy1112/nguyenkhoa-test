<?php
/*+***********************************************************************************
 * HelpDesk_RuleEdit_View – create / edit a single Support Rule.
 * URL: index.php?module=HelpDesk&view=RuleEdit&rule_id=ID
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_RuleEdit_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$service    = HelpDesk_SupportRulesService::getInstance();

		$ruleId = (int)$request->get('rule_id');
		$rule   = null;
		if ($ruleId > 0) {
			$rule = $service->getRuleById($ruleId);
		}
		if (!$rule) {
			$rule = [
				'id'                   => 0,
				'rule_name'            => '',
				'rule_type'            => 'first_response',
				'description'          => '',
				'is_active'            => 1,
				'level_1_time_minutes' => null,
				'level_2_time_minutes' => null,
				'level_3_time_minutes' => null,
			];
		}

		$ruleTypes = [
			'first_response'          => 'First Response',
			'customer_update'         => 'Customer Update',
			'project_progress_update' => 'Project Progress Update',
			'meeting_summary'         => 'Meeting Summary',
		];

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RULE', $rule);
		$viewer->assign('RULE_TYPES', $ruleTypes);
		$viewer->view('RuleEdit.tpl', $moduleName);
	}
}

