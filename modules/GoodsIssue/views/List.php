<?php
class GoodsIssue_List_View extends Vtiger_Index_View {
	protected function preProcessTplName(Vtiger_Request $request) {
		$appName = $request->get('app');
		if ($appName === 'INVENTORY' || $appName === '') {
			return 'ListViewPreProcess.tpl';
		}
		return 'IndexViewPreProcess.tpl';
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

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);

		$search = trim((string) $request->get('search'));
		$dateFrom = trim((string) $request->get('date_from'));
		$dateTo = trim((string) $request->get('date_to'));
		$destination = trim((string) $request->get('destination'));

		$where = array('gi.deleted = 0');
		$params = array();
		if ($search !== '') {
			$where[] = '(gi.subject LIKE ? OR gi.destination LIKE ?)';
			$params[] = '%' . $search . '%';
			$params[] = '%' . $search . '%';
		}
		if ($destination !== '') {
			$where[] = 'gi.destination LIKE ?';
			$params[] = '%' . $destination . '%';
		}
		if ($dateFrom !== '') {
			$where[] = 'gi.issued_date >= ?';
			$params[] = $dateFrom;
		}
		if ($dateTo !== '') {
			$where[] = 'gi.issued_date <= ?';
			$params[] = $dateTo;
		}
		$whereSql = implode(' AND ', $where);

		$rs = $db->pquery(
			"SELECT gi.issueid, gi.code, gi.subject, gi.issued_date, gi.destination, gi.updatedtime,
			        COALESCE(SUM(gii.quantity), 0) AS total_qty
			 FROM vtiger_goodsissue gi
			 LEFT JOIN vtiger_goodsissue_items gii ON gii.issueid = gi.issueid
			 WHERE {$whereSql}
			 GROUP BY gi.issueid
			 ORDER BY gi.issued_date DESC, gi.issueid DESC",
			$params
		);
		$rows = array();
		while ($r = $db->fetchByAssoc($rs)) {
			$rows[] = array(
				'issueid' => (int) $r['issueid'],
				'code' => (string) $r['code'],
				'subject' => (string) $r['subject'],
				'issued_date' => (string) $r['issued_date'],
				'destination' => (string) $r['destination'],
				'updatedtime' => (string) $r['updatedtime'],
				'total_qty' => (float) $r['total_qty'],
			);
		}

		$viewer->assign('ROWS', $rows);
		$viewer->assign('SEARCH', $search);
		$viewer->assign('DATE_FROM', $dateFrom);
		$viewer->assign('DATE_TO', $dateTo);
		$viewer->assign('DESTINATION', $destination);
		$viewer->assign('SHOW_DELETED', (string) $request->get('deleted') === '1');
		$viewer->assign('SHOW_DELETE_ERROR', (string) $request->get('delete_error') === '1');
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}
}
?>