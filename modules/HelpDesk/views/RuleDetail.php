<?php
/*+***********************************************************************************
 * HelpDesk_RuleDetail_View – view / edit a single Support Rule (SUPPORT shell).
 * URL: index.php?module=HelpDesk&view=RuleDetail&rule_id=ID&app=SUPPORT
 * New rule: rule_id=0 (same form; save creates the record).
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_RuleDetail_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'RuleDetailViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'RuleDetail');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, false);
		$this->getViewer($request)->assign('MENU_SELECTED_MODULENAME', 'Rules');
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('RuleDetailViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'modules.HelpDesk.resources.RuleDetail',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/HelpDesk/resources/HelpDeskRuleDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	private static function detailRedirectUrl(int $ruleId) {
		return 'index.php?module=HelpDesk&view=RuleDetail&rule_id=' . $ruleId . '&app=SUPPORT';
	}

	private static function rulesRedirectUrl() {
		return 'index.php?module=HelpDesk&view=Rules&app=SUPPORT';
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$service    = HelpDesk_SupportRulesService::getInstance();

		$ruleId = (int)$request->get('rule_id');
		$mode   = (string)$request->get('mode');

		if ($mode === 'enable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, true);
			header('Location: ' . self::detailRedirectUrl($ruleId));
			return;
		}
		if ($mode === 'disable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, false);
			header('Location: ' . self::detailRedirectUrl($ruleId));
			return;
		}

		// New rule: same RuleDetail UI with empty defaults (rule_id=0).
		if ($ruleId <= 0) {
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
			$ruleTypes = [
				'first_response'          => 'First Response',
				'customer_update'         => 'Customer Update',
				'project_progress_update' => 'Project Progress Update',
				'meeting_summary'         => 'Meeting Summary',
			];
			$viewer->assign('MODULE', $moduleName);
			$viewer->assign('RULE', $rule);
			$viewer->assign('RULE_TYPES', $ruleTypes);
			$viewer->assign('RULE_CREATED_LABEL', '');
			$viewer->assign('RULE_IS_NEW', true);
			$viewer->view('RuleDetail.tpl', $moduleName);
			return;
		}

		$rule = $service->getRuleById($ruleId);
		if (!$rule) {
			header('Location: ' . self::rulesRedirectUrl());
			return;
		}

		$ruleTypes = [
			'first_response'          => 'First Response',
			'customer_update'         => 'Customer Update',
			'project_progress_update' => 'Project Progress Update',
			'meeting_summary'         => 'Meeting Summary',
		];

		$createdLabel = '';
		if (!empty($rule['created_at'])) {
			$ts = strtotime($rule['created_at']);
			if ($ts !== false) {
				$createdLabel = date('d-m-Y', $ts);
			}
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RULE', $rule);
		$viewer->assign('RULE_TYPES', $ruleTypes);
		$viewer->assign('RULE_CREATED_LABEL', $createdLabel);
		$viewer->assign('RULE_IS_NEW', false);
		$viewer->view('RuleDetail.tpl', $moduleName);
	}
}
