<?php
/*+***********************************************************************************
 * Potentials Edit — premium Create workspace (SALES, new record). Stock Save + all fields.
 *************************************************************************************/

class Potentials_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernOpportunityCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string)$request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$user = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Potentials');
		$viewer->assign('MK_MODERN_OPPORTUNITY_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
		$viewer->assign('MK_OPP_OWNER_NAME', trim($user->getName()));
		$viewer->assign('MK_OPP_OWNER_INITIAL', $this->getUserInitial($user->getName()));
	}

	protected function getUserInitial($name) {
		$name = trim((string)$name);
		if ($name === '') {
			return '?';
		}
		$parts = preg_split('/\s+/', $name);
		if (count($parts) >= 2) {
			return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
		}
		return strtoupper(substr($name, 0, 1));
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === 'MARKETING' && empty($request->get('record'))) {
			header('Location: index.php?module=Potentials&view=Edit&app=SALES');
			exit;
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernOpportunityCreate($request)) {
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
		if ($this->isMkModernOpportunityCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernOpportunityCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernOpportunityCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernOpportunityCreate($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Potentials/resources/OpportunityMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Potentials/resources/EditLockAutoFields.js?v=20260618_opp_name_edit1',
		);
		if ($this->isMkModernOpportunityCreate($request)) {
			$jsFileNames[] = '~layouts/v7/modules/Potentials/resources/OpportunityMkEdit.js';
		}
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
