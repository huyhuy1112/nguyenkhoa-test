<?php
/*+***********************************************************************************
 * Leads List: modern SALES UI (UI-only demo data — no database list).
 ************************************************************************************/

class Leads_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	protected function resolveAppCategory(Vtiger_Request $request) {
		return 'SALES';
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === 'MARKETING') {
			$query = array(
				'module' => 'Leads',
				'view' => $request->get('view') ? $request->get('view') : 'List',
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
		$viewer->assign('VIEW', 'List');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Leads');
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
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$this->redirectMarketingToSales($request);
		require_once 'modules/Leads/models/ModernService.php';
		$viewer = $this->getViewer($request);
		$this->assignModernContext($request);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_BASIC_ACTIONS', array());
		$viewer->assign('MODULE_SETTING_ACTIONS', array());
		$viewer->assign('MK_LEADS_ASSIGNABLE_USERS', Leads_ModernService::listAssignableUsers());
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
		return true;
	}
}
