<?php
/*********************************************************************************
 * SupportFAQ Edit — create / edit with modern SUPPORT shell UI.
 ********************************************************************************/

class SupportFAQ_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernFaqCreate(Vtiger_Request $request) {
		if (!empty($request->get('record')) && !$request->get('isDuplicate')) {
			return false;
		}
		$app = strtoupper((string)$request->get('app'));
		return ($app === 'SUPPORT' || $app === '');
	}

	protected function isSupportShell(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		return ($app === 'SUPPORT' || $app === '');
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'SupportFAQ');
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		if ($this->isSupportShell($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		if ($this->isSupportShell($request)) {
			parent::preProcess($request, false);
			$this->assignSupportContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isSupportShell($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernFaqCreate($request)) {
			$this->assignSupportContext($request);
			$viewer = $this->getViewer($request);
			$viewer->assign('MK_MODERN_SUPPORTFAQ_CREATE', true);
			$viewer->assign('MODE', 'create');
		} elseif ($this->isSupportShell($request)) {
			$this->assignSupportContext($request);
			$viewer = $this->getViewer($request);
			$viewer->assign('MODE', 'edit');
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isSupportShell($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/SupportFAQ/resources/SupportFAQEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->isMkModernFaqCreate($request)) {
			$jsFileNames = array(
				'~layouts/v7/modules/SupportFAQ/resources/SupportFAQMkEdit.js',
			);
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			return array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}
}
