<?php
/**
 * Warehouse management API — state / CRUD (mirrors Leads ModernApi pattern).
 */
require_once 'modules/Warehouse/models/WhMgmtService.php';

class Warehouse_WhMgmtApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$mode = strtolower((string) $request->get('mode'));
		if (in_array($mode, array('save', 'delete', 'archive', 'seed', 'save_receipt', 'save_issue', 'receipt_action', 'issue_action'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$mode = strtolower((string) $request->get('mode'));

		try {
			Warehouse_WhMgmtService::ensureInstalled();

			switch ($mode) {
				case 'state':
					$response->setResult(array(
						'success' => true,
						'state' => Warehouse_WhMgmtService::getFullState(),
					));
					break;

				case 'list':
					$response->setResult(array(
						'success' => true,
						'warehouses' => Warehouse_WhMgmtService::listWarehouses(),
					));
					break;

				case 'get':
					$code = trim((string) $request->get('id'));
					if ($code === '') {
						$code = trim((string) $request->get('whId'));
					}
					$db = PearDatabase::getInstance();
					$warehouses = Warehouse_WhMgmtService::listWarehouses($db);
					$found = null;
					foreach ($warehouses as $w) {
						if ($w['id'] === $code) {
							$found = $w;
							break;
						}
					}
					if (!$found) {
						throw new Exception('Không tìm thấy kho.');
					}
					$response->setResult(array(
						'success' => true,
						'warehouse' => $found,
						'data' => Warehouse_WhMgmtService::getWarehouseData($db, $code),
					));
					break;

				case 'save':
					$payload = $this->decodePayload($request);
					$id = isset($payload['id']) ? $payload['id'] : $request->get('id');
					$warehouse = Warehouse_WhMgmtService::saveWarehouse($payload, $id);
					$response->setResult(array('success' => true, 'warehouse' => $warehouse));
					break;

				case 'delete':
					$id = trim((string) $request->get('id'));
					Warehouse_WhMgmtService::deleteWarehouse($id);
					$response->setResult(array('success' => true));
					break;

				case 'archive':
					$id = trim((string) $request->get('id'));
					$warehouse = Warehouse_WhMgmtService::archiveWarehouse($id);
					$response->setResult(array('success' => true, 'warehouse' => $warehouse));
					break;

				case 'seed':
					Warehouse_WhMgmtService::seedAll();
					$response->setResult(array(
						'success' => true,
						'state' => Warehouse_WhMgmtService::getFullState(),
					));
					break;

				case 'product_catalog':
					$response->setResult(array(
						'success' => true,
						'products' => Warehouse_WhMgmtService::listProductCatalog(),
					));
					break;

				case 'save_receipt':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$payload = $this->decodePayload($request);
					$result = Warehouse_WhMgmtService::saveInboundReceipt($whId, $payload, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'save_issue':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$payload = $this->decodePayload($request);
					$result = Warehouse_WhMgmtService::saveOutboundIssue($whId, $payload, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'receipt_action':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$action = trim((string) $request->get('actionKey'));
					$note = $this->readActionNote($request);
					$role = trim((string) $request->get('role'));
					$targetStatus = trim((string) $request->get('targetStatus'));
					$result = Warehouse_WhMgmtService::applyReceiptAction($whId, $code, $action, $role, $note, $userId, $targetStatus);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'issue_action':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$action = trim((string) $request->get('actionKey'));
					$note = $this->readActionNote($request);
					$role = trim((string) $request->get('role'));
					$targetStatus = trim((string) $request->get('targetStatus'));
					$result = Warehouse_WhMgmtService::applyIssueAction($whId, $code, $action, $role, $note, $userId, $targetStatus);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				default:
					throw new Exception('Unsupported mode: ' . $mode);
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}

	protected function decodePayload(Vtiger_Request $request) {
		$raw = $request->getRaw('payload');
		if ($raw === null || $raw === '') {
			$raw = $request->get('payload');
		}
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		return array(
			'name' => $request->get('name'),
			'type' => $request->get('type'),
			'address' => $request->get('address'),
			'manager' => $request->get('manager'),
			'status' => $request->get('status'),
		);
	}

	/**
	 * Read QC / action note from payload + raw POST (avoids vtiger purify edge cases).
	 */
	protected function readActionNote(Vtiger_Request $request) {
		$payload = $this->decodePayload($request);
		$note = '';
		if (is_array($payload)) {
			if (isset($payload['qcNote'])) {
				$note = trim((string) $payload['qcNote']);
			} else if (isset($payload['note'])) {
				$note = trim((string) $payload['note']);
			}
		}
		if ($note === '') {
			$raw = $request->getRaw('qcNote');
			if ($raw !== null && $raw !== '') {
				$note = trim((string) $raw);
			}
		}
		if ($note === '') {
			$raw = $request->getRaw('note');
			if ($raw !== null && $raw !== '') {
				$note = trim((string) $raw);
			}
		}
		if ($note === '') {
			$note = trim((string) $request->get('qcNote'));
		}
		if ($note === '') {
			$note = trim((string) $request->get('note'));
		}
		return $note;
	}
}

?>
