<?php
/*+***********************************************************************************
 * Accounts Edit — premium Create workspace (SALES, new record). Stock Save + all fields.
 *************************************************************************************/

class Accounts_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernOrganizationCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string)$request->get('app'));
		return $app === 'SALES' || $app === 'SUPPORT' || $app === 'MARKETING' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$app = strtoupper((string)$request->get('app'));
		$viewer->assign('SELECTED_MENU_CATEGORY', $app ? $app : 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Accounts');
		$viewer->assign('MK_MODERN_ORG_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		// No-op: modern create supports MARKETING directly now.
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernOrganizationCreate($request)) {
			$this->redirectMarketingToSales($request);
			parent::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernOrganizationCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernOrganizationCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernOrganizationCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isMkModernOrganizationCreate($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/Accounts/resources/AccountMkEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->isMkModernOrganizationCreate($request)) {
			$jsFileNames = array(
				'~layouts/v7/modules/Accounts/resources/AccountMkEdit.js',
			);
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			return array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}
}

