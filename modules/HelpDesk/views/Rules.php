<?php
/*+***********************************************************************************
 * HelpDesk_Rules_View – list page for Support Rules (SLA engine).
 * URL: index.php?module=HelpDesk&view=Rules
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_Rules_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$service    = HelpDesk_SupportRulesService::getInstance();

		// Bật / tắt nhanh
		$mode   = (string)$request->get('mode');
		$ruleId = (int)$request->get('rule_id');
		if ($mode === 'enable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, true);
			header('Location: index.php?module=HelpDesk&view=Rules');
			return;
		}
		if ($mode === 'disable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, false);
			header('Location: index.php?module=HelpDesk&view=Rules');
			return;
		}

		$rules = $service->getAllRules();

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SUPPORT_RULES', $rules);
		$viewer->view('Rules.tpl', $moduleName);
	}
}

