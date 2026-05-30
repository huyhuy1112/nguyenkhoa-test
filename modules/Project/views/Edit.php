<?php
/*+**********************************************************************************
 * Project Edit: team groups + modern MANAGEMENT create shell (sidebar + topbar).
 *************************************************************************************/

class Project_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernProjectCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		if (!empty($request->get('record')) && !$request->get('isDuplicate')) {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		return $app === 'MANAGEMENT' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Project');
		$viewer->assign('MK_MODERN_PROJECT_CREATE', true);
	}

	protected function ensureManagementApp(Vtiger_Request $request) {
		if (empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
		}
	}

	/**
	 * Best-effort load Teams module model to avoid fatal if autoload fails.
	 */
	protected function loadTeamsModel() {
		if (class_exists('Teams_Module_Model')) {
			return true;
		}
		$path = 'modules/Teams/models/Module.php';
		if (file_exists($path)) {
			require_once $path;
		}
		return class_exists('Teams_Module_Model');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernProjectCreate($request)) {
			$this->ensureManagementApp($request);
			parent::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernProjectCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernProjectCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernProjectCreate($request)) {
			$this->ensureManagementApp($request);
			$this->assignModernContext($request);
		}

		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		if (!empty($recordId) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record ? $this->record : Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$this->record = $recordModel;
		} elseif (!empty($recordId)) {
			$recordModel = $this->record ? $this->record : Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$this->record = $recordModel;

			if ($this->loadTeamsModel()) {
				Teams_Module_Model::ensureProjectAssignSchema();
			}
			$gr = $db->pquery('SELECT team_groupid FROM vtiger_project_team_groups WHERE projectid = ?', array($recordId));
			if ($gr && $db->num_rows($gr) > 0) {
				$gid = (int) $db->query_result($gr, 0, 'team_groupid');
				$recordModel->set('assigned_user_id', -$gid);
			}
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$this->record = $recordModel;
		}

		$additionalAssignees = array();
		if (!empty($recordId)) {
			$res = $db->pquery('SELECT userid FROM vtiger_project_assignees WHERE projectid = ?', array($recordId));
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$additionalAssignees[] = (int) $row['userid'];
			}
		}
		$assignableUsers = $currentUser->getAccessibleUsersForModule($moduleName);
		if (!is_array($assignableUsers)) {
			$assignableUsers = array();
		}

		$teamGroupsForOwner = array();
		if ($this->loadTeamsModel()) {
			Teams_Module_Model::ensureGroupSchema();
			$grRes = $db->pquery('SELECT groupid, group_name FROM vtiger_team_groups ORDER BY group_name', array());
			while ($grRes && ($gRow = $db->fetchByAssoc($grRes))) {
				$teamGroupsForOwner[-(int) $gRow['groupid']] = $gRow['group_name'];
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('ADDITIONAL_ASSIGNEES', $additionalAssignees);
		$viewer->assign('ASSIGNABLE_USERS', $assignableUsers);
		$viewer->assign('TEAM_GROUPS_FOR_OWNER', $teamGroupsForOwner);

		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isMkModernProjectCreate($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/Project/resources/ProjectMkEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'layouts.v7.modules.Project.resources.ProjectTeamGroup',
		);
		if ($this->isMkModernProjectCreate($request)) {
			$jsFileNames[] = 'layouts.v7.modules.Project.resources.ProjectMkEdit';
		}
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
