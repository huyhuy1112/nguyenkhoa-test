<?php
require_once 'modules/SalesOrder/helpers/SaleInvoicePdf.php';

class Quotes_Print_View extends Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		return;
	}

	public function postProcess(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		$focus = CRMEntity::getInstance($moduleName);
		$focus->retrieve_entity_info($recordId, $moduleName);
		$focus->apply_field_security();
		$focus->id = $recordId;

		$autoPrint = $request->get('autoprint') === '1' || $request->get('autoprint') === 1;
		SalesOrder_SaleInvoicePdf_Helper::outputHtml($focus, $moduleName, $autoPrint);
	}
}
