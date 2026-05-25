<?php
class Warehouse_Detail_View extends Vtiger_Index_View {

	protected $stockRow = null;
	protected $recordModel = null;

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

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
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Storage');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$stockId = (int) $request->get('record');
		if ($stockId <= 0) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}
		$row = $this->loadStockRow($stockId);
		if (!$row) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}
		$row = $this->normalizeRowForDisplay($row);
		$this->stockRow = $row;
		$this->recordModel = $this->buildRecordModel($row);
		$viewer = $this->getViewer($request);
		$this->assignInventoryContext($request);
		$viewer->assign('RECORD', $this->recordModel);
		$viewer->assign('RECORD_MODEL', $this->recordModel);
		$viewer->assign('RECORD_DATA', $this->stockRow);
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
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);

		$row = $this->stockRow;
		$recordModel = $this->recordModel;

		$stockId = (int) $request->get('record');
		if (!$row) {
			$row = $this->loadStockRow($stockId);
			if ($row) {
				$row = $this->normalizeRowForDisplay($row);
				$this->stockRow = $row;
				$recordModel = $this->buildRecordModel($row);
			}
		}
		if (!$row) {
			header('Location: index.php?module=Warehouse&view=List&app=INVENTORY');
			exit;
		}

		$serialIndexes = Warehouse_Stock_Helper::fetchInboundSerialIndexes($db);
		$stockSerialList = Warehouse_Stock_Helper::resolveInboundSerialsForStockRow($row, $serialIndexes);
		list($stockSerialDisp, $stockSerialFull) = Warehouse_Stock_Helper::formatSerialDisplayList($stockSerialList);
		$row['serial_display'] = ($stockSerialDisp !== '') ? $stockSerialDisp : '—';
		$row['serial_full'] = $stockSerialFull;

		$params = array();
		$match = Warehouse_Stock_Helper::inboundItemsMatchWhere($row, $params);
		$histSql = "SELECT gr.receiptid, gr.code, gr.subject, gr.received_date, gr.storage_location, gr.note,
				gr.createdtime AS receipt_createdtime, gr.updatedtime AS receipt_updatedtime,
				gri.quantity, gri.unit_price, gri.product_name, gri.product_type, gri.itemid, gri.serial_number, gri.description
			FROM vtiger_goodsreceipt_items gri
			INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			WHERE {$match}
			ORDER BY gr.received_date DESC, gri.itemid DESC";
		$hq = $db->pquery($histSql, $params);
		$inboundHistory = array();
		while ($h = $db->fetchByAssoc($hq)) {
			$h['product_type'] = Warehouse_Stock_Helper::formatProductTypeLabel(isset($h['product_type']) ? $h['product_type'] : null);
			if ($h['product_type'] === null || $h['product_type'] === '') {
				$h['product_type'] = 'Other';
			}
			$h['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($h['product_name']);
			$h['quantity_display'] = Warehouse_Stock_Helper::formatNumber($h['quantity'], 2);
			$h['unit_price_display'] = Warehouse_Stock_Helper::formatNumber($h['unit_price'], 0);
			$h['received_date_display'] = Warehouse_Stock_Helper::formatDateTimeDisplay($h['received_date']);
			$snIn = '';
			if (isset($h['serial_number'])) {
				$snIn = trim(html_entity_decode((string) $h['serial_number'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			}
			$h['serial_display'] = $snIn;
			$h['description'] = isset($h['description'])
				? trim(html_entity_decode((string) $h['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
				: '';
			$inboundHistory[] = $h;
		}

		$outParams = array();
		$outMatch = Warehouse_Stock_Helper::outboundItemsMatchWhere($row, $outParams);
		$outSql = "SELECT gi.issueid, gi.code, gi.subject, gi.issued_date, gi.destination, gi.storage_location,
				gi.createdtime AS issue_createdtime, gi.updatedtime AS issue_updatedtime,
				gii.productid, gii.quantity, gii.unit_price, gii.product_name, gii.product_type, gii.itemid, gii.serial_number, gii.description,
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
			INNER JOIN vtiger_goodsissue gi ON gi.issueid = gii.issueid AND gi.deleted = 0
			WHERE {$outMatch}
			ORDER BY gi.issued_date DESC, gii.itemid DESC";
		$oq = $db->pquery($outSql, $outParams);
		$outboundHistory = array();
		while ($o = $db->fetchByAssoc($oq)) {
			$o['product_type'] = Warehouse_Stock_Helper::formatProductTypeLabel(isset($o['product_type']) ? $o['product_type'] : null);
			if ($o['product_type'] === null || $o['product_type'] === '') {
				$o['product_type'] = 'Other';
			}
			$o['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($o['product_name']);
			$o['quantity_display'] = Warehouse_Stock_Helper::formatNumber($o['quantity'], 2);
			$o['unit_price_display'] = Warehouse_Stock_Helper::formatNumber($o['unit_price'], 0);
			$o['issued_date_display'] = Warehouse_Stock_Helper::formatDateTimeDisplay($o['issued_date']);
			$sn = '';
			if (isset($o['serial_number'])) {
				$sn = trim(html_entity_decode((string) $o['serial_number'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			}
			$o['serial_display'] = ($sn !== '') ? $sn : '';
			$o['serial_full'] = $sn;
			$o['description'] = isset($o['description'])
				? trim(html_entity_decode((string) $o['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
				: '';
			if ($o['description'] === '' && isset($o['source_description'])) {
				$o['description'] = trim(html_entity_decode((string) $o['source_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			}
			if ($o['description'] === '0') {
				$o['description'] = '';
			}
			$outboundHistory[] = $o;
		}

		$movementEvents = array();
		foreach ($inboundHistory as $h) {
			$eventTime = '';
			if (!empty($h['receipt_updatedtime'])) {
				$eventTime = (string) $h['receipt_updatedtime'];
			} elseif (!empty($h['receipt_createdtime'])) {
				$eventTime = (string) $h['receipt_createdtime'];
			} elseif (!empty($h['received_date'])) {
				$eventTime = (string) $h['received_date'] . ' 00:00:00';
			}
			if ($eventTime === '') {
				continue;
			}
			$movementEvents[] = array(
				'event_time' => $eventTime,
				'event_type' => 'inbound',
				'inbound' => (float) $h['quantity'],
				'outbound' => 0.0,
				'seq' => (int) $h['itemid'],
			);
		}
		foreach ($outboundHistory as $o) {
			$eventTime = '';
			if (!empty($o['issue_updatedtime'])) {
				$eventTime = (string) $o['issue_updatedtime'];
			} elseif (!empty($o['issue_createdtime'])) {
				$eventTime = (string) $o['issue_createdtime'];
			} elseif (!empty($o['issued_date'])) {
				$eventTime = (string) $o['issued_date'] . ' 00:00:00';
			}
			if ($eventTime === '') {
				continue;
			}
			$movementEvents[] = array(
				'event_time' => $eventTime,
				'event_type' => 'outbound',
				'inbound' => 0.0,
				'outbound' => (float) $o['quantity'],
				'seq' => (int) $o['itemid'],
			);
		}
		usort($movementEvents, function ($a, $b) {
			$ta = strtotime((string) $a['event_time']);
			$tb = strtotime((string) $b['event_time']);
			if ($ta === $tb) {
				$sa = isset($a['seq']) ? (int) $a['seq'] : 0;
				$sb = isset($b['seq']) ? (int) $b['seq'] : 0;
				return $sa <=> $sb;
			}
			return $ta <=> $tb;
		});
		$movementSeries = array();
		foreach ($movementEvents as $ev) {
			$movementSeries[] = array(
				'event_time' => (string) $ev['event_time'],
				'inbound' => (float) $ev['inbound'],
				'outbound' => (float) $ev['outbound'],
				'event_type' => (string) $ev['event_type'],
			);
		}

		$linkOptions = array();
		$lo = $db->pquery(
			'SELECT productsservicesid, productsservicesname FROM vtiger_productsservices ORDER BY productsservicesname ASC',
			array()
		);
		while ($p = $db->fetchByAssoc($lo)) {
			$linkOptions[] = array(
				'id' => (int) $p['productsservicesid'],
				'name' => (string) $p['productsservicesname'],
			);
		}

		$row['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($row['product_name']);
		$row['quantity_display'] = Warehouse_Stock_Helper::formatNumber($row['quantity'], 2);
		$row['shrinkage_display'] = Warehouse_Stock_Helper::formatNumber($row['shrinkage_qty'], 2);
		$row['available_display'] = Warehouse_Stock_Helper::formatNumber(Warehouse_Stock_Helper::availableQty($row['quantity'], $row['shrinkage_qty']), 2);
		$row['last_price_display'] = Warehouse_Stock_Helper::formatNumber($row['last_price'], 0);
		$row['updatedtime_display'] = Warehouse_Stock_Helper::formatDateTimeDisplay($row['updatedtime']);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_DATA', $row);
		$viewer->assign('STOCK', $row);
		$viewer->assign('PRODUCT_KEY_DISPLAY', Warehouse_Stock_Helper::formatProductKeyDisplay($row));
		$typeLabel = Warehouse_Stock_Helper::formatProductTypeLabel(isset($row['raw_item_type']) ? $row['raw_item_type'] : null);
		if ($typeLabel === null || $typeLabel === '') {
			$typeLabel = 'Other';
		}
		$viewer->assign('TYPE_LABEL', $typeLabel);
		$viewer->assign('AVAILABLE_QTY', Warehouse_Stock_Helper::availableQty($row['quantity'], $row['shrinkage_qty']));
		$viewer->assign('INBOUND_HISTORY', $inboundHistory);
		$viewer->assign('OUTBOUND_HISTORY', $outboundHistory);
		$viewer->assign('MOVEMENT_SERIES_JSON', json_encode($movementSeries));
		$viewer->assign('CAN_DELETE', $this->canDeleteStockRow($row));
		$viewer->assign('IS_LEGACY_IDENTITY', Warehouse_Stock_Helper::isLegacyNameKey($row));
		$viewer->assign('CATALOG_PRODUCT_ID', !empty($row['productid']) ? (int) $row['productid'] : 0);
		$viewer->assign('LINK_PRODUCT_OPTIONS', $linkOptions);
		$viewer->assign('SHOW_SAVED', (string) $request->get('saved') === '1');
		$viewer->assign('SHOW_DELETE_BLOCKED', (string) $request->get('deleteBlocked') === '1');
		$viewer->assign('SHOW_LINK_SUCCESS', (string) $request->get('linkSuccess') === '1');
		$viewer->assign('LINK_ERROR', trim((string) $request->get('linkError')));
		$viewer->assign('INBOUND_HISTORY_COUNT', count($inboundHistory));
		$viewer->assign('OUTBOUND_HISTORY_COUNT', count($outboundHistory));

		require_once 'modules/Warehouse/helpers/InventoryCrossNavHelper.php';
		$viewer->assign('LINKED_INBOUND_RECEIPT_ID', Inventory_CrossNav_Helper::resolveInboundReceiptIdForStock($db, $row));
		$viewer->assign('LINKED_OUTBOUND_ISSUE_ID', Inventory_CrossNav_Helper::resolveOutboundIssueIdForStock($db, $row));
		$viewer->assign('LINKED_STORAGE_STOCK_ID', 0);
		$viewer->assign('MK_INV_NAV_ACTIVE', 'Warehouse');
		$viewer->assign('MK_INV_NAV_CLASS', 'mk-wh-detail-topnav');

		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	protected function loadStockRow($stockId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			"SELECT ws.*, COALESCE(NULLIF(ws.product_type, ''), ps.item_type) AS raw_item_type
			 FROM vtiger_warehouse_stock ws
			 LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ws.productid AND ws.productid > 0
			 WHERE ws.stockid = ?",
			array($stockId)
		);
		if ($db->num_rows($rs) <= 0) {
			return null;
		}
		return $db->fetchByAssoc($rs);
	}

	protected function normalizeRowForDisplay(array $row) {
		$textFields = array('product_name', 'storage_location', 'warehouse_note', 'inbound_note');
		foreach ($textFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = html_entity_decode((string) $row[$f], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
		}
		if (!isset($row['shrinkage_qty']) || $row['shrinkage_qty'] === null) {
			$row['shrinkage_qty'] = 0;
		}
		return $row;
	}

	protected function buildRecordModel(array $row) {
		$recordModel = new Vtiger_Record_Model();
		$recordModel->setModule('Warehouse');
		$recordModel->setId((int) $row['stockid']);
		$label = Warehouse_Stock_Helper::normalizeDisplayName(isset($row['product_name']) ? (string) $row['product_name'] : 'Stock');
		$recordModel->set('label', $label);
		$recordModel->setData($row);
		return $recordModel;
	}

	protected function canDeleteStockRow(array $row) {
		$q = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
		$s = isset($row['shrinkage_qty']) ? (float) $row['shrinkage_qty'] : 0.0;
		return (abs($q) < 0.0000001) && (abs($s) < 0.0000001);
	}
}
