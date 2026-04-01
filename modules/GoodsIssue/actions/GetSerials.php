<?php

class GoodsIssue_GetSerials_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return;
	}

	/**
	 * Distinct serials from a query returning serial_number.
	 *
	 * @param PearDatabase $db
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	protected function loadSerialsFromQuery(PearDatabase $db, $sql, array $params) {
		$serials = array();
		$rs = $db->pquery($sql, $params);
		if (!$rs) {
			return $serials;
		}
		while ($row = $db->fetchByAssoc($rs)) {
			$s = isset($row['serial_number']) ? trim((string) $row['serial_number']) : '';
			if ($s !== '') {
				$serials[] = $s;
			}
		}
		return $serials;
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$productId = (int) $request->get('productid');
		$productName = trim((string) $request->get('product_name'));
		$productKey = trim((string) $request->get('product_key'));
		$productType = trim((string) $request->get('product_type'));

		if ($productId <= 0 && $productKey !== '' && strpos($productKey, 'P:') === 0) {
			$productId = (int) substr($productKey, 2);
		}

		$lookupName = $productName;
		if ($lookupName === '' && $productKey !== '' && strpos($productKey, 'N:') === 0) {
			$lookupName = trim(substr($productKey, 2));
		}

		$serials = array();

		if ($productId > 0) {
			$sql = "SELECT DISTINCT gri.serial_number
				FROM vtiger_goodsreceipt_items gri
				INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				WHERE gri.productid = ? AND gri.serial_number IS NOT NULL AND TRIM(gri.serial_number) <> ''
				ORDER BY gri.serial_number ASC";
			$serials = $this->loadSerialsFromQuery($db, $sql, array($productId));
		}

		if (empty($serials) && $lookupName !== '') {
			$sqlBase = "SELECT DISTINCT gri.serial_number
				FROM vtiger_goodsreceipt_items gri
				INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
				WHERE gri.serial_number IS NOT NULL AND TRIM(gri.serial_number) <> ''
				AND LOWER(TRIM(COALESCE(gri.product_name,''))) = LOWER(TRIM(?))";

			if ($productType !== '') {
				$sql = $sqlBase . " AND LOWER(TRIM(COALESCE(gri.product_type,''))) = LOWER(TRIM(?)) ORDER BY gri.serial_number ASC";
				$serials = $this->loadSerialsFromQuery($db, $sql, array($lookupName, $productType));
			}
			if (empty($serials)) {
				$sql = $sqlBase . " ORDER BY gri.serial_number ASC";
				$serials = $this->loadSerialsFromQuery($db, $sql, array($lookupName));
			}
		}

		$serials = array_values(array_unique($serials));

		$resultRows = array();
		foreach ($serials as $s) {
			$resultRows[] = array('serial' => $s);
		}

		$response = array(
			'success' => true,
			'result' => $resultRows,
			'serials' => $serials,
		);

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($response);
	}
}
