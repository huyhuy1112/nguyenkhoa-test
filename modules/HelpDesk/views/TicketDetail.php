<?php
/*+***********************************************************************************
 * TicketDetail view: dedicated detail page for new Ticket system.
 *
 * We use a separate view name (TicketDetail) instead of overriding core Detail view
 * to avoid any conflicts with Vtiger's legacy HelpDesk handler.
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TicketService.php';

class HelpDesk_TicketDetail_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		if ($recordId <= 0) {
			header('Location: index.php?module=HelpDesk&view=List');
			return;
		}

		$service = HelpDesk_TicketService::getInstance();
		$ticket  = $service->getTicketById($recordId);
		if (!$ticket) {
			header('Location: index.php?module=HelpDesk&view=List');
			return;
		}

		$db = PearDatabase::getInstance();

		// Customer label
		$customerName = '';
		if (!empty($ticket['customer_id'])) {
			$res = $db->pquery('SELECT label FROM vtiger_crmentity WHERE crmid = ?', [$ticket['customer_id']]);
			if ($res && $db->num_rows($res) > 0) {
				$customerName = $db->query_result($res, 0, 'label');
			}
		}

		// Project label (optional)
		$projectName = '';
		if (!empty($ticket['project_id'])) {
			$res = $db->pquery('SELECT label FROM vtiger_crmentity WHERE crmid = ?', [$ticket['project_id']]);
			if ($res && $db->num_rows($res) > 0) {
				$projectName = $db->query_result($res, 0, 'label');
			}
		}

		// Assigned users (current)
		$assignedUsers = [];
		$assignedUserIds = [];
		$asRes = $db->pquery(
			'SELECT u.id, u.first_name, u.last_name FROM ticket_assignments ta
			 INNER JOIN vtiger_users u ON u.id = ta.user_id
			 WHERE ta.ticket_id = ?',
			[$recordId]
		);
		if ($asRes && $db->num_rows($asRes) > 0) {
			while ($row = $db->fetchByAssoc($asRes)) {
				$assignedUsers[] = $row;
				$assignedUserIds[] = (int)$row['id'];
			}
		}

		// All active users for assignment dropdown
		$allUsers = [];
		$uRes = $db->pquery(
			"SELECT id, first_name, last_name FROM vtiger_users WHERE status = 'Active' ORDER BY first_name, last_name",
			[]
		);
		if ($uRes && $db->num_rows($uRes) > 0) {
			while ($row = $db->fetchByAssoc($uRes)) {
				$allUsers[] = $row;
			}
		}

		// Files
		$files = [];
		$fRes = $db->pquery(
			'SELECT tf.*, CONCAT(u.first_name," ",u.last_name) AS uploaded_by_name
			 FROM ticket_files tf
			 INNER JOIN vtiger_users u ON u.id = tf.uploaded_by
			 WHERE tf.ticket_id = ?
			 ORDER BY tf.uploaded_at DESC',
			[$recordId]
		);
		if ($fRes && $db->num_rows($fRes) > 0) {
			while ($row = $db->fetchByAssoc($fRes)) {
				$files[] = $row;
			}
		}

		// Time logs
		$timeLogs = [];
		$tRes = $db->pquery(
			'SELECT tl.*, CONCAT(u.first_name," ",u.last_name) AS user_name
			 FROM ticket_time_logs tl
			 INNER JOIN vtiger_users u ON u.id = tl.user_id
			 WHERE tl.ticket_id = ?
			 ORDER BY tl.created_at DESC',
			[$recordId]
		);
		if ($tRes && $db->num_rows($tRes) > 0) {
			while ($row = $db->fetchByAssoc($tRes)) {
				$timeLogs[] = $row;
			}
		}

		// Related Activities (custom Activities module linked by ticketid)
		$relatedActivities = [];
		$raRes = $db->pquery(
			"SELECT a.activityid, a.activity_type, a.content, a.status, a.activity_date, a.modifiedtime,
			        CONCAT(u.first_name,' ',u.last_name) AS assigned_name
			   FROM vtiger_activities a
			   LEFT JOIN vtiger_users u ON u.id = a.assigned_user_id
			  WHERE a.ticketid = ?
		   ORDER BY a.activity_date DESC, a.activityid DESC",
			[$recordId]
		);
		if ($raRes && $db->num_rows($raRes) > 0) {
			while ($row = $db->fetchByAssoc($raRes)) {
				$relatedActivities[] = $row;
			}
		}

		// Activity logs (with human‑readable description)
		$activities = [];
		$aRes = $db->pquery(
			'SELECT al.*, CONCAT(u.first_name," ",u.last_name) AS user_name
			 FROM ticket_activity_logs al
			 INNER JOIN vtiger_users u ON u.id = al.changed_by
			 WHERE al.ticket_id = ?
			 ORDER BY al.changed_at DESC',
			[$recordId]
		);
		if ($aRes && $db->num_rows($aRes) > 0) {
			while ($row = $db->fetchByAssoc($aRes)) {
				$actionType = (string)$row['action_type'];
				$oldValue   = $row['old_value'];
				$newValue   = $row['new_value'];

				$label   = '';
				$details = '';

				switch ($actionType) {
					case 'create':
						$label = 'Created ticket';
						break;

					case 'update':
						$label = 'Updated ticket';
						break;

					case 'status_change':
						$label = 'Status changed';
						if ($oldValue !== null || $newValue !== null) {
							$details = trim((string)$oldValue) . ' → ' . trim((string)$newValue);
						}
						break;

					case 'assignment':
						$label = 'Updated assignees';
						$ids   = json_decode((string)$newValue, true);
						if (is_array($ids) && !empty($ids)) {
							$details = 'User IDs: ' . implode(', ', array_map('intval', $ids));
						}
						break;

					case 'file_upload':
						$label = 'Uploaded file';
						if (is_string($newValue) && $newValue !== '') {
							$details = basename($newValue);
						}
						break;

					case 'rule_applied':
						$label = 'Applied rules';
						$ids   = json_decode((string)$newValue, true);
						if (is_array($ids) && !empty($ids)) {
							$details = 'Rule IDs: ' . implode(', ', array_map('intval', $ids));
						}
						break;

					default:
						$label = ucwords(str_replace('_', ' ', $actionType));
						break;
				}

				$row['action_label']   = $label;
				$row['action_details'] = $details;

				$activities[] = $row;
			}
		}

		// SLA countdown seconds (legacy field on tickets)
		$ticket['sla_countdown'] = null;
		if (!empty($ticket['sla_due_at']) && in_array($ticket['status'], ['Open','In Progress'], true)) {
			$nowTs = time();
			$slaTs = strtotime($ticket['sla_due_at']);
			if ($slaTs !== false) {
				$ticket['sla_countdown'] = $slaTs - $nowTs;
			}
		}

		// Support Rules Engine SLA entries (ticket_sla)
		$slaEntries = [];
		$slaRes = $db->pquery(
			"SELECT ts.*, sr.rule_name, sr.rule_type
			   FROM ticket_sla ts
			   JOIN support_rules sr ON sr.id = ts.rule_id
			  WHERE ts.ticket_id = ?
		   ORDER BY sr.rule_type, sr.rule_name",
			[$recordId]
		);
		if ($slaRes && $db->num_rows($slaRes) > 0) {
			while ($row = $db->fetchByAssoc($slaRes)) {
				$remain = null;
				if (!empty($row['deadline_at']) && $row['status'] === 'pending') {
					$remain = strtotime($row['deadline_at']) - time();
				}
				$row['remaining_seconds'] = $remain;
				$slaEntries[] = $row;
			}
		}

		$viewer = $this->getViewer($request);

		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('TICKET', $ticket);
		$viewer->assign('CUSTOMER_NAME', $customerName);
		$viewer->assign('PROJECT_NAME', $projectName);
		$viewer->assign('ASSIGNED_USERS', $assignedUsers);
		$viewer->assign('TICKET_FILES', $files);
		$viewer->assign('ALL_USERS', $allUsers);
		$viewer->assign('ASSIGNED_USER_IDS', $assignedUserIds);
		$viewer->assign('TIME_LOGS', $timeLogs);
		$viewer->assign('ACTIVITY_LOGS', $activities);
		$viewer->assign('SLA_ENTRIES', $slaEntries);
		$viewer->assign('RELATED_ACTIVITIES', $relatedActivities);
		$viewer->assign('ACTIVITY_STATUS_OPTIONS', ['Scheduled','Ready','Completed','Skipped']);

		$viewer->view('DetailViewSummaryContents.tpl', $request->getModule());
	}
}

