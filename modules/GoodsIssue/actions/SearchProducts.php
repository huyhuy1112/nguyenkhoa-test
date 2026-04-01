<?php

/**
 * Outbound line-item product picker: warehouse stock only, available qty > 0.
 *
 * - Empty q: return a limited default list (ordered by name) so the field can show storage on focus.
 * - Non-empty q: prefix match on product name (LIKE 'q%'), optional warehouse code + catalog productcode.
 * Does not search serial numbers.
 */
class GoodsIssue_SearchProducts_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return;
	}

	protected function normalizeTypeLabel($value) {
		$v = strtolower(trim((string) $value));
		if ($v === '') {
			return 'Other';
		}
		if (in_array($v, array('hardware', 'product', 'products'), true)) {
			return 'Hardware';
		}
		if ($v === 'software') {
			return 'Software';
		}
		if (in_array($v, array('service', 'services'), true)) {
			return 'Service';
		}
		return 'Other';
	}

	/**
	 * @param array $row
	 * @return array|null
	 */
	protected function rowToOption(array $row) {
		$quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
		$shrink = isset($row['shrinkage_qty']) ? (float) $row['shrinkage_qty'] : 0.0;
		$available = $quantity - $shrink;
		if ($available <= 0) {
			return null;
		}

		return array(
			'stockid' => (int) $row['stockid'],
			'product_key' => (string) $row['product_key'],
			'productid' => !empty($row['productid']) ? (int) $row['productid'] : 0,
			'name' => (string) $row['product_name'],
			'type' => $this->normalizeTypeLabel($row['product_type']),
			'available_qty' => (float) $available,
			'stock_location' => isset($row['storage_location']) ? (string) $row['storage_location'] : '',
			'unit_price' => isset($row['last_price']) ? (float) $row['last_price'] : 0.0,
			'identity_type' => !empty($row['productid']) ? 'catalog' : 'legacy',
		);
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$q = trim((string) $request->get('q'));
		$q = preg_replace('/[%_\\\\]/', '', $q);

		$options = array();

		$baseSelect = "SELECT ws.stockid, ws.product_key, ws.productid, ws.product_name, ws.product_type,
				ws.quantity, ws.shrinkage_qty, ws.storage_location, ws.last_price, ws.code";

		if ($q === '') {
			// Default: show available storage rows (lightweight; not serial search).
			$sql = $baseSelect . "
				FROM vtiger_warehouse_stock ws
				WHERE (ws.quantity - COALESCE(ws.shrinkage_qty, 0)) > 0
				ORDER BY ws.product_name ASC
				LIMIT 100";
			$rs = $db->pquery($sql, array());
		} else {
			$prefix = $q . '%';
			$sql = $baseSelect . "
				FROM vtiger_warehouse_stock ws
				LEFT JOIN vtiger_products p ON p.productid = ws.productid
				LEFT JOIN vtiger_crmentity ce ON ce.crmid = p.productid AND ce.setype = 'Products'
				WHERE (ws.quantity - COALESCE(ws.shrinkage_qty, 0)) > 0
				AND (
					ws.product_name LIKE ?
					OR (ws.code IS NOT NULL AND TRIM(ws.code) <> '' AND ws.code LIKE ?)
					OR (p.productid IS NOT NULL AND p.productcode IS NOT NULL AND TRIM(p.productcode) <> ''
						AND p.productcode LIKE ? AND ce.deleted = 0)
				)
				ORDER BY ws.product_name ASC
				LIMIT 50";
			$rs = $db->pquery($sql, array($prefix, $prefix, $prefix));
		}

		if ($rs) {
			while ($row = $db->fetchByAssoc($rs)) {
				$opt = $this->rowToOption($row);
				if ($opt !== null) {
					$options[] = $opt;
				}
			}
		}

		$response = array(
			'success' => true,
			'result' => array(
				'options' => $options,
				'mode' => ($q === '') ? 'default' : 'prefix',
			),
		);

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($response);
	}
}
