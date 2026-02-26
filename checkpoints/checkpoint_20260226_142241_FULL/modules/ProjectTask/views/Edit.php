<?php
/*+**********************************************************************************
 * ProjectTask Edit: load team group + additional assignees for form.
 *************************************************************************************/

class ProjectTask_Edit_View extends Vtiger_Edit_View {

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

	public function process(Vtiger_Request $request) {
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
			// If this task is assigned to a team group, show group in Assigned To (value = -groupid)
			$gr = $db->pquery("SELECT team_groupid FROM vtiger_projecttask_team_groups WHERE projecttaskid = ?", array($recordId));
			if ($gr && $db->num_rows($gr) > 0) {
				$gid = (int)$db->query_result($gr, 0, 'team_groupid');
				$recordModel->set('assigned_user_id', -$gid);
			}
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$this->record = $recordModel;
		}

		$additionalAssignees = array();
		if (!empty($recordId)) {
			$res = $db->pquery("SELECT userid FROM vtiger_projecttask_assignees WHERE projecttaskid = ?", array($recordId));
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$additionalAssignees[] = (int)$row['userid'];
			}
		}
		$assignableUsers = $currentUser->getAccessibleUsersForModule($moduleName);
		if (!is_array($assignableUsers)) {
			$assignableUsers = array();
		}

		// Team groups for Assigned To dropdown (value = -groupid)
		$teamGroupsForOwner = array();
		if ($this->loadTeamsModel()) {
			Teams_Module_Model::ensureGroupSchema();
			$grRes = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY group_name", array());
			while ($grRes && ($gRow = $db->fetchByAssoc($grRes))) {
				$teamGroupsForOwner[-(int)$gRow['groupid']] = $gRow['group_name'];
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('ADDITIONAL_ASSIGNEES', $additionalAssignees);
		$viewer->assign('ASSIGNABLE_USERS', $assignableUsers);
		$viewer->assign('TEAM_GROUPS_FOR_OWNER', $teamGroupsForOwner);

		parent::process($request);
	}

	/**
	 * Load ProjectTeamGroup.js so Assigned To team group + _team_group_id work on ProjectTask Edit.
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'layouts.v7.modules.Project.resources.ProjectTeamGroup',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
