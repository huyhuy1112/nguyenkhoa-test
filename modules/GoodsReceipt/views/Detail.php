<?php
class GoodsReceipt_Detail_View extends Vtiger_Index_View {
	protected $detailRecordData = null;
	protected $detailRecordModel = null;

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function isInventoryApp(Vtiger_Request $request) {
		$appName = $request->get('app');
		return ($appName === 'INVENTORY' || $appName === '');
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		if ($this->isInventoryApp($request)) {
			return 'DetailViewPreProcess.tpl';
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
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'INVENTORY');
		$viewer->assign('VIEW', 'Detail');
		$viewer->assign('NO_PAGINATION', true);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY');
			exit;
		}

		$viewer = $this->getViewer($request);

		$record = $this->getReceiptById($recordId);
		if (!$record) {
			header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY');
			exit;
		}

		$this->detailRecordData = $record;
		$this->detailRecordModel = $this->buildRecordModel($recordId, $record);
		$viewer->assign('RECORD', $this->detailRecordModel);
		$viewer->assign('RECORD_MODEL', $this->detailRecordModel);
		$viewer->assign('RECORD_DATA', $this->detailRecordData);

		$this->assignInventoryContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if ($this->isInventoryApp($request)) {
			$viewer->view('DetailViewPostProcess.tpl', $request->getModule());
		} else {
			$viewer->view('IndexPostProcess.tpl', $request->getModule());
		}
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$recordId = (int) $request->get('record');

		$record = $this->detailRecordData ?: $this->getReceiptById($recordId);
		if (!$record) {
			header('Location: index.php?module=GoodsReceipt&view=List&app=INVENTORY');
			exit;
		}
		$recordModel = $this->detailRecordModel ?: $this->buildRecordModel($recordId, $record);

		$ri = $db->pquery(
			"SELECT itemid, receiptid, productid, product_name, product_type, quantity, unit_price, description, line_note, serial_number
			 FROM vtiger_goodsreceipt_items
			 WHERE receiptid = ?
			 ORDER BY itemid ASC",
			array($recordId)
		);
		$items = array();
		$totalQty = 0.0;
		$totalValue = 0.0;
		while ($row = $db->fetchByAssoc($ri)) {
			$row['line_total'] = (float) $row['quantity'] * (float) $row['unit_price'];
			$totalQty += (float) $row['quantity'];
			$totalValue += (float) $row['line_total'];
			$row['product_name'] = $this->decodeText($row['product_name']);
			$row['product_name_display'] = ucwords(mb_strtolower(trim($row['product_name']), 'UTF-8'));
			if (!isset($row['product_type']) || $row['product_type'] === '') {
				$row['product_type'] = 'Other';
			}
			$row['description'] = $this->decodeText(isset($row['description']) ? $row['description'] : '');
			$row['line_note'] = $this->decodeText($row['line_note']);
			$row['quantity_display'] = number_format((float) $row['quantity'], 2, '.', ',');
			$row['unit_price_display'] = number_format((float) $row['unit_price'], 0, '.', ',');
			$row['line_total_display'] = number_format((float) $row['line_total'], 0, '.', ',');
			$items[] = $row;
		}
		$ra = $db->pquery(
			"SELECT attachmentid, filename, filetype, filesize, createdtime
			 FROM vtiger_goodsreceipt_attachments
			 WHERE receiptid = ? AND deleted = 0
			 ORDER BY createdtime DESC, attachmentid DESC",
			array($recordId)
		);
		$attachments = array();
		while ($row = $db->fetchByAssoc($ra)) {
			$row['filename'] = $this->decodeText($row['filename']);
			$attachments[] = $row;
		}

		$record = $this->normalizeRecordForDisplay($record);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_DATA', $record);
		$viewer->assign('ITEMS', $items);
		$viewer->assign('ITEM_COUNT', count($items));
		$viewer->assign('TOTAL_QTY_DISPLAY', number_format($totalQty, 2, '.', ','));
		$viewer->assign('TOTAL_VALUE_DISPLAY', number_format($totalValue, 0, '.', ','));
		$viewer->assign('ATTACHMENTS', $attachments);

		require_once 'modules/Warehouse/helpers/InventoryCrossNavHelper.php';
		$viewer->assign('LINKED_STORAGE_STOCK_ID', Inventory_CrossNav_Helper::resolveStockIdForInboundReceipt($db, $recordId, $items));
		$viewer->assign('LINKED_OUTBOUND_ISSUE_ID', Inventory_CrossNav_Helper::resolveOutboundIssueId($db, $items));
		$viewer->assign('LINKED_INBOUND_RECEIPT_ID', 0);
		$viewer->assign('MK_INV_NAV_ACTIVE', 'GoodsReceipt');
		$viewer->assign('MK_INV_NAV_CLASS', 'mk-gr-detail-topnav');

		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	protected function getReceiptById($recordId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery("SELECT * FROM vtiger_goodsreceipt WHERE receiptid = ? AND deleted = 0", array($recordId));
		if ($db->num_rows($rs) <= 0) {
			return false;
		}
		return $db->fetchByAssoc($rs);
	}

	protected function decodeText($value) {
		return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	protected function normalizeRecordForDisplay(array $record) {
		$textFields = array('subject', 'source_name', 'storage_location', 'note');
		foreach ($textFields as $field) {
			if (isset($record[$field])) {
				$record[$field] = $this->decodeText($record[$field]);
			}
		}
		return $record;
	}

	protected function buildRecordModel($recordId, array $record) {
		$recordModel = new Vtiger_Record_Model();
		$recordModel->setModule('GoodsReceipt');
		$recordModel->setId($recordId);
		$recordModel->set('label', (string) $record['subject']);
		$recordModel->setData($record);
		return $recordModel;
	}
}
