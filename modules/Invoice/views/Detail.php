<?php
/*+***********************************************************************************
 * Invoice Detail — premium workspace (TOOLS / SUPPORT).
 *************************************************************************************/

class Invoice_Detail_View extends Inventory_Detail_View {

	protected function isMkModernInvoiceDetail(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		return $app === 'TOOLS' || $app === 'SUPPORT';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernInvoiceDetail($request)) {
			$viewer = $this->getViewer($request);
			$app = strtoupper((string) $request->get('app'));
			$viewer->assign('SELECTED_MENU_CATEGORY', $app);
			$viewer->assign('MK_INV_MK_DETAIL', true);
		}
		parent::preProcess($request, $display);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernInvoiceDetail($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Invoice/resources/InvoiceToolsDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}
