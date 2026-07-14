<?php
/*+***********************************************************************************
 * HelpDesk_TagRuleEngineService — Tag Rule Engine (DB).
 * Nếu tags (AND) → Thì next_action / cảnh báo / kịch bản.
 * Dùng slug catalog Sales; ghi bace_lead_profile.next_action khi match.
 *************************************************************************************/

class HelpDesk_TagRuleEngineService {

	/** @var PearDatabase */
	protected $db;

	const SCHEMA_VERSION = 1;

	public function __construct() {
		$this->db = PearDatabase::getInstance();
		$this->ensureSchema();
	}

	public static function getInstance(): self {
		return new self();
	}

	public function ensureSchema() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_catalog (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			name VARCHAR(150) NOT NULL,
			category VARCHAR(100) NULL,
			description TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_scenarios (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			title VARCHAR(200) NOT NULL,
			description TEXT NULL,
			channel VARCHAR(50) NULL,
			owner_role VARCHAR(50) NULL,
			content TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_rules (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			status_label VARCHAR(200) NULL,
			name VARCHAR(150) NOT NULL,
			priority INT NOT NULL DEFAULT 100,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			alert_days INT UNSIGNED NULL,
			next_action VARCHAR(500) NULL,
			require_note TINYINT(1) NOT NULL DEFAULT 0,
			scenario_id VARCHAR(64) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_mk_tag_rules_active_prio (is_active, priority)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_rule_conditions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			rule_id VARCHAR(64) NOT NULL,
			tag_id VARCHAR(64) NOT NULL,
			UNIQUE KEY uq_mk_tag_rule_cond (rule_id, tag_id),
			INDEX idx_mk_tag_rule_cond_tag (tag_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_rule_dismissals (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			lead_id INT NOT NULL,
			rule_id VARCHAR(64) NOT NULL,
			snooze_until DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uq_mk_tag_dismiss (user_id, lead_id, rule_id),
			INDEX idx_mk_tag_dismiss_lead (lead_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_rule_meta (
			meta_key VARCHAR(64) NOT NULL PRIMARY KEY,
			meta_value VARCHAR(255) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$ver = $this->getMeta('schema_version');
		if ((int)$ver < self::SCHEMA_VERSION) {
			$this->seedIfEmpty();
			$this->setMeta('schema_version', (string)self::SCHEMA_VERSION);
		}
	}

	protected function getMeta($key) {
		$res = $this->db->pquery('SELECT meta_value FROM mk_tag_rule_meta WHERE meta_key = ?', array($key));
		if ($res && $this->db->num_rows($res) > 0) {
			return (string)$this->db->query_result($res, 0, 'meta_value');
		}
		return null;
	}

	protected function setMeta($key, $value) {
		$this->db->pquery(
			'INSERT INTO mk_tag_rule_meta (meta_key, meta_value) VALUES (?, ?)
			 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)',
			array($key, $value)
		);
	}

	public function seedIfEmpty($force = false) {
		$res = $this->db->pquery('SELECT COUNT(*) AS c FROM mk_tag_rules', array());
		$count = ($res && $this->db->num_rows($res) > 0) ? (int)$this->db->query_result($res, 0, 'c') : 0;
		if (!$force && $count > 0) {
			return false;
		}
		if ($force) {
			$this->db->query('DELETE FROM mk_tag_rule_conditions');
			$this->db->query('DELETE FROM mk_tag_rules');
			$this->db->query('DELETE FROM mk_tag_scenarios');
			$this->db->query('DELETE FROM mk_tag_catalog');
		}
		foreach ($this->seedTags() as $tag) {
			$this->upsertTag($tag, false);
		}
		foreach ($this->seedScenarios() as $sc) {
			$this->upsertScenario($sc, false);
		}
		foreach ($this->seedRules() as $rule) {
			$this->upsertRule($rule, false);
		}
		$this->setMeta('seeded_at', date('Y-m-d H:i:s'));
		return true;
	}

	/** ——— Slug / catalog ——— */

	public function slugify($raw) {
		$s = trim((string)$raw);
		if ($s === '') {
			return '';
		}
		if (function_exists('mb_strtolower')) {
			$s = mb_strtolower($s, 'UTF-8');
		} else {
			$s = strtolower($s);
		}
		$map = array(
			'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
			'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
			'ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
			'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
			'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
			'ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
			'đ'=>'d',
		);
		$s = strtr($s, $map);
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		$s = trim($s, '_');
		return $s;
	}

	public function normalizeTagToSlug($raw) {
		$raw = trim(decode_html((string)$raw));
		if ($raw === '') {
			return '';
		}
		$byId = $this->getTagById($raw);
		if ($byId) {
			return $byId['id'];
		}
		$all = $this->getTags();
		$lower = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);
		foreach ($all as $tag) {
			$name = function_exists('mb_strtolower') ? mb_strtolower($tag['name'], 'UTF-8') : strtolower($tag['name']);
			if ($name === $lower) {
				return $tag['id'];
			}
		}
		$aliases = array(
			'khu vuc 1' => 'kv1', 'kv 1' => 'kv1', 'area 1' => 'kv1',
			'khu vuc 2' => 'kv2', 'kv 2' => 'kv2',
			'khu vuc 3' => 'kv3', 'kv 3' => 'kv3',
			'cong ty' => 'company', 'ca nhan' => 'individual',
			'chua pcth' => 'chua_PCTH', 'da pcth' => 'da_PCTH',
		);
		$slug = $this->slugify($raw);
		if (isset($aliases[$slug])) {
			return $aliases[$slug];
		}
		foreach ($all as $tag) {
			if ($this->slugify($tag['name']) === $slug || $tag['id'] === $slug) {
				return $tag['id'];
			}
		}
		return $slug;
	}

	public function normalizeTagList(array $tags) {
		$out = array();
		foreach ($tags as $t) {
			$slug = $this->normalizeTagToSlug($t);
			if ($slug !== '') {
				$out[$slug] = true;
			}
		}
		return array_keys($out);
	}

	/** ——— Bootstrap / CRUD ——— */

	public function bootstrap() {
		return array(
			'tags' => $this->getTags(),
			'rules' => $this->getRules(),
			'scenarios' => $this->getScenarios(),
		);
	}

	public function getTags() {
		$res = $this->db->pquery('SELECT id, name, category, description FROM mk_tag_catalog ORDER BY category, name', array());
		$rows = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rows[] = array(
					'id' => (string)$row['id'],
					'name' => decode_html($row['name']),
					'category' => $row['category'] !== null ? decode_html($row['category']) : null,
					'description' => $row['description'] !== null ? decode_html($row['description']) : null,
				);
			}
		}
		return $rows;
	}

	public function getTagById($id) {
		$res = $this->db->pquery('SELECT id, name, category, description FROM mk_tag_catalog WHERE id = ?', array($id));
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		$row = $this->db->fetchByAssoc($res);
		return array(
			'id' => (string)$row['id'],
			'name' => decode_html($row['name']),
			'category' => $row['category'] !== null ? decode_html($row['category']) : null,
			'description' => $row['description'] !== null ? decode_html($row['description']) : null,
		);
	}

	public function upsertTag(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$name = trim((string)($payload['name'] ?? ''));
		if ($name === '') {
			throw new Exception('Tag name required');
		}
		if ($id === '' && $generateId) {
			$id = $this->slugify($name);
			if ($id === '') {
				$id = 'tag_' . substr(md5(uniqid('', true)), 0, 8);
			}
		}
		$category = isset($payload['category']) && $payload['category'] !== '' ? (string)$payload['category'] : null;
		$description = isset($payload['description']) && $payload['description'] !== '' ? (string)$payload['description'] : null;
		$exists = $this->db->pquery('SELECT id FROM mk_tag_catalog WHERE id = ?', array($id));
		if ($exists && $this->db->num_rows($exists) > 0) {
			$this->db->pquery(
				'UPDATE mk_tag_catalog SET name = ?, category = ?, description = ? WHERE id = ?',
				array($name, $category, $description, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_tag_catalog (id, name, category, description) VALUES (?,?,?,?)',
				array($id, $name, $category, $description)
			);
		}
		return $this->getTagById($id);
	}

	public function deleteTag($id) {
		$this->db->pquery('DELETE FROM mk_tag_rule_conditions WHERE tag_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_catalog WHERE id = ?', array($id));
		return true;
	}

	public function getScenarios() {
		$res = $this->db->pquery(
			'SELECT id, title, description, channel, owner_role, content FROM mk_tag_scenarios ORDER BY title',
			array()
		);
		$rows = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rows[] = array(
					'id' => (string)$row['id'],
					'title' => decode_html($row['title']),
					'description' => $row['description'] !== null ? decode_html($row['description']) : null,
					'channel' => $row['channel'] !== null ? decode_html($row['channel']) : null,
					'owner' => $row['owner_role'] !== null ? decode_html($row['owner_role']) : null,
					'content' => $row['content'] !== null ? decode_html($row['content']) : null,
				);
			}
		}
		return $rows;
	}

	public function getScenarioById($id) {
		if (!$id) {
			return null;
		}
		$res = $this->db->pquery(
			'SELECT id, title, description, channel, owner_role, content FROM mk_tag_scenarios WHERE id = ?',
			array($id)
		);
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		$row = $this->db->fetchByAssoc($res);
		return array(
			'id' => (string)$row['id'],
			'title' => decode_html($row['title']),
			'description' => $row['description'] !== null ? decode_html($row['description']) : null,
			'channel' => $row['channel'] !== null ? decode_html($row['channel']) : null,
			'owner' => $row['owner_role'] !== null ? decode_html($row['owner_role']) : null,
			'content' => $row['content'] !== null ? decode_html($row['content']) : null,
		);
	}

	public function upsertScenario(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$title = trim((string)($payload['title'] ?? ''));
		if ($title === '') {
			throw new Exception('Scenario title required');
		}
		if ($id === '' && $generateId) {
			$id = 'sc-' . substr(md5(uniqid('', true)), 0, 8);
		}
		$description = isset($payload['description']) && $payload['description'] !== '' ? (string)$payload['description'] : null;
		$channel = isset($payload['channel']) && $payload['channel'] !== '' ? (string)$payload['channel'] : null;
		$owner = isset($payload['owner']) ? $payload['owner'] : (isset($payload['owner_role']) ? $payload['owner_role'] : null);
		$owner = ($owner !== null && $owner !== '') ? (string)$owner : null;
		$content = isset($payload['content']) && $payload['content'] !== '' ? (string)$payload['content'] : null;
		$exists = $this->db->pquery('SELECT id FROM mk_tag_scenarios WHERE id = ?', array($id));
		if ($exists && $this->db->num_rows($exists) > 0) {
			$this->db->pquery(
				'UPDATE mk_tag_scenarios SET title=?, description=?, channel=?, owner_role=?, content=? WHERE id=?',
				array($title, $description, $channel, $owner, $content, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_tag_scenarios (id, title, description, channel, owner_role, content) VALUES (?,?,?,?,?,?)',
				array($id, $title, $description, $channel, $owner, $content)
			);
		}
		return $this->getScenarioById($id);
	}

	public function deleteScenario($id) {
		$this->db->pquery('UPDATE mk_tag_rules SET scenario_id = NULL WHERE scenario_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_scenarios WHERE id = ?', array($id));
		return true;
	}

	public function getRules($activeOnly = false) {
		$sql = 'SELECT * FROM mk_tag_rules';
		$params = array();
		if ($activeOnly) {
			$sql .= ' WHERE is_active = 1';
		}
		$sql .= ' ORDER BY priority ASC, name ASC';
		$res = $this->db->pquery($sql, $params);
		$rules = array();
		$ids = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rid = (string)$row['id'];
				$ids[] = $rid;
				$rules[$rid] = array(
					'id' => $rid,
					'status_label' => $row['status_label'] !== null ? decode_html($row['status_label']) : '',
					'name' => decode_html($row['name']),
					'priority' => (int)$row['priority'],
					'is_active' => (int)$row['is_active'] === 1,
					'alert_days' => $row['alert_days'] !== null ? (int)$row['alert_days'] : null,
					'next_action' => $row['next_action'] !== null ? decode_html($row['next_action']) : null,
					'require_note' => (int)$row['require_note'] === 1,
					'scenario_id' => $row['scenario_id'] !== null ? (string)$row['scenario_id'] : null,
					'tag_ids' => array(),
				);
			}
		}
		if (!empty($ids)) {
			$cres = $this->db->pquery(
				'SELECT rule_id, tag_id FROM mk_tag_rule_conditions WHERE rule_id IN (' . generateQuestionMarks($ids) . ')',
				$ids
			);
			if ($cres) {
				while ($crow = $this->db->fetchByAssoc($cres)) {
					$rid = (string)$crow['rule_id'];
					if (isset($rules[$rid])) {
						$rules[$rid]['tag_ids'][] = (string)$crow['tag_id'];
					}
				}
			}
		}
		return array_values($rules);
	}

	public function getRuleById($id) {
		foreach ($this->getRules() as $rule) {
			if ($rule['id'] === $id) {
				return $rule;
			}
		}
		return null;
	}

	public function upsertRule(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$name = trim((string)($payload['name'] ?? ''));
		if ($name === '') {
			throw new Exception('Rule name required');
		}
		if ($id === '' && $generateId) {
			$id = 'rule-' . substr(md5(uniqid('', true)), 0, 8);
		}
		$statusLabel = isset($payload['status_label']) ? (string)$payload['status_label'] : '';
		$priority = isset($payload['priority']) ? (int)$payload['priority'] : 100;
		$isActive = !isset($payload['is_active']) || $payload['is_active'] === true || $payload['is_active'] === 1 || $payload['is_active'] === '1';
		$alertDays = null;
		if (isset($payload['alert_days']) && $payload['alert_days'] !== '' && $payload['alert_days'] !== null) {
			$alertDays = (int)$payload['alert_days'];
		}
		$nextAction = isset($payload['next_action']) && $payload['next_action'] !== '' ? (string)$payload['next_action'] : null;
		$requireNote = !empty($payload['require_note']);
		$scenarioId = isset($payload['scenario_id']) && $payload['scenario_id'] !== '' ? (string)$payload['scenario_id'] : null;
		$tagIds = array();
		if (!empty($payload['tag_ids']) && is_array($payload['tag_ids'])) {
			foreach ($payload['tag_ids'] as $tid) {
				$tid = trim((string)$tid);
				if ($tid !== '') {
					$tagIds[$tid] = true;
				}
			}
		}
		$tagIds = array_keys($tagIds);

		$exists = $this->db->pquery('SELECT id FROM mk_tag_rules WHERE id = ?', array($id));
		if ($exists && $this->db->num_rows($exists) > 0) {
			$this->db->pquery(
				'UPDATE mk_tag_rules SET status_label=?, name=?, priority=?, is_active=?, alert_days=?, next_action=?, require_note=?, scenario_id=? WHERE id=?',
				array($statusLabel, $name, $priority, $isActive ? 1 : 0, $alertDays, $nextAction, $requireNote ? 1 : 0, $scenarioId, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_tag_rules (id, status_label, name, priority, is_active, alert_days, next_action, require_note, scenario_id)
				 VALUES (?,?,?,?,?,?,?,?,?)',
				array($id, $statusLabel, $name, $priority, $isActive ? 1 : 0, $alertDays, $nextAction, $requireNote ? 1 : 0, $scenarioId)
			);
		}
		$this->db->pquery('DELETE FROM mk_tag_rule_conditions WHERE rule_id = ?', array($id));
		foreach ($tagIds as $tid) {
			$this->db->pquery(
				'INSERT INTO mk_tag_rule_conditions (rule_id, tag_id) VALUES (?,?)',
				array($id, $tid)
			);
		}
		return $this->getRuleById($id);
	}

	public function setRuleActive($id, $active) {
		$this->db->pquery('UPDATE mk_tag_rules SET is_active = ? WHERE id = ?', array($active ? 1 : 0, $id));
		return $this->getRuleById($id);
	}

	public function deleteRule($id) {
		$this->db->pquery('DELETE FROM mk_tag_rule_conditions WHERE rule_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_rule_dismissals WHERE rule_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_rules WHERE id = ?', array($id));
		return true;
	}

	/** ——— Match + apply to Lead ——— */

	public function matchRules(array $tagIdsOrLabels, $activeOnly = true) {
		$slugs = $this->normalizeTagList($tagIdsOrLabels);
		$set = array_fill_keys($slugs, true);
		$matches = array();
		foreach ($this->getRules($activeOnly) as $rule) {
			$need = $rule['tag_ids'];
			if (empty($need)) {
				continue;
			}
			$ok = true;
			foreach ($need as $tid) {
				if (!isset($set[$tid])) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				$matches[] = array('rule' => $rule);
			}
		}
		usort($matches, function ($a, $b) {
			return ((int)$a['rule']['priority']) - ((int)$b['rule']['priority']);
		});
		// next_action trên Lead: ưu tiên rule giai đoạn muộn nhất (priority cao nhất)
		$best = null;
		foreach ($matches as $m) {
			if ($best === null || (int)$m['rule']['priority'] >= (int)$best['priority']) {
				$best = $m['rule'];
			}
		}
		return array(
			'slugs' => $slugs,
			'matches' => $matches,
			'best' => $best,
		);
	}

	public function getLeadTagLabels($leadId) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return array();
		}
		$res = $this->db->pquery(
			"SELECT t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id = ?
			 ORDER BY fo.tagged_on ASC",
			array('Leads', $leadId)
		);
		$tags = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$tags[] = decode_html($row['tag']);
			}
		}
		return $tags;
	}

	public function getNextActionForLead($leadId) {
		$match = $this->matchRules($this->getLeadTagLabels($leadId), true);
		if (!empty($match['best']['next_action'])) {
			return (string)$match['best']['next_action'];
		}
		return '';
	}

	/**
	 * Ghi next_action từ rule thắng (priority cao nhất) vào bace_lead_profile.
	 * @return string label đã ghi (có thể rỗng nếu không match)
	 */
	public function applyNextActionToLead($leadId) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return '';
		}
		$action = $this->getNextActionForLead($leadId);
		if ($action === '') {
			return '';
		}
		if (function_exists('mb_substr')) {
			$action = mb_substr($action, 0, 255, 'UTF-8');
		} else {
			$action = substr($action, 0, 255);
		}
		$now = date('Y-m-d H:i:s');
		$exists = $this->db->pquery('SELECT leadid FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if ($exists && $this->db->num_rows($exists) > 0) {
			$this->db->pquery(
				'UPDATE bace_lead_profile SET next_action = ?, modified_at = ? WHERE leadid = ?',
				array($action, $now, $leadId)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO bace_lead_profile (leadid, next_action, is_modern, created_at, modified_at) VALUES (?,?,1,?,?)',
				array($leadId, $action, $now, $now)
			);
		}
		return $action;
	}

	/** Cảnh báo từ Lead thật + rules có alert_days */
	public function getAlerts($userId, $limit = 100) {
		$userId = (int)$userId;
		$rules = array();
		foreach ($this->getRules(true) as $rule) {
			if ($rule['alert_days'] !== null && $rule['alert_days'] > 0) {
				$rules[] = $rule;
			}
		}
		if (empty($rules)) {
			return array();
		}

		$dismissMap = array();
		$dres = $this->db->pquery(
			'SELECT lead_id, rule_id, snooze_until FROM mk_tag_rule_dismissals WHERE user_id = ?',
			array($userId)
		);
		$nowTs = time();
		if ($dres) {
			while ($drow = $this->db->fetchByAssoc($dres)) {
				$key = (int)$drow['lead_id'] . ':' . $drow['rule_id'];
				$until = $drow['snooze_until'];
				if ($until === null || $until === '' || strtotime($until) > $nowTs) {
					$dismissMap[$key] = true;
				}
			}
		}

		$leadRes = $this->db->pquery(
			"SELECT ld.leadid, ld.firstname, ld.lastname, ld.company, ld.phone,
			        ce.smownerid, ce.modifiedtime, ce.createdtime,
			        p.next_action, p.last_touch
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0 AND ce.setype = 'Leads'
			 LEFT JOIN bace_lead_profile p ON p.leadid = ld.leadid
			 ORDER BY ce.modifiedtime DESC
			 LIMIT 500",
			array()
		);
		$leads = array();
		$leadIds = array();
		if ($leadRes) {
			while ($row = $this->db->fetchByAssoc($leadRes)) {
				$lid = (int)$row['leadid'];
				$leadIds[] = $lid;
				$leads[$lid] = $row;
			}
		}
		if (empty($leadIds)) {
			return array();
		}

		$tagMap = array();
		$tres = $this->db->pquery(
			"SELECT fo.object_id, t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = 'Leads' AND fo.object_id IN (" . generateQuestionMarks($leadIds) . ")",
			$leadIds
		);
		if ($tres) {
			while ($trow = $this->db->fetchByAssoc($tres)) {
				$lid = (int)$trow['object_id'];
				if (!isset($tagMap[$lid])) {
					$tagMap[$lid] = array();
				}
				$tagMap[$lid][] = decode_html($trow['tag']);
			}
		}

		$alerts = array();
		foreach ($leads as $lid => $lead) {
			$labels = isset($tagMap[$lid]) ? $tagMap[$lid] : array();
			if (empty($labels)) {
				continue;
			}
			$matched = $this->matchRules($labels, true);
			foreach ($matched['matches'] as $m) {
				$rule = $m['rule'];
				if ($rule['alert_days'] === null || $rule['alert_days'] <= 0) {
					continue;
				}
				$ref = !empty($lead['last_touch']) ? $lead['last_touch'] : (!empty($lead['modifiedtime']) ? $lead['modifiedtime'] : $lead['createdtime']);
				$refTs = $ref ? strtotime($ref) : $nowTs;
				$days = (int)floor(($nowTs - $refTs) / 86400);
				if ($days < (int)$rule['alert_days']) {
					continue;
				}
				$dkey = $lid . ':' . $rule['id'];
				if (isset($dismissMap[$dkey])) {
					continue;
				}
				$firstname = decode_html($lead['firstname']);
				$lastname = decode_html($lead['lastname']);
				$name = trim($firstname . ' ' . $lastname);
				if ($name === '') {
					$name = decode_html($lead['company']) ?: ('Lead #' . $lid);
				}
				$alerts[] = array(
					'lead_id' => $lid,
					'name' => $name,
					'phone' => decode_html($lead['phone']),
					'tags' => $labels,
					'tag_slugs' => $matched['slugs'],
					'rule' => $rule,
					'days_idle' => $days,
					'next_action' => $rule['next_action'],
				);
				if (count($alerts) >= $limit) {
					return $alerts;
				}
			}
		}
		return $alerts;
	}

	public function upsertDismissal($userId, $leadId, $ruleId, $snoozeUntil = null) {
		$userId = (int)$userId;
		$leadId = (int)$leadId;
		$ruleId = (string)$ruleId;
		$this->db->pquery(
			'INSERT INTO mk_tag_rule_dismissals (user_id, lead_id, rule_id, snooze_until)
			 VALUES (?,?,?,?)
			 ON DUPLICATE KEY UPDATE snooze_until = VALUES(snooze_until)',
			array($userId, $leadId, $ruleId, $snoozeUntil)
		);
		return true;
	}

	/** Seed data (Sales slugs) */

	protected function seedTags() {
		return array(
			array('id' => 'facebook', 'name' => 'Facebook', 'category' => 'Nguồn'),
			array('id' => 'tiktok', 'name' => 'TikTok', 'category' => 'Nguồn'),
			array('id' => 'website', 'name' => 'Website', 'category' => 'Nguồn'),
			array('id' => 'zalo', 'name' => 'Zalo', 'category' => 'Nguồn'),
			array('id' => 'hotline', 'name' => 'Hotline', 'category' => 'Nguồn'),
			array('id' => 'other', 'name' => 'Khác', 'category' => 'Nguồn'),
			array('id' => 'kv1', 'name' => 'Khu vực 1', 'category' => 'Khu vực'),
			array('id' => 'kv2', 'name' => 'Khu vực 2', 'category' => 'Khu vực'),
			array('id' => 'kv3', 'name' => 'Khu vực 3', 'category' => 'Khu vực'),
			array('id' => 'individual', 'name' => 'Cá nhân', 'category' => 'Phân loại KH'),
			array('id' => 'company', 'name' => 'Công ty', 'category' => 'Phân loại KH'),
			array('id' => 'tiem_nang', 'name' => 'Tiềm năng', 'category' => 'Mua hàng'),
			array('id' => 'chua_hoc', 'name' => 'Chưa học', 'category' => 'Học liệu'),
			array('id' => 'da_hoc', 'name' => 'Đã học', 'category' => 'Học liệu'),
			array('id' => 'mien_phi_online', 'name' => 'Miễn phí Online', 'category' => 'Chương trình'),
			array('id' => 'mien_phi_offline', 'name' => 'Miễn phí Offline', 'category' => 'Chương trình'),
			array('id' => 'da_tg_free', 'name' => 'Đã TG FREE', 'category' => 'Chương trình'),
			array('id' => 'thu_3', 'name' => 'THỨ 3', 'category' => 'Lịch hẹn'),
			array('id' => 'pcth', 'name' => 'PCTH', 'category' => 'Chương trình'),
			array('id' => 'chua_PCTH', 'name' => 'Chưa PCTH', 'category' => 'Chương trình'),
			array('id' => 'da_PCTH', 'name' => 'Đã PCTH', 'category' => 'Chương trình'),
			array('id' => 'van_hanh', 'name' => 'Vận hành', 'category' => 'Chương trình'),
			array('id' => 'mkt', 'name' => 'Marketing', 'category' => 'Chương trình'),
			array('id' => 'lop_khac', 'name' => 'Lớp khác', 'category' => 'Chương trình'),
			array('id' => 'nhuong_quyen', 'name' => 'Nhượng quyền', 'category' => 'Nhượng quyền'),
			array('id' => 'khong_nghe_may', 'name' => 'Không nghe máy', 'category' => 'Liên hệ'),
			array('id' => 'thue_bao', 'name' => 'Thuê bao', 'category' => 'Liên hệ'),
			array('id' => 'trung_so', 'name' => 'Trùng số / Sai số', 'category' => 'Liên hệ'),
			array('id' => 'doi_lich', 'name' => 'Dời lịch', 'category' => 'Lịch hẹn'),
			array('id' => 'xac_nhan_tham_gia', 'name' => 'Xác nhận tham gia', 'category' => 'Xác nhận'),
			array('id' => 'khong_xac_nhan_tham_gia', 'name' => 'Không tham gia', 'category' => 'Xác nhận'),
			array('id' => 'moi_lai', 'name' => 'Mời lại', 'category' => 'Lịch hẹn'),
			array('id' => 'ngung_cham_soc', 'name' => 'Ngừng chăm sóc', 'category' => 'Liên hệ'),
			array('id' => 'goi_lan_1', 'name' => 'Gọi lần 1', 'category' => 'Liên hệ'),
			array('id' => 'goi_lan_2', 'name' => 'Gọi lần 2', 'category' => 'Liên hệ'),
			array('id' => 'goi_lan_3', 'name' => 'Gọi lần 3', 'category' => 'Liên hệ'),
			array('id' => 'mua_lan_dau', 'name' => 'Mua lần đầu', 'category' => 'Mua hàng'),
			array('id' => 'mua_lai', 'name' => 'Mua lại', 'category' => 'Mua hàng'),
			array('id' => 'mua_on_dinh', 'name' => 'Mua ổn định', 'category' => 'Mua hàng'),
			array('id' => 'khong_mua', 'name' => 'Không mua', 'category' => 'Mua hàng'),
			array('id' => 'ngung_mua', 'name' => 'Ngưng mua', 'category' => 'Mua hàng'),
			array('id' => 'mua_it_lai', 'name' => 'Mua ít lại', 'category' => 'Mua hàng'),
			array('id' => 'vang', 'name' => 'Vàng', 'category' => 'Hạng KH'),
			array('id' => 'bac', 'name' => 'Bạc', 'category' => 'Hạng KH'),
			array('id' => 'dong', 'name' => 'Đồng', 'category' => 'Hạng KH'),
		);
	}

	protected function seedScenarios() {
		return array(
			array('id' => 'sc-onboard', 'title' => 'Tư vấn / onboarding data mới', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Chào {Tên}, em là Sale Nguyên Khoa...'),
			array('id' => 'sc-miss', 'title' => 'Không nghe máy', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Nhắn Zalo + lên lịch gọi lại.'),
			array('id' => 'sc-bad-phone', 'title' => 'Thuê bao / trùng số', 'channel' => 'Điện thoại', 'owner' => 'Sale', 'content' => 'Rà số thay thế + ghi chú.'),
			array('id' => 'sc-care', 'title' => 'Chuỗi gọi lần 1–3', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Follow-up theo cấp gọi.'),
			array('id' => 'sc-reschedule', 'title' => 'Dời lịch', 'channel' => 'Điện thoại', 'owner' => 'Sale', 'content' => 'Xác nhận lịch mới.'),
			array('id' => 'sc-confirm', 'title' => 'Xác nhận tham gia', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Gửi thư mời + nhắc hẹn.'),
			array('id' => 'sc-reask', 'title' => 'Mời lại', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Kịch bản hỏi / mời lại.'),
			array('id' => 'sc-online-onboard', 'title' => 'Online free', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Info lớp → login → đốc thúc.'),
			array('id' => 'sc-online-low', 'title' => 'Online <80%', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Đốc thúc + mời mua NL.'),
			array('id' => 'sc-online-high', 'title' => 'Online ≥80% chốt PCTH', 'channel' => 'Điện thoại', 'owner' => 'Sale', 'content' => 'Gọi + nhắn riêng chốt PCTH.'),
			array('id' => 'sc-offline', 'title' => 'Offline free', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Nhắc lịch offline → mời PCTH.'),
			array('id' => 'sc-pcth-wait', 'title' => 'Chưa PCTH', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Gửi lịch → nhắc đến khi tham gia.'),
			array('id' => 'sc-pcth-done', 'title' => 'Sau PCTH', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Mời lớp VH/MKT hoặc NL.'),
			array('id' => 'sc-buy-first', 'title' => 'Mời mua lần đầu', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Ưu đãi mua lần đầu.'),
			array('id' => 'sc-no-buy', 'title' => 'Không mua', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Ghi chú lý do + nuôi lại.'),
			array('id' => 'sc-after-first', 'title' => 'Sau mua lần đầu', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Chăm đơn đầu + mời mua lại.'),
			array('id' => 'sc-no-rebuy', 'title' => 'Mua ít lại', 'channel' => 'Zalo', 'owner' => 'Sale', 'content' => 'Ghi chú + kéo lại.'),
			array('id' => 'sc-rebuy', 'title' => 'Đã mua lại', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Chăm + phân hạng.'),
			array('id' => 'sc-tier', 'title' => 'Chăm hạng V/B/Đ', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Chăm hạng + upsell.'),
			array('id' => 'sc-churn', 'title' => 'Ngưng mua', 'channel' => 'Điện thoại', 'owner' => 'Sale', 'content' => 'Ghi chú + kích hoạt lại.'),
			array('id' => 'sc-class', 'title' => 'Chăm lớp chuyên sâu', 'channel' => 'Zalo', 'owner' => 'CSKH', 'content' => 'Chăm lớp + upsell NL.'),
			array('id' => 'sc-franchise', 'title' => 'Nhượng quyền', 'channel' => 'Điện thoại', 'owner' => 'Sale', 'content' => 'Tư vấn nhượng quyền.'),
		);
	}

	protected function seedRules() {
		return array(
			array('id' => 'rule-r01', 'status_label' => 'Data mới — tiềm năng', 'name' => 'R01 Tiềm năng', 'tag_ids' => array('tiem_nang'), 'priority' => 10, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 0, 'scenario_id' => 'sc-onboard', 'next_action' => 'Kịch bản tư vấn bán hàng / chào hỏi Zalo'),
			array('id' => 'rule-r02', 'status_label' => 'Chưa học nguyên liệu', 'name' => 'R02 Chưa học', 'tag_ids' => array('chua_hoc'), 'priority' => 15, 'is_active' => 1, 'alert_days' => 2, 'require_note' => 0, 'scenario_id' => 'sc-onboard', 'next_action' => 'Mời học liệu / lớp miễn phí'),
			array('id' => 'rule-r03', 'status_label' => 'Không nghe máy', 'name' => 'R03 Miss call', 'tag_ids' => array('khong_nghe_may'), 'priority' => 20, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 0, 'scenario_id' => 'sc-miss', 'next_action' => 'Nhắn Zalo + lên lịch gọi lại (tối đa ~3 lần)'),
			array('id' => 'rule-r04', 'status_label' => 'Thuê bao', 'name' => 'R04 Thuê bao', 'tag_ids' => array('thue_bao'), 'priority' => 22, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 1, 'scenario_id' => 'sc-bad-phone', 'next_action' => 'Rà số thay thế / Messenger + ghi chú lý do'),
			array('id' => 'rule-r05', 'status_label' => 'Sai / trùng số', 'name' => 'R05 Trùng số', 'tag_ids' => array('trung_so'), 'priority' => 23, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 1, 'scenario_id' => 'sc-bad-phone', 'next_action' => 'Xác minh số đúng + ghi chú CRM'),
			array('id' => 'rule-r06', 'status_label' => 'Gọi lần 1', 'name' => 'R06 Gọi lần 1', 'tag_ids' => array('goi_lan_1'), 'priority' => 30, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 0, 'scenario_id' => 'sc-care', 'next_action' => 'Follow-up lần 2: lộ trình + ưu đãi'),
			array('id' => 'rule-r07', 'status_label' => 'Gọi lần 2', 'name' => 'R07 Gọi lần 2', 'tag_ids' => array('goi_lan_2'), 'priority' => 31, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 0, 'scenario_id' => 'sc-care', 'next_action' => 'Follow-up lần 3: chốt lịch tư vấn / lớp'),
			array('id' => 'rule-r08', 'status_label' => 'Gọi lần 3', 'name' => 'R08 Gọi lần 3', 'tag_ids' => array('goi_lan_3'), 'priority' => 32, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 0, 'scenario_id' => 'sc-care', 'next_action' => 'Escalate Sale Lead nếu chưa chốt'),
			array('id' => 'rule-r09', 'status_label' => 'Dời lịch', 'name' => 'R09 Dời lịch', 'tag_ids' => array('doi_lich'), 'priority' => 40, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 1, 'scenario_id' => 'sc-reschedule', 'next_action' => 'Xác nhận lịch mới + nhắc trước 1 ngày'),
			array('id' => 'rule-r10', 'status_label' => 'Xác nhận tham gia', 'name' => 'R10 Xác nhận', 'tag_ids' => array('xac_nhan_tham_gia'), 'priority' => 50, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 0, 'scenario_id' => 'sc-confirm', 'next_action' => 'Gửi thư mời / địa chỉ / checklist + nhắc hẹn'),
			array('id' => 'rule-r11', 'status_label' => 'Không tham gia', 'name' => 'R11 Ngừng theo dõi', 'tag_ids' => array('khong_xac_nhan_tham_gia'), 'priority' => 55, 'is_active' => 1, 'alert_days' => null, 'require_note' => 1, 'scenario_id' => null, 'next_action' => 'Ghi chú lý do — dừng nurture'),
			array('id' => 'rule-r11b', 'status_label' => 'Ngừng chăm sóc', 'name' => 'R11b Ngừng CS', 'tag_ids' => array('ngung_cham_soc'), 'priority' => 56, 'is_active' => 1, 'alert_days' => null, 'require_note' => 1, 'scenario_id' => null, 'next_action' => 'Ghi chú lý do ngừng chăm sóc'),
			array('id' => 'rule-r12', 'status_label' => 'Mời lại', 'name' => 'R12 Mời lại', 'tag_ids' => array('moi_lai'), 'priority' => 57, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 1, 'scenario_id' => 'sc-reask', 'next_action' => 'Kịch bản hỏi / mời lại + ghi chú thời điểm'),
			array('id' => 'rule-r13', 'status_label' => 'Miễn phí Online', 'name' => 'R13 Online', 'tag_ids' => array('mien_phi_online'), 'priority' => 60, 'is_active' => 1, 'alert_days' => null, 'require_note' => 0, 'scenario_id' => 'sc-online-onboard', 'next_action' => 'Chuỗi: info lớp → hướng dẫn login → đốc thúc'),
			array('id' => 'rule-r15', 'status_label' => 'Online + Chưa PCTH', 'name' => 'R15 Online chốt PCTH', 'tag_ids' => array('mien_phi_online', 'chua_PCTH'), 'priority' => 64, 'is_active' => 1, 'alert_days' => 2, 'require_note' => 0, 'scenario_id' => 'sc-online-high', 'next_action' => 'Gọi + nhắn riêng chốt đăng ký PCTH'),
			array('id' => 'rule-r14', 'status_label' => 'Online dưới 80%', 'name' => 'R14 Online low', 'tag_ids' => array('mien_phi_online'), 'priority' => 66, 'is_active' => 1, 'alert_days' => 5, 'require_note' => 0, 'scenario_id' => 'sc-online-low', 'next_action' => 'Chuỗi đốc thúc + mời mua NL + ĐK lớp có phí'),
			array('id' => 'rule-r16', 'status_label' => 'Miễn phí Offline', 'name' => 'R16 Offline', 'tag_ids' => array('mien_phi_offline'), 'priority' => 62, 'is_active' => 1, 'alert_days' => 2, 'require_note' => 0, 'scenario_id' => 'sc-offline', 'next_action' => 'Nhắc lịch offline → sau buổi: mời PCTH / Nhượng quyền'),
			array('id' => 'rule-r16b', 'status_label' => 'Đã TG FREE', 'name' => 'R16b Đã TG FREE', 'tag_ids' => array('da_tg_free'), 'priority' => 63, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 0, 'scenario_id' => 'sc-offline', 'next_action' => 'After free: mời PCTH / mua NL'),
			array('id' => 'rule-r17', 'status_label' => 'Chưa PCTH', 'name' => 'R17 Chưa PCTH', 'tag_ids' => array('chua_PCTH'), 'priority' => 70, 'is_active' => 1, 'alert_days' => 2, 'require_note' => 0, 'scenario_id' => 'sc-pcth-wait', 'next_action' => 'Gửi lịch → xác nhận → nhắc đến khi tham gia'),
			array('id' => 'rule-r18', 'status_label' => 'PCTH', 'name' => 'R18 PCTH', 'tag_ids' => array('pcth'), 'priority' => 72, 'is_active' => 1, 'alert_days' => 5, 'require_note' => 0, 'scenario_id' => 'sc-pcth-done', 'next_action' => 'Mời lớp VH/MKT/khác hoặc mua NL / nhượng quyền'),
			array('id' => 'rule-r18b', 'status_label' => 'Đã PCTH', 'name' => 'R18b da_PCTH', 'tag_ids' => array('da_PCTH'), 'priority' => 73, 'is_active' => 1, 'alert_days' => 5, 'require_note' => 0, 'scenario_id' => 'sc-pcth-done', 'next_action' => 'Mời lớp nâng cao / NL / nhượng quyền'),
			array('id' => 'rule-r19', 'status_label' => 'Đã học', 'name' => 'R19 Đã học', 'tag_ids' => array('da_hoc'), 'priority' => 75, 'is_active' => 1, 'alert_days' => 7, 'require_note' => 0, 'scenario_id' => 'sc-buy-first', 'next_action' => 'Mời mua lần đầu + tư vấn set-up'),
			array('id' => 'rule-r20', 'status_label' => 'Không mua', 'name' => 'R20 Không mua', 'tag_ids' => array('khong_mua'), 'priority' => 78, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 1, 'scenario_id' => 'sc-no-buy', 'next_action' => 'Bắt buộc ghi chú lý do + kịch bản mời mua NL / lớp free'),
			array('id' => 'rule-r21', 'status_label' => 'Mua lần đầu', 'name' => 'R21 Mua lần đầu', 'tag_ids' => array('mua_lan_dau'), 'priority' => 80, 'is_active' => 1, 'alert_days' => 14, 'require_note' => 0, 'scenario_id' => 'sc-after-first', 'next_action' => 'Chăm đơn đầu + chuỗi mời mua lại / lớp nâng cao'),
			array('id' => 'rule-r22', 'status_label' => 'Mua ít lại', 'name' => 'R22 Mua ít lại', 'tag_ids' => array('mua_it_lai'), 'priority' => 85, 'is_active' => 1, 'alert_days' => 7, 'require_note' => 1, 'scenario_id' => 'sc-no-rebuy', 'next_action' => 'Ghi chú lý do + kịch bản kéo lại'),
			array('id' => 'rule-r23', 'status_label' => 'Mua lại', 'name' => 'R23 Mua lại', 'tag_ids' => array('mua_lai'), 'priority' => 82, 'is_active' => 1, 'alert_days' => 30, 'require_note' => 0, 'scenario_id' => 'sc-rebuy', 'next_action' => 'Chăm mua lại + phân hạng Vàng/Bạc/Đồng'),
			array('id' => 'rule-r23b', 'status_label' => 'Mua ổn định', 'name' => 'R23b ổn định', 'tag_ids' => array('mua_on_dinh'), 'priority' => 83, 'is_active' => 1, 'alert_days' => 45, 'require_note' => 0, 'scenario_id' => 'sc-tier', 'next_action' => 'Duy trì chăm + upsell hạng'),
			array('id' => 'rule-r24', 'status_label' => 'Hạng Vàng', 'name' => 'R24 Vàng', 'tag_ids' => array('vang'), 'priority' => 90, 'is_active' => 1, 'alert_days' => 45, 'require_note' => 0, 'scenario_id' => 'sc-tier', 'next_action' => 'Chuỗi chăm hạng + quà / thử nước / upsell'),
			array('id' => 'rule-r25', 'status_label' => 'Hạng Bạc', 'name' => 'R25 Bạc', 'tag_ids' => array('bac'), 'priority' => 91, 'is_active' => 1, 'alert_days' => 45, 'require_note' => 0, 'scenario_id' => 'sc-tier', 'next_action' => 'Chăm hạng Bạc + khuyến khích lên Vàng'),
			array('id' => 'rule-r26', 'status_label' => 'Hạng Đồng', 'name' => 'R26 Đồng', 'tag_ids' => array('dong'), 'priority' => 92, 'is_active' => 1, 'alert_days' => 30, 'require_note' => 0, 'scenario_id' => 'sc-tier', 'next_action' => 'Chăm hạng Đồng + đẩy mua lại'),
			array('id' => 'rule-r28', 'status_label' => 'Ngưng mua', 'name' => 'R28 Ngưng mua', 'tag_ids' => array('ngung_mua'), 'priority' => 96, 'is_active' => 1, 'alert_days' => 3, 'require_note' => 1, 'scenario_id' => 'sc-churn', 'next_action' => 'Ghi chú lý do + kéo lại funnel free/PCTH'),
			array('id' => 'rule-r29', 'status_label' => 'Vận hành', 'name' => 'R29 Vận hành', 'tag_ids' => array('van_hanh'), 'priority' => 100, 'is_active' => 1, 'alert_days' => 10, 'require_note' => 0, 'scenario_id' => 'sc-class', 'next_action' => 'Chăm lớp VH + mời mua NL / upsell'),
			array('id' => 'rule-r30', 'status_label' => 'MKT', 'name' => 'R30 MKT', 'tag_ids' => array('mkt'), 'priority' => 101, 'is_active' => 1, 'alert_days' => 10, 'require_note' => 0, 'scenario_id' => 'sc-class', 'next_action' => 'Chăm lớp MKT + upsell'),
			array('id' => 'rule-r31', 'status_label' => 'Lớp khác', 'name' => 'R31 Lớp khác', 'tag_ids' => array('lop_khac'), 'priority' => 102, 'is_active' => 1, 'alert_days' => 10, 'require_note' => 0, 'scenario_id' => 'sc-class', 'next_action' => 'Chăm lớp khác + upsell'),
			array('id' => 'rule-r32', 'status_label' => 'Nhượng quyền', 'name' => 'R32 Nhượng quyền', 'tag_ids' => array('nhuong_quyen'), 'priority' => 105, 'is_active' => 1, 'alert_days' => 7, 'require_note' => 0, 'scenario_id' => 'sc-franchise', 'next_action' => 'Kịch bản nhượng quyền + nguyên liệu chuỗi'),
			array('id' => 'rule-r33', 'status_label' => 'THỨ 3', 'name' => 'R33 THỨ 3', 'tag_ids' => array('thu_3'), 'priority' => 48, 'is_active' => 1, 'alert_days' => 1, 'require_note' => 0, 'scenario_id' => 'sc-confirm', 'next_action' => 'Nhắc lịch Thứ 3 + xác nhận tham gia'),
		);
	}
}
