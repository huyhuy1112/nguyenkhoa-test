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
		if (in_array($mode, array('save', 'delete', 'archive', 'seed', 'save_receipt', 'save_purchase', 'complete_purchase', 'transfer_purchase_misa', 'save_issue', 'receipt_action', 'issue_action', 'save_return', 'return_action', 'set_settings', 'qc_upload_image', 'qc_delete_image', 'qc_update', 'import_stock_excel', 'set_stock_fill', 'save_stock_fill'), true)) {
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
						'canStockFillAdmin' => Warehouse_WhMgmtService::isStockFillAdmin() ? 1 : 0,
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
				case 'save_purchase':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$payload = $this->decodePayload($request);
					if ($whId === '' && isset($payload['warehouse'])) {
						$whId = trim((string) $payload['warehouse']);
					}
					if ($whId === '' && isset($payload['whId'])) {
						$whId = trim((string) $payload['whId']);
					}
					$result = Warehouse_WhMgmtService::saveInboundReceipt($whId, $payload, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'complete_purchase':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$payload = $this->decodePayload($request);
					if ($whId === '' && isset($payload['warehouse'])) {
						$whId = trim((string) $payload['warehouse']);
					}
					if ($code === '' && isset($payload['code'])) {
						$code = trim((string) $payload['code']);
					}
					$result = Warehouse_WhMgmtService::completePurchaseDraft($whId, $code, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'list_purchases':
					$q = trim((string) $request->get('q'));
					$status = trim((string) $request->get('status'));
					$payload = $this->decodePayload($request);
					if ($q === '' && isset($payload['q'])) {
						$q = trim((string) $payload['q']);
					}
					if ($status === '' && isset($payload['status'])) {
						$status = trim((string) $payload['status']);
					}
					$response->setResult(array(
						'success' => true,
						'receipts' => Warehouse_WhMgmtService::listPurchaseReceipts($q, $status),
					));
					break;

				case 'transfer_purchase_misa':
					$code = trim((string) $request->get('code'));
					$whId = trim((string) $request->get('whId'));
					$payload = $this->decodePayload($request);
					if ($code === '' && isset($payload['code'])) {
						$code = trim((string) $payload['code']);
					}
					if ($whId === '' && isset($payload['warehouse'])) {
						$whId = trim((string) $payload['warehouse']);
					}
					$receipt = Warehouse_WhMgmtService::getPurchaseReceipt($code, $whId);
					require_once 'modules/Vtiger/helpers/NkApiConnection.php';
					require_once 'modules/Invoice/models/MisaSyncService.php';
					$api = NkApiConnection::adapter('misa');
					if ($api && method_exists($api, 'prepareConnection')) {
						$api->prepareConnection();
					}
					if (!$api || !method_exists($api, 'isEnabled') || !$api->isEnabled()) {
						throw new Exception('Kết nối MISA đang tắt. Bật trong Cài đặt → Tích hợp hệ thống.');
					}
					$result = Invoice_MisaSyncService::pushGoodsReceipt($api, $receipt);
					if (!empty($result['error'])) {
						throw new Exception((string) $result['error']);
					}
					$response->setResult($result);
					break;

				case 'get_purchase':
					$code = trim((string) $request->get('code'));
					$whId = trim((string) $request->get('whId'));
					$payload = $this->decodePayload($request);
					if ($code === '' && isset($payload['code'])) {
						$code = trim((string) $payload['code']);
					}
					if ($whId === '' && isset($payload['warehouse'])) {
						$whId = trim((string) $payload['warehouse']);
					}
					$response->setResult(array(
						'success' => true,
						'receipt' => Warehouse_WhMgmtService::getPurchaseReceipt($code, $whId),
					));
					break;

				case 'search_vendors':
					$q = trim((string) $request->get('q'));
					$payload = $this->decodePayload($request);
					if ($q === '' && isset($payload['q'])) {
						$q = trim((string) $payload['q']);
					}
					$response->setResult(array(
						'success' => true,
						'vendors' => Warehouse_WhMgmtService::searchVendors($q),
					));
					break;

				case 'list_vendors':
					$q = trim((string) $request->get('q'));
					$payload = $this->decodePayload($request);
					if ($q === '' && isset($payload['q'])) {
						$q = trim((string) $payload['q']);
					}
					$response->setResult(array(
						'success' => true,
						'vendors' => Warehouse_WhMgmtService::listVendorsDetailed($q),
					));
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

				case 'get_settings':
					$response->setResult(array(
						'success' => true,
						'settings' => Warehouse_WhMgmtService::publicSettings(),
					));
					break;

				case 'get_slow_moving':
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$minRisk = $request->has('minRisk') ? (float) $request->get('minRisk') : 0.0;
					$report = Warehouse_WhMgmtService::getSlowMovingReport($whId, $minRisk);
					$response->setResult(array_merge(array('success' => true), $report));
					break;

				case 'set_settings':
					require_once 'modules/Warehouse/helpers/SettingsHelper.php';
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$payload = $this->decodePayload($request);
					$allow = null;
					if (array_key_exists('wh_allow_negative_stock', $payload)) {
						$allow = $payload['wh_allow_negative_stock'];
					} else if ($request->has('wh_allow_negative_stock')) {
						$allow = $request->get('wh_allow_negative_stock');
					}
					$expiryDays = null;
					if (array_key_exists('wh_expiry_warn_days', $payload)) {
						$expiryDays = $payload['wh_expiry_warn_days'];
					} else if ($request->has('wh_expiry_warn_days')) {
						$expiryDays = $request->get('wh_expiry_warn_days');
					}
					$slowKeys = array(
						'wh_slow_window_days', 'wh_doi_threshold', 'wh_dsi_threshold', 'wh_age_max',
						'wh_risk_w1', 'wh_risk_w2', 'wh_risk_w3', 'wh_risk_w4',
					);
					$slowPartial = array();
					foreach ($slowKeys as $sk) {
						if (array_key_exists($sk, $payload)) {
							$slowPartial[$sk] = $payload[$sk];
						} else if ($request->has($sk)) {
							$slowPartial[$sk] = $request->get($sk);
						}
					}
					if ($allow === null && $expiryDays === null && empty($slowPartial)) {
						throw new Exception('Thiếu cấu hình kho.');
					}
					if ($allow !== null) {
						$enabled = in_array(strtolower(trim((string) $allow)), array('1', 'true', 'yes', 'on'), true)
							|| $allow === 1 || $allow === true;
						Warehouse_Settings_Helper::setAllowNegativeStock($enabled, $userId);
					}
					if ($expiryDays !== null && $expiryDays !== '') {
						Warehouse_Settings_Helper::setExpiryWarnDays((int) $expiryDays, $userId);
					}
					if (!empty($slowPartial)) {
						Warehouse_Settings_Helper::setSlowMovingConfig($slowPartial, $userId);
					}
					$response->setResult(array(
						'success' => true,
						'settings' => Warehouse_WhMgmtService::publicSettings(),
					));
					break;

				case 'search_return_sources':
					require_once 'modules/Warehouse/helpers/ReturnHelper.php';
					$q = trim((string) $request->get('q'));
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$payload = $this->decodePayload($request);
					if ($q === '' && isset($payload['q'])) {
						$q = trim((string) $payload['q']);
					}
					if ($whId === '' && isset($payload['whId'])) {
						$whId = trim((string) $payload['whId']);
					}
					$response->setResult(array(
						'success' => true,
						'issues' => Warehouse_Return_Helper::searchOutboundIssues($whId, $q),
						'sources' => array(),
					));
					break;

				case 'save_return':
					require_once 'modules/Warehouse/helpers/ReturnHelper.php';
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$payload = $this->decodePayload($request);
					$result = Warehouse_Return_Helper::save($whId, $payload, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'return_action':
					require_once 'modules/Warehouse/helpers/ReturnHelper.php';
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$action = strtolower(trim((string) $request->get('actionKey')));
					if ($action === 'confirm') {
						$result = Warehouse_Return_Helper::confirm($whId, $code, $userId);
					} else if ($action === 'cancel') {
						$result = Warehouse_Return_Helper::cancel($whId, $code);
					} else {
						throw new Exception('Hành động phiếu thu hồi không hợp lệ.');
					}
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'qc_upload_image':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$role = trim((string) $request->get('role'));
					if (!isset($_FILES['qcImage']) || !is_array($_FILES['qcImage'])) {
						throw new Exception('Không có file ảnh.');
					}
					$result = Warehouse_WhMgmtService::uploadQcImage($whId, $code, $_FILES['qcImage'], $userId, $role);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'qc_delete_image':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$imageId = trim((string) $request->get('imageId'));
					$result = Warehouse_WhMgmtService::deleteQcImage($whId, $code, $imageId, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'qc_update':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$code = trim((string) $request->get('code'));
					$role = trim((string) $request->get('role'));
					$note = $this->readActionNote($request);
					$result = Warehouse_WhMgmtService::updateQcRecord($whId, $code, $note, $userId, $role);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'set_stock_fill':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$openRaw = $request->get('open');
					$open = !($openRaw === '0' || $openRaw === 0 || $openRaw === false || $openRaw === 'false');
					$result = Warehouse_WhMgmtService::setStockFillOpen($whId, $open, $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'save_stock_fill':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$productKey = trim((string) $request->get('stockKey'));
					$result = Warehouse_WhMgmtService::saveStockFill($whId, $productKey, array(
						'expiry' => $request->get('expiry'),
						'location' => $request->get('location'),
						'lot' => $request->get('lot'),
					), $userId);
					$response->setResult(array_merge(array('success' => true), $result));
					break;

				case 'import_stock_excel':
					global $current_user;
					$userId = isset($current_user->id) ? (int) $current_user->id : 0;
					$whId = trim((string) $request->get('whId'));
					if ($whId === '') {
						$whId = trim((string) $request->get('id'));
					}
					$wipeRaw = $request->get('wipe');
					$wipe = !($wipeRaw === '0' || $wipeRaw === 0 || $wipeRaw === false || $wipeRaw === 'false');
					if (!isset($_FILES['stockExcel']) || !is_array($_FILES['stockExcel'])) {
						throw new Exception('Chưa chọn file Excel.');
					}
					$result = Warehouse_WhMgmtService::importStockFromExcel($whId, $_FILES['stockExcel'], $wipe, $userId);
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
