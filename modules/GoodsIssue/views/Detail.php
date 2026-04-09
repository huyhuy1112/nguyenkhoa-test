<?php
class GoodsIssue_Detail_View extends Vtiger_Detail_View {
	protected $detailRecordData = null;
	protected $detailRecordModel = null;

	public function preProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPreProcess.tpl';
	}
	public function postProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPostProcess.tpl';
	}

	protected function assignInventoryContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		if (!empty($appName)) {
			$viewer->assign('SELECTED_MENU_CATEGORY', $appName);
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$viewer = $this->getViewer($request);

		$this->assignInventoryContext($request);

		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$db = PearDatabase::getInstance();
		$issue = $this->getIssueById($db, $recordId);
		if (!$issue) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$this->detailRecordData = $this->normalizeRecordForDisplay($issue);
		$this->detailRecordModel = $this->buildRecordModel($recordId, $this->detailRecordData);

		// Must be assigned before ModuleHeader.tpl in preProcess chain.
		$viewer->assign('RECORD', $this->detailRecordModel);
		$viewer->assign('RECORD_MODEL', $this->detailRecordModel);
		$viewer->assign('RECORD_DATA', $this->detailRecordData);
		parent::preProcess($request, $display);
	}

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function loadIssue(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT * FROM vtiger_goodsissue WHERE issueid = ? LIMIT 1",
			array($issueId)
		);
		if ($db->num_rows($rs) <= 0) return null;
		return $db->fetchByAssoc($rs);
	}

	protected function loadItems(PearDatabase $db, $issueId) {
		// Keep Outbound detail consistent with Storage outbound history:
		// prefer saved outbound description, otherwise resolve from inbound/source.
		$rs = $db->pquery(
			"SELECT gii.*,
				(
					SELECT gri.description
					FROM vtiger_goodsreceipt_items gri
					INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
					WHERE
						(
							TRIM(gii.serial_number) <> ''
							AND TRIM(gri.serial_number) <> ''
							AND gri.serial_number = gii.serial_number
							AND (
								(gii.productid IS NOT NULL AND gii.productid > 0 AND gri.productid = gii.productid)
								OR
								((gii.productid IS NULL OR gii.productid = 0) AND LOWER(TRIM(gri.product_name)) = LOWER(TRIM(gii.product_name)))
							)
						)
						OR
						(
							(TRIM(gii.serial_number) = '' OR gii.serial_number IS NULL)
							AND gii.productid IS NOT NULL AND gii.productid > 0
							AND gri.productid = gii.productid
						)
						OR
						(
							(TRIM(gii.serial_number) = '' OR gii.serial_number IS NULL)
							AND (gii.productid IS NULL OR gii.productid = 0)
							AND LOWER(TRIM(gri.product_name)) = LOWER(TRIM(gii.product_name))
							AND LOWER(TRIM(COALESCE(gri.product_type,''))) = LOWER(TRIM(COALESCE(gii.product_type,'')))
						)
					ORDER BY gri.itemid DESC
					LIMIT 1
				) AS source_description
			 FROM vtiger_goodsissue_items gii
			 WHERE gii.issueid = ?
			 ORDER BY gii.itemid ASC",
			array($issueId)
		);
		$items = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$qty = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
			$unit = isset($row['unit_price']) ? (float) $row['unit_price'] : 0.0;
			$discount = isset($row['discount_percent']) ? (float) $row['discount_percent'] : 0.0;
			if ($discount < 0) $discount = 0.0;
			if ($discount > 100) $discount = 100.0;
			$lineTotal = $qty * $unit * (1.0 - ($discount / 100.0));

			$sn = isset($row['serial_number']) ? trim($this->decodeText($row['serial_number'])) : '';
			$desc = '';
			if (isset($row['description']) && $row['description'] !== null) {
				$desc = trim($this->decodeText($row['description']));
			}
			if ($desc === '' && isset($row['source_description']) && $row['source_description'] !== null) {
				$desc = trim($this->decodeText($row['source_description']));
			}
			if ($desc === '0') {
				$desc = '';
			}
			if ($desc === '') {
				$desc = '-';
			}
			$items[] = array(
				'productid' => !empty($row['productid']) ? (int) $row['productid'] : 0,
				'product_name' => (string) $row['product_name'],
				'product_type' => (string) $row['product_type'],
				'quantity' => $qty,
				'unit_price' => $unit,
				'discount_percent' => $discount,
				'serial_number' => $sn,
				'description' => $desc,
				'line_note' => (string) $row['line_note'],
				'line_total' => $lineTotal,
			);
		}
		return $items;
	}

	protected function loadAttachments(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT attachmentid, filename, filetype, filesize, createdtime
			 FROM vtiger_goodsissue_attachments
			 WHERE issueid = ? AND deleted = 0
			 ORDER BY createdtime DESC, attachmentid DESC",
			array($issueId)
		);
		$atts = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$atts[] = array(
				'attachmentid' => (int) $row['attachmentid'],
				'filename' => (string) $row['filename'],
				'filetype' => (string) $row['filetype'],
				'filesize' => (int) $row['filesize'],
				'createdtime' => isset($row['createdtime']) ? (string) $row['createdtime'] : '',
			);
		}
		return $atts;
	}

	protected function formatNumber($v, $dec = 2) {
		return number_format((float) $v, $dec, '.', ',');
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);

		$issueId = (int) $request->get('record');
		if ($issueId <= 0) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$issue = $this->loadIssue($db, $issueId);
		if (!$issue || (int)$issue['deleted'] === 1) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$recordData = $this->detailRecordData ?: $this->normalizeRecordForDisplay($issue);
		$recordModel = $this->detailRecordModel ?: $this->buildRecordModel($issueId, $recordData);

		$items = $this->loadItems($db, $issueId);
		foreach ($items as &$it) {
			$it['quantity_display'] = $this->formatNumber($it['quantity'], 2);
			$it['unit_price_display'] = $this->formatNumber($it['unit_price'], 0);
			$it['line_total_display'] = $this->formatNumber($it['line_total'], 0);
		}
		unset($it);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_DATA', $recordData);

		$viewer->assign('ITEMS', $items);
		$viewer->assign('ATTACHMENTS', $this->loadAttachments($db, $issueId));
		$viewer->assign('SHOW_DELETE_BLOCKED', (string) $request->get('deleteBlocked') === '1');
		$viewer->assign('SHOW_DELETE_ERROR', (string) $request->get('delete_error') === '1');
		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	protected function getIssueById(PearDatabase $db, $issueId) {
		$rs = $db->pquery("SELECT * FROM vtiger_goodsissue WHERE issueid = ? AND deleted = 0 LIMIT 1", array($issueId));
		if ($db->num_rows($rs) <= 0) {
			return null;
		}
		return $db->fetchByAssoc($rs);
	}

	protected function decodeText($value) {
		return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	protected function normalizeRecordForDisplay(array $record) {
		$textFields = array('subject', 'issued_by', 'destination', 'storage_location', 'note');
		foreach ($textFields as $f) {
			if (isset($record[$f])) {
				$record[$f] = $this->decodeText($record[$f]);
			}
		}
		return $record;
	}

	protected function buildRecordModel($recordId, array $record) {
		$recordModel = new Vtiger_Record_Model();
		$recordModel->setModule('GoodsIssue');
		$recordModel->setId((int) $recordId);
		$label = isset($record['subject']) ? (string) $record['subject'] : 'Outbound';
		$recordModel->set('label', $label);
		$recordModel->setData($record);
		return $recordModel;
	}
}

?>

