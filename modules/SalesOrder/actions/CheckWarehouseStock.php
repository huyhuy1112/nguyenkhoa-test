<?php
class SalesOrder_CheckWarehouseStock_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
		require_once 'modules/GoodsIssue/helpers/CreateFromSalesOrder.php';

		$warehouseId = trim((string) $request->get('warehouse_id'));
		$warehouse = Warehouse_Registry::findById($warehouseId);
		$warehouseName = $warehouse ? (string) $warehouse['name'] : '';

		$productIds = $request->get('product_id');
		$productNames = $request->get('product_name');
		$quantities = $request->get('quantity');
		if (!is_array($productIds)) {
			$productIds = array($productIds);
		}
		if (!is_array($productNames)) {
			$productNames = array($productNames);
		}
		if (!is_array($quantities)) {
			$quantities = array($quantities);
		}

		$lines = array();
		$count = max(count($productIds), count($productNames), count($quantities));
		for ($i = 0; $i < $count; $i++) {
			$qty = (float) (isset($quantities[$i]) ? $quantities[$i] : 0);
			if ($qty <= 0) {
				continue;
			}
			$lines[] = array(
				'productid' => (int) (isset($productIds[$i]) ? $productIds[$i] : 0),
				'product_name' => trim((string) (isset($productNames[$i]) ? $productNames[$i] : '')),
				'quantity' => $qty,
			);
		}

		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$db = PearDatabase::getInstance();
		$resultLines = array();
		$allOk = true;
		foreach ($lines as $line) {
			$available = Warehouse_Stock_Helper::sumAvailableQtyForProductAtWarehouse(
				$db,
				(int) $line['productid'],
				(string) $line['product_name'],
				$warehouseName
			);
			$ok = $available >= (float) $line['quantity'];
			if (!$ok) {
				$allOk = false;
			}
			$resultLines[] = array(
				'product_id' => (int) $line['productid'],
				'product_name' => (string) $line['product_name'],
				'quantity' => (float) $line['quantity'],
				'available' => $available,
				'ok' => $ok,
			);
		}

		$errors = GoodsIssue_CreateFromSalesOrder_Helper::validateWarehouseStock($lines, $warehouseName);

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => $allOk && empty($errors),
			'warehouse' => $warehouse,
			'lines' => $resultLines,
			'errors' => $errors,
		));
		$response->emit();
	}
}

?>
