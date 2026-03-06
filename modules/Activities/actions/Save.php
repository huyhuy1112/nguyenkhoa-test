<?php
/*+***********************************************************************************
 * Activities_Save_Action – create/update Activities.
 ************************************************************************************/

class Activities_Save_Action extends Vtiger_Action_Controller {
	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$adb = PearDatabase::getInstance();
		$mode = (string)$request->get('mode');

		if ($mode === 'changeStatus') {
			$recordId = (int)$request->get('record');
			$ticketId = (int)$request->get('ticketid');
			$status   = $this->normalizeStatus((string)$request->get('status'));
			$current  = $this->getCurrentStatus($recordId);
			$canMove  = $this->isAllowedTransition($current, $status);

			if ($recordId > 0 && $canMove) {
				$adb->pquery(
					"UPDATE vtiger_activities SET status = ?, modifiedtime = NOW() WHERE activityid = ?",
					[$status, $recordId]
				);
				$adb->pquery(
					"UPDATE vtiger_crmentity SET modifiedtime = NOW() WHERE crmid = ?",
					[$recordId]
				);
			}

			if ($ticketId > 0) {
				header('Location: index.php?module=HelpDesk&view=TicketDetail&record=' . $ticketId . '&app=SUPPORT');
			} else {
				header('Location: index.php?module=Activities&view=Detail&record=' . $recordId . '&app=SUPPORT');
			}
			exit;
		}

		$id  = (int)$request->get('record');

		$type    = trim($request->get('activity_type'));
		$content = trim($request->get('content'));
		$orgId   = (int)$request->get('organizationid');
		$projId  = (int)$request->get('projectid');
		$ticketId = (int)$request->get('ticketid');
		$userId  = (int)$request->get('assigned_user_id');
		$date    = trim($request->get('activity_date'));
		$status  = $this->normalizeStatus((string)$request->get('status'));
		$noteB   = trim($request->get('note_before'));
		$noteA   = trim($request->get('note_after'));
		$fromTicket = (int)$request->get('from_ticket');

		$userId = $userId > 0 ? $userId : $this->getCurrentUserId();

		if ($id > 0) {
			$current = $this->getCurrentStatus($id);
			if (!$this->isAllowedTransition($current, $status)) {
				// Không cho nhảy trạng thái sai workflow; giữ trạng thái cũ.
				$status = $current;
			}

			// update
			$adb->pquery(
				"UPDATE vtiger_activities
				    SET activity_type=?, content=?, organizationid=?, projectid=?, ticketid=?, activity_date=?, assigned_user_id=?, status=?, note_before=?, note_after=?, modifiedtime=NOW()
				  WHERE activityid=?",
				[$type, $content, $orgId ?: null, $projId ?: null, $ticketId ?: null, $date ?: null, $userId, $status, $noteB, $noteA, $id]
			);
			$adb->pquery("UPDATE vtiger_crmentity SET smownerid=?, modifiedtime=NOW(), label=? WHERE crmid=?", [$userId, $content ?: $type, $id]);
			$recordId = $id;
		} else {
			if ($status === '') {
				$status = 'Scheduled';
			}

			// create new
			$recordId = $adb->getUniqueID('vtiger_crmentity');
			$adb->pquery(
				"INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, createdtime, modifiedtime, label)
				 VALUES (?, ?, ?, 'Activities', NOW(), NOW(), ?)",
				[$recordId, $userId, $userId, $content ?: $type]
			);
			$adb->pquery(
				"INSERT INTO vtiger_activities (activityid, activity_type, content, organizationid, projectid, ticketid, activity_date, assigned_user_id, status, note_before, note_after, createdtime, modifiedtime)
				 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
				[$recordId, $type, $content, $orgId ?: null, $projId ?: null, $ticketId ?: null, $date ?: null, $userId, $status, $noteB, $noteA]
			);
		}

		if ($fromTicket > 0 && $ticketId > 0) {
			header('Location: index.php?module=HelpDesk&view=TicketDetail&record=' . $ticketId . '&app=SUPPORT');
		} else {
			header('Location: index.php?module=Activities&view=Detail&record=' . $recordId . '&app=SUPPORT');
		}
		exit;
	}

	protected function getCurrentUserId() {
		global $current_user;
		return $current_user ? $current_user->id : 1;
	}

	protected function getCurrentStatus(int $recordId): string {
		if ($recordId <= 0) {
			return '';
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT status FROM vtiger_activities WHERE activityid = ?", [$recordId]);
		if ($res && $adb->num_rows($res) > 0) {
			return (string)$adb->query_result($res, 0, 'status');
		}
		return '';
	}

	protected function normalizeStatus(string $status): string {
		$status = trim($status);
		$allowed = ['Scheduled', 'Ready', 'Completed', 'Skipped'];
		return in_array($status, $allowed, true) ? $status : '';
	}

	protected function isAllowedTransition(string $from, string $to): bool {
		if ($to === '') {
			return false;
		}
		if ($from === '' || $from === $to) {
			return true;
		}
		$transitions = [
			'Scheduled' => ['Ready', 'Skipped'],
			'Ready'     => ['Completed'],
			'Completed' => [],
			'Skipped'   => [],
		];
		return in_array($to, $transitions[$from] ?? [], true);
	}
}
