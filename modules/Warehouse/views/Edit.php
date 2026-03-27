<?php
class Warehouse_Edit_View extends Vtiger_Index_View {

	protected $stockRow = null;
	protected $recordModel = null;

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPreProcess.tpl';
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
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
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
		$this->stockRow = $this->normalizeRowForDisplay($row);
		$this->recordModel = $this->buildRecordModel($this->stockRow);
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $this->recordModel);
		$viewer->assign('RECORD_MODEL', $this->recordModel);
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Storage');

		$this->assignInventoryContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('IndexPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$viewer = $this->getViewer($request);
		$row = $this->stockRow;
		$row['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($row['product_name']);
		$row['quantity_display'] = Warehouse_Stock_Helper::formatNumber($row['quantity'], 2);
		$row['shrinkage_display'] = Warehouse_Stock_Helper::formatNumber($row['shrinkage_qty'], 2);
		$row['available_display'] = Warehouse_Stock_Helper::formatNumber(
			Warehouse_Stock_Helper::availableQty($row['quantity'], $row['shrinkage_qty']),
			2
		);
		$row['last_price_display'] = Warehouse_Stock_Helper::formatNumber($row['last_price'], 0);
		$typeLabel = Warehouse_Stock_Helper::formatProductTypeLabel(isset($row['raw_item_type']) ? $row['raw_item_type'] : null);
		if ($typeLabel === null || $typeLabel === '') {
			$typeLabel = 'Other';
		}
		$viewer->assign('RECORD', $this->recordModel);
		$viewer->assign('RECORD_MODEL', $this->recordModel);
		$viewer->assign('STOCK', $row);
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Storage');
		$viewer->assign('AVAILABLE_QTY', Warehouse_Stock_Helper::availableQty($row['quantity'], $row['shrinkage_qty']));
		$viewer->assign('PRODUCT_KEY_DISPLAY', Warehouse_Stock_Helper::formatProductKeyDisplay($row));
		$viewer->assign('TYPE_LABEL', $typeLabel);
		$viewer->assign('IS_LEGACY_IDENTITY', Warehouse_Stock_Helper::isLegacyNameKey($row));
		$viewer->assign('CATALOG_PRODUCT_ID', !empty($row['productid']) ? (int) $row['productid'] : 0);
		$viewer->view('EditView.tpl', $request->getModule());
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
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$recordModel = new Vtiger_Record_Model();
		$recordModel->setModule('Warehouse');
		$recordModel->setId((int) $row['stockid']);
		$label = Warehouse_Stock_Helper::normalizeDisplayName(isset($row['product_name']) ? (string) $row['product_name'] : 'Stock');
		$recordModel->set('label', $label);
		$recordModel->setData($row);
		return $recordModel;
	}
}
