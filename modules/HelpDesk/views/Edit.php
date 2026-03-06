<?php
/*+***********************************************************************************
 * Modern Ticket Edit View for HelpDesk module.
 *
 * Uses TicketService + tickets* tables instead of legacy HelpDesk edit.
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TicketService.php';

class HelpDesk_Edit_View extends Vtiger_Index_View {

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

		// Load contacts as potential customers (with organization)
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

		// Load users for assignment
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

		// Existing assignees for edit mode
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
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('TICKET', $ticket);
		$viewer->assign('CONTACTS', $contacts);
		$viewer->assign('USERS', $users);
		$viewer->assign('ASSIGNED_USER_IDS', $assignedUserIds);

		$viewer->view('EditView.tpl', $request->getModule());
	}
}

