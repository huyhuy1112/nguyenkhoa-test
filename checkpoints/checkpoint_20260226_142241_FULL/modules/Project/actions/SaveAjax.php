<?php
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Project_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('saveColor');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		// Khi assign team group (assigned_user_id < 0): giữ nguyên -groupid để cột Assigned To hiển thị tên nhóm
		parent::process($request);
	}

	/**
	 * Sau khi save, ghi vtiger_project_team_groups và vtiger_project_assignees.
	 */
	public function saveRecord(Vtiger_Request $request) {
		$recordModel = parent::saveRecord($request);
		$projectId = (int) $recordModel->getId();
		if ($projectId <= 0) return $recordModel;

		$db = PearDatabase::getInstance();
		if (class_exists('Teams_Module_Model')) {
			Teams_Module_Model::ensureProjectAssignSchema();
		}

		// Team group: _team_group_id từ request (gửi bởi ProjectTeamGroup.js)
		$teamGroupId = (int) $request->get('_team_group_id');
		$db->pquery("DELETE FROM vtiger_project_team_groups WHERE projectid = ?", array($projectId));
		if ($teamGroupId > 0) {
			$db->pquery("INSERT INTO vtiger_project_team_groups (projectid, team_groupid) VALUES (?, ?)",
				array($projectId, $teamGroupId));
		}

		// Additional assignees: mảng user ids
		$assignees = $request->get('_additional_assignees');
		if (!is_array($assignees)) {
			$assignees = array();
		}
		$db->pquery("DELETE FROM vtiger_project_assignees WHERE projectid = ?", array($projectId));
		foreach ($assignees as $uid) {
			$uid = (int) $uid;
			if ($uid > 0) {
				$db->pquery("INSERT IGNORE INTO vtiger_project_assignees (projectid, userid) VALUES (?, ?)",
					array($projectId, $uid));
			}
		}

		return $recordModel;
	}

	function saveColor(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$color = $request->get('color');
		$status = $request->get('status');

		$db->pquery('INSERT INTO vtiger_projecttask_status_color(status,color) VALUES(?,?) ON DUPLICATE KEY UPDATE color = ?', array($status, $color, $color));
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(true);
		$response->emit();
	}

}
