<?php
class GoodsIssue_Edit_View extends Vtiger_Index_View {

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
		$this->assignInventoryContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('IndexPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	protected function normalizeTypeLabel($value) {
		$v = strtolower(trim((string) $value));
		if ($v === '') return 'Other';
		if (in_array($v, array('hardware','product','products'), true)) return 'Hardware';
		if ($v === 'software') return 'Software';
		if (in_array($v, array('service','services'), true)) return 'Service';
		return 'Other';
	}

	protected function loadIssue(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT * FROM vtiger_goodsissue WHERE issueid = ? LIMIT 1",
			array($issueId)
		);
		if ($db->num_rows($rs) <= 0) return null;
		return $db->fetchByAssoc($rs);
	}

	protected function loadItems(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT * FROM vtiger_goodsissue_items WHERE issueid = ? ORDER BY itemid ASC",
			array($issueId)
		);
		$items = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$pid = !empty($row['productid']) ? (int) $row['productid'] : 0;
			$pname = trim((string) $row['product_name']);
			$keyHint = $pid > 0 ? ('P:' . $pid) : ($pname !== '' ? ('N:' . mb_strtolower($pname)) : '');
			$items[] = array(
				'productid' => $pid,
				'product_name' => (string) $row['product_name'],
				'product_type' => $this->normalizeTypeLabel($row['product_type']),
				'quantity' => (float) $row['quantity'],
				'unit_price' => (float) $row['unit_price'],
				'discount_percent' => isset($row['discount_percent']) ? (float) $row['discount_percent'] : 0.0,
				'serial_number' => isset($row['serial_number']) ? (string) $row['serial_number'] : '',
				'line_note' => (string) $row['line_note'],
				'product_key_hint' => $keyHint,
			);
		}
		return $items;
	}

	protected function loadStockByProductKeys(PearDatabase $db, array $keys) {
		$keys = array_values(array_unique(array_filter($keys)));
		if (empty($keys)) return array();
		$placeholders = implode(',', array_fill(0, count($keys), '?'));
		$rs = $db->pquery(
			"SELECT product_key, productid, product_name, product_type, quantity, shrinkage_qty, storage_location, last_price
			 FROM vtiger_warehouse_stock
			 WHERE product_key IN ($placeholders)",
			$keys
		);
		$map = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$map[(string)$row['product_key']] = $row;
		}
		return $map;
	}

	protected function loadAttachments(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT attachmentid, filename, stored_name, filetype, filesize, createdtime
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

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);

		$issueId = (int) $request->get('record');
		$mode = $issueId > 0 ? 'edit' : 'create';

		$issuedByDefault = '';
		try {
			$issuedByDefault = (string) Users_Record_Model::getCurrentUserModel()->get('user_name');
		} catch (Throwable $e) {
			$issuedByDefault = '';
		}

		$issue = array(
			'issueid' => 0,
			'code' => '',
			'subject' => '',
			'issued_date' => date('Y-m-d'),
			'issued_by' => $issuedByDefault,
			'destination' => '',
			'storage_location' => '',
			'note' => '',
		);
		$items = array(
			array('productid' => 0, 'product_name' => '', 'product_type' => 'Other', 'quantity' => 1, 'unit_price' => 0, 'line_note' => ''),
		);

		if ($issueId > 0) {
			$row = $this->loadIssue($db, $issueId);
			if (!$row || (int)$row['deleted'] === 1) {
				header('Location: index.php?module=GoodsIssue&view=List&app=INVENTORY');
				exit;
			}
			$issue = array(
				'issueid' => (int) $row['issueid'],
				'code' => isset($row['code']) ? (string) $row['code'] : '',
				'subject' => (string) $row['subject'],
				'issued_by' => isset($row['issued_by']) ? (string) $row['issued_by'] : '',
				'issued_date' => (string) $row['issued_date'],
				'destination' => (string) $row['destination'],
				'storage_location' => (string) $row['storage_location'],
				'note' => (string) $row['note'],
			);
			$items = $this->loadItems($db, $issueId);
			if (empty($items)) {
				$items = array(
					array('productid' => 0, 'product_name' => '', 'product_type' => 'Other', 'quantity' => 1, 'unit_price' => 0, 'line_note' => '', 'product_key_hint' => ''),
				);
			}
		}

		$attachments = array();
		if ($issueId > 0) {
			$attachments = $this->loadAttachments($db, $issueId);
		}

		// Product picker options load via AJAX (GoodsIssue_SearchProducts_Action) using name/code prefix match.

		// Enrich existing line items with available/location/type hints (UI only).
		$keys = array();
		foreach ($items as $it) {
			if (!empty($it['productid'])) {
				$keys[] = 'P:' . (int) $it['productid'];
			} else {
				$keys[] = 'N:' . mb_strtolower(trim((string) $it['product_name']));
			}
		}
		$stockByKey = $this->loadStockByProductKeys($db, $keys);
		foreach ($items as &$it) {
			$isStockLinked = !empty($it['productid']);
			$k = $isStockLinked ? ('P:' . (int) $it['productid']) : ('N:' . mb_strtolower(trim((string) $it['product_name'])));
			$it['is_stock_linked'] = $isStockLinked;

			if (!isset($stockByKey[$k])) {
				$it['available_qty'] = null;
				$it['stock_location'] = '';
				continue;
			}

			$st = $stockByKey[$k];
			$q = isset($st['quantity']) ? (float) $st['quantity'] : 0.0;
			$sh = isset($st['shrinkage_qty']) ? (float) $st['shrinkage_qty'] : 0.0;
			$avail = $q - $sh;
			if ($avail < 0) $avail = 0.0;

			$it['available_qty'] = (float) $avail;
			$it['stock_location'] = isset($st['storage_location']) ? (string) $st['storage_location'] : '';
			$it['identity_type'] = !empty($st['productid']) ? 'catalog' : 'legacy';

			if (!empty($st['product_type'])) {
				$it['product_type'] = $this->normalizeTypeLabel($st['product_type']);
			}
		}
		unset($it);

		$viewer->assign('MODE', $mode);
		$viewer->assign('ISSUE', $issue);
		$viewer->assign('ITEMS', $items);
		$viewer->assign('ATTACHMENTS', $attachments);

		$viewer->assign('SHOW_VALIDATION', (string) $request->get('validation') === '1');
		$viewer->assign('SHOW_OUT_OF_STOCK', (string) $request->get('out_of_stock') === '1');
		$viewer->assign('SHOW_ERROR_STOCK_MISSING', (string) $request->get('error_stock_missing') === '1');

		$viewer->view('EditView.tpl', $request->getModule());
	}
}

?>

