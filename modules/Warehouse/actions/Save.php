<?php
class Warehouse_Save_Action extends Vtiger_Action_Controller {

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
		$location = trim((string) $request->get('storage_location'));
		$note = (string) $request->get('warehouse_note');
		$shrink = (float) $request->get('shrinkage_qty');

		if ($stockId <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}

		$rs = $db->pquery('SELECT quantity FROM vtiger_warehouse_stock WHERE stockid = ?', array($stockId));
		if ($db->num_rows($rs) <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}
		$qty = (float) $db->query_result($rs, 0, 'quantity');
		if ($shrink < 0) {
			$shrink = 0;
		}
		if ($shrink > $qty) {
			$shrink = $qty;
		}

		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');
		$db->pquery(
			'UPDATE vtiger_warehouse_stock SET storage_location = ?, warehouse_note = ?, shrinkage_qty = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
			array($location, $note, $shrink, $userId, $now, $stockId)
		);
		header('Location: index.php?module=Warehouse&view=Detail&record=' . $stockId . '&app=INVENTORY&saved=1');
		exit;
	}
}
