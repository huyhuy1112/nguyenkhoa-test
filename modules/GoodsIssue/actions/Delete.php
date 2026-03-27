<?php
class GoodsIssue_Delete_Action extends Vtiger_Action_Controller {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	protected function itemKey(array $item) {
		if (!empty($item['productid'])) {
			return 'P:' . (int) $item['productid'];
		}
		return 'N:' . mb_strtolower(trim((string) $item['product_name']));
	}

	protected function aggregateByKey(array $items) {
		$agg = array();
		foreach ($items as $it) {
			$k = $this->itemKey($it);
			if (!isset($agg[$k])) $agg[$k] = 0.0;
			$agg[$k] += (float) $it['quantity'];
		}
		return $agg;
	}

	protected function loadItems(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT productid, product_name, quantity
			 FROM vtiger_goodsissue_items
			 WHERE issueid = ?",
			array($issueId)
		);
		$items = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$items[] = array(
				'productid' => !empty($row['productid']) ? (int) $row['productid'] : null,
				'product_name' => (string) $row['product_name'],
				'quantity' => (float) $row['quantity'],
			);
		}
		return $items;
	}

	protected function loadStockRowsByKeys(PearDatabase $db, array $keys) {
		$keys = array_values(array_unique(array_filter($keys)));
		if (empty($keys)) return array();
		$placeholders = implode(',', array_fill(0, count($keys), '?'));
		$rs = $db->pquery(
			"SELECT stockid, product_key
			 FROM vtiger_warehouse_stock
			 WHERE product_key IN ($placeholders)",
			$keys
		);
		$map = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$map[(string) $row['product_key']] = (int) $row['stockid'];
		}
		return $map;
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');

		$issueId = (int) $request->get('record');
		if ($issueId <= 0) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		// Only delete non-deleted issues (idempotent).
		$hdr = $db->pquery("SELECT deleted FROM vtiger_goodsissue WHERE issueid = ? LIMIT 1", array($issueId));
		if ($db->num_rows($hdr) <= 0) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY&delete_error=1');
			exit;
		}
		$isDeleted = (int) $db->query_result($hdr, 0, 'deleted') === 1;
		if ($isDeleted) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY&deleted=1');
			exit;
		}

		$items = $this->loadItems($db, $issueId);
		$agg = $this->aggregateByKey($items);
		$keys = array_keys($agg);
		$stockIdByKey = $this->loadStockRowsByKeys($db, $keys);

		// If any stock row missing, block (do not create stock rows).
		foreach ($agg as $k => $qty) {
			if (!isset($stockIdByKey[$k])) {
				header('Location: index.php?module=GoodsIssue&view=Detail&record=' . $issueId . '&app=INVENTORY&deleteBlocked=1');
				exit;
			}
		}

		$db->startTransaction();
		try {
			// Restore stock
			foreach ($agg as $k => $qty) {
				$stockId = (int) $stockIdByKey[$k];
				$db->pquery(
					"UPDATE vtiger_warehouse_stock
					 SET quantity = quantity + ?, updatedby = ?, updatedtime = ?
					 WHERE stockid = ?",
					array((float) $qty, $userId, $now, $stockId)
				);
			}

			// Soft delete header
			$db->pquery(
				"UPDATE vtiger_goodsissue SET deleted = 1, updatedby = ?, updatedtime = ? WHERE issueid = ?",
				array($userId, $now, $issueId)
			);

			$db->completeTransaction();
		} catch (Throwable $e) {
			$db->rollbackTransaction();
			header('Location: index.php?module=GoodsIssue&view=Detail&record=' . $issueId . '&app=INVENTORY&delete_error=1');
			exit;
		}

		header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY&deleted=1');
		exit;
	}
}

?>

