<?php
class GoodsIssue_Detail_View extends Vtiger_Index_View {
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
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$viewer = $this->getViewer($request);
		$db = PearDatabase::getInstance();
		$issue = $this->getIssueById($db, $recordId);
		if (!$issue) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$this->detailRecordData = $this->normalizeRecordForDisplay($issue);
		$this->detailRecordModel = $this->buildRecordModel($recordId, $this->detailRecordData);
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
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$issueId = (int) $request->get('record');

		if ($issueId <= 0) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$issue = $this->detailRecordData;
		if (!$issue) {
			$issue = $this->getIssueById($db, $issueId);
		}
		if (!$issue || (int) $issue['deleted'] === 1) {
			header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
			exit;
		}

		$recordData = $this->normalizeRecordForDisplay($issue);
		$recordModel = $this->detailRecordModel ?: $this->buildRecordModel($issueId, $recordData);

		$items = $this->loadItems($db, $issueId);
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$earliestExpired = Warehouse_Stock_Helper::resolveEarliestExpiredDate($db, $items);
		$totalQty = 0.0;
		$totalValue = 0.0;
		foreach ($items as &$it) {
			$totalQty += (float) $it['quantity'];
			$totalValue += (float) $it['line_total'];
			$name = trim((string) $it['product_name']);
			$it['product_name_display'] = $name !== '' ? ucwords(mb_strtolower($name, 'UTF-8')) : '—';
			$it['quantity_display'] = $this->formatNumber($it['quantity'], 2);
			$it['unit_price_display'] = $this->formatNumber($it['unit_price'], 0);
			$it['discount_percent_display'] = $this->formatNumber($it['discount_percent'], 2);
			$it['line_total_display'] = $this->formatNumber($it['line_total'], 0);
			if (!isset($it['product_type']) || $it['product_type'] === '') {
				$it['product_type'] = 'Other';
			}
			$it['line_note'] = $this->decodeText($it['line_note']);
		}
		unset($it);

		$recordData['expired_date_display'] = ($earliestExpired !== '' ? date('d/m/Y', strtotime($earliestExpired)) : '—');
		$today = date('Y-m-d');
		$threeMonths = date('Y-m-d', strtotime('+3 months'));
		$recordData['is_expired'] = ($earliestExpired !== '' && $earliestExpired < $today);
		$recordData['is_expiring_soon'] = ($earliestExpired !== '' && !$recordData['is_expired'] && $earliestExpired <= $threeMonths);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_DATA', $recordData);
		$viewer->assign('ITEMS', $items);
		$viewer->assign('ITEM_COUNT', count($items));
		$viewer->assign('TOTAL_QTY_DISPLAY', $this->formatNumber($totalQty, 2));
		$viewer->assign('TOTAL_VALUE_DISPLAY', $this->formatNumber($totalValue, 0));

		require_once 'modules/Warehouse/helpers/InventoryCrossNavHelper.php';
		$viewer->assign('LINKED_STORAGE_STOCK_ID', Inventory_CrossNav_Helper::resolveStockId($db, $items));
		$viewer->assign('LINKED_INBOUND_RECEIPT_ID', Inventory_CrossNav_Helper::resolveInboundReceiptIdFromOutboundItems($db, $items));
		$viewer->assign('LINKED_OUTBOUND_ISSUE_ID', $issueId);
		$viewer->assign('MK_INV_NAV_ACTIVE', 'GoodsIssue');
		$viewer->assign('MK_INV_NAV_CLASS', 'mk-go-detail-topnav');

		$viewer->assign('ATTACHMENTS', $this->loadAttachments($db, $issueId));
		$viewer->assign('SHOW_DELETE_BLOCKED', (string) $request->get('deleteBlocked') === '1');
		$viewer->assign('SHOW_DELETE_ERROR', (string) $request->get('delete_error') === '1');
		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	protected function loadItems(PearDatabase $db, $issueId) {
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
			if ($discount < 0) {
				$discount = 0.0;
			}
			if ($discount > 100) {
				$discount = 100.0;
			}
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

			$items[] = array(
				'productid' => !empty($row['productid']) ? (int) $row['productid'] : 0,
				'product_name' => $this->decodeText($row['product_name']),
				'product_type' => $this->decodeText($row['product_type']),
				'quantity' => $qty,
				'unit_price' => $unit,
				'discount_percent' => $discount,
				'serial_number' => $sn,
				'description' => $desc,
				'line_note' => $this->decodeText($row['line_note']),
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
				'filename' => $this->decodeText($row['filename']),
				'filetype' => $this->decodeText($row['filetype']),
				'filesize' => (int) $row['filesize'],
				'createdtime' => isset($row['createdtime']) ? (string) $row['createdtime'] : '',
			);
		}
		return $atts;
	}

	protected function formatNumber($v, $dec = 2) {
		return number_format((float) $v, $dec, '.', ',');
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
		$textFields = array('subject', 'issued_by', 'destination', 'storage_location', 'note', 'code');
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
