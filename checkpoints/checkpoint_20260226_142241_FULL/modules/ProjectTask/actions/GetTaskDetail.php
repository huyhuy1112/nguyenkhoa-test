<?php
/*+***********************************************************************************
 * Get full task/subtask detail for use in Task Detail Panel (same shape as board task)
 *************************************************************************************/

class ProjectTask_GetTaskDetail_Action extends Vtiger_Action_Controller {

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
		$recordId = (int) $request->get('record');
		$db = PearDatabase::getInstance();

		$res = $db->pquery("SHOW COLUMNS FROM vtiger_projecttask LIKE 'parent_projecttaskid'", array());
		$hasParent = ($db->num_rows($res) > 0);
		$parentSelect = $hasParent ? ", vtiger_projecttask.parent_projecttaskid" : "";

		$sql = "SELECT vtiger_projecttask.projecttaskid AS recordid,
				vtiger_projecttask.projecttaskname AS name,
				vtiger_projecttask.projecttask_no,
				vtiger_projecttask.projectid,
				vtiger_project.projectname AS project_name,
				vtiger_projecttask.startdate,
				vtiger_projecttask.enddate,
				vtiger_projecttask.projecttaskstatus,
				vtiger_projecttask.projecttaskprogress,
				vtiger_projecttask.projecttaskhours,
				vtiger_crmentity.smownerid,
				vtiger_crmentity.description,
				vtiger_crmentity.createdtime
				" . $parentSelect . "
				FROM vtiger_projecttask
				INNER JOIN vtiger_crmentity ON vtiger_projecttask.projecttaskid = vtiger_crmentity.crmid
				LEFT JOIN vtiger_project ON vtiger_project.projectid = vtiger_projecttask.projectid
				WHERE vtiger_projecttask.projecttaskid = ? AND vtiger_crmentity.deleted = 0";

		$result = $db->pquery($sql, array($recordId));
		if ($db->num_rows($result) === 0) {
			$response = new Vtiger_Response();
			$response->setEmitType(Vtiger_Response::$EMIT_JSON);
			$response->setError(new Exception('Task not found'));
			$response->emit();
			return;
		}

		$row = $db->fetchByAssoc($result);
		$row['name'] = decode_html($row['name']);
		$row['project_name'] = isset($row['project_name']) ? decode_html($row['project_name']) : '';
		$row['owner_name'] = getOwnerName($row['smownerid']);
		$row['progress'] = $row['projecttaskprogress'];
		$row['description'] = decode_html($row['description']);
		$row['createdtime_display'] = $this->getTimeAgoDisplay($row['createdtime']);
		$row['comment_count'] = $this->getCommentCount($db, $recordId);
		if (!isset($row['parent_projecttaskid'])) {
			$row['parent_projecttaskid'] = null;
		}

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array('task' => $row));
		$response->emit();
	}

	protected function getTimeAgoDisplay($datetime) {
		if (empty($datetime)) return '';
		$ts = strtotime($datetime);
		$diff = time() - $ts;
		if ($diff < 60) return $diff . 'm';
		if ($diff < 3600) return floor($diff / 60) . 'm';
		if ($diff < 86400) return floor($diff / 3600) . 'h';
		if ($diff < 604800) return floor($diff / 86400) . 'd';
		if ($diff < 2592000) return floor($diff / 604800) . 'w';
		return date('M j', $ts);
	}

	protected function getCommentCount($db, $taskId) {
		$res = $db->pquery("SELECT COUNT(*) AS cnt FROM vtiger_modcomments WHERE related_to = ?", array($taskId));
		$row = $db->fetchByAssoc($res);
		return (int)$row['cnt'];
	}
}
