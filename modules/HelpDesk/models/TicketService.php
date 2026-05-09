<?php
require_once 'modules/HelpDesk/models/SupportRulesService.php';
/*+***********************************************************************************
 * TicketService for new Support Ticket system.
 *
 * - Uses custom tables:
 *   tickets, ticket_assignments, ticket_files,
 *   ticket_time_logs, ticket_rules, ticket_activity_logs
 * - Keeps HelpDesk as the VTiger module shell (permissions, menu, etc.)
 *
 * PHP 8.x compatible.
 ************************************************************************************/

class HelpDesk_TicketService {

	/** @var PearDatabase */
	protected $db;

	/** Allowed status transitions for state machine */
	protected $allowedTransitions = [
		'Open' => ['In Progress'],
		'In Progress' => ['Resolved'],
		'Resolved' => ['Closed', 'In Progress'], // allow rollback from Resolved
		'Closed' => ['In Progress'],             // Reopen goes to In Progress
	];

	public function __construct() {
		$this->db = PearDatabase::getInstance();
	}

	public static function getInstance(): self {
		return new self();
	}

	/**
	 * Create ticket_code like TCK-YYYYMMDD-XXXX (sequence per day).
	 */
	protected function generateTicketCode(): string {
		$today = date('Ymd');
		$prefix = 'TCK-' . $today . '-';

		$sql = 'SELECT ticket_code FROM tickets WHERE ticket_code LIKE ? ORDER BY ticket_code DESC LIMIT 1';
		$result = $this->db->pquery($sql, [$prefix . '%']);
		$next = 1;
		if ($result && $this->db->num_rows($result) > 0) {
			$lastCode = $this->db->query_result($result, 0, 'ticket_code');
			$lastSeq  = (int)substr($lastCode, -4);
			$next     = $lastSeq + 1;
		}

		return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Create a new ticket.
	 *
	 * $data keys:
	 * - customer_id (required)
	 * - project_id (optional)
	 * - subject (required)
	 * - description (optional)
	 * - priority (Low|Medium|High|Critical, default Medium)
	 * - status (optional, default Open)
	 *
	 * @return array [ 'id' => int, 'ticket_code' => string ]
	 */
	public function createTicket(array $data, int $currentUserId): array {
		$customerId  = (int)($data['customer_id'] ?? 0);
		$projectId   = !empty($data['project_id']) ? (int)$data['project_id'] : null;
		$subject     = trim((string)($data['subject'] ?? ''));
		$description = (string)($data['description'] ?? '');
		$priority    = $data['priority'] ?? 'Medium';
		$status      = $data['status'] ?? 'Open';

		if ($customerId <= 0 || $subject === '') {
			throw new Exception('customer_id and subject are required');
		}

		$ticketCode = $this->generateTicketCode();

		$sql = 'INSERT INTO tickets
			(ticket_code, customer_id, project_id, subject, description, priority, status, created_by, created_at, updated_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';

		$params = [
			$ticketCode,
			$customerId,
			$projectId,
			$subject,
			$description,
			$priority,
			$status,
			$currentUserId,
		];

		$this->db->pquery($sql, $params);
		$ticketId = (int)$this->db->getLastInsertID();

		// Initial activity log
		$this->logActivity($ticketId, 'create', null, json_encode($data, JSON_UNESCAPED_UNICODE), $currentUserId);

		// Apply legacy ticket_rules (auto-assign, priority, simple SLA on tickets table)
		$this->applyRulesForTicket($ticketId, $currentUserId);

		// Apply new Support Rules Engine SLA (ticket_sla table) nếu service tồn tại
		if (class_exists('HelpDesk_SupportRulesService')) {
			$rulesService = HelpDesk_SupportRulesService::getInstance();
			$rulesService->createSlaForTicket($ticketId, $customerId, date('Y-m-d H:i:s'));
		}

		// Evaluate SLA overdue flag for initial state (legacy field on tickets)
		$this->recalculateSlaAndOverdue($ticketId);

		return ['id' => $ticketId, 'ticket_code' => $ticketCode];
	}

	/**
	 * Update a ticket basic fields.
	 */
	public function updateTicket(int $ticketId, array $data, int $currentUserId): void {
		$current = $this->getTicketById($ticketId);
		if (!$current) {
			throw new Exception("Ticket $ticketId not found");
		}

		$fields = [];
		$params = [];

		$updatable = ['customer_id', 'project_id', 'subject', 'description', 'priority'];

		foreach ($updatable as $field) {
			if (array_key_exists($field, $data)) {
				$fields[] = "$field = ?";
				$params[] = $data[$field];
			}
		}

		if (empty($fields)) {
			return;
		}

		$params[] = $ticketId;

		$sql = 'UPDATE tickets SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ?';
		$this->db->pquery($sql, $params);

		$this->logActivity($ticketId, 'update', json_encode($current, JSON_UNESCAPED_UNICODE), json_encode($data, JSON_UNESCAPED_UNICODE), $currentUserId);

		// Re-apply rules if priority/customer/project changed
		$this->applyRulesForTicket($ticketId, $currentUserId);
		$this->recalculateSlaAndOverdue($ticketId);
	}

	/**
	 * Get ticket row as associative array.
	 */
	public function getTicketById(int $ticketId): ?array {
		$sql    = 'SELECT * FROM tickets WHERE id = ?';
		$result = $this->db->pquery($sql, [$ticketId]);
		if (!$result || $this->db->num_rows($result) === 0) {
			return null;
		}
		return $this->db->fetchByAssoc($result, 0);
	}

	/**
	 * Change status with state machine validation.
	 */
	public function changeStatus(int $ticketId, string $newStatus, int $currentUserId): void {
		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			throw new Exception("Ticket $ticketId not found");
		}

		$oldStatus = $ticket['status'];
		if ($oldStatus === $newStatus) {
			return;
		}

		if (!isset($this->allowedTransitions[$oldStatus]) ||
			!in_array($newStatus, $this->allowedTransitions[$oldStatus], true)) {
			throw new Exception("Transition $oldStatus -> $newStatus is not allowed");
		}

		$params = [$newStatus];
		$sql    = 'UPDATE tickets SET status = ?, updated_at = NOW()';

		// Track closed_at when going to Closed
		if ($newStatus === 'Closed') {
			$sql      .= ', closed_at = NOW()';
		}

		$sql    .= ' WHERE id = ?';
		$params[] = $ticketId;

		$this->db->pquery($sql, $params);

		$this->logActivity($ticketId, 'status_change', $oldStatus, $newStatus, $currentUserId);

		// Recalculate SLA / overdue + total time if closed
		$this->recalculateSlaAndOverdue($ticketId);

		// Support Rules Engine SLA lifecycle: complete all SLA entries on Resolved/Closed
		if (in_array($newStatus, ['Resolved', 'Closed'], true) && class_exists('HelpDesk_SupportRulesService')) {
			$rulesService = HelpDesk_SupportRulesService::getInstance();
			$rulesService->completeSlaForTicket($ticketId);
		}

		if ($newStatus === 'Closed') {
			$this->recalculateTotalTime($ticketId);
		}
	}

	/**
	 * Assign ticket to multiple users.
	 *
	 * @param int[] $userIds
	 */
	public function assignUsers(int $ticketId, array $userIds, int $currentUserId): void {
		$ticket = $this->getTicketById($ticketId);

		// Normalize & dedupe
		$userIds = array_unique(array_map('intval', $userIds));
		$userIds = array_filter($userIds, static function ($id) {
			return $id > 0;
		});

		// Remove assignments that are not in the new list
		if (!empty($userIds)) {
			$placeholders = implode(',', array_fill(0, count($userIds), '?'));
			$params       = $userIds;
			$params[]     = $ticketId;

			$sqlDelete = "DELETE FROM ticket_assignments
				WHERE ticket_id = ? AND user_id NOT IN ($placeholders)";
			// ticket_id is last parameter
			$paramsForDelete = array_merge([$ticketId], $userIds);
			$this->db->pquery($sqlDelete, $paramsForDelete);
		} else {
			$this->db->pquery('DELETE FROM ticket_assignments WHERE ticket_id = ?', [$ticketId]);
		}

		// Add new assignments
		foreach ($userIds as $userId) {
			$sql = 'INSERT IGNORE INTO ticket_assignments (ticket_id, user_id, assigned_at) VALUES (?, ?, NOW())';
			$this->db->pquery($sql, [$ticketId, $userId]);

			// Notify assignee (basic in-app notification)
			if ($ticket) {
				$this->notifyAssignment($ticket, $userId);
			}
		}

		$this->logActivity($ticketId, 'assignment', null, json_encode($userIds), $currentUserId);
	}

	/**
	 * Add a time log entry.
	 */
	public function addTimeLog(int $ticketId, int $userId, int $minutes, string $note = ''): void {
		if ($minutes <= 0) {
			throw new Exception('minutes_spent must be > 0');
		}

		$sql = 'INSERT INTO ticket_time_logs (ticket_id, user_id, minutes_spent, note, created_at)
			VALUES (?, ?, ?, ?, NOW())';
		$this->db->pquery($sql, [$ticketId, $userId, $minutes, $note]);
	}

	/**
	 * Add file records for previously uploaded files (file moving handled by controller).
	 *
	 * @param array[] $files each: ['path' => string, 'type' => string]
	 */
	public function addFiles(int $ticketId, int $userId, array $files): void {
		foreach ($files as $file) {
			if (empty($file['path'])) {
				continue;
			}
			$path = $file['path'];
			$type = $file['type'] ?? null;

			$sql = 'INSERT INTO ticket_files (ticket_id, file_path, file_type, uploaded_by, uploaded_at)
				VALUES (?, ?, ?, ?, NOW())';
			$this->db->pquery($sql, [$ticketId, $path, $type, $userId]);

			$this->logActivity($ticketId, 'file_upload', null, $path, $userId);
		}
	}

	/**
	 * Apply matching ticket_rules for a ticket.
	 *
	 * condition_json format (simple AND conditions), example:
	 * {
	 *   "priority": "High",
	 *   "status": "Open"
	 * }
	 */
	public function applyRulesForTicket(int $ticketId, int $currentUserId): void {
		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return;
		}

		$result = $this->db->pquery('SELECT * FROM ticket_rules', []);
		if (!$result) {
			return;
		}

		$matchedUpdates = [];

		while ($row = $this->db->fetchByAssoc($result)) {
			$ruleId      = (int)$row['id'];
			$conditions  = json_decode((string)$row['condition_json'], true);

			if (!is_array($conditions) || empty($conditions)) {
				continue;
			}

			$match = true;
			foreach ($conditions as $field => $expected) {
				if (!array_key_exists($field, $ticket)) {
					$match = false;
					break;
				}
				if ((string)$ticket[$field] !== (string)$expected) {
					$match = false;
					break;
				}
			}

			if (!$match) {
				continue;
			}

			$updates = [];
			$params  = [];

			if (!empty($row['auto_priority']) && $ticket['priority'] !== $row['auto_priority']) {
				$updates[]          = 'priority = ?';
				$params[]           = $row['auto_priority'];
				$ticket['priority'] = $row['auto_priority'];
			}

			// SLA
			if (!empty($row['sla_minutes'])) {
				$slaMinutes = (int)$row['sla_minutes'];
				$updates[]  = 'sla_due_at = DATE_ADD(created_at, INTERVAL ? MINUTE)';
				$params[]   = $slaMinutes;
				// we don't update $ticket here, SLA recalculated later
			}

			if (!empty($updates)) {
				$params[] = $ticketId;
				$sql      = 'UPDATE tickets SET ' . implode(',', $updates) . ' WHERE id = ?';
				$this->db->pquery($sql, $params);
			}

			// Assign user (into ticket_assignments)
			if (!empty($row['auto_assign_user_id'])) {
				$this->assignUsers($ticketId, [(int)$row['auto_assign_user_id']], $currentUserId);
			}

			$matchedUpdates[] = $ruleId;
		}

		if (!empty($matchedUpdates)) {
			$this->logActivity(
				$ticketId,
				'rule_applied',
				null,
				json_encode($matchedUpdates),
				$currentUserId
			);
		}
	}

	/**
	 * Recalculate SLA overdue for one ticket.
	 */
	public function recalculateSlaAndOverdue(int $ticketId): void {
		$sql = "UPDATE tickets
			SET is_overdue = CASE
				WHEN sla_due_at IS NOT NULL
				     AND status IN ('Open','In Progress')
				     AND NOW() > sla_due_at
				THEN 1 ELSE 0 END
			WHERE id = ?";
		$this->db->pquery($sql, [$ticketId]);
	}

	/**
	 * Recalculate total time when closed (for reporting; currently just ensures logs exist).
	 */
	public function recalculateTotalTime(int $ticketId): int {
		$sql    = 'SELECT COALESCE(SUM(minutes_spent),0) AS total_min
			FROM ticket_time_logs WHERE ticket_id = ?';
		$result = $this->db->pquery($sql, [$ticketId]);
		$total  = 0;
		if ($result && $this->db->num_rows($result) > 0) {
			$total = (int)$this->db->query_result($result, 0, 'total_min');
		}

		// Could be stored in separate summary table/field in future.
		return $total;
	}

	/**
	 * Write log to ticket_activity_logs.
	 */
	protected function logActivity(
		int $ticketId,
		string $actionType,
		$oldValue,
		$newValue,
		int $changedBy
	): void {
		$sql = 'INSERT INTO ticket_activity_logs
			(ticket_id, action_type, old_value, new_value, changed_by, changed_at)
			VALUES (?, ?, ?, ?, ?, NOW())';

		// Normalize values as strings
		$old = is_string($oldValue) ? $oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE);
		$new = is_string($newValue) ? $newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE);

		$this->db->pquery($sql, [$ticketId, $actionType, $old, $new, $changedBy]);
	}

	/**
	 * Insert notification row into vtiger_notifications for assignment.
	 *
	 * @param array $ticket tickets table row
	 */
	protected function notifyAssignment(array $ticket, int $userId): void {
		global $adb;
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}

		$ticketId    = (int)$ticket['id'];
		$ticketCode  = (string)$ticket['ticket_code'];
		$ticketTitle = (string)$ticket['subject'];

		$message = sprintf(
			'Bạn được assign vào Ticket: %s - %s',
			$ticketCode,
			$ticketTitle
		);

		$adb->pquery(
			'INSERT INTO vtiger_notifications (userid, module, recordid, message, is_read, created_at)
			 VALUES (?, ?, ?, ?, 0, NOW())',
			[$userId, 'HelpDesk', $ticketId, $message]
		);
	}
}

