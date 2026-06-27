<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Project_Detail_View extends Vtiger_Detail_View {

	protected function isManagementShell(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		return $app === 'MANAGEMENT' || $app === '';
	}

	protected function assignManagementContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Project');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isManagementShell($request) && empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
			$_REQUEST['app'] = 'MANAGEMENT';
		}
		parent::preProcess($request, $display);
		if ($this->isManagementShell($request)) {
			$this->assignManagementContext($request);
		}
	}
	
	function __construct() {
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
        $this->exposeMethod('showChart');
		$this->exposeMethod('showTaskBoard');
	}

	public function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_SUMMARY);
		
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
        $viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_INFORMATION', $recordModel->getSummaryInfo());
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);

		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}
	
	/**
	 * Function returns related records based on related moduleName
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showRelatedRecords(Vtiger_Request $request) {
		$parentId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$relatedModuleName = $request->get('relatedModule');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$whereCondition = $request->get('whereCondition');
		$moduleName = $request->getModule();
		$relatedModuleInstance = Vtiger_Module_Model::getInstance($relatedModuleName);
		
		if($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "icon-chevron-down";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "icon-chevron-up";
		}
		
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);
		$relatedModuleModel = $relationListView->getRelationModel()->getRelationModuleModel();
		
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder', $sortOrder);
		}

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}
		
		if ($whereCondition) {
			$relationListView->set('whereCondition', $whereCondition);
		}
		
		$models = $relationListView->getEntries($pagingModel);
		$totalCount = $relationListView->getRelatedEntriesCount();
		$header = $relationListView->getHeaders();
		//ProjectTask Progress and Status should show in Projects summary view 
		if($relatedModuleName == 'ProjectTask') {
			$fieldModel = Vtiger_Field_Model::getInstance('projecttaskstatus', $relatedModuleInstance);
			if($fieldModel && $fieldModel->isViewableInDetailView()) {
				$header['projecttaskstatus'] = $relatedModuleModel->getField('projecttaskstatus');
			}
			$fieldModel = Vtiger_Field_Model::getInstance('projecttaskprogress', $relatedModuleInstance);
			if($fieldModel && $fieldModel->isViewableInDetailView()) {
				$header['projecttaskprogress'] = $relatedModuleModel->getField('projecttaskprogress');
			}
		}
		
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE' , $moduleName);
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE' , $relatedModuleName);
		$viewer->assign('RELATED_MODULE_MODEL', $relatedModuleInstance);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('TOTAL_RELATED_ENTRIES', $totalCount);

		return $viewer->view('SummaryWidgets.tpl', $moduleName, 'true');
	}

	/**
	 * Updates tab — limit 5 per page + total count for "Xem thêm" logic.
	 */
	function showRecentActivities(Vtiger_Request $request) {
		if (empty($request->get('limit'))) {
			$request->set('limit', 5);
		}
		$this->_showRecentActivities($request);
		$viewer = $this->getViewer($request);
		$viewer->assign(
			'TOTAL_UPDATES_COUNT',
			ModTracker_Record_Model::getTotalRecordCount($request->get('record'))
		);
		echo $viewer->view('RecentActivities.tpl', $request->getModule(), true);
	}

	/**
	 * Function to show Gantt chart
	 * @param Vtiger_Request $request
	 */
	public function showChart(Vtiger_Request $request) {
		$parentId = $request->get('record');
		$projectTasks = array();
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$projectTaskModel = Vtiger_Module_Model::getInstance('ProjectTask');
		$projectTasks['tasks'] = $parentRecordModel->getProjectTasks();
		$projectTasks["selectedRow"] = 0;
		$projectTasks["canWrite"] = true;
		$projectTasks["canWriteOnParent"] = true;
		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_ID', $parentId);
		$viewer->assign('MODULE' , $moduleName);
		$viewer->assign('PROJECT_TASKS' , $projectTasks);
		$viewer->assign('SCRIPTS',$this->getHeaderScripts($request));
		$viewer->assign('TASK_STATUS', Vtiger_Util_Helper::getRoleBasedPicklistValues('projecttaskstatus', $currentUserModel->get('roleid')));
		$viewer->assign('TASK_STATUS_COLOR', $parentRecordModel->getStatusColors());
		$viewer->assign('STYLES',$this->getHeaderCss($request));
		$viewer->assign('USER_DATE_FORMAT', $currentUserModel->get('date_format'));
		$viewer->assign('STATUS_FIELD_MODEL', Vtiger_Field_Model::getInstance('projecttaskstatus', $projectTaskModel));

		return $viewer->view('ShowChart.tpl', $moduleName, 'true');
	}

	/**
	 * Function to show Project Task board
	 * @param Vtiger_Request $request
	 */
	public function showTaskBoard(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$tasks = array();
		if (method_exists($recordModel, 'getProjectTasksForBoard')) {
			$tasks = $recordModel->getProjectTasksForBoard();
		}

		$columns = array(
			'Backlog' => array(),
			'In Progress' => array(),
			'Completed' => array(),
			'Redundancy' => array(),
		);
		foreach ($tasks as $task) {
			$status = $task['projecttaskstatus'];
			if ($status === 'In Progress') {
				$columns['In Progress'][] = $task;
			} elseif ($status === 'Completed') {
				$columns['Completed'][] = $task;
			} elseif ($status === 'Open' || $status === 'Backlog' || empty($status)) {
				$columns['Backlog'][] = $task;
			} else {
				$columns['Redundancy'][] = $task;
			}
		}

		$createTaskUrl = 'index.php?module=ProjectTask&view=Edit&sourceModule=Project&sourceRecord='
			.$recordId.'&relationOperation=true';
		$statusMap = array(
			'Backlog' => 'Open',
			'In Progress' => 'In Progress',
			'Completed' => 'Completed',
			'Redundancy' => 'Open',
		);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$taskStatus = Vtiger_Util_Helper::getRoleBasedPicklistValues('projecttaskstatus', $currentUserModel->get('roleid'));
		foreach (array('Deferred', 'Cancelled', 'On Hold', 'Waiting') as $redundancyCandidate) {
			if (in_array($redundancyCandidate, $taskStatus, true)) {
				$statusMap['Redundancy'] = $redundancyCandidate;
				break;
			}
		}
		$users = Users_Record_Model::getAll(true);
		$userOptions = array();
		foreach ($users as $userId => $userModel) {
			$userOptions[$userId] = $userModel->getName();
		}

		$projectName = $recordModel ? $recordModel->get('projectname') : '';
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('PROJECT_NAME', $projectName);
		$viewer->assign('TASK_COLUMNS', $columns);
		$viewer->assign('CREATE_TASK_URL', $createTaskUrl);
		$viewer->assign('STATUS_MAP', $statusMap);
		$viewer->assign('TASK_STATUS', $taskStatus);
		$viewer->assign('TASK_USERS', $userOptions);
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));

		return $viewer->view('TaskBoard.tpl', $moduleName, true);
	}

	/**
	 * Function get gantt specific headerscript
	 * @param Vtiger_Request $request
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~/libraries/jquery/gantt/libs/jquery.livequery.min.js',
			'~/libraries/jquery/gantt/libs/jquery.timers.js',
			'~/libraries/jquery/gantt/libs/platform.js',
			'~/libraries/jquery/gantt/libs/date.js',
			'~/libraries/jquery/gantt/libs/i18nJs.js',
			'~/libraries/jquery/gantt/libs/JST/jquery.JST.js',
			'~/libraries/jquery/gantt/libs/jquery.svg.min.js',
			'~/libraries/jquery/gantt/ganttUtilities.js',
			'~/libraries/jquery/gantt/ganttTask.js',
			'~/libraries/jquery/gantt/ganttDrawerSVG.js',
			'~/libraries/jquery/gantt/ganttGridEditor.js',
			'~/libraries/jquery/gantt/ganttMaster.js',
			'~/libraries/jquery/gantt/libs/moment.min.js',
			'~/libraries/jquery/colorpicker/js/colorpicker.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances,$jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the css styles for gantt chart
	 * @param  Vtiger_Request $request
	 */
	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~/libraries/jquery/gantt/platform.css',
			'~/libraries/jquery/gantt/gantt.css',
			'~/libraries/jquery/colorpicker/css/colorpicker.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
?>
