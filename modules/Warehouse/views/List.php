<?php
class Warehouse_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
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

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Storage');

		$search = trim((string) $request->get('search'));
		$qtyMin = trim((string) $request->get('qty_min'));
		$qtyMax = trim((string) $request->get('qty_max'));
		$location = trim((string) $request->get('storage_location'));
		$typeFilter = trim((string) $request->get('item_type'));
		$legacyOnly = (string) $request->get('legacy_only') === '1';

		$where = array('1=1');
		$params = array();

		if ($search !== '') {
			$where[] = '(ws.product_name LIKE ? OR ws.product_key LIKE ?)';
			$like = '%' . $search . '%';
			$params[] = $like;
			$params[] = $like;
		}
		if ($qtyMin !== '' && is_numeric($qtyMin)) {
			$where[] = 'ws.quantity >= ?';
			$params[] = (float) $qtyMin;
		}
		if ($qtyMax !== '' && is_numeric($qtyMax)) {
			$where[] = 'ws.quantity <= ?';
			$params[] = (float) $qtyMax;
		}
		if ($location !== '') {
			$where[] = 'ws.storage_location LIKE ?';
			$params[] = '%' . $location . '%';
		}
		if ($legacyOnly) {
			$where[] = "ws.product_key LIKE 'N:%'";
		}
		if ($typeFilter !== '') {
			if ($typeFilter === '__empty__') {
				$where[] = "(COALESCE(NULLIF(TRIM(ws.product_type), ''), NULLIF(TRIM(ps.item_type), '')) IS NULL)";
			} elseif ($typeFilter === 'hardware') {
				$where[] = "(LOWER(TRIM(COALESCE(NULLIF(ws.product_type, ''), ps.item_type))) IN ('hardware','product','products'))";
			} elseif ($typeFilter === 'software') {
				$where[] = "(LOWER(TRIM(COALESCE(NULLIF(ws.product_type, ''), ps.item_type))) IN ('software'))";
			} elseif ($typeFilter === 'service') {
				$where[] = "(LOWER(TRIM(COALESCE(NULLIF(ws.product_type, ''), ps.item_type))) IN ('service','services'))";
			} else {
				$where[] = 'LOWER(TRIM(COALESCE(NULLIF(ws.product_type, \'\'), ps.item_type))) = ?';
				$params[] = strtolower($typeFilter);
			}
		}

		$sql = "SELECT ws.stockid, ws.code, ws.product_key, ws.productid, ws.product_name, ws.quantity,
				COALESCE(ws.shrinkage_qty, 0) AS shrinkage_qty, ws.last_price, ws.storage_location,
				ws.warehouse_note, ws.inbound_note, ws.updatedtime, ws.createdtime,
				COALESCE(NULLIF(ws.product_type, ''), ps.item_type) AS raw_item_type,
				GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) AS available_qty
			FROM vtiger_warehouse_stock ws
			LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ws.productid AND ws.productid > 0
			WHERE " . implode(' AND ', $where) . "
			ORDER BY ws.updatedtime DESC, ws.stockid DESC";

		$rs = $db->pquery($sql, $params);
		$rows = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$row['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($row['product_name']);
			$row['code'] = isset($row['code']) ? (string) $row['code'] : '';
			$row['type_label'] = Warehouse_Stock_Helper::formatProductTypeLabel($row['raw_item_type']);
			$row['product_key_display'] = Warehouse_Stock_Helper::formatProductKeyDisplay($row);
			$row['quantity_display'] = Warehouse_Stock_Helper::formatNumber($row['quantity'], 2);
			$row['shrinkage_display'] = Warehouse_Stock_Helper::formatNumber($row['shrinkage_qty'], 2);
			$row['available_display'] = Warehouse_Stock_Helper::formatNumber($row['available_qty'], 2);
			$row['last_price_display'] = Warehouse_Stock_Helper::formatNumber($row['last_price'], 0);
			$row['updatedtime_display'] = Warehouse_Stock_Helper::formatDateTimeDisplay($row['updatedtime']);
			$row['is_legacy_identity'] = Warehouse_Stock_Helper::isLegacyNameKey($row);
			$avail = (float) $row['available_qty'];
			$row['is_low_stock'] = ($avail > 0 && $avail < 5);
			$row['has_shrinkage'] = ((float) $row['shrinkage_qty']) > 0.0000001;
			if ($row['type_label'] === null || $row['type_label'] === '') {
				$row['type_label'] = 'Other';
			}
			$rows[] = $row;
		}

		$viewer->assign('ROWS', $rows);
		$viewer->assign('SEARCH', $search);
		$viewer->assign('QTY_MIN', $qtyMin);
		$viewer->assign('QTY_MAX', $qtyMax);
		$viewer->assign('FILTER_LOCATION', $location);
		$viewer->assign('FILTER_ITEM_TYPE', $typeFilter);
		$viewer->assign('FILTER_LEGACY_ONLY', $legacyOnly);
		$viewer->assign('SHOW_DELETED', (string) $request->get('deleted') === '1');
		$viewer->assign('SHOW_DELETE_ERROR', (string) $request->get('deleteError') === '1');
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}
}
