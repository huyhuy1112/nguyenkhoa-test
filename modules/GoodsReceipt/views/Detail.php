<?php
class GoodsReceipt_Detail_View extends Vtiger_Detail_View {
	protected $detailRecordData = null;
	protected $detailRecordModel = null;

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function preProcessTplName(Vtiger_Request $request) { return 'DetailViewPreProcess.tpl'; }
	public function postProcessTplName(Vtiger_Request $request) { return 'DetailViewPostProcess.tpl'; }
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

		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
		parent::preProcess($request, $display);
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
		while ($row = $db->fetchByAssoc($ri)) {
			$row['line_total'] = (float) $row['quantity'] * (float) $row['unit_price'];
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
		$viewer->assign('ATTACHMENTS', $attachments);
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

