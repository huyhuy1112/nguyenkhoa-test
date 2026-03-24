<?php
/**
 * DocumentTemplate - Tools module list view (MVP).
 *
 * Provides:
 * - search by template name
 * - filter by feature
 * - grouping by feature
 */
class DocumentTemplate_List_View extends Vtiger_Index_View {
	protected function isToolsContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if (!$this->isToolsContext($request)) {
			$viewer->view('OperationNotPermitted.tpl', 'Vtiger');
			return;
		}

		$viewer->assign('LISTVIEW_MODULE_TITLE', 'Document Templates');
		$templates = $this->getTemplates($request);
		$viewer->assign('GROUPS', $templates['groups']);
		$viewer->assign('PRESENT_FEATURES', $templates['presentFeatures']);
		$viewer->assign('FEATURES', $templates['features']);
		$viewer->assign('FILTER_FEATURE', $request->get('feature'));
		$viewer->assign('FILTER_SEARCH', (string) $request->get('search'));
		$viewer->view('ListViewContents.tpl', $request->getModule());
	}

	protected function getTemplates(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();

		$features = array('Invoice', 'Quote', 'Contract', 'Other');
		$feature = (string) $request->get('feature');
		if ($feature === '' || !in_array($feature, $features, true)) {
			$feature = '';
		}

		$search = trim((string) $request->get('search'));
		$searchSql = '';
		$params = array();

		if ($search !== '') {
			$searchSql = ' AND dt.templatename LIKE ? ';
			$params[] = '%' . $search . '%';
		}

		$featureSql = '';
		if ($feature !== '') {
			$featureSql = ' AND dt.feature = ? ';
			$params[] = $feature;
		}

		$sql = "
			SELECT
				dt.templateid,
				dt.templatename,
				dt.feature,
				dt.version,
				dt.description,
				dt.updatedtime,
				u.user_name,
				u.first_name,
				u.last_name,
				dt.isdefault
			FROM vtiger_documenttemplates dt
			LEFT JOIN vtiger_users u ON u.id = dt.updatedby
			WHERE dt.deleted = 0
			$searchSql
			$featureSql
			ORDER BY dt.feature ASC, dt.updatedtime DESC, dt.templateid DESC
		";

		$result = $db->pquery($sql, $params);
		$rows = array();
		while ($row = $db->fetchByAssoc($result)) {
			$userFullName = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
			if ($userFullName === '') {
				$userFullName = (string) $row['user_name'];
			}

			$rows[] = array(
				'templateid' => (int) $row['templateid'],
				'templatename' => (string) $row['templatename'],
				'feature' => (string) $row['feature'],
				'version' => (int) $row['version'],
				'description' => (string) $row['description'],
				'updatedtime' => (string) $row['updatedtime'],
				'updatedby_name' => $userFullName,
				'isdefault' => (int) $row['isdefault'],
			);
		}

		$groups = array();
		foreach ($rows as $r) {
			$feat = (string) $r['feature'];
			if (!isset($groups[$feat])) {
				$groups[$feat] = array();
			}
			$groups[$feat][] = $r;
		}
		$presentFeatures = array_keys($groups);

		return array(
			'groups' => $groups,
			'presentFeatures' => $presentFeatures,
			'features' => $features,
		);
	}
}
?>

