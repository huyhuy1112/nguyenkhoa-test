<?php
class GoodsReceipt_List_View extends Vtiger_Index_View {
	protected function preProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
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
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Inbound');

		$search = trim((string) $request->get('search'));
		$dateFrom = trim((string) $request->get('date_from'));
		$dateTo = trim((string) $request->get('date_to'));
		$location = trim((string) $request->get('storage_location'));

		$where = array("r.deleted = 0");
		$params = array();
		if ($search !== '') {
			$where[] = "(r.subject LIKE ? OR r.source_name LIKE ?)";
			$params[] = '%' . $search . '%';
			$params[] = '%' . $search . '%';
		}
		if ($dateFrom !== '') {
			$where[] = "r.received_date >= ?";
			$params[] = $dateFrom;
		}
		if ($dateTo !== '') {
			$where[] = "r.received_date <= ?";
			$params[] = $dateTo;
		}
		if ($location !== '') {
			$where[] = "r.storage_location LIKE ?";
			$params[] = '%' . $location . '%';
		}

		$sql = "SELECT
					r.receiptid, r.code, r.subject, r.source_name, r.received_date, r.storage_location,
					r.updatedtime, r.note,
					COALESCE(SUM(i.quantity), 0) AS total_qty,
					COALESCE(SUM(i.quantity * i.unit_price), 0) AS total_value
				FROM vtiger_goodsreceipt r
				LEFT JOIN vtiger_goodsreceipt_items i ON i.receiptid = r.receiptid
				WHERE " . implode(' AND ', $where) . "
				GROUP BY r.receiptid
				ORDER BY r.createdtime DESC, r.receiptid DESC";
		$result = $db->pquery($sql, $params);
		$rows = array();
		while ($row = $db->fetchByAssoc($result)) {
			$rows[] = array(
				'receiptid' => (int) $row['receiptid'],
				'code' => (string) $row['code'],
				'subject' => (string) $row['subject'],
				'source_name' => (string) $row['source_name'],
				'received_date' => (string) $row['received_date'],
				'storage_location' => (string) $row['storage_location'],
				'updatedtime' => (string) $row['updatedtime'],
				'note' => (string) $row['note'],
				'total_qty' => (string) $row['total_qty'],
				'total_value' => (string) $row['total_value'],
				'total_qty_display' => number_format((float) $row['total_qty'], 2, '.', ','),
				'total_value_display' => number_format((float) $row['total_value'], 0, '.', ','),
			);
		}

		$viewer->assign('ROWS', $rows);
		$viewer->assign('SEARCH', $search);
		$viewer->assign('DATE_FROM', $dateFrom);
		$viewer->assign('DATE_TO', $dateTo);
		$viewer->assign('STORAGE_LOCATION', $location);
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}
}
?>