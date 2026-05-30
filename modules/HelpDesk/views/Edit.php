<?php
/*+***********************************************************************************
 * Modern Ticket Edit View for HelpDesk module.
 *
 * Uses TicketService + tickets* tables instead of legacy HelpDesk edit.
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TicketService.php';

class HelpDesk_Edit_View extends Vtiger_Index_View {

	protected function isSupportApp(Vtiger_Request $request) {
		$appName = strtoupper((string)$request->get('app'));
		return ($appName === 'SUPPORT' || $appName === '');
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		if ($this->isSupportApp($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return 'IndexViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'HelpDesk');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		if ($this->isSupportApp($request)) {
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'SUPPORT');
		}
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if ($this->isSupportApp($request)) {
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
		} else {
			$viewer->view('IndexPostProcess.tpl', $request->getModule());
		}
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$service  = HelpDesk_TicketService::getInstance();
		$ticket   = null;

		if ($recordId > 0) {
			$ticket = $service->getTicketById($recordId);
			if (!$ticket) {
				throw new Exception("Ticket $recordId not found");
			}
		}

		$db = PearDatabase::getInstance();

		$contacts = [];
		$cRes = $db->pquery(
			"SELECT cd.contactid, cd.firstname, cd.lastname,
			        cd.accountid,
			        acc.accountname
			 FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid
			 LEFT JOIN vtiger_account acc ON acc.accountid = cd.accountid
			 WHERE ce.deleted = 0
			 ORDER BY cd.lastname, cd.firstname
			 LIMIT 0, 500",
			[]
		);
		if ($cRes && $db->num_rows($cRes) > 0) {
			while ($row = $db->fetchByAssoc($cRes)) {
				$contacts[] = $row;
			}
		}

		$users = [];
		$uRes = $db->pquery(
			"SELECT id, first_name, last_name FROM vtiger_users WHERE status = 'Active' ORDER BY first_name, last_name",
			[]
		);
		if ($uRes && $db->num_rows($uRes) > 0) {
			while ($row = $db->fetchByAssoc($uRes)) {
				$users[] = $row;
			}
		}

		$assignedUserIds = [];
		if ($recordId > 0) {
			$aRes = $db->pquery(
				'SELECT user_id FROM ticket_assignments WHERE ticket_id = ?',
				[$recordId]
			);
			if ($aRes && $db->num_rows($aRes) > 0) {
				while ($row = $db->fetchByAssoc($aRes)) {
					$assignedUserIds[] = (int)$row['user_id'];
				}
			}
		}

		$viewer = $this->getViewer($request);
		$this->assignSupportContext($request);
		$viewer->assign('TICKET', $ticket);
		$viewer->assign('MODE', ($ticket ? 'edit' : 'create'));
		$viewer->assign('CONTACTS', $contacts);
		$viewer->assign('USERS', $users);
		$viewer->assign('ASSIGNED_USER_IDS', $assignedUserIds);

		$viewer->view('EditView.tpl', $request->getModule());
	}
}
