<?php
/*+***********************************************************************************
 * Plans Edit/Create (MARKETING) — split shell create UI (content only).
 *************************************************************************************/

class Plans_Edit_View extends Vtiger_Edit_View {

	protected function isMarketingCreateShell(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		if ($app !== 'MARKETING') {
			return false;
		}
		// Apply to Create (and Duplicate) only.
		return empty($request->get('record')) || $request->get('isDuplicate');
	}

	protected function assignMarketingContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MARKETING');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Plans');
		$viewer->assign('MK_PLANS_MODERN_CREATE', true);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMarketingCreateShell($request)) {
			parent::preProcess($request, false);
			$this->assignMarketingContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMarketingCreateShell($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMarketingCreateShell($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMarketingCreateShell($request)) {
			$this->assignMarketingContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isMarketingCreateShell($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/Plans/resources/PlansEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->isMarketingCreateShell($request)) {
			$jsFileNames = array(
				'~layouts/v7/modules/Plans/resources/PlansMkEdit.js',
			);
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			return array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}
}

