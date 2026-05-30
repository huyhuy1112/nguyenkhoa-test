<?php
/*+***********************************************************************************
 * Documents Edit/Create — MANAGEMENT dashboard shell (sidebar + topbar).
 *************************************************************************************/

Class Documents_Edit_View extends Vtiger_Edit_View {

	protected function isManagementShellEdit(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		return $app === 'MANAGEMENT' || $app === '';
	}

	protected function assignManagementEditContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Documents');
	}

	function preProcess(Vtiger_Request $request, $display = true) {
		if (empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
		}

		if ($this->isManagementShellEdit($request)) {
			parent::preProcess($request, false);
			$this->assignManagementEditContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}

		$parentApp = $request->get('app');
		if ($parentApp === 'MANAGEMENT') {
			$this->assignManagementEditContext($request);
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isManagementShellEdit($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isManagementShellEdit($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($request->get('app') !== 'MANAGEMENT') {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Documents/resources/DocumentsMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$jsFileNames = array(
			'libraries.jquery.ckeditor.ckeditor',
			'libraries.jquery.ckeditor.adapters.jquery',
			'modules.Vtiger.resources.CkEditor',
		);
		if ($request->get('app') === 'MANAGEMENT') {
			$jsFileNames[] = 'layouts.v7.modules.Documents.resources.DocumentsMkEdit';
		}
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
