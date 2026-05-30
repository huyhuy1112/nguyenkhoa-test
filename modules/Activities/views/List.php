<?php
/*+***********************************************************************************
 * Activities list view (SUPPORT app) — modern split shell UI.
 ************************************************************************************/

require_once 'modules/Support/models/Activities.php';

class Activities_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'List');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);

		$filters = array(
			'status'           => trim((string)$request->get('status')),
			'activity_type'    => trim((string)$request->get('activity_type')),
			'assigned_user_id' => (int)$request->get('assigned_user_id'),
			'activity_date'    => trim((string)$request->get('activity_date')),
			'sort'             => trim((string)$request->get('sort')),
			'q'                => trim((string)$request->get('q')),
		);

		$page = (int)$request->get('page');
		$page = $page > 0 ? $page : 1;
		$pageLimit = 25;

		$listData = Support_Activities_Model::getListData($filters, $page, $pageLimit);

		// Safety net: if count > 0 but list query returned nothing, use upcoming feed.
		if ($listData['total'] > 0 && empty($listData['rows'])) {
			$fallback = Support_Activities_Model::getUpcomingData(null, 0);
			$listData['rows'] = $fallback['all'];
			$listData['counts'] = $fallback['counts'];
			$listData['show_from'] = count($listData['rows']) > 0 ? 1 : 0;
			$listData['show_to'] = count($listData['rows']);
		}

		$users = array();
		$userRes = $db->pquery(
			"SELECT id, first_name, last_name FROM vtiger_users WHERE status='Active' ORDER BY first_name, last_name",
			array()
		);
		while ($userRes && ($row = $db->fetchByAssoc($userRes))) {
			$users[] = $row;
		}

		$viewer->assign('ACTIVITIES', $listData['rows']);
		$viewer->assign('ACTIVITY_COUNTS', $listData['counts']);
		$viewer->assign('TOTAL_COUNT', $listData['total']);
		$viewer->assign('SHOW_FROM', $listData['show_from']);
		$viewer->assign('SHOW_TO', $listData['show_to']);
		$viewer->assign('CURRENT_PAGE', $listData['page']);
		$viewer->assign('PAGE_COUNT', $listData['page_count']);

		$viewer->assign('FILTER_STATUS', $filters['status']);
		$viewer->assign('FILTER_TYPE', $filters['activity_type']);
		$viewer->assign('FILTER_STAFF', $filters['assigned_user_id']);
		$viewer->assign('FILTER_DATE', $filters['activity_date']);
		$viewer->assign('FILTER_SORT', $filters['sort'] !== '' ? $filters['sort'] : 'latest');
		$viewer->assign('FILTER_KEYWORD', $filters['q']);

		$viewer->assign('USERS', $users);
		$viewer->assign('STATUS_OPTIONS', array('Scheduled', 'Ready', 'Completed', 'Skipped'));
		$viewer->assign('TYPE_OPTIONS', array('Follow up', 'Anniversary', 'Meeting', 'Gift', 'Intro', 'Other', 'Task', 'Call', 'Event'));

		$viewer->view('ListViewContents.tpl', $moduleName);
	}
}
