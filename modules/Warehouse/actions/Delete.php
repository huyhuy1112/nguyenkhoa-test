<?php
class Warehouse_Delete_Action extends Vtiger_Action_Controller {

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
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$stockId = (int) $request->get('record');
		if ($stockId <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}

		$rs = $db->pquery(
			'SELECT quantity, COALESCE(shrinkage_qty, 0) AS shrinkage_qty FROM vtiger_warehouse_stock WHERE stockid = ?',
			array($stockId)
		);
		if ($db->num_rows($rs) <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY&deleteError=1');
			exit;
		}
		$qty = (float) $db->query_result($rs, 0, 'quantity');
		$sh = (float) $db->query_result($rs, 0, 'shrinkage_qty');
		if (abs($qty) > 0.0000001 || abs($sh) > 0.0000001) {
			header('Location: index.php?module=Warehouse&view=Detail&record=' . $stockId . '&app=INVENTORY&deleteBlocked=1');
			exit;
		}

		$db->pquery('DELETE FROM vtiger_warehouse_stock WHERE stockid = ?', array($stockId));
		header('Location: index.php?module=Warehouse&view=List&app=INVENTORY&deleted=1');
		exit;
	}
}
