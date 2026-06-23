<?php
/*+***********************************************************************************
 * AJAX: Quotes BA context (company profile, term templates, defaults).
 *************************************************************************************/

class Quotes_GetBaContext_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$response = new Vtiger_Response();
		$response->setResult(Quotes_QuoteBaService_Helper::getBaContext());
		$response->emit();
	}
}
