<?php
class Warehouse_List_View extends Vtiger_Index_View {
	protected function preProcessTplName(Vtiger_Request $request) { return 'ListViewPreProcess.tpl'; }
	public function preProcess(Vtiger_Request $request, $display = true) { parent::preProcess($request, $display); }
	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Storage');

		$search = trim((string) $request->get('search'));
		$where = '';
		$params = array();
		if ($search !== '') {
			$where = " WHERE product_name LIKE ? ";
			$params[] = '%' . $search . '%';
		}
		$rs = $db->pquery(
			"SELECT product_name, quantity, last_price, updatedtime
			 FROM vtiger_warehouse_stock {$where}
			 ORDER BY quantity DESC, product_name ASC",
			$params
		);
		$rows = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$rows[] = $row;
		}
		$viewer->assign('ROWS', $rows);
		$viewer->assign('SEARCH', $search);
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}
}
?>