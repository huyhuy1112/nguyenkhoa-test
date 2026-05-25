<?php
/*+***********************************************************************************
 * HelpDesk_Rules_View – list page for Support Rules (SLA engine).
 * URL: index.php?module=HelpDesk&view=Rules&app=SUPPORT
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/SupportRulesService.php';

class HelpDesk_Rules_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'RulesViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'Rules');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		// Must set before preProcessDisplay (sidebar renders in RulesViewPreProcess.tpl).
		parent::preProcess($request, false);
		$this->getViewer($request)->assign('MENU_SELECTED_MODULENAME', 'Rules');
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('RulesViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'modules.HelpDesk.resources.Rules',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/HelpDesk/resources/HelpDeskRulesList.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	private static function rulesRedirectUrl() {
		return 'index.php?module=HelpDesk&view=Rules&app=SUPPORT';
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$service    = HelpDesk_SupportRulesService::getInstance();

		$mode   = (string)$request->get('mode');
		$ruleId = (int)$request->get('rule_id');
		if ($mode === 'enable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, true);
			header('Location: ' . self::rulesRedirectUrl());
			return;
		}
		if ($mode === 'disable' && $ruleId > 0) {
			$service->setRuleActive($ruleId, false);
			header('Location: ' . self::rulesRedirectUrl());
			return;
		}

		$page      = (int)$request->get('page');
		$page      = $page > 0 ? $page : 1;
		$pageLimit = 20;

		$allRules = $service->getAllRules();
		$total    = count($allRules);
		$activeCount = 0;
		foreach ($allRules as $r) {
			if (!empty($r['is_active'])) {
				$activeCount++;
			}
		}
		$pages    = $total > 0 ? (int)ceil($total / $pageLimit) : 1;
		if ($page > $pages) {
			$page = $pages;
		}
		$offset   = ($page - 1) * $pageLimit;
		$rules    = array_slice($allRules, $offset, $pageLimit);

		$showFrom = $total > 0 ? $offset + 1 : 0;
		$showTo   = min($offset + $pageLimit, $total);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SUPPORT_RULES', $rules);
		$viewer->assign('RULES_TOTAL', $total);
		$viewer->assign('RULES_PAGE', $page);
		$viewer->assign('RULES_PAGES', $pages);
		$viewer->assign('RULES_SHOW_FROM', $showFrom);
		$viewer->assign('RULES_SHOW_TO', $showTo);
		$viewer->assign('RULES_ACTIVE_COUNT', $activeCount);
		$viewer->view('Rules.tpl', $moduleName);
	}
}
