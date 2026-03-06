<?php
/*+***********************************************************************************
 * Activities_Edit_View – create / edit activity.
 ************************************************************************************/

class Activities_Edit_View extends Vtiger_Edit_View {
	public function requiresPermission(Vtiger_Request $request) {
		return [];
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$adb = PearDatabase::getInstance();
		$recordId = (int)$request->get('record');
		$data = [
			'activityid'       => null,
			'activity_type'    => '',
			'content'          => '',
			'organizationid'   => (int)$request->get('organizationid'),
			'projectid'        => (int)$request->get('projectid'),
			'ticketid'         => (int)$request->get('ticketid'),
			'assigned_user_id' => $this->getCurrentUserId(),
			'activity_date'    => '',
			'status'           => 'Scheduled',
			'note_before'      => '',
			'note_after'       => '',
		];
		if ($recordId > 0) {
			$res = $adb->pquery(
				"SELECT a.*, ce.smownerid
				   FROM vtiger_activities a
				   JOIN vtiger_crmentity ce ON ce.crmid=a.activityid AND ce.deleted=0
				  WHERE a.activityid=?",
				[$recordId]
			);
			if ($res && $adb->num_rows($res) > 0) {
				$data = $adb->fetchByAssoc($res);
				$data['assigned_user_id'] = $data['smownerid'];
			}
		}

		// dropdowns
		$accounts = [];
		$res = $adb->pquery(
			"SELECT acc.accountid, acc.accountname
			   FROM vtiger_account acc
			   JOIN vtiger_crmentity ce ON ce.crmid=acc.accountid AND ce.deleted=0
			  ORDER BY acc.accountname ASC LIMIT 200",
			[]
		);
		while ($res && ($row = $adb->fetchByAssoc($res))) {
			$accounts[] = $row;
		}

		$projects = [];
		$res = $adb->pquery(
			"SELECT projectid, projectname
			   FROM vtiger_project p
			   JOIN vtiger_crmentity ce ON ce.crmid=p.projectid AND ce.deleted=0
			  ORDER BY projectname ASC LIMIT 200",
			[]
		);
		while ($res && ($row = $adb->fetchByAssoc($res))) {
			$projects[] = $row;
		}

		$users = [];
		$res = $adb->pquery("SELECT id, first_name, last_name FROM vtiger_users WHERE status='Active' ORDER BY first_name, last_name", []);
		while ($res && ($row = $adb->fetchByAssoc($res))) {
			$users[] = $row;
		}

		$fromTicket = (int)$request->get('from_ticket');
		if ($fromTicket <= 0 && !empty($data['ticketid'])) {
			$fromTicket = 1;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', 'Activities');
		$viewer->assign('RECORD', $data);
		$viewer->assign('FROM_TICKET', $fromTicket);
		$viewer->assign('ACCOUNTS', $accounts);
		$viewer->assign('PROJECTS', $projects);
		$viewer->assign('USERS', $users);
		$viewer->assign('STATUS_OPTIONS', ['Scheduled','Ready','Completed','Skipped']);
		$viewer->assign('TYPE_OPTIONS', ['Follow up','Anniversary','Meeting','Gift','Intro','Other']);
		$viewer->view('Edit.tpl', 'Activities');
	}

	protected function getCurrentUserId() {
		global $current_user;
		return $current_user ? $current_user->id : 1;
	}
}
