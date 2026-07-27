<?php
/*+***********************************************************************************
 * ServiceContracts Edit — premium Create workspace (SALES, new record). Stock Save + fields.
 *************************************************************************************/

class ServiceContracts_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernServiceContractCreate(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'ServiceContracts');
		$viewer->assign('MK_MODERN_SERVICE_CONTRACT_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD', $recordId);
	}

	protected function redirectSupportToSales(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === 'SUPPORT' && empty($request->get('record'))) {
			header('Location: index.php?module=ServiceContracts&view=Edit&app=SALES');
			exit;
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernServiceContractCreate($request)) {
			$this->redirectSupportToSales($request);
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
		if ($this->isMkModernServiceContractCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernServiceContractCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernServiceContractCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernServiceContractCreate($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/ServiceContracts/resources/ServiceContractMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if (!$this->isMkModernServiceContractCreate($request)) {
			return $headerScriptInstances;
		}
		$jsFileNames = array(
			'~layouts/v7/modules/ServiceContracts/resources/ServiceContractMkEdit.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
