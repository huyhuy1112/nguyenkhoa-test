<?php
class GoodsReceipt_Edit_View extends Vtiger_Index_View {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPreProcess.tpl';
	}
	public function preProcess(Vtiger_Request $request, $display = true) { parent::preProcess($request, $display); }
	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('IndexPostProcess.tpl', $request->getModule());
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
			'code' => '',
			'subject' => '',
			'source_name' => '',
			'received_date' => date('Y-m-d'),
			'storage_location' => '',
			'note' => '',
		);
		$items = array();
		$attachments = array();
		$productOptions = array();

		if ($recordId > 0) {
			$rs = $db->pquery("SELECT * FROM vtiger_goodsreceipt WHERE receiptid = ? AND deleted = 0", array($recordId));
			if ($db->num_rows($rs) > 0) {
				$record = $db->fetchByAssoc($rs);
			}
			$ri = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ? ORDER BY itemid ASC", array($recordId));
			while ($row = $db->fetchByAssoc($ri)) {
				if (!isset($row['product_type']) || $row['product_type'] === '') {
					$row['product_type'] = 'Other';
				}
				$items[] = $row;
			}
			$ra = $db->pquery(
				"SELECT attachmentid, filename, filetype, filesize, createdtime
				 FROM vtiger_goodsreceipt_attachments
				 WHERE receiptid = ? AND deleted = 0
				 ORDER BY createdtime DESC, attachmentid DESC",
				array($recordId)
			);
			while ($row = $db->fetchByAssoc($ra)) {
				$attachments[] = $row;
			}
		}

		if (empty($items)) {
			$items[] = array('productid' => '', 'product_name' => '', 'product_type' => 'Other', 'quantity' => '1', 'unit_price' => '0', 'line_note' => '');
		}

		$rp = $db->pquery(
			"SELECT productsservicesid, productsservicesname, item_type
			 FROM vtiger_productsservices
			 ORDER BY productsservicesname ASC",
			array()
		);
		while ($p = $db->fetchByAssoc($rp)) {
			$type = isset($p['item_type']) ? strtolower(trim((string) $p['item_type'])) : '';
			if ($type === 'product' || $type === 'products') {
				$type = 'Hardware';
			} elseif ($type === 'service' || $type === 'services') {
				$type = 'Service';
			} elseif ($type === 'software') {
				$type = 'Software';
			} else {
				$type = 'Other';
			}
			$productOptions[] = array(
				'id' => (int) $p['productsservicesid'],
				'name' => (string) $p['productsservicesname'],
				'type' => $type,
			);
		}

		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD', $record);
		$viewer->assign('ITEMS', $items);
		$viewer->assign('PRODUCT_OPTIONS', $productOptions);
		$viewer->assign('ATTACHMENTS', $attachments);
		$viewer->view('EditView.tpl', $request->getModule());
	}
}

