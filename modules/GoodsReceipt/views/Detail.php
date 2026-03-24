<?php
class GoodsReceipt_Detail_View extends Vtiger_Index_View {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	protected function preProcessTplName(Vtiger_Request $request) { return 'ListViewPreProcess.tpl'; }
	public function preProcess(Vtiger_Request $request, $display = true) { parent::preProcess($request, $display); }
	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$recordId = (int) $request->get('record');

		$rs = $db->pquery("SELECT * FROM vtiger_goodsreceipt WHERE receiptid = ? AND deleted = 0", array($recordId));
		if ($db->num_rows($rs) <= 0) {
			header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY');
			exit;
		}
		$record = $db->fetchByAssoc($rs);
		$recordModel = new Vtiger_Record_Model();
		$recordModel->setModule('GoodsReceipt');
		$recordModel->setId($recordId);
		$recordModel->set('label', (string) $record['subject']);
		$recordModel->setData($record);

		$ri = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ? ORDER BY itemid ASC", array($recordId));
		$items = array();
		while ($row = $db->fetchByAssoc($ri)) {
			$row['line_total'] = (float) $row['quantity'] * (float) $row['unit_price'];
			$items[] = $row;
		}

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_DATA', $record);
		$viewer->assign('ITEMS', $items);
		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}
}

