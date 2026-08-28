<?php
/*+***********************************************************************************
 * Contacts Commerce API — purchase history from Sales Orders (Customer / BA).
 *************************************************************************************/

require_once 'modules/Leads/models/CommerceService.php';

class Contacts_CommerceApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'DetailView'),
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$mode = strtolower((string)$request->get('mode'));
		if ($mode === 'link_order') {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));

		try {
			switch ($mode) {
				case 'get':
					$id = (int)$request->get('record');
					if ($id <= 0) {
						$id = (int)$request->get('id');
					}
					if ($id <= 0) {
						throw new Exception('Contact id is required.');
					}
					if (!Users_Privileges_Model::isPermitted('Contacts', 'DetailView', $id)) {
						throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
					}
					$map = Leads_CommerceService::getPurchasesForContactIds(array($id));
					$response->setResult(array(
						'success' => true,
						'purchases' => isset($map[$id]) ? $map[$id] : array(),
					));
					break;

				case 'link_order':
					$id = (int)$request->get('record');
					if ($id <= 0) {
						$id = (int)$request->get('id');
					}
					$salesOrderId = (int)$request->get('salesorder_id');
					if ($salesOrderId <= 0) {
						$salesOrderId = (int)$request->get('salesorderid');
					}
					if ($id <= 0 || $salesOrderId <= 0) {
						throw new Exception('Contact and Sales Order id are required.');
					}
					if (!Users_Privileges_Model::isPermitted('Contacts', 'DetailView', $id)) {
						throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
					}
					Leads_CommerceService::linkSalesOrderToContact($id, $salesOrderId);
					$map = Leads_CommerceService::getPurchasesForContactIds(array($id));
					$response->setResult(array(
						'success' => true,
						'purchases' => isset($map[$id]) ? $map[$id] : array(),
					));
					break;

				case 'search_orders':
					$query = trim((string)$request->get('q'));
					$response->setResult(array(
						'success' => true,
						'orders' => Leads_CommerceService::searchSalesOrders($query),
					));
					break;

				default:
					throw new Exception('Unknown mode: ' . $mode);
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}
}
