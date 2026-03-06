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
		$field = (string)$request->get('field');
		$value = $request->get('value');
		$isInlineOwnerUpdate = ($field === 'assigned_user_id' && $request->get('record'));
		$inlineOwnerValue = ($isInlineOwnerUpdate && $value !== null && $value !== '') ? (int)$value : null;

		// Inline edit owner -> user/group thường: đảm bảo không còn _team_group_id cũ đi kèm.
		if ($isInlineOwnerUpdate && $inlineOwnerValue !== null && $inlineOwnerValue >= 0) {
			$request->set('_team_group_id', 0);
		}

		// Khi assign team group (assigned_user_id < 0): giữ nguyên -groupid để cột Assigned To hiển thị tên nhóm
		parent::process($request);

		// Safety net: sau khi save inline owner về user/group thường, xóa mapping team group còn sót.
		if ($isInlineOwnerUpdate && $inlineOwnerValue !== null && $inlineOwnerValue >= 0) {
			$projectId = (int)$request->get('record');
			if ($projectId > 0) {
				$db = PearDatabase::getInstance();
				$db->pquery("DELETE FROM vtiger_project_team_groups WHERE projectid = ?", array($projectId));
			}
		}
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

		// Team group: chỉ nhận khi owner hiện tại là negative id (team group).
		// Nếu owner là user/group thường (>=0) thì bắt buộc clear team group mapping.
		$teamGroupId = $this->resolveTeamGroupIdFromRequest($request);
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

	/**
	 * Resolve team group id safely from request.
	 * Priority:
	 * 1) If assigned_user_id (or inline value) is negative => use abs(value) as team group id.
	 * 2) If assigned_user_id is non-negative => force no team group (return 0).
	 * 3) Fallback to _team_group_id only when owner is not explicitly provided.
	 */
	protected function resolveTeamGroupIdFromRequest(Vtiger_Request $request) {
		$rawOwner = $request->get('assigned_user_id');
		$field = (string)$request->get('field');
		if (($rawOwner === null || $rawOwner === '') && $field === 'assigned_user_id') {
			$rawOwner = $request->get('value');
		}

		if ($rawOwner !== null && $rawOwner !== '') {
			$ownerId = (int)$rawOwner;
			if ($ownerId < 0) {
				return abs($ownerId);
			}
			return 0;
		}

		return (int)$request->get('_team_group_id');
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
