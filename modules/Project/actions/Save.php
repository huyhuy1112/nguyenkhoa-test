<?php
/*+***********************************************************************************
 * Project Save: xử lý assign team group (assigned_user_id < 0) và lưu project_team_groups
 *************************************************************************************/

class Project_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		// Save form chuẩn (non-ajax): cần xử lý team group mapping giống SaveAjax.
		parent::process($request);
	}

	/**
	 * Sau khi save Project, đồng bộ mapping team group để Assigned To hiển thị đúng.
	 * - owner < 0  => lưu team_groupid = abs(owner)
	 * - owner >= 0 => xóa mapping nhóm cũ
	 */
	public function saveRecord(Vtiger_Request $request) {
		$recordModel = parent::saveRecord($request);
		$projectId = (int)$recordModel->getId();
		if ($projectId <= 0) {
			return $recordModel;
		}

		$db = PearDatabase::getInstance();
		if (class_exists('Teams_Module_Model')) {
			Teams_Module_Model::ensureProjectAssignSchema();
		}

		$teamGroupId = $this->resolveTeamGroupIdFromRequest($request);
		$db->pquery("DELETE FROM vtiger_project_team_groups WHERE projectid = ?", array($projectId));
		if ($teamGroupId > 0) {
			$db->pquery(
				"INSERT INTO vtiger_project_team_groups (projectid, team_groupid) VALUES (?, ?)",
				array($projectId, $teamGroupId)
			);
		}

		return $recordModel;
	}

	/**
	 * Resolve team group id safely from request.
	 * Priority:
	 * 1) assigned_user_id negative => team group id = abs(owner)
	 * 2) assigned_user_id non-negative => no team group (0)
	 * 3) fallback _team_group_id when owner absent
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
}
