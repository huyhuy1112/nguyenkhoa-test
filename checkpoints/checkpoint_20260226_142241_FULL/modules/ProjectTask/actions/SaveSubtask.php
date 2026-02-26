<?php
/*+***********************************************************************************
 * Create a new subtask for a parent ProjectTask
 *************************************************************************************/

class ProjectTask_SaveSubtask_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$parentId = $request->get('parent_record');
		if (!$parentId) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		if (!Users_Privileges_Model::isPermitted('ProjectTask', 'CreateView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		if (!Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView', $parentId)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$parentId = (int) $request->get('parent_record');
		$title = trim($request->get('projecttaskname'));
		$description = trim($request->get('description'));
		$db = PearDatabase::getInstance();

		if (empty($title)) {
			$response = new Vtiger_Response();
			$response->setError(vtranslate('LBL_TITLE_REQUIRED', 'ProjectTask'));
			$response->emit();
			return;
		}

		// Get projectid from parent task
		$parentRes = $db->pquery("SELECT projectid, smownerid FROM vtiger_projecttask 
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projecttask.projecttaskid
			WHERE projecttaskid = ? AND deleted = 0", array($parentId));
		if ($db->num_rows($parentRes) == 0) {
			$response = new Vtiger_Response();
			$response->setError('Parent task not found');
			$response->emit();
			return;
		}
		$projectid = $db->query_result($parentRes, 0, 'projectid');
		$smownerid = $db->query_result($parentRes, 0, 'smownerid');

		// Generate sequence number
		$seqRes = $db->pquery("SELECT COALESCE(MAX(projecttasknumber), 0) + 1 AS nextnum 
			FROM vtiger_projecttask WHERE projectid = ?", array($projectid));
		$projecttasknumber = (int) $db->query_result($seqRes, 0, 'nextnum');

		// Generate record id
		$recordId = $db->getUniqueId('vtiger_crmentity');

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$now = date('Y-m-d H:i:s');

		// Insert crmentity
		$db->pquery(
			"INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, label, deleted)
			 VALUES (?, ?, ?, ?, 'ProjectTask', ?, ?, ?, ?, 0)",
			array($recordId, $currentUser->getId(), $smownerid, $currentUser->getId(), $description, $now, $now, $title)
		);

		// Insert projecttask
		$modentityRes = $db->pquery("SELECT cur_id, prefix FROM vtiger_modentity_num WHERE semodule = 'ProjectTask' AND active = 1 LIMIT 1", array());
		$seqNo = 1;
		if ($db->num_rows($modentityRes) > 0) {
			$curId = (int) $db->query_result($modentityRes, 0, 'cur_id');
			$prefix = $db->query_result($modentityRes, 0, 'prefix');
			$seqNo = $curId + 1;
			$db->pquery("UPDATE vtiger_modentity_num SET cur_id = ? WHERE semodule = 'ProjectTask' AND active = 1", array($seqNo));
		}
		$projecttask_no = 'PT' . str_pad($seqNo, 5, '0', STR_PAD_LEFT);

		$db->pquery(
			"INSERT INTO vtiger_projecttask (projecttaskid, projecttaskname, projecttask_no, projectid, projecttasknumber, parent_projecttaskid)
			 VALUES (?, ?, ?, ?, ?, ?)",
			array($recordId, $title, $projecttask_no, $projectid, $projecttasknumber, $parentId)
		);

		// Insert projecttaskcf if table exists
		$cfCheck = $db->pquery("SHOW TABLES LIKE 'vtiger_projecttaskcf'", array());
		if ($db->num_rows($cfCheck) > 0) {
			$db->pquery("INSERT INTO vtiger_projecttaskcf (projecttaskid) VALUES (?)", array($recordId));
		}

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array(
			'recordid' => $recordId,
			'name' => decode_html($title),
			'detail_url' => 'index.php?module=ProjectTask&view=Detail&record=' . $recordId,
		));
		$response->emit();
	}
}
