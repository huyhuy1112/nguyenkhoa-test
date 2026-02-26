<?php
/*+***********************************************************************************
 * Get subtasks of a ProjectTask for the SubTask widget
 *************************************************************************************/

class ProjectTask_GetSubtasks_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = $request->get('record');
		if (!$recordId) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		if (!Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView', $recordId)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$parentId = (int) $request->get('record');
		$db = PearDatabase::getInstance();
		$this->ensureParentProjectTaskIdColumn($db);

		$sql = "SELECT vtiger_projecttask.projecttaskid AS recordid,
				vtiger_projecttask.projecttaskname AS name,
				vtiger_projecttask.projecttask_no,
				vtiger_projecttask.projecttaskstatus,
				vtiger_projecttask.projecttaskprogress,
				vtiger_projecttask.projecttaskhours,
				vtiger_projecttask.startdate,
				vtiger_projecttask.enddate,
				vtiger_projecttask.projectid,
				vtiger_crmentity.smownerid,
				vtiger_crmentity.description
				FROM vtiger_projecttask
				INNER JOIN vtiger_crmentity ON vtiger_projecttask.projecttaskid = vtiger_crmentity.crmid
				WHERE vtiger_projecttask.parent_projecttaskid = ? AND vtiger_crmentity.deleted = 0
				ORDER BY vtiger_projecttask.projecttaskid ASC";

		$result = $db->pquery($sql, array($parentId));
		$subtasks = array();
		while ($row = $db->fetchByAssoc($result)) {
			$row['name'] = decode_html($row['name']);
			$row['owner_name'] = getOwnerName($row['smownerid']);
			$row['duration'] = self::formatDuration($row['projecttaskhours']);
			$row['description'] = decode_html($row['description']);
			$row['detail_url'] = 'index.php?module=ProjectTask&view=Detail&record=' . $row['recordid'];
			$row['completed'] = ($row['projecttaskstatus'] === 'Completed' || (int)$row['projecttaskprogress'] >= 100);
			$subtasks[] = $row;
		}

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array('subtasks' => $subtasks));
		$response->emit();
	}

	/**
	 * Format projecttaskhours as display string (e.g. 0.5 -> 30m, 1 -> 1h)
	 */
	public static function formatDuration($hours) {
		if (empty($hours) && $hours !== '0' && $hours !== 0) return '';
		$h = (float) $hours;
		if ($h < 1 && $h > 0) {
			return round($h * 60) . 'm';
		}
		return $h . 'h';
	}

	public static function ensureParentProjectTaskIdColumn() {
		$db = PearDatabase::getInstance();
		$res = $db->pquery("SHOW COLUMNS FROM vtiger_projecttask LIKE 'parent_projecttaskid'", array());
		if ($db->num_rows($res) == 0) {
			@$db->pquery("ALTER TABLE vtiger_projecttask ADD COLUMN parent_projecttaskid INT(11) NULL DEFAULT NULL", array());
		}
	}
}
