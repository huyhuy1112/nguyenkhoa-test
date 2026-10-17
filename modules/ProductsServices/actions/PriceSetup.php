<?php
/*+***********************************************************************************
 * Đọc / lưu bậc chiết khấu trên màn Hàng hoá.
 *************************************************************************************/

require_once 'modules/ProductsServices/helpers/PriceSetup.php';

class ProductsServices_PriceSetup_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$mode = (string) $request->get('mode');
		if ($mode === 'save') {
			if (!Users_Privileges_Model::isPermitted('ProductsServices', 'EditView')) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
			return;
		}
		$canRead = Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView')
			|| Users_Privileges_Model::isPermitted('Quotes', 'EditView')
			|| Users_Privileges_Model::isPermitted('Quotes', 'CreateView')
			|| Users_Privileges_Model::isPermitted('SalesOrder', 'EditView')
			|| Users_Privileges_Model::isPermitted('SalesOrder', 'CreateView');
		if (!$canRead) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			ProductsServices_PriceSetup_Helper::ensure();
			$mode = (string) $request->get('mode');
			if ($mode === 'save') {
				$raw = $request->get('policy');
				if (is_array($raw)) {
					$input = $raw;
				} else {
					$decoded = json_decode(html_entity_decode((string) $raw, ENT_QUOTES, 'UTF-8'), true);
					$input = is_array($decoded) ? $decoded : array();
				}
				$result = ProductsServices_PriceSetup_Helper::savePolicy($input);
			} else {
				$result = ProductsServices_PriceSetup_Helper::getPolicy();
			}
			$response->setResult($result);
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		if ((string) $request->get('mode') === 'save') {
			$request->validateWriteAccess();
			return;
		}
		$request->validateReadAccess();
	}
}
