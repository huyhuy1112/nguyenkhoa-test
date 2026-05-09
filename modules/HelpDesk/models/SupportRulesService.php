<?php
/*+***********************************************************************************
 * HelpDesk_SupportRulesService – business logic for Support Rules Engine.
 *
 * Tables:
 *  - support_rules
 *  - support_rule_levels
 *  - ticket_sla
 *  - rule_activity_logs
 ************************************************************************************/

class HelpDesk_SupportRulesService {

	/** @var PearDatabase */
	protected $db;

	public function __construct() {
		$this->db = PearDatabase::getInstance();
	}

	public static function getInstance(): self {
		return new self();
	}

	/** Lấy tất cả rule + thời gian (phút) theo level – dùng cho trang list. */
	public function getAllRules(): array {
		$sql = "SELECT
					r.*,
					l.level_1_time_minutes,
					l.level_2_time_minutes,
					l.level_3_time_minutes
				FROM support_rules r
				LEFT JOIN support_rule_levels l ON l.rule_id = r.id
				ORDER BY r.rule_type, r.rule_name";

		$result = $this->db->pquery($sql, []);
		$rows   = [];

		if ($result && $this->db->num_rows($result) > 0) {
			while ($row = $this->db->fetchByAssoc($result)) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/** Lấy 1 rule theo id (kèm level). */
	public function getRuleById(int $id): ?array {
		if ($id <= 0) {
			return null;
		}

		$sql = "SELECT
					r.*,
					l.level_1_time_minutes,
					l.level_2_time_minutes,
					l.level_3_time_minutes
				FROM support_rules r
				LEFT JOIN support_rule_levels l ON l.rule_id = r.id
				WHERE r.id = ?";

		$result = $this->db->pquery($sql, [$id]);
		if (!$result || $this->db->num_rows($result) === 0) {
			return null;
		}

		return $this->db->fetchByAssoc($result, 0);
	}

	/**
	 * Tạo / cập nhật rule + level times.
	 *
	 * $data:
	 *  - id (optional)
	 *  - rule_name
	 *  - rule_type
	 *  - description
	 *  - is_active (0|1)
	 *  - level_1_time_minutes
	 *  - level_2_time_minutes
	 *  - level_3_time_minutes
	 *
	 * @return int rule_id
	 * @throws Exception
	 */
	public function saveRule(array $data): int {
		$id       = isset($data['id']) ? (int)$data['id'] : 0;
		$name     = trim((string)($data['rule_name'] ?? ''));
		$type     = (string)($data['rule_type'] ?? '');
		$desc     = (string)($data['description'] ?? '');
		$isActive = !empty($data['is_active']) ? 1 : 0;

		if ($name === '' || $type === '') {
			throw new Exception('rule_name and rule_type are required');
		}

		$l1 = $this->normalizeMinutes($data['level_1_time_minutes'] ?? null);
		$l2 = $this->normalizeMinutes($data['level_2_time_minutes'] ?? null);
		$l3 = $this->normalizeMinutes($data['level_3_time_minutes'] ?? null);

		if ($id > 0) {
			$sql = "UPDATE support_rules
					   SET rule_name = ?, rule_type = ?, description = ?, is_active = ?, updated_at = NOW()
					 WHERE id = ?";
			$this->db->pquery($sql, [$name, $type, $desc, $isActive, $id]);
		} else {
			$sql = "INSERT INTO support_rules
						(rule_name, rule_type, description, is_active, created_at, updated_at)
					VALUES (?, ?, ?, ?, NOW(), NOW())";
			$this->db->pquery($sql, [$name, $type, $desc, $isActive]);
			$id = (int)$this->db->getLastInsertID();
		}

		// upsert support_rule_levels
		$res = $this->db->pquery('SELECT id FROM support_rule_levels WHERE rule_id = ?', [$id]);
		if ($res && $this->db->num_rows($res) > 0) {
			$sql = "UPDATE support_rule_levels
					 SET level_1_time_minutes = ?, level_2_time_minutes = ?, level_3_time_minutes = ?
					 WHERE rule_id = ?";
			$this->db->pquery($sql, [$l1, $l2, $l3, $id]);
		} else {
			$sql = "INSERT INTO support_rule_levels
						(rule_id, level_1_time_minutes, level_2_time_minutes, level_3_time_minutes)
					VALUES (?, ?, ?, ?)";
			$this->db->pquery($sql, [$id, $l1, $l2, $l3]);
		}

		return $id;
	}

	/** Bật / tắt rule. */
	public function setRuleActive(int $id, bool $active): void {
		if ($id <= 0) {
			return;
		}
		$this->db->pquery(
			'UPDATE support_rules SET is_active = ?, updated_at = NOW() WHERE id = ?',
			[$active ? 1 : 0, $id]
		);
	}

	/**
	 * Khi tạo ticket mới, sinh các dòng ticket_sla dựa trên support_level của customer.
	 */
	public function createSlaForTicket(int $ticketId, int $customerId, string $createdAt): void {
		if ($ticketId <= 0 || $customerId <= 0) {
			return;
		}

		// Support level (Contacts.picklist: usually '1'|'2'|'3'), default = level 2
		$level = $this->getCustomerSupportLevel($customerId);

		// Lấy các rule đang active
		$rulesRes = $this->db->pquery(
			"SELECT r.id,
					l.level_1_time_minutes,
					l.level_2_time_minutes,
					l.level_3_time_minutes
			   FROM support_rules r
		  LEFT JOIN support_rule_levels l ON l.rule_id = r.id
			  WHERE r.is_active = 1",
			[]
		);
		if (!$rulesRes || $this->db->num_rows($rulesRes) === 0) {
			return;
		}

		$createdTs = strtotime($createdAt) ?: time();

		while ($row = $this->db->fetchByAssoc($rulesRes)) {
			$ruleId = (int)$row['id'];

			if ($level === 1) {
				$minutes = $this->normalizeMinutes($row['level_1_time_minutes'] ?? null);
			} elseif ($level === 2) {
				$minutes = $this->normalizeMinutes($row['level_2_time_minutes'] ?? null);
			} else {
				$minutes = $this->normalizeMinutes($row['level_3_time_minutes'] ?? null);
			}

			if ($minutes === null) {
				continue;
			}

			$deadlineTs  = $createdTs + ($minutes * 60);
			$deadlineStr = date('Y-m-d H:i:s', $deadlineTs);

			$this->db->pquery(
				"INSERT INTO ticket_sla (ticket_id, rule_id, deadline_at, status)
				 VALUES (?, ?, ?, 'pending')",
				[$ticketId, $ruleId, $deadlineStr]
			);
		}
	}

	/** Đánh dấu ticket_sla quá hạn. */
	public function markOverdue(): int {
		$sql = "UPDATE ticket_sla
				   SET status = 'overdue'
				 WHERE status = 'pending'
				   AND deadline_at < NOW()";
		$res = $this->db->pquery($sql, []);
		return $res ? (int)$this->db->getAffectedRowCount($res) : 0;
	}

	/**
	 * Mark all SLA entries for a ticket as completed.
	 * Minimal lifecycle: complete when ticket is Closed/Resolved.
	 */
	public function completeSlaForTicket(int $ticketId): int {
		if ($ticketId <= 0) {
			return 0;
		}
		$res = $this->db->pquery(
			"UPDATE ticket_sla
			    SET status = 'completed',
			        completed_at = NOW()
			  WHERE ticket_id = ?
			    AND status IN ('pending','overdue')",
			[$ticketId]
		);
		return $res ? (int)$this->db->getAffectedRowCount($res) : 0;
	}

	/**
	 * Resolve customer support level from Contacts.
	 * Accepts either numeric picklist values ('1','2','3') or labels ('Level 1', ...).
	 */
	protected function getCustomerSupportLevel(int $contactId): int {
		if ($contactId <= 0) {
			return 2;
		}

		$level = 2;
		$res = $this->db->pquery(
			"SELECT cd.support_level
			   FROM vtiger_contactdetails cd
			   INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			  WHERE cd.contactid = ?",
			[$contactId]
		);
		if ($res && $this->db->num_rows($res) > 0) {
			$raw = (string)$this->db->query_result($res, 0, 'support_level');
			$raw = trim($raw);
			if ($raw !== '') {
				if ($raw === '1' || stripos($raw, 'level 1') !== false) {
					$level = 1;
				} elseif ($raw === '2' || stripos($raw, 'level 2') !== false) {
					$level = 2;
				} elseif ($raw === '3' || stripos($raw, 'level 3') !== false) {
					$level = 3;
				}
			}
		}

		return $level;
	}

	protected function normalizeMinutes($value): ?int {
		if ($value === null || $value === '') {
			return null;
		}
		$m = (int)$value;
		return $m > 0 ? $m : null;
	}
}

