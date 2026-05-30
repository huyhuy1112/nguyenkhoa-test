<?php
/*+**********************************************************************************
 * Project List — MANAGEMENT dashboard shell (sidebar + topbar + padded content).
 ************************************************************************************/

class Project_List_View extends Vtiger_List_View {

	protected function isManagementShell(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		return $app === 'MANAGEMENT' || $app === '';
	}

	protected function assignManagementContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Project');
	}

	function preProcess(Vtiger_Request $request, $display = true) {
		if (empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
			$_REQUEST['app'] = 'MANAGEMENT';
		}

		$useShell = $this->isManagementShell($request);
		parent::preProcess($request, false);

		if ($useShell) {
			$this->assignManagementContext($request);
		}

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isManagementShell($request)) {
			return 'ListViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isManagementShell($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('ListViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}
}
