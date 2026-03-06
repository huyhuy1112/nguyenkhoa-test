<?php
/*+***********************************************************************************
 * SaveTicket action for new Ticket system.
 *
 * Handles:
 * - Create / Update ticket
 * - Change status
 * - Assign users
 * - Add time log
 * - Handle basic file uploads
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TicketService.php';

class HelpDesk_SaveTicket_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Basic check via module permissions
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$privilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		if (!$privilegesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;

		$mode      = $request->get('mode');
		$ticketId  = (int)$request->get('ticket_id');
		$service   = HelpDesk_TicketService::getInstance();
		$currentId = (int)$current_user->id;

		try {
			if ($mode === 'changeStatus') {
				$newStatus = $request->get('status');
				$service->changeStatus($ticketId, $newStatus, $currentId);
				$this->redirectToDetail($ticketId);
				return;
			}

			if ($mode === 'assignUsers') {
				$userIds = $this->normalizeUserIds($request->get('assigned_users_ids'));
				$service->assignUsers($ticketId, $userIds, $currentId);
				$this->redirectToDetail($ticketId);
				return;
			}

			if ($mode === 'addTimeLog') {
				$minutes = (int)$request->get('minutes_spent');
				$note    = (string)$request->get('note');
				$service->addTimeLog($ticketId, $currentId, $minutes, $note);
				$this->redirectToDetail($ticketId);
				return;
			}

			// Default: create or update ticket from Edit view
			$data = [
				'customer_id' => $request->get('customer_id'),
				'project_id'  => $request->get('project_id'),
				'subject'     => $request->get('subject'),
				'description' => $request->get('description'),
				'priority'    => $request->get('priority'),
				'status'      => $request->get('status'),
			];

			if ($ticketId > 0) {
				$service->updateTicket($ticketId, $data, $currentId);
			} else {
				$result   = $service->createTicket($data, $currentId);
				$ticketId = (int)$result['id'];
			}

			// Simple file upload handling (optional)
			if (!empty($_FILES['ticket_files']) && is_array($_FILES['ticket_files']['name'])) {
				$uploaded = [];
				$baseDir  = 'storage/Tickets';
				if (!is_dir($baseDir)) {
					@mkdir($baseDir, 0755, true);
				}

				foreach ($_FILES['ticket_files']['name'] as $idx => $name) {
					$tmpName = $_FILES['ticket_files']['tmp_name'][$idx];
					$error   = $_FILES['ticket_files']['error'][$idx];
					$size    = $_FILES['ticket_files']['size'][$idx];

					if ($error !== UPLOAD_ERR_OK || !$tmpName || $size <= 0) {
						continue;
					}

					// Basic extension whitelist
					$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
					$allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','zip','rar','txt'];
					if (!in_array($ext, $allowed, true)) {
						continue;
					}

					$safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
					$target   = $baseDir . '/T' . $ticketId . '_' . uniqid('', true) . '_' . $safeName;

					if (@move_uploaded_file($tmpName, $target)) {
						$uploaded[] = [
							'path' => $target,
							'type' => $ext,
						];
					}
				}

				if (!empty($uploaded)) {
					$service->addFiles($ticketId, $currentId, $uploaded);
				}
			}

			// Optional: assign on create from Edit view
			$userIds = $this->normalizeUserIds($request->get('assigned_users_ids'));
			if (!empty($userIds)) {
				$service->assignUsers($ticketId, $userIds, $currentId);
			}

			$this->redirectToDetail($ticketId);
		} catch (Exception $e) {
			throw $e;
		}
	}

	protected function redirectToDetail(int $ticketId): void {
		$detailUrl = 'index.php?module=HelpDesk&view=TicketDetail&record=' . $ticketId;
		header("Location: $detailUrl");
		exit;
	}

	/**
	 * Normalize assigned user IDs from either CSV string or array.
	 *
	 * @param mixed $raw
	 * @return int[]
	 */
	protected function normalizeUserIds($raw): array {
		$userIds = [];

		if (is_array($raw)) {
			foreach ($raw as $id) {
				$id = (int)$id;
				if ($id > 0) {
					$userIds[] = $id;
				}
			}
		} elseif (is_string($raw) && $raw !== '') {
			foreach (explode(',', $raw) as $id) {
				$id = (int)trim($id);
				if ($id > 0) {
					$userIds[] = $id;
				}
			}
		}

		return array_values(array_unique($userIds));
	}
}

