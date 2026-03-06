<?php
/*+***********************************************************************************
 * Activities_Detail_View – show one activity.
 * URL: index.php?module=Activities&view=Detail&record=ID
 ************************************************************************************/

class Activities_Detail_View extends Vtiger_Detail_View {
	public function requiresPermission(Vtiger_Request $request) {
		return [];
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$adb = PearDatabase::getInstance();
		$row = null;

		if ($recordId > 0) {
			$res = $adb->pquery(
				"SELECT a.*, ce.smownerid, ce.createdtime,
				        u.first_name, u.last_name,
				        org.accountname AS org_name,
				        pr.projectname AS project_name
				   FROM vtiger_activities a
				   JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
				   LEFT JOIN vtiger_users u ON u.id = ce.smownerid
				   LEFT JOIN vtiger_account org ON org.accountid = a.organizationid
				   LEFT JOIN vtiger_project pr ON pr.projectid = a.projectid
				  WHERE a.activityid = ?",
				[$recordId]
			);
			if ($res && $adb->num_rows($res) > 0) {
				$row = $adb->fetchByAssoc($res);
			}
		}

		if ($row === null) {
			header('Location: index.php?module=Activities&view=List&app=SUPPORT');
			exit;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', 'Activities');
		$viewer->assign('RECORD', $row);
		$viewer->view('Detail.tpl', 'Activities');
	}
}
