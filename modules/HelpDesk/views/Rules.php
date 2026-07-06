<?php
/*+***********************************************************************************
 * HelpDesk_Rules_View – Tag Rule Engine manage page (in-memory UI).
 * URL: index.php?module=HelpDesk&view=Rules&app=SUPPORT
 * ************************************************************************************/

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
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngineStore.js',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.js',
			'modules.HelpDesk.resources.Rules',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/HelpDesk/resources/HelpDeskRulesList.css',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('Rules.tpl', $request->getModule());
	}
}
