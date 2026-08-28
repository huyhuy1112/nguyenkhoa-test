<?php
class Warehouse_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		$appName = $request->get('app');
		if ($appName === 'INVENTORY' || $appName === '') {
			return 'ListViewPreProcess.tpl';
		}
		return 'IndexViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignInventoryContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$appName = $request->get('app');
		if ($appName === 'INVENTORY' || $appName === '') {
			$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		} else {
			$viewer->view('IndexPostProcess.tpl', $request->getModule());
		}
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
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'INVENTORY');
		$viewer->assign('VIEW', 'List');
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
		$lowStockOnly = (string) $request->get('low_stock') === '1';

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

		$stats = Warehouse_Stock_Helper::computeStorageListStats($db, $where, $params);
		$viewer->assign('STORAGE_STATS', $stats);
		$viewer->assign('STORAGE_STATS_SKU_DISPLAY', (string) $stats['sku_in_stock']);
		$viewer->assign('STORAGE_STATS_VALUE_DISPLAY', Warehouse_Stock_Helper::formatVnd($stats['inventory_value']));
		$viewer->assign('STORAGE_STATS_LOW_DISPLAY', (string) $stats['low_stock_count']);
		$viewer->assign('STORAGE_STATS_MOVEMENTS_DISPLAY', (string) $stats['outbound_movements']);
		$viewer->assign('LOW_STOCK_THRESHOLD', (int) Warehouse_Stock_Helper::LOW_STOCK_THRESHOLD);

		$expiringOnly = (string) $request->get('expiring') === '1';

		$sql = "SELECT ws.stockid, ws.code, ws.product_key, ws.productid, ws.product_name, ws.quantity,
				COALESCE(ws.shrinkage_qty, 0) AS shrinkage_qty, ws.last_price, ws.storage_location, ws.expired_date,
				ws.warehouse_note, ws.inbound_note, ws.updatedtime, ws.createdtime,
				COALESCE(NULLIF(ws.product_type, ''), ps.item_type) AS raw_item_type,
				GREATEST(ws.quantity - COALESCE(ws.shrinkage_qty, 0), 0) AS available_qty
			FROM vtiger_warehouse_stock ws
			LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ws.productid AND ws.productid > 0
			WHERE " . implode(' AND ', $where) . "
			ORDER BY ws.updatedtime DESC, ws.stockid DESC";

		$rs = $db->pquery($sql, $params);
		$rows = array();
		$today = date('Y-m-d');
		$threeMonths = date('Y-m-d', strtotime('+3 months'));
		$didNotify = false;
		while ($row = $db->fetchByAssoc($rs)) {
			$row['product_name'] = Warehouse_Stock_Helper::decodeDisplayText($row['product_name']);
			$row['storage_location'] = Warehouse_Stock_Helper::decodeDisplayText(
				isset($row['storage_location']) ? $row['storage_location'] : ''
			);
			$row['product_name_display'] = Warehouse_Stock_Helper::normalizeDisplayName($row['product_name']);
			$row['code'] = isset($row['code']) ? (string) $row['code'] : '';
			$row['type_label'] = Warehouse_Stock_Helper::formatProductTypeLabel($row['raw_item_type']);
			$row['product_key_display'] = Warehouse_Stock_Helper::formatProductKeyDisplay($row);
			$row['quantity_display'] = Warehouse_Stock_Helper::formatNumber($row['quantity'], 2);
			$row['available_display'] = Warehouse_Stock_Helper::formatNumber($row['available_qty'], 2);
			$row['last_price_display'] = Warehouse_Stock_Helper::formatNumber($row['last_price'], 0);
			$row['updatedtime_display'] = Warehouse_Stock_Helper::formatDateTimeDisplay($row['updatedtime']);
			$row['is_legacy_identity'] = Warehouse_Stock_Helper::isLegacyNameKey($row);
			$avail = (float) $row['available_qty'];
			$row['is_low_stock'] = ($avail > 0 && $avail < 5);
			$exp = isset($row['expired_date']) ? trim((string) $row['expired_date']) : '';
			$row['expired_date_display'] = ($exp !== '' ? date('d/m/Y', strtotime($exp)) : '—');
			$row['is_expiring_soon'] = false;
			$row['is_expired'] = false;
			if ($exp !== '') {
				$row['is_expired'] = ($exp < $today);
				$row['is_expiring_soon'] = (!$row['is_expired'] && $exp <= $threeMonths);
			}
			if ($row['type_label'] === null || $row['type_label'] === '') {
				$row['type_label'] = 'Other';
			}
			if ($expiringOnly && !$row['is_expiring_soon']) {
				continue;
			}
			$rows[] = $row;
		}

		$serialIndexes = Warehouse_Stock_Helper::fetchInboundSerialIndexes($db);
		foreach ($rows as &$r) {
			$r['serial_display'] = '—';
			$r['serial_full'] = '';
			$list = Warehouse_Stock_Helper::resolveInboundSerialsForStockRow($r, $serialIndexes);
			if (!empty($list)) {
				list($r['serial_display'], $r['serial_full']) = Warehouse_Stock_Helper::formatSerialDisplayList($list);
			}
		}
		unset($r);

		// Expiry notifications: once per stock row per day, for current user only.
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		foreach ($rows as $r) {
			if (empty($r['is_expiring_soon'])) {
				continue;
			}
			$stockId = (int) $r['stockid'];
			$msg = sprintf(
				'Hàng sắp hết hạn: %s (%s)',
				(string) $r['product_name_display'],
				(string) $r['expired_date_display']
			);
			$chk = $db->pquery(
				"SELECT id FROM vtiger_notifications WHERE userid = ? AND module = 'Warehouse' AND recordid = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1",
				array($userId, $stockId)
			);
			if ($db->num_rows($chk) > 0) {
				continue;
			}
			$db->pquery(
				"INSERT INTO vtiger_notifications (userid, module, recordid, message, is_read, created_at) VALUES (?, 'Warehouse', ?, ?, 0, NOW())",
				array($userId, $stockId, $msg)
			);
			$didNotify = true;
		}

		$viewer->assign('ROWS', $rows);
		$viewer->assign('SEARCH', $search);
		$viewer->assign('QTY_MIN', $qtyMin);
		$viewer->assign('QTY_MAX', $qtyMax);
		$viewer->assign('FILTER_LOCATION', $location);
		$viewer->assign('FILTER_ITEM_TYPE', $typeFilter);
		$viewer->assign('FILTER_LEGACY_ONLY', $legacyOnly);
		$viewer->assign('FILTER_LOW_STOCK', $lowStockOnly);
		$viewer->assign('FILTER_EXPIRING', $expiringOnly);
		$viewer->assign('SHOW_DELETED', (string) $request->get('deleted') === '1');
		$viewer->assign('SHOW_DELETE_ERROR', (string) $request->get('deleteError') === '1');
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}
}
