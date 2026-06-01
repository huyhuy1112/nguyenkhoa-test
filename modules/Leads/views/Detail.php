<?php

/* +***********************************************************************************
 * Leads Detail: modern SALES/MARKETING shell with standard Vtiger detail + comments.
 ************************************************************************************/

class Leads_Detail_View extends Accounts_Detail_View {

	protected function isModernLeadsDetailUi(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === '') {
			$app = strtoupper((string)$request->get('SELECTED_MENU_CATEGORY'));
		}
		return in_array($app, array('SALES', 'MARKETING'), true);
	}

	protected function assignModernLeadsDetailUi(Vtiger_Request $request) {
		if (!$this->isModernLeadsDetailUi($request)) {
			return;
		}
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		$viewer->assign('MK_LEADS_MODERN_UI', true);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Leads');
		$viewer->assign('VIEW', 'Detail');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignModernLeadsDetailUi($request);
		return parent::preProcess($request, $display);
	}

	/**
	 * Use standard Vtiger detail (tabs, comments). Skip Accounts tag ACL on Leads.
	 */
	public function process(Vtiger_Request $request) {
		$this->assignModernLeadsDetailUi($request);
		return Vtiger_Detail_View::process($request);
	}

	public function postProcess(Vtiger_Request $request) {
		$this->assignModernLeadsDetailUi($request);
		return parent::postProcess($request);
	}

	public function showModuleBasicView(Vtiger_Request $request) {
		$this->assignModernLeadsDetailUi($request);
		return parent::showModuleBasicView($request);
	}

	public function showModuleSummaryView($request) {
		$this->assignModernLeadsDetailUi($request);
		return parent::showModuleSummaryView($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$this->assignModernLeadsDetailUi($request);
		return parent::showModuleDetailView($request);
	}
}
