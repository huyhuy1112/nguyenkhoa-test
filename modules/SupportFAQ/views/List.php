<?php
/*+***********************************************************************************
 * SupportFAQ list → Cảnh báo hành động (Tag Rule Engine alerts).
 * URL: index.php?module=SupportFAQ&view=List&app=SUPPORT
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TagRuleEngineService.php';

class SupportFAQ_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'SupportFAQViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'List');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'SupportFAQ');
		$this->assignSupportContext($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('SupportFAQViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngineStore.js?mk_v=20260714_db2',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleAlerts.js?mk_v=20260714_db2',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/SupportFAQ/resources/SupportFAQList.css',
			'~layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.css?mk_v=20260714_db2',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$viewer = $this->getViewer($request);
		try {
			$svc = HelpDesk_TagRuleEngineService::getInstance();
			$bootstrap = $svc->bootstrap();
			$userId = is_object($current_user) ? (int)$current_user->id : 0;
			$bootstrap['alerts'] = $svc->getAlerts($userId, 200);
		} catch (Exception $e) {
			$bootstrap = array(
				'tags' => array(),
				'rules' => array(),
				'scenarios' => array(),
				'alerts' => array(),
				'error' => $e->getMessage(),
			);
		}
		$viewer->assign('MK_TAG_RULE_ALERT_COUNT', isset($bootstrap['alerts']) ? count($bootstrap['alerts']) : 0);
		$viewer->assign('MK_TAG_RULE_BOOTSTRAP_JSON', json_encode(
			$bootstrap,
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		));
		$viewer->view('SupportFAQList.tpl', $request->getModule());
	}
}
