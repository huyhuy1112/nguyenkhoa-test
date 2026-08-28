<?php
/*+***********************************************************************************
 * HelpDesk_Rules_View – Tag Rule Engine manage page (DB-backed).
 * URL: index.php?module=HelpDesk&view=Rules&app=SUPPORT
 * ************************************************************************************/

require_once 'modules/HelpDesk/models/TagRuleEngineService.php';

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
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngineStore.js?mk_v=20260827_score_opt1',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.js?mk_v=20260827_score_opt1',
			'modules.HelpDesk.resources.Rules',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/HelpDesk/resources/HelpDeskRulesList.css',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.css?mk_v=20260827_score_opt1',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		try {
			$svc = HelpDesk_TagRuleEngineService::getInstance();
			$bootstrap = $svc->bootstrap();
		} catch (Exception $e) {
			$bootstrap = array('tags' => array(), 'rules' => array(), 'scenarios' => array());
		}
		$viewer->assign('MK_TAG_RULE_BOOTSTRAP_JSON', json_encode(
			$bootstrap,
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		));
		$viewer->view('Rules.tpl', $request->getModule());
	}
}
