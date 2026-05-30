<?php
/*+**********************************************************************************
 * Documents History view - hiển thị lịch sử chỉnh sửa / di chuyển / xóa / upload
 * Dữ liệu lấy từ vtiger_modtracker_basic (ModTracker)
 ************************************************************************************/

class Documents_History_View extends Vtiger_Index_View {

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($request->get('app') === 'MANAGEMENT') {
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
			$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
			$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
			if (isset($menuGroupedByParent['MANAGEMENT'])) {
				$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
			}
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($request->get('app') === 'MANAGEMENT') {
			return 'HistoryViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		if ($request->get('app') === 'MANAGEMENT') {
			$viewer->view('HistoryViewPostProcess.tpl', $moduleName);
		} else {
			$viewer->view('IndexPostProcess.tpl', $moduleName);
		}
		parent::postProcess($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($request->get('app') !== 'MANAGEMENT') {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Documents/resources/DocumentsMkHistory.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($request->get('app') !== 'MANAGEMENT') {
			return $headerScriptInstances;
		}
		$jsFileNames = array(
			'~layouts/v7/modules/Documents/resources/DocumentsMkHistory.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$app = $request->get('app');

		$db = PearDatabase::getInstance();
		$params = array('Documents');

		$sql = "SELECT b.id, b.crmid, b.module, b.whodid, b.changedon, b.status,
					ce.label AS record_label,
					u.first_name, u.last_name, u.user_name
				FROM vtiger_modtracker_basic b
				INNER JOIN vtiger_crmentity ce ON ce.crmid = b.crmid AND ce.deleted = 0
				LEFT JOIN vtiger_users u ON u.id = b.whodid
				WHERE b.module = ?
				ORDER BY b.changedon DESC
				LIMIT 100";

		$result = $db->pquery($sql, $params);
		$rows = array();
		if ($result) {
			while ($row = $db->fetchByAssoc($result)) {
				$label = $row['record_label'];
				$userFullName = trim($row['first_name'] . ' ' . $row['last_name']);
				if ($userFullName === '') {
					$userFullName = $row['user_name'];
				}
				$action = $this->getStatusLabel((int)$row['status']);
				$detailUrl = 'index.php?module=Documents&view=Detail&record=' . (int)$row['crmid'];
				if ($app === 'MANAGEMENT') {
					$detailUrl .= '&app=MANAGEMENT';
				}
				$rows[] = array(
					'id' => (int)$row['id'],
					'crmid' => (int)$row['crmid'],
					'label' => $label,
					'changedon' => $row['changedon'],
					'user' => $userFullName,
					'userInitial' => $this->getUserInitial($userFullName),
					'action' => $action,
					'actionSlug' => $this->getActionSlug($action),
					'detailUrl' => $detailUrl,
				);
			}
		}

		$viewer->assign('HISTORY_ROWS', $rows);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('History.tpl', $moduleName);
	}

	protected function getUserInitial($name) {
		$name = trim($name);
		if ($name === '') {
			return '?';
		}
		$parts = preg_split('/\s+/', $name);
		if (count($parts) >= 2) {
			return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
		}
		return strtoupper(substr($name, 0, 1));
	}

	protected function getActionSlug($action) {
		return strtolower(preg_replace('/[^a-z0-9]+/i', '', $action));
	}

	protected function getStatusLabel($status) {
		require_once 'modules/ModTracker/ModTracker.php';
		switch ($status) {
			case ModTracker::$CREATED: return 'Created';
			case ModTracker::$UPDATED: return 'Updated';
			case ModTracker::$DELETED: return 'Deleted';
			case ModTracker::$RESTORED: return 'Restored';
			case ModTracker::$LINK: return 'Related';
			case ModTracker::$UNLINK: return 'Unrelated';
			default: return 'Updated';
		}
	}
}
