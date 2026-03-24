<?php
class GoodsReceipt_Edit_View extends Vtiger_Index_View {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}
	public function preProcess(Vtiger_Request $request, $display = true) { parent::preProcess($request, $display); }
	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$recordId = (int) $request->get('record');
		$mode = ($recordId > 0) ? 'edit' : 'new';

		$record = array(
			'receiptid' => 0,
			'subject' => '',
			'source_name' => '',
			'received_date' => date('Y-m-d'),
			'storage_location' => '',
			'note' => '',
		);
		$items = array();

		if ($recordId > 0) {
			$rs = $db->pquery("SELECT * FROM vtiger_goodsreceipt WHERE receiptid = ? AND deleted = 0", array($recordId));
			if ($db->num_rows($rs) > 0) {
				$record = $db->fetchByAssoc($rs);
			}
			$ri = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ? ORDER BY itemid ASC", array($recordId));
			while ($row = $db->fetchByAssoc($ri)) {
				$items[] = $row;
			}
		}

		if (empty($items)) {
			$items[] = array('productid' => '', 'product_name' => '', 'quantity' => '1', 'unit_price' => '0', 'line_note' => '');
		}

		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD', $record);
		$viewer->assign('ITEMS', $items);
		$viewer->view('EditView.tpl', $request->getModule());
	}
}

