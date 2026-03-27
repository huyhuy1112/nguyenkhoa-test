<?php
/**
 * Soft migration: attach legacy name-based stock (N:*) to a ProductsServices catalog id (P:*).
 * Updates matching inbound line items and merges or re-keys the stock row. Does not touch attachments.
 *
 * Limitation: line match uses exact legacy product_name; two different products with identical free-text
 * names would both be updated — rare; operator should verify before linking.
 */
class Warehouse_LinkProduct_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$stockId = (int) $request->get('record');
		$linkPid = (int) $request->get('link_productid');
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');

		if ($stockId <= 0 || $linkPid <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY&linkError=1');
			exit;
		}

		$rs = $db->pquery('SELECT * FROM vtiger_warehouse_stock WHERE stockid = ?', array($stockId));
		if ($db->num_rows($rs) <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY&linkError=1');
			exit;
		}
		$legacy = $db->fetchByAssoc($rs);
		if (!Warehouse_Stock_Helper::isLegacyNameKey($legacy)) {
			header('Location: index.php?module=Warehouse&view=Detail&record=' . $stockId . '&app=INVENTORY&linkError=not_legacy');
			exit;
		}

		$pr = $db->pquery(
			'SELECT productsservicesname, item_type FROM vtiger_productsservices WHERE productsservicesid = ?',
			array($linkPid)
		);
		if ($db->num_rows($pr) <= 0) {
			header('Location: index.php?module=Warehouse&view=Detail&record=' . $stockId . '&app=INVENTORY&linkError=invalid_product');
			exit;
		}
		$canonicalName = (string) $db->query_result($pr, 0, 'productsservicesname');
		$itemTypeRaw = (string) $db->query_result($pr, 0, 'item_type');
		$lineType = Warehouse_Stock_Helper::mapCatalogItemTypeToLabel($itemTypeRaw);
		$storageType = $lineType;

		$legacyName = isset($legacy['product_name']) ? (string) $legacy['product_name'] : '';
		$db->pquery(
			'UPDATE vtiger_goodsreceipt_items SET productid = ?, product_name = ?, product_type = ?
			 WHERE (productid IS NULL OR productid = 0) AND product_name = ?',
			array($linkPid, $canonicalName, $lineType, $legacyName)
		);

		$newKey = 'P:' . $linkPid;
		$existing = $db->pquery(
			'SELECT stockid, quantity, COALESCE(shrinkage_qty, 0) AS shrinkage_qty, last_price, updatedtime
			 FROM vtiger_warehouse_stock WHERE product_key = ? AND stockid <> ?',
			array($newKey, $stockId)
		);

		if ($db->num_rows($existing) > 0) {
			$targetId = (int) $db->query_result($existing, 0, 'stockid');
			$tQty = (float) $db->query_result($existing, 0, 'quantity');
			$tShrink = (float) $db->query_result($existing, 0, 'shrinkage_qty');
			$tLp = (float) $db->query_result($existing, 0, 'last_price');
			$tUpd = (string) $db->query_result($existing, 0, 'updatedtime');
			$lQty = (float) $legacy['quantity'];
			$lShrink = (float) (isset($legacy['shrinkage_qty']) ? $legacy['shrinkage_qty'] : 0);
			$lLp = (float) $legacy['last_price'];
			$lUpd = isset($legacy['updatedtime']) ? (string) $legacy['updatedtime'] : '';

			$newQty = $tQty + $lQty;
			$newShrink = $tShrink + $lShrink;
			if ($newShrink > $newQty) {
				$newShrink = $newQty;
			}
			$tsL = strtotime($lUpd);
			$tsT = strtotime($tUpd);
			$newLp = ($tsL !== false && $tsT !== false && $tsL >= $tsT) ? $lLp : $tLp;

			$tNote = '';
			$tNb = $db->pquery('SELECT warehouse_note FROM vtiger_warehouse_stock WHERE stockid = ?', array($targetId));
			if ($db->num_rows($tNb) > 0) {
				$tNote = (string) $db->query_result($tNb, 0, 'warehouse_note');
			}
			$lNote = isset($legacy['warehouse_note']) ? (string) $legacy['warehouse_note'] : '';
			$mergedNote = $tNote;
			if ($lNote !== '') {
				$mergedNote = trim($tNote) === '' ? $lNote : trim($tNote) . "\n\n--- merged from legacy row ---\n" . $lNote;
			}

			$db->pquery(
				'UPDATE vtiger_warehouse_stock SET quantity = ?, shrinkage_qty = ?, last_price = ?,
					product_name = ?, product_type = ?, productid = ?, warehouse_note = ?, updatedby = ?, updatedtime = ?
				 WHERE stockid = ?',
				array($newQty, $newShrink, $newLp, $canonicalName, $storageType, $linkPid, $mergedNote, $userId, $now, $targetId)
			);
			$db->pquery('DELETE FROM vtiger_warehouse_stock WHERE stockid = ?', array($stockId));
			header('Location: index.php?module=Warehouse&view=Detail&record=' . $targetId . '&app=INVENTORY&linkSuccess=1');
			exit;
		}

		$db->pquery(
			'UPDATE vtiger_warehouse_stock SET product_key = ?, productid = ?, product_name = ?, product_type = ?,
				updatedby = ?, updatedtime = ? WHERE stockid = ?',
			array($newKey, $linkPid, $canonicalName, $storageType, $userId, $now, $stockId)
		);
		header('Location: index.php?module=Warehouse&view=Detail&record=' . $stockId . '&app=INVENTORY&linkSuccess=1');
		exit;
	}
}
