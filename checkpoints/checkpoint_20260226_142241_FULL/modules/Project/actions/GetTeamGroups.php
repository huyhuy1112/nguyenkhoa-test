<?php
/*+**********************************************************************************
 * Returns team groups for Assigned To dropdown (value = -groupid for UI).
 *************************************************************************************/

class Project_GetTeamGroups_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted($request->getModule(), 'EditView')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateReadAccess();
	}

	public function process(Vtiger_Request $request) {
		Teams_Module_Model::ensureGroupSchema();
		$db = PearDatabase::getInstance();
		$list = array();
		$res = $db->pquery(
			"SELECT g.groupid, g.group_name,
				(SELECT COUNT(*) FROM vtiger_team_group_users gu WHERE gu.groupid = g.groupid) AS member_count
			 FROM vtiger_team_groups g
			 ORDER BY g.group_name",
			array()
		);
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$list[] = array(
				'id' => (int)$row['groupid'],
				'value' => -(int)$row['groupid'],
				'label' => $row['group_name'],
				'member_count' => (int)$row['member_count']
			);
		}
		$response = new Vtiger_Response();
		$response->setResult(array('groups' => $list));
		$response->emit();
	}
}
