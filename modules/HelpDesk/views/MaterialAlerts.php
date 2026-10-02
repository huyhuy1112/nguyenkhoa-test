<?php
require_once 'modules/HelpDesk/models/MaterialAlertService.php';

class HelpDesk_MaterialAlerts_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'RulesViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SUPPORT');
		$viewer->assign('VIEW', 'MaterialAlerts');
		parent::preProcess($request, false);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Rules');
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$this->getViewer($request)->view('RulesViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		HelpDesk_MaterialAlertService::refresh();
		$viewer = $this->getViewer($request);
		$contactId = (int) $request->get('contact_id');
		$viewer->assign('NL_FIELDS', HelpDesk_MaterialAlertService::settingFields());
		$viewer->assign('NL_SETTINGS', HelpDesk_MaterialAlertService::formSettings());
		$viewer->assign('NL_ALERTS', HelpDesk_MaterialAlertService::listAlerts());
		$viewer->assign('NL_METRICS', $contactId > 0 ? HelpDesk_MaterialAlertService::contactMetrics($contactId) : null);
		$viewer->assign('NL_CONTACT_ID', $contactId);
		$viewer->view('MaterialAlerts.tpl', $request->getModule());
	}
}
