<?php
/*+**********************************************************************************
 * ProjectTask Detail — MANAGEMENT dashboard shell (sidebar + topbar + padded content).
 *************************************************************************************/

class ProjectTask_Detail_View extends Vtiger_Detail_View {

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
		$viewer->assign('MENU_SELECTED_MODULENAME', 'ProjectTask');
	}

	protected function ensureManagementApp(Vtiger_Request $request) {
		if (empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
			$_REQUEST['app'] = 'MANAGEMENT';
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isManagementShell($request)) {
			$this->ensureManagementApp($request);
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
}
