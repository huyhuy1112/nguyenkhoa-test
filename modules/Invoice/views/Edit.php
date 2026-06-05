<?php
/*+***********************************************************************************
 * Invoice Edit — premium Create workspace (TOOLS / SUPPORT). Stock Inventory Save + line items.
 *************************************************************************************/

class Invoice_Edit_View extends Inventory_Edit_View {

	protected function isMkModernInvoiceCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		return $app === 'TOOLS' || $app === 'SUPPORT';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$app = strtoupper((string) $request->get('app'));
		if ($app !== 'TOOLS' && $app !== 'SUPPORT') {
			$app = 'TOOLS';
		}
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', $app);
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Invoice');
		$viewer->assign('MK_MODERN_INVOICE_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernInvoiceCreate($request)) {
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
		if ($this->isMkModernInvoiceCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernInvoiceCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernInvoiceCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernInvoiceCreate($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Invoice/resources/InvoiceMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if (!$this->isMkModernInvoiceCreate($request)) {
			return $headerScriptInstances;
		}
		$jsFileNames = array(
			'~layouts/v7/modules/Invoice/resources/InvoiceMkEdit.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
