<?php
class GoodsReceipt_Delete_Action extends Vtiger_Action_Controller {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/actions/Save.php';
		$db = PearDatabase::getInstance();
		$recordId = (int) $request->get('record');
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');

		$rs = $db->pquery("SELECT * FROM vtiger_goodsreceipt WHERE receiptid = ? AND deleted = 0", array($recordId));
		if ($db->num_rows($rs) <= 0) {
			header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY');
			exit;
		}
		$items = array();
		$ri = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ?", array($recordId));
		while ($row = $db->fetchByAssoc($ri)) $items[] = $row;

		$saveAction = new GoodsReceipt_Save_Action();
		$saveAction->applyStockDelta($db, $items, array(), $userId, $now);
		$db->pquery("UPDATE vtiger_goodsreceipt SET deleted = 1, updatedby = ?, updatedtime = ? WHERE receiptid = ?", array($userId, $now, $recordId));
		header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY&deleted=1');
		exit;
	}
}

