<?php
/*+***********************************************************************************
 * Leads Detail: modern SALES UI (UI-only demo — no database record).
 ************************************************************************************/

class Leads_Detail_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPreProcess.tpl';
	}

	protected function resolveAppCategory(Vtiger_Request $request) {
		return 'SALES';
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === 'MARKETING') {
			$query = array(
				'module' => 'Leads',
				'view' => 'Detail',
				'app' => 'SALES',
			);
			if ($request->get('record')) {
				$query['record'] = $request->get('record');
			}
			header('Location: index.php?' . http_build_query($query));
			exit;
		}
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Detail');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Leads');
		$viewer->assign('MK_LEADS_DETAIL_RECORD', $request->get('record'));
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->redirectMarketingToSales($request);
		parent::preProcess($request, false);
		$this->assignModernContext($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('DetailViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$this->redirectMarketingToSales($request);
		$viewer = $this->getViewer($request);
		$this->assignModernContext($request);
		$viewer->view('DetailView.tpl', $request->getModule());
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
		return true;
	}
}
