<?php
class GoodsReceipt_Edit_View extends Vtiger_Index_View {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function isInventoryApp(Vtiger_Request $request) {
		$appName = $request->get('app');
		return ($appName === 'INVENTORY' || $appName === '');
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		if ($this->isInventoryApp($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return 'IndexViewPreProcess.tpl';
	}

	protected function assignInventoryContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if ($moduleModel) {
			$viewer->assign('MODULE_MODEL', $moduleModel);
		}
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignInventoryContext($request);
		if ($this->isInventoryApp($request)) {
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'INVENTORY');
			$viewer->assign('MK_INV_NAV_ACTIVE', 'GoodsReceipt');
			$viewer->assign('LINKED_INBOUND_RECEIPT_ID', (int) $request->get('record'));
		}
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if ($this->isInventoryApp($request)) {
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
		} else {
			$viewer->view('IndexPostProcess.tpl', $request->getModule());
		}
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
			$items[] = array(
				'productid' => '',
				'product_name' => '',
				'product_type' => 'Other',
				'quantity' => '1',
				'unit_price' => '0',
				'line_note' => '',
				'serial_number' => '',
				'expired_date' => '',
				'description' => '',
			);
		}

		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD', $record);
		$viewer->assign('ITEMS', $items);
		$viewer->assign('ATTACHMENTS', $attachments);
		$viewer->view('EditView.tpl', $request->getModule());
	}
}
