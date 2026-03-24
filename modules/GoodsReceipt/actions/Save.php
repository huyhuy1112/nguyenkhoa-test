<?php
class GoodsReceipt_Save_Action extends Vtiger_Action_Controller {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	protected function parseItems(Vtiger_Request $request) {
		$items = array();
		$names = $request->get('item_product_name');
		$ids = $request->get('item_productid');
		$qtys = $request->get('item_quantity');
		$prices = $request->get('item_unit_price');
		$notes = $request->get('item_line_note');

		if (!is_array($names)) {
			return array();
		}
		$count = count($names);
		for ($i = 0; $i < $count; $i++) {
			$name = trim((string) $names[$i]);
			$qty = (float) (isset($qtys[$i]) ? $qtys[$i] : 0);
			$price = (float) (isset($prices[$i]) ? $prices[$i] : 0);
			$productId = (int) (isset($ids[$i]) ? $ids[$i] : 0);
			$note = isset($notes[$i]) ? (string) $notes[$i] : '';
			if ($name === '' || $qty <= 0) {
				continue;
			}
			$items[] = array(
				'productid' => $productId > 0 ? $productId : null,
				'product_name' => $name,
				'quantity' => $qty,
				'unit_price' => $price,
				'line_note' => $note,
			);
		}
		return $items;
	}

	protected function itemKey(array $item) {
		if (!empty($item['productid'])) {
			return 'P:' . (int) $item['productid'];
		}
		return 'N:' . mb_strtolower(trim((string) $item['product_name']));
	}

	protected function aggregateByProduct(array $items) {
		$agg = array();
		foreach ($items as $item) {
			$key = $this->itemKey($item);
			if (!isset($agg[$key])) {
				$agg[$key] = array(
					'product_key' => $key,
					'productid' => !empty($item['productid']) ? (int) $item['productid'] : null,
					'product_name' => (string) $item['product_name'],
					'quantity' => 0.0,
					'last_price' => 0.0,
				);
			}
			$agg[$key]['quantity'] += (float) $item['quantity'];
			$agg[$key]['last_price'] = (float) $item['unit_price'];
		}
		return $agg;
	}

	public function applyStockDelta(PearDatabase $db, array $oldItems, array $newItems, $userId, $now) {
		$oldAgg = $this->aggregateByProduct($oldItems);
		$newAgg = $this->aggregateByProduct($newItems);
		$keys = array_unique(array_merge(array_keys($oldAgg), array_keys($newAgg)));

		foreach ($keys as $key) {
			$oldQty = isset($oldAgg[$key]) ? (float) $oldAgg[$key]['quantity'] : 0.0;
			$newQty = isset($newAgg[$key]) ? (float) $newAgg[$key]['quantity'] : 0.0;
			$delta = $newQty - $oldQty;
			if (abs($delta) < 0.00000001) {
				continue;
			}

			$stockRow = isset($newAgg[$key]) ? $newAgg[$key] : $oldAgg[$key];
			$check = $db->pquery("SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ?", array($key));
			if ($db->num_rows($check) > 0) {
				$stockId = (int) $db->query_result($check, 0, 'stockid');
				$currentQty = (float) $db->query_result($check, 0, 'quantity');
				$nextQty = $currentQty + $delta;
				if ($nextQty < 0) $nextQty = 0;
				$db->pquery(
					"UPDATE vtiger_warehouse_stock
					 SET quantity = ?, product_name = ?, last_price = ?, updatedby = ?, updatedtime = ?
					 WHERE stockid = ?",
					array($nextQty, $stockRow['product_name'], (float) $stockRow['last_price'], $userId, $now, $stockId)
				);
			} else {
				$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
				$qty = $delta > 0 ? $delta : 0;
				$db->pquery(
					"INSERT INTO vtiger_warehouse_stock(stockid, product_key, productid, product_name, quantity, last_price, createdtime, updatedtime, updatedby)
					 VALUES(?,?,?,?,?,?,?,?,?)",
					array($stockId, $key, $stockRow['productid'], $stockRow['product_name'], $qty, (float) $stockRow['last_price'], $now, $now, $userId)
				);
			}
		}
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');
		$recordId = (int) $request->get('record');
		$subject = trim((string) $request->get('subject'));
		$sourceName = trim((string) $request->get('source_name'));
		$receivedDate = trim((string) $request->get('received_date'));
		$storageLocation = trim((string) $request->get('storage_location'));
		$note = (string) $request->get('note');
		$items = $this->parseItems($request);

		if ($subject === '' || empty($items)) {
			header('Location: index.php?module=GoodsReceipt&view=Edit&app=INVENTORY&record=' . $recordId . '&validation=1');
			exit;
		}

		$oldItems = array();
		if ($recordId > 0) {
			$rsOld = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ?", array($recordId));
			while ($r = $db->fetchByAssoc($rsOld)) {
				$oldItems[] = $r;
			}
		}

		if ($recordId > 0) {
			$db->pquery(
				"UPDATE vtiger_goodsreceipt SET subject=?, source_name=?, received_date=?, storage_location=?, note=?, updatedby=?, updatedtime=? WHERE receiptid=?",
				array($subject, $sourceName, $receivedDate, $storageLocation, $note, $userId, $now, $recordId)
			);
			$db->pquery("DELETE FROM vtiger_goodsreceipt_items WHERE receiptid = ?", array($recordId));
		} else {
			$recordId = (int) $db->getUniqueID('vtiger_goodsreceipt');
			$db->pquery(
				"INSERT INTO vtiger_goodsreceipt(receiptid, subject, source_name, received_date, storage_location, note, createdby, updatedby, createdtime, updatedtime, deleted)
				 VALUES(?,?,?,?,?,?,?,?,?,?,0)",
				array($recordId, $subject, $sourceName, $receivedDate, $storageLocation, $note, $userId, $userId, $now, $now)
			);
		}

		foreach ($items as $item) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsreceipt_items');
			$db->pquery(
				"INSERT INTO vtiger_goodsreceipt_items(itemid, receiptid, productid, product_name, quantity, unit_price, line_note)
				 VALUES(?,?,?,?,?,?,?)",
				array($itemId, $recordId, $item['productid'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['line_note'])
			);
		}

		$this->applyStockDelta($db, $oldItems, $items, $userId, $now);
		header('Location: index.php?module=GoodsReceipt&view=Detail&record=' . $recordId . '&app=INVENTORY');
		exit;
	}
}

