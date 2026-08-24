<?php
/*+***********************************************************************************
 * HelpDesk_TagRuleEngineService — Tag Rule Engine (DB).
 * Nếu tags (AND) → Thì next_action / cảnh báo / kịch bản.
 * Dùng slug catalog Sales; ghi bace_lead_profile.next_action khi match.
 *************************************************************************************/

class HelpDesk_TagRuleEngineService {

	/** @var PearDatabase */
	protected $db;

	const SCHEMA_VERSION = 5;
	const CSKH_RULE_ID = 'rule-cskh';
	const CSKH_ALERT_DAYS_DEFAULT = 7;

	/** Tag cha cố định trên form tạo Lead — không được xoá. */
	const CORE_CREATE_GROUP_IDS = array(
		'nguyen_lieu',
		'nhuong_quyen_group',
		'lop_hoc',
	);

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

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_groups (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			name VARCHAR(150) NOT NULL,
			sort_order INT NOT NULL DEFAULT 0,
			show_on_create TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_tag_group_members (
			group_id VARCHAR(64) NOT NULL,
			tag_id VARCHAR(64) NOT NULL,
			sort_order INT NOT NULL DEFAULT 0,
			PRIMARY KEY (group_id, tag_id),
			INDEX idx_mk_tag_group_members_tag (tag_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS mk_affiliate_reward_tiers (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			prefix CHAR(1) NOT NULL,
			tier_name VARCHAR(100) NOT NULL,
			reward_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
			retention_days INT UNSIGNED NOT NULL DEFAULT 180,
			effective_from DATE NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY uq_mk_aff_tier_prefix (prefix),
			INDEX idx_mk_aff_tier_status (status, effective_from)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->ensureCatalogExtraColumns();

		$ver = $this->getMeta('schema_version');
		if ((int)$ver < 1) {
			$this->seedIfEmpty();
			$this->setMeta('schema_version', '1');
			$ver = '1';
		}
		if ((int)$ver < self::SCHEMA_VERSION) {
			$this->migrateGroupsV2();
			$this->pruneLegacyGroupsV4();
			$this->setMeta('schema_version', (string)self::SCHEMA_VERSION);
		}
		if ($this->getMeta('cskh_alert_days') === null) {
			$this->setMeta('cskh_alert_days', (string)self::CSKH_ALERT_DAYS_DEFAULT);
		}
		$this->seedAffiliateTiersIfEmpty();
	}

	protected function ensureCatalogExtraColumns() {
		$cols = array();
		try {
			$rs = $this->db->getColumnNames('mk_tag_catalog');
			if (is_array($rs)) {
				foreach ($rs as $c) {
					$cols[strtolower((string)$c)] = true;
				}
			}
		} catch (Exception $e) {
			$cols = array();
		}
		if (empty($cols['group_id'])) {
			$this->db->query('ALTER TABLE mk_tag_catalog ADD COLUMN group_id VARCHAR(64) NULL');
		}
		if (empty($cols['scope_lead'])) {
			$this->db->query('ALTER TABLE mk_tag_catalog ADD COLUMN scope_lead TINYINT(1) NOT NULL DEFAULT 1');
		}
		if (empty($cols['scope_opp'])) {
			$this->db->query('ALTER TABLE mk_tag_catalog ADD COLUMN scope_opp TINYINT(1) NOT NULL DEFAULT 1');
		}
		if (empty($cols['scope_contact'])) {
			$this->db->query('ALTER TABLE mk_tag_catalog ADD COLUMN scope_contact TINYINT(1) NOT NULL DEFAULT 1');
		}
	}

	/**
	 * Seed tag-cha groups + map existing tags; ensure Lead create pools exist.
	 */
	protected function migrateGroupsV2() {
		foreach ($this->seedGroups() as $g) {
			$this->upsertGroup($g, false);
		}
		// Ensure create-pool child tags exist (keep old labels).
		foreach ($this->seedCreatePoolTags() as $tag) {
			$existing = $this->getTagById($tag['id']);
			if (!$existing) {
				$this->upsertTag($tag, false);
			} else {
				// Remap category/group without renaming if already present.
				$this->upsertTag(array_merge($existing, array(
					'category' => $tag['category'],
					'group_id' => $tag['group_id'],
					'scope_lead' => 1,
					'scope_opp' => isset($tag['scope_opp']) ? (int)$tag['scope_opp'] : 1,
					'scope_contact' => isset($tag['scope_contact']) ? (int)$tag['scope_contact'] : 1,
				)), false);
			}
			$this->addTagToGroup($tag['group_id'], $tag['id'], isset($tag['sort_order']) ? (int)$tag['sort_order'] : 0);
		}
		// Map remaining catalog rows by category name → group.
		$catToGroup = array(
			'Nguồn' => 'nguon',
			'Khu vực' => 'khu_vuc',
			'Phân loại KH' => 'dang_kh',
			'Dạng KH' => 'dang_kh',
			'Học liệu' => 'lop_hoc',
			'Chương trình' => 'lop_hoc',
			'Nhượng quyền' => 'nhuong_quyen_group',
			'Liên hệ' => 'lien_he',
			'Lịch hẹn' => 'lop_hoc',
			'Xác nhận' => 'xac_nhan',
			'Mua hàng' => 'nguyen_lieu',
			'Hạng KH' => 'hang_kh',
			'Nguyên liệu' => 'nguyen_lieu',
			'Lớp học' => 'lop_hoc',
		);
		foreach ($this->getTags() as $tag) {
			$gid = isset($tag['group_id']) ? trim((string)$tag['group_id']) : '';
			if ($gid !== '') {
				$this->addTagToGroup($gid, $tag['id'], 0);
				continue;
			}
			$cat = isset($tag['category']) ? trim((string)$tag['category']) : '';
			if ($cat !== '' && isset($catToGroup[$cat])) {
				$this->db->pquery('UPDATE mk_tag_catalog SET group_id = ? WHERE id = ?', array($catToGroup[$cat], $tag['id']));
				$this->addTagToGroup($catToGroup[$cat], $tag['id'], 0);
			}
		}
	}

	protected function seedGroups() {
		// Chỉ 3 tag cha chính (form tạo Lead). Tag cha mới thêm qua UI “＋ Thêm mới…”.
		return array(
			array('id' => 'nguyen_lieu', 'name' => 'Nguyên liệu', 'sort_order' => 10, 'show_on_create' => 1),
			array('id' => 'nhuong_quyen_group', 'name' => 'Nhượng quyền', 'sort_order' => 20, 'show_on_create' => 1),
			array('id' => 'lop_hoc', 'name' => 'Lớp học', 'sort_order' => 30, 'show_on_create' => 1),
		);
	}

	/**
	 * Gộp / xoá nhóm legacy (Chương trình, Học liệu, …) — chỉ giữ 3 tag cha chính + nhóm show_on_create.
	 */
	protected function pruneLegacyGroupsV4() {
		$keep = array(
			'nguyen_lieu' => true,
			'nhuong_quyen_group' => true,
			'lop_hoc' => true,
		);
		// Alias legacy → nhóm chính
		$aliasToKeep = array(
			'mua_hang' => 'nguyen_lieu',
			'chuong_trinh' => 'lop_hoc',
			'hoc_lieu' => 'lop_hoc',
			'lich_hen' => 'lop_hoc',
			'phan_loai_kh' => 'nguyen_lieu',
			'dang_kh' => 'nguyen_lieu',
			'nguon' => 'nguyen_lieu',
			'khu_vuc' => 'nguyen_lieu',
			'lien_he' => 'nhuong_quyen_group',
			'xac_nhan' => 'lop_hoc',
			'hang_kh' => 'nguyen_lieu',
		);
		foreach ($aliasToKeep as $fromId => $toId) {
			$toGroup = $this->getGroupById($toId);
			$toName = $toGroup ? $toGroup['name'] : null;
			$rs = $this->db->pquery(
				'SELECT tag_id, sort_order FROM mk_tag_group_members WHERE group_id = ?',
				array($fromId)
			);
			if ($rs) {
				while ($row = $this->db->fetchByAssoc($rs)) {
					$this->addTagToGroup($toId, $row['tag_id'], (int)$row['sort_order']);
					if ($toName !== null) {
						$this->db->pquery(
							'UPDATE mk_tag_catalog SET group_id = ?, category = ? WHERE id = ? AND (group_id IS NULL OR group_id = ? OR group_id = ?)',
							array($toId, $toName, $row['tag_id'], $fromId, $toId)
						);
					} else {
						$this->db->pquery(
							'UPDATE mk_tag_catalog SET group_id = ? WHERE id = ? AND (group_id IS NULL OR group_id = ?)',
							array($toId, $row['tag_id'], $fromId)
						);
					}
				}
			}
			$this->db->pquery('DELETE FROM mk_tag_group_members WHERE group_id = ?', array($fromId));
			$this->db->pquery('DELETE FROM mk_tag_groups WHERE id = ?', array($fromId));
		}
		// Xoá mọi nhóm còn lại không phải 3 chính và không show_on_create
		$all = $this->getGroups();
		foreach ($all as $g) {
			if (!empty($keep[$g['id']])) {
				continue;
			}
			if (!empty($g['show_on_create'])) {
				continue; // giữ tag cha user tự tạo cho form Lead
			}
			$this->deleteGroup($g['id']);
		}
		// Đảm bảo 3 nhóm chính tồn tại + show_on_create
		foreach ($this->seedGroups() as $g) {
			$this->upsertGroup($g, false);
		}
	}

	protected function seedCreatePoolTags() {
		$n = 'Nguyên liệu';
		$f = 'Nhượng quyền';
		$l = 'Lớp học';
		$out = array();
		// Image 2 — Tag Nguyên Liệu (BA / Opp material pool)
		$intent = array(
			array('dang_tu_van', 'Đang tư vấn'),
			array('mua_lan_dau', 'Mua lần đầu'),
			array('dung_cham_soc', 'Dừng chăm sóc'),
			array('kh_can_nhac', 'KH Cân Nhắc'),
			array('mua_lai', 'Mua Lại'),
			array('mua_it_lai', 'Mua ít lại'),
			array('ngung_mua', 'Ngừng Mua'),
		);
		$i = 0;
		foreach ($intent as $pair) {
			$out[] = array('id' => $pair[0], 'name' => $pair[1], 'category' => $n, 'group_id' => 'nguyen_lieu', 'sort_order' => ++$i, 'scope_opp' => 1, 'scope_contact' => 1);
		}
		// Image 4 — Tag Nhượng Quyền
		$franchise = array(
			array('dang_tu_van', 'Đang tư vấn'),
			array('khong_nghe_may', 'Không nghe máy'),
			array('thue_bao', 'Thuê Bao'),
			array('tiem_nang', 'Tiềm năng'),
			array('tham_khao', 'Tham Khảo'),
			array('dung_cham_soc', 'Dừng Chăm Sóc'),
			array('khong_du_tai_chinh', 'Không đủ tài chính'),
			array('da_ky_quy', 'Đã Ký Quỹ'),
			array('mien_bac', 'Miền Bắc'),
		);
		$i = 0;
		foreach ($franchise as $pair) {
			$out[] = array('id' => $pair[0], 'name' => $pair[1], 'category' => $f, 'group_id' => 'nhuong_quyen_group', 'sort_order' => ++$i, 'scope_opp' => 1, 'scope_contact' => 1);
		}
		// Image 3 — Tag Lớp Học
		$entry = array(
			array('thu_3', 'THỨ 3'),
			array('lop_online', 'lớp online'),
			array('moi_lai', 'Mời lại'),
			array('da_tg_free', 'Đã TG FREE'),
			array('doi_lich', 'Dời lịch'),
			array('L1', 'L1'),
			array('L2', 'L2'),
			array('khong_hoc', 'Không học'),
			array('thue_bao', 'thuê bao'),
			array('trung_so', 'trùng số'),
			array('khong_nghe_may', 'không nghe máy'),
			array('ngung_cham_soc', 'Ngừng chăm sóc'),
			array('chua_MQBB_chua_PCTH', 'Chưa MQBB + Chưa PCTH'),
			array('chua_MQBB_da_PCTH', 'Chưa MQBB + Đã PCTH'),
			array('da_MQBB_chua_PCTH', 'Đã MQBB + Chưa PCTH'),
			array('da_MQBB_da_PCTH', 'Đã MQBB + Đã PCTH'),
			array('da_MQBB', 'Đã MQBB'),
			array('chua_MQBB', 'Chưa MQBB'),
			array('da_PCTH', 'Đã PCTH'),
			array('chua_PCTH', 'Chưa PCTH'),
			array('da_990k', 'Đã 990k'),
			array('chua_990k', 'Chưa 990k'),
			array('hoan_tien_lop_hoc', 'Hoàn tiền lớp học'),
			array('lop_khac', 'Lớp khác'),
			array('mkt', 'Marketing'),
			array('van_hanh', 'Vận hành'),
			array('nguyen_lieu_chuoi', 'NL chuỗi'),
		);
		$i = 0;
		foreach ($entry as $pair) {
			$out[] = array('id' => $pair[0], 'name' => $pair[1], 'category' => $l, 'group_id' => 'lop_hoc', 'sort_order' => ++$i, 'scope_opp' => 1, 'scope_contact' => 1);
		}
		// Image 5 — Dạng KH (không đưa tên cha vào danh sách con)
		$customer = array(
			array('co_quan', 'CÓ QUÁN'),
			array('chuan_bi_mo', 'CHUẨN BỊ MỞ'),
			array('gia_dinh', 'GIA ĐÌNH'),
		);
		$i = 0;
		foreach ($customer as $pair) {
			$out[] = array('id' => $pair[0], 'name' => $pair[1], 'category' => 'Dạng KH', 'group_id' => 'dang_kh', 'sort_order' => ++$i, 'scope_opp' => 1, 'scope_contact' => 1);
		}
		// Image 6 — Nguồn (bổ sung nguồn thực tế)
		$sources = array(
			array('facebook', 'Facebook'),
			array('tiktok', 'tiktok'),
			array('website', 'Website'),
			array('zalo', 'Zalo'),
			array('hotline', 'Hotline'),
			array('ladipage_fb', 'Ladipage FB'),
			array('nguyen_khoa_fnb', 'nguyên khoa F&B'),
			array('nguyen_lieu_gia_si', 'nguyên liệu giá sỉ'),
			array('khach_tu_tim_toi', 'khách tự tìm tới'),
			array('khach_di_chung', 'Khách đi chung'),
			array('other', 'Khác'),
		);
		$i = 0;
		foreach ($sources as $pair) {
			$out[] = array('id' => $pair[0], 'name' => $pair[1], 'category' => 'Nguồn', 'group_id' => 'nguon', 'sort_order' => ++$i, 'scope_opp' => 1, 'scope_contact' => 1);
		}
		return $out;
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
			$this->db->query('DELETE FROM mk_tag_group_members');
			$this->db->query('DELETE FROM mk_tag_catalog');
			$this->db->query('DELETE FROM mk_tag_groups');
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
		$this->migrateGroupsV2();
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

	public function bootstrap($userId = null) {
		$out = array(
			'tags' => $this->getTags(),
			'groups' => $this->getGroups(),
			'rules' => $this->getRules(),
			'scenarios' => $this->getScenarios(),
			'affiliate_tiers' => $this->getAffiliateTiers(),
			'sheet_scoring' => $this->getSheetScoringConfig(),
			'channel_options' => $this->getScenarioChannelOptions(),
			'assignee_options' => $this->getScenarioAssigneeOptions(),
			'cskh_alert_days' => $this->getCskhAlertDays(),
			'create_tag_groups' => $this->getCreateTagGroups(),
		);
		if ($userId !== null) {
			$out['alerts'] = $this->getAlerts((int)$userId, 200);
		}
		return $out;
	}

	/**
	 * Kênh kịch bản — lấy từ tag nhóm Nguồn/Kênh + kênh đã dùng trong kịch bản.
	 */
	public function getScenarioChannelOptions() {
		$channels = array();
		foreach ($this->getTags() as $tag) {
			$cat = isset($tag['category']) ? trim((string)$tag['category']) : '';
			$name = isset($tag['name']) ? trim((string)$tag['name']) : '';
			if ($name === '') {
				continue;
			}
			$catLower = function_exists('mb_strtolower') ? mb_strtolower($cat, 'UTF-8') : strtolower($cat);
			if ($cat === 'Nguồn' || $cat === 'Kênh' || $catLower === 'nguon' || $catLower === 'kênh' || $catLower === 'kenh') {
				$channels[$name] = true;
			}
		}
		foreach ($this->getScenarios() as $sc) {
			if (!empty($sc['channel'])) {
				$channels[trim((string)$sc['channel'])] = true;
			}
		}
		$list = array_keys($channels);
		usort($list, function ($a, $b) {
			return strcasecmp($a, $b);
		});
		return $list;
	}

	/**
	 * Người phụ trách — user + nhóm CRM + giá trị đã dùng trong kịch bản.
	 */
	public function getScenarioAssigneeOptions() {
		global $current_user;
		$options = array();
		$seen = array();

		$userModel = Users_Record_Model::getCurrentUserModel();
		$users = $userModel->getAccessibleUsersForModule('HelpDesk');
		if (!is_array($users) || empty($users)) {
			$users = $userModel->getAccessibleUsers();
		}
		if (is_array($users)) {
			foreach ($users as $userId => $label) {
				$userId = (int)$userId;
				if ($userId <= 0) {
					continue;
				}
				$label = decode_html(trim((string)$label));
				if ($label === '' || isset($seen[$label])) {
					continue;
				}
				$seen[$label] = true;
				$options[] = array(
					'type' => 'user',
					'id' => $userId,
					'value' => $label,
					'label' => $label,
				);
			}
		}

		$groups = $userModel->getAccessibleGroups('', 'HelpDesk');
		if (!is_array($groups) || empty($groups)) {
			$groups = $userModel->getAccessibleGroups();
		}
		if (is_array($groups)) {
			foreach ($groups as $groupId => $groupName) {
				$groupId = (int)$groupId;
				$groupName = decode_html(trim((string)$groupName));
				if ($groupId <= 0 || $groupName === '') {
					continue;
				}
				$label = 'Nhóm: ' . $groupName;
				if (isset($seen[$label])) {
					continue;
				}
				$seen[$label] = true;
				$options[] = array(
					'type' => 'group',
					'id' => $groupId,
					'value' => $label,
					'label' => $label,
				);
			}
		}

		foreach ($this->getScenarios() as $sc) {
			$owner = isset($sc['owner']) ? trim((string)$sc['owner']) : '';
			if ($owner !== '' && !isset($seen[$owner])) {
				$seen[$owner] = true;
				$options[] = array(
					'type' => 'custom',
					'id' => null,
					'value' => $owner,
					'label' => $owner,
				);
			}
		}

		usort($options, function ($a, $b) {
			return strcasecmp($a['label'], $b['label']);
		});
		return $options;
	}

	public function getTags() {
		$res = $this->db->pquery(
			'SELECT id, name, category, description, group_id, scope_lead, scope_opp, scope_contact
			 FROM mk_tag_catalog ORDER BY category, name',
			array()
		);
		$rows = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rows[] = $this->hydrateTagRow($row);
			}
		}
		return $rows;
	}

	public function getTagById($id) {
		$res = $this->db->pquery(
			'SELECT id, name, category, description, group_id, scope_lead, scope_opp, scope_contact
			 FROM mk_tag_catalog WHERE id = ?',
			array($id)
		);
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		return $this->hydrateTagRow($this->db->fetchByAssoc($res));
	}

	/**
	 * Resolve mk_tag_catalog row from freetag id, slug, or display name.
	 */
	public function resolveTagInCatalog($tagName) {
		$raw = trim(decode_html((string)$tagName));
		if ($raw === '') {
			return null;
		}
		if ($raw[0] === '#') {
			$raw = substr($raw, 1);
		}

		$byId = $this->getTagById($raw);
		if ($byId) {
			return $byId;
		}

		$slug = $this->slugify($raw);
		if ($slug !== '') {
			$bySlug = $this->getTagById($slug);
			if ($bySlug) {
				return $bySlug;
			}
		}

		$res = $this->db->pquery(
			'SELECT id, name, category, description, group_id, scope_lead, scope_opp, scope_contact
			 FROM mk_tag_catalog WHERE LOWER(name) = LOWER(?) LIMIT 1',
			array($raw)
		);
		if ($res && $this->db->num_rows($res) > 0) {
			return $this->hydrateTagRow($this->db->fetchByAssoc($res));
		}
		return null;
	}

	/**
	 * Check catalog tag scope (lead / opp / contact).
	 */
	public function isTagInScope($tagName, $scope) {
		$scope = strtolower(trim((string)$scope));
		if (!in_array($scope, array('lead', 'opp', 'contact'), true)) {
			return false;
		}
		$tag = $this->resolveTagInCatalog($tagName);
		if (!$tag) {
			return false;
		}
		$key = 'scope_' . $scope;
		return !empty($tag[$key]);
	}

	/**
	 * id/slug => display label for tags in a module scope (UI).
	 */
	public function getScopeTagLabels($scope) {
		$scope = strtolower(trim((string)$scope));
		$key = 'scope_' . $scope;
		if (!in_array($scope, array('lead', 'opp', 'contact'), true)) {
			return array();
		}
		$labels = array();
		foreach ($this->getTags() as $tag) {
			if (empty($tag[$key])) {
				continue;
			}
			$labels[(string)$tag['id']] = (string)$tag['name'];
			$slug = $this->slugify($tag['name']);
			if ($slug !== '') {
				$labels[$slug] = (string)$tag['name'];
			}
		}
		return $labels;
	}

	protected function hydrateTagRow(array $row) {
		$groupId = isset($row['group_id']) && $row['group_id'] !== null && $row['group_id'] !== ''
			? (string)$row['group_id'] : null;
		$category = $row['category'] !== null ? decode_html($row['category']) : null;
		if (($category === null || $category === '') && $groupId) {
			$g = $this->getGroupById($groupId);
			if ($g) {
				$category = $g['name'];
			}
		}
		return array(
			'id' => (string)$row['id'],
			'name' => decode_html($row['name']),
			'category' => $category,
			'group_id' => $groupId,
			'description' => $row['description'] !== null ? decode_html($row['description']) : null,
			'scope_lead' => !isset($row['scope_lead']) || (int)$row['scope_lead'] ? 1 : 0,
			'scope_opp' => !isset($row['scope_opp']) || (int)$row['scope_opp'] ? 1 : 0,
			'scope_contact' => !isset($row['scope_contact']) || (int)$row['scope_contact'] ? 1 : 0,
		);
	}

	public function getGroups() {
		$res = $this->db->pquery(
			'SELECT id, name, sort_order, show_on_create FROM mk_tag_groups ORDER BY sort_order ASC, name ASC',
			array()
		);
		$rows = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rows[] = array(
					'id' => (string)$row['id'],
					'name' => decode_html($row['name']),
					'sort_order' => (int)$row['sort_order'],
					'show_on_create' => (int)$row['show_on_create'] ? 1 : 0,
				);
			}
		}
		return $rows;
	}

	public function getGroupById($id) {
		$res = $this->db->pquery(
			'SELECT id, name, sort_order, show_on_create FROM mk_tag_groups WHERE id = ?',
			array($id)
		);
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		$row = $this->db->fetchByAssoc($res);
		return array(
			'id' => (string)$row['id'],
			'name' => decode_html($row['name']),
			'sort_order' => (int)$row['sort_order'],
			'show_on_create' => (int)$row['show_on_create'] ? 1 : 0,
		);
	}

	public function upsertGroup(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$name = trim((string)(isset($payload['name']) ? $payload['name'] : ''));
		if ($name === '') {
			throw new Exception('Tên tag cha (nhóm) bắt buộc.');
		}
		if ($id === '' && $generateId) {
			$id = $this->slugify($name);
			if ($id === '') {
				$id = 'grp_' . substr(md5(uniqid('', true)), 0, 8);
			}
		}
		// Duplicate name check (new only)
		$dup = $this->db->pquery(
			'SELECT id FROM mk_tag_groups WHERE LOWER(name) = LOWER(?) AND id <> ? LIMIT 1',
			array($name, $id !== '' ? $id : '__none__')
		);
		$existsSelf = $this->db->pquery('SELECT id FROM mk_tag_groups WHERE id = ?', array($id));
		$isUpdate = $existsSelf && $this->db->num_rows($existsSelf) > 0;
		if (!$isUpdate && $dup && $this->db->num_rows($dup) > 0) {
			throw new Exception('Tag cha đã tồn tại: ' . $name);
		}
		if (!$isUpdate && $generateId) {
			$dupId = $this->db->pquery('SELECT id FROM mk_tag_groups WHERE id = ?', array($id));
			if ($dupId && $this->db->num_rows($dupId) > 0) {
				throw new Exception('Tag cha đã tồn tại (id): ' . $id);
			}
		}
		$sort = isset($payload['sort_order']) ? (int)$payload['sort_order'] : 200;
		$show = !empty($payload['show_on_create']) ? 1 : 0;
		if ($isUpdate && $this->isProtectedGroup($id)) {
			$show = 1;
		}
		if ($isUpdate) {
			$this->db->pquery(
				'UPDATE mk_tag_groups SET name = ?, sort_order = ?, show_on_create = ? WHERE id = ?',
				array($name, $sort, $show, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_tag_groups (id, name, sort_order, show_on_create) VALUES (?,?,?,?)',
				array($id, $name, $sort, $show)
			);
		}
		return $this->getGroupById($id);
	}

	public function isProtectedGroup($id) {
		return in_array(trim((string)$id), self::CORE_CREATE_GROUP_IDS, true);
	}

	public function countGroupMembers($groupId) {
		$groupId = trim((string)$groupId);
		if ($groupId === '') {
			return 0;
		}
		$rs = $this->db->pquery(
			'SELECT COUNT(*) AS cnt FROM mk_tag_group_members WHERE group_id = ?',
			array($groupId)
		);
		if (!$rs || $this->db->num_rows($rs) === 0) {
			return 0;
		}
		$row = $this->db->fetchByAssoc($rs);
		return isset($row['cnt']) ? (int)$row['cnt'] : 0;
	}

	/**
	 * Xoá tag cha tuỳ chỉnh khi không còn tag con (form Lead tự ẩn card).
	 */
	protected function maybeDeleteEmptyCustomGroup($groupId) {
		$groupId = trim((string)$groupId);
		if ($groupId === '' || $this->isProtectedGroup($groupId)) {
			return false;
		}
		$group = $this->getGroupById($groupId);
		if (!$group || empty($group['show_on_create'])) {
			return false;
		}
		if ($this->countGroupMembers($groupId) > 0) {
			return false;
		}
		return $this->deleteGroup($groupId, true);
	}

	public function deleteGroup($id, $allowProtected = false) {
		$id = trim((string)$id);
		if ($id === '') {
			return false;
		}
		if (!$allowProtected && $this->isProtectedGroup($id)) {
			throw new Exception('Không thể xoá tag cha hệ thống (Nguyên liệu, Nhượng quyền, Lớp học).');
		}
		$this->db->pquery('DELETE FROM mk_tag_group_members WHERE group_id = ?', array($id));
		$this->db->pquery('UPDATE mk_tag_catalog SET group_id = NULL WHERE group_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_groups WHERE id = ?', array($id));
		return true;
	}

	public function addTagToGroup($groupId, $tagId, $sortOrder = 0) {
		$groupId = trim((string)$groupId);
		$tagId = trim((string)$tagId);
		if ($groupId === '' || $tagId === '') {
			return false;
		}
		$this->db->pquery(
			'INSERT INTO mk_tag_group_members (group_id, tag_id, sort_order) VALUES (?,?,?)
			 ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)',
			array($groupId, $tagId, (int)$sortOrder)
		);
		return true;
	}

	/**
	 * Groups marked show_on_create + children for Lead create form.
	 */
	public function getCreateTagGroups() {
		$groups = array();
		$leadIntentAllow = array(
			'dang_tu_van',
			'mua_lan_dau',
			'dung_cham_soc',
			'kh_can_nhac',
			'mua_lai',
			'mua_it_lai',
			'ngung_mua',
		);
		foreach ($this->getGroups() as $g) {
			if (empty($g['show_on_create'])) {
				continue;
			}
			$children = array();
			$rs = $this->db->pquery(
				'SELECT t.id, t.name, m.sort_order
				 FROM mk_tag_group_members m
				 INNER JOIN mk_tag_catalog t ON t.id = m.tag_id
				 WHERE m.group_id = ?
				 ORDER BY m.sort_order ASC, t.name ASC',
				array($g['id'])
			);
			if ($rs) {
				while ($row = $this->db->fetchByAssoc($rs)) {
					$children[] = array(
						'id' => (string)$row['id'],
						'name' => decode_html($row['name']),
					);
				}
			}
			if ($g['id'] === 'nguyen_lieu') {
				$byId = array();
				foreach ($children as $c) {
					$byId[$c['id']] = $c;
				}
				$children = array();
				foreach ($leadIntentAllow as $tid) {
					if (isset($byId[$tid])) {
						$children[] = $byId[$tid];
					}
				}
			}
			if (empty($children)) {
				continue;
			}
			$groups[] = array(
				'id' => $g['id'],
				'name' => $g['name'],
				'sort_order' => $g['sort_order'],
				'children' => $children,
			);
		}
		return $groups;
	}

	public function upsertTag(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$name = trim((string)(isset($payload['name']) ? $payload['name'] : ''));
		if ($name === '') {
			throw new Exception('Tag name required');
		}
		if ($id === '' && $generateId) {
			$id = $this->slugify($name);
			if ($id === '') {
				$id = 'tag_' . substr(md5(uniqid('', true)), 0, 8);
			}
		}
		$exists = $this->db->pquery('SELECT id FROM mk_tag_catalog WHERE id = ?', array($id));
		$isUpdate = $exists && $this->db->num_rows($exists) > 0;
		if (!$isUpdate && $generateId) {
			// Block duplicate by id or by name (case-insensitive)
			$dupName = $this->db->pquery(
				'SELECT id, name FROM mk_tag_catalog WHERE LOWER(name) = LOWER(?) LIMIT 1',
				array($name)
			);
			if ($dupName && $this->db->num_rows($dupName) > 0) {
				throw new Exception('Tag đã tồn tại: ' . decode_html($this->db->query_result($dupName, 0, 'name')));
			}
			$dupId = $this->db->pquery('SELECT id FROM mk_tag_catalog WHERE id = ?', array($id));
			if ($dupId && $this->db->num_rows($dupId) > 0) {
				throw new Exception('Tag đã tồn tại (id): ' . $id);
			}
		}

		$groupId = null;
		if (isset($payload['group_id']) && trim((string)$payload['group_id']) !== '') {
			$groupId = trim((string)$payload['group_id']);
		}
		$category = isset($payload['category']) && $payload['category'] !== '' ? (string)$payload['category'] : null;
		if ($groupId) {
			$g = $this->getGroupById($groupId);
			if ($g) {
				$category = $g['name'];
			} elseif ($category === null || $category === '') {
				// Auto-create group if user typed new parent name into category + show_on_create flag
				if (!empty($payload['create_group']) && $category) {
					$g = $this->upsertGroup(array(
						'name' => $category,
						'show_on_create' => !empty($payload['show_on_create']) ? 1 : 0,
						'sort_order' => 200,
					), true);
					$groupId = $g['id'];
					$category = $g['name'];
				}
			}
		} elseif ($category) {
			// Resolve or create group from category name
			$found = null;
			foreach ($this->getGroups() as $g) {
				if (strcasecmp($g['name'], $category) === 0) {
					$found = $g;
					break;
				}
			}
			if ($found) {
				$groupId = $found['id'];
			} elseif (!empty($payload['create_group']) || !empty($payload['new_group'])) {
				$g = $this->upsertGroup(array(
					'name' => $category,
					'show_on_create' => !empty($payload['show_on_create']) ? 1 : 0,
					'sort_order' => 200,
				), true);
				$groupId = $g['id'];
			}
		}

		$description = isset($payload['description']) && $payload['description'] !== '' ? (string)$payload['description'] : null;
		$scopeLead = array_key_exists('scope_lead', $payload) ? (!empty($payload['scope_lead']) ? 1 : 0) : 1;
		$scopeOpp = array_key_exists('scope_opp', $payload) ? (!empty($payload['scope_opp']) ? 1 : 0) : 1;
		$scopeContact = array_key_exists('scope_contact', $payload) ? (!empty($payload['scope_contact']) ? 1 : 0) : 1;

		if ($isUpdate) {
			$this->db->pquery(
				'UPDATE mk_tag_catalog SET name = ?, category = ?, description = ?, group_id = ?,
				 scope_lead = ?, scope_opp = ?, scope_contact = ? WHERE id = ?',
				array($name, $category, $description, $groupId, $scopeLead, $scopeOpp, $scopeContact, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_tag_catalog (id, name, category, description, group_id, scope_lead, scope_opp, scope_contact)
				 VALUES (?,?,?,?,?,?,?,?)',
				array($id, $name, $category, $description, $groupId, $scopeLead, $scopeOpp, $scopeContact)
			);
		}
		if ($groupId) {
			$this->addTagToGroup($groupId, $id, isset($payload['sort_order']) ? (int)$payload['sort_order'] : 0);
		}
		return $this->getTagById($id);
	}

	public function deleteTag($id) {
		$tag = $this->getTagById($id);
		$groupId = ($tag && !empty($tag['group_id'])) ? (string)$tag['group_id'] : null;

		$this->db->pquery('DELETE FROM mk_tag_rule_conditions WHERE tag_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_group_members WHERE tag_id = ?', array($id));
		$this->db->pquery('DELETE FROM mk_tag_catalog WHERE id = ?', array($id));

		if ($groupId) {
			$this->maybeDeleteEmptyCustomGroup($groupId);
		}
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

	/** ——— Affiliate reward tiers (Prefix → Hạng → Tiền thưởng) ——— */

	public function seedAffiliateTiersIfEmpty($force = false) {
		$res = $this->db->pquery('SELECT COUNT(*) AS c FROM mk_affiliate_reward_tiers', array());
		$count = ($res && $this->db->num_rows($res) > 0) ? (int)$this->db->query_result($res, 0, 'c') : 0;
		if (!$force && $count > 0) {
			return false;
		}
		if ($force) {
			$this->db->query('DELETE FROM mk_affiliate_reward_tiers');
		}
		$today = date('Y-m-d');
		foreach ($this->seedAffiliateTiers() as $tier) {
			$tier['effective_from'] = isset($tier['effective_from']) ? $tier['effective_from'] : $today;
			$this->upsertAffiliateTier($tier, false);
		}
		return true;
	}

	protected function seedAffiliateTiers() {
		return array(
			array('id' => 'aff-tier-a', 'prefix' => 'A', 'tier_name' => 'Diamond', 'reward_amount' => 30000000, 'retention_days' => 180, 'status' => 'active'),
			array('id' => 'aff-tier-b', 'prefix' => 'B', 'tier_name' => 'Gold', 'reward_amount' => 20000000, 'retention_days' => 180, 'status' => 'active'),
			array('id' => 'aff-tier-c', 'prefix' => 'C', 'tier_name' => 'Silver', 'reward_amount' => 10000000, 'retention_days' => 180, 'status' => 'active'),
			array('id' => 'aff-tier-d', 'prefix' => 'D', 'tier_name' => 'Standard', 'reward_amount' => 5000000, 'retention_days' => 180, 'status' => 'active'),
		);
	}

	protected function mapAffiliateTierRow(array $row) {
		return array(
			'id' => (string)$row['id'],
			'prefix' => strtoupper((string)$row['prefix']),
			'tier_name' => decode_html($row['tier_name']),
			'reward_amount' => (float)$row['reward_amount'],
			'retention_days' => (int)$row['retention_days'],
			'effective_from' => (string)$row['effective_from'],
			'status' => ((string)$row['status'] === 'active') ? 'active' : 'inactive',
			'is_active' => ((string)$row['status'] === 'active'),
		);
	}

	public function getAffiliateTiers() {
		$res = $this->db->pquery(
			'SELECT id, prefix, tier_name, reward_amount, retention_days, effective_from, status
			 FROM mk_affiliate_reward_tiers
			 ORDER BY prefix ASC',
			array()
		);
		$rows = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$rows[] = $this->mapAffiliateTierRow($row);
			}
		}
		return $rows;
	}

	public function getAffiliateTierById($id) {
		if (!$id) {
			return null;
		}
		$res = $this->db->pquery(
			'SELECT id, prefix, tier_name, reward_amount, retention_days, effective_from, status
			 FROM mk_affiliate_reward_tiers WHERE id = ?',
			array($id)
		);
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		return $this->mapAffiliateTierRow($this->db->fetchByAssoc($res));
	}

	public function upsertAffiliateTier(array $payload, $generateId = true) {
		$id = isset($payload['id']) ? trim((string)$payload['id']) : '';
		$prefix = isset($payload['prefix']) ? strtoupper(trim((string)$payload['prefix'])) : '';
		$tierName = isset($payload['tier_name']) ? trim((string)$payload['tier_name']) : '';
		if ($prefix === '' || !preg_match('/^[A-Z]$/', $prefix)) {
			throw new Exception('Prefix phải là 1 chữ cái A–Z.');
		}
		if ($tierName === '') {
			throw new Exception('Tên hạng là bắt buộc.');
		}
		$reward = isset($payload['reward_amount']) ? (float)$payload['reward_amount'] : 0;
		if ($reward < 0) {
			throw new Exception('Tiền thưởng không hợp lệ.');
		}
		$retention = isset($payload['retention_days']) ? (int)$payload['retention_days'] : 180;
		if ($retention <= 0) {
			$retention = 180;
		}
		$effectiveFrom = isset($payload['effective_from']) ? trim((string)$payload['effective_from']) : '';
		if ($effectiveFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
			$effectiveFrom = date('Y-m-d');
		}
		$status = 'active';
		if (isset($payload['status'])) {
			$status = (strtolower((string)$payload['status']) === 'inactive') ? 'inactive' : 'active';
		} elseif (isset($payload['is_active'])) {
			$active = $payload['is_active'];
			$status = ($active === true || $active === 1 || $active === '1' || $active === 'true') ? 'active' : 'inactive';
		}
		if ($id === '' && $generateId) {
			$id = 'aff-tier-' . strtolower($prefix);
		}
		if ($id === '') {
			$id = 'aff-tier-' . substr(md5(uniqid('', true)), 0, 8);
		}

		$dup = $this->db->pquery(
			'SELECT id FROM mk_affiliate_reward_tiers WHERE prefix = ? AND id <> ?',
			array($prefix, $id)
		);
		if ($dup && $this->db->num_rows($dup) > 0) {
			throw new Exception('Prefix "' . $prefix . '" đã tồn tại.');
		}

		$exists = $this->db->pquery('SELECT id FROM mk_affiliate_reward_tiers WHERE id = ?', array($id));
		if ($exists && $this->db->num_rows($exists) > 0) {
			$this->db->pquery(
				'UPDATE mk_affiliate_reward_tiers
				 SET prefix=?, tier_name=?, reward_amount=?, retention_days=?, effective_from=?, status=?
				 WHERE id=?',
				array($prefix, $tierName, $reward, $retention, $effectiveFrom, $status, $id)
			);
		} else {
			$this->db->pquery(
				'INSERT INTO mk_affiliate_reward_tiers
				 (id, prefix, tier_name, reward_amount, retention_days, effective_from, status)
				 VALUES (?,?,?,?,?,?,?)',
				array($id, $prefix, $tierName, $reward, $retention, $effectiveFrom, $status)
			);
		}
		return $this->getAffiliateTierById($id);
	}

	public function setAffiliateTierActive($id, $active) {
		$tier = $this->getAffiliateTierById($id);
		if (!$tier) {
			throw new Exception('Không tìm thấy mã giới thiệu.');
		}
		$status = $active ? 'active' : 'inactive';
		$this->db->pquery(
			'UPDATE mk_affiliate_reward_tiers SET status = ? WHERE id = ?',
			array($status, $id)
		);
		return $this->getAffiliateTierById($id);
	}

	public function deleteAffiliateTier($id) {
		$this->db->pquery('DELETE FROM mk_affiliate_reward_tiers WHERE id = ?', array($id));
		return true;
	}

	/** ——— Google Sheet scoring config (Q1/Q2/Q3/Region) ——— */

	public function getDefaultSheetScoringConfig() {
		return array(
			'q1' => array('A' => 20, 'B' => 15, 'C' => 10, 'D' => 0),
			'q2' => array('A' => 5, 'B' => 10, 'C' => 15, 'D' => 20, 'E' => 20, 'F' => 15, 'G' => 0),
			'q3' => array('A' => 0, 'B' => 10, 'C' => 20, 'D' => 30, 'E' => 40),
			'region' => array('Khu vực 1' => 5, 'Khu vực 2' => 3, 'Khu vực 3' => 0),
			'threshold' => array('khong_dat_max' => 19, 'xac_minh_max' => 34, 'du_dk_min' => 35),
			'note' => 'Cấu hình điểm tham chiếu cho sàng lọc Google Sheet (Bộ A).',
		);
	}

	public function getSheetScoringConfig() {
		$default = $this->getDefaultSheetScoringConfig();
		$raw = $this->getMeta('sheet_scoring_json');
		if ($raw === null || trim((string)$raw) === '') {
			return $default;
		}
		$cfg = json_decode((string)$raw, true);
		if (!is_array($cfg)) {
			return $default;
		}
		return $this->mergeSheetScoringConfig($default, $cfg);
	}

	protected function mergeSheetScoringConfig(array $default, array $given) {
		$out = $default;
		foreach (array('q1', 'q2', 'q3') as $k) {
			if (isset($given[$k]) && is_array($given[$k])) {
				foreach ($default[$k] as $opt => $val) {
					if (array_key_exists($opt, $given[$k])) {
						$out[$k][$opt] = (int)$given[$k][$opt];
					}
				}
			}
		}
		if (isset($given['region']) && is_array($given['region'])) {
			$regionOut = array();
			foreach ($given['region'] as $name => $score) {
				$name = trim((string)$name);
				if ($name === '') {
					continue;
				}
				$regionOut[$name] = (int)$score;
			}
			if (!empty($regionOut)) {
				$out['region'] = $regionOut;
			}
		}
		if (isset($given['threshold']) && is_array($given['threshold'])) {
			$t = $out['threshold'];
			foreach (array('khong_dat_max', 'xac_minh_max', 'du_dk_min') as $key) {
				if (array_key_exists($key, $given['threshold'])) {
					$t[$key] = (int)$given['threshold'][$key];
				}
			}
			$out['threshold'] = $t;
		}
		if (isset($given['note'])) {
			$out['note'] = trim((string)$given['note']);
		}
		return $out;
	}

	public function saveSheetScoringConfig(array $payload) {
		$cfg = $this->mergeSheetScoringConfig($this->getDefaultSheetScoringConfig(), $payload);
		$this->setMeta('sheet_scoring_json', json_encode($cfg, JSON_UNESCAPED_UNICODE));
		return $cfg;
	}

	public function resetSheetScoringConfig() {
		$cfg = $this->getDefaultSheetScoringConfig();
		$this->setMeta('sheet_scoring_json', json_encode($cfg, JSON_UNESCAPED_UNICODE));
		return $cfg;
	}

	/**
	 * Resolve referral code → active tier.
	 * - A0906345551 (new): TierLetter + 10-digit phone (identity = referrer customer by phone+tier)
	 * - AFF-###### (legacy): identity = referrer customer by affiliate_code stored in bace_sc_profile
	 * - A000123 (legacy): tier-only (no referrer identity)
	 *
	 * @param string $code
	 * @param string|null $asOfDate Y-m-d
	 * @return array|null
	 */
	public function resolveAffiliateReward($code, $asOfDate = null) {
		$code = strtoupper(trim((string)$code));
		if ($code === '') {
			return null;
		}
		$asOf = $asOfDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate) ? $asOfDate : date('Y-m-d');
		$prefix = '';
		$referrerMeta = null;

		// New format: [A-D][10-digit-phone]
		if (preg_match('/^([A-D])(\d{10})$/', $code, $m)) {
			$prefix = strtoupper((string) $m[1]);
			$referrerMeta = $this->lookupScAffiliateMeta($code);
			if (!$referrerMeta) {
				return null;
			}
		} elseif (preg_match('/^AFF-\d+$/', $code)) {
			$referrerMeta = $this->lookupScAffiliateMeta($code);
			if (!$referrerMeta || empty($referrerMeta['affiliate_tier_prefix'])) {
				return null;
			}
			$prefix = strtoupper((string) $referrerMeta['affiliate_tier_prefix']);
		} elseif (preg_match('/^([A-D])\d+$/', $code, $m)) {
			// Legacy tier-only
			$prefix = strtoupper((string) $m[1]);
		} else {
			return null;
		}

		if (!preg_match('/^[A-D]$/', $prefix)) {
			return null;
		}

		$res = $this->db->pquery(
			'SELECT id, prefix, tier_name, reward_amount, retention_days, effective_from, status
			 FROM mk_affiliate_reward_tiers
			 WHERE prefix = ? AND status = ? AND effective_from <= ?
			 ORDER BY effective_from DESC
			 LIMIT 1',
			array($prefix, 'active', $asOf)
		);
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		$tier = $this->mapAffiliateTierRow($this->db->fetchByAssoc($res));
		$tier['referral_code'] = $code;
		$tier['resolved_prefix'] = $prefix;
		if ($referrerMeta) {
			$tier['referrer_id'] = isset($referrerMeta['id']) ? (int) $referrerMeta['id'] : 0;
			$tier['referrer_name'] = isset($referrerMeta['full_name']) ? $referrerMeta['full_name'] : '';
			$tier['referrer_phone'] = isset($referrerMeta['phone']) ? $referrerMeta['phone'] : '';
		}
		return $tier;
	}

	/**
	 * Lookup SC profile by:
	 * - new: [A-D][10-digit-phone]
	 * - legacy: AFF-###### (identity = affiliate_code stored in bace_sc_profile)
	 * @return array|null
	 */
	protected function lookupScAffiliateMeta($affiliateCode) {
		$code = strtoupper(trim((string) $affiliateCode));
		if ($code === '' || !preg_match('/^AFF-\d+$/', $code)) {
			// New format: [A-D][10-digit-phone]
			if (preg_match('/^([A-D])(\d{10})$/', $code, $m)) {
				$prefix = strtoupper((string) $m[1]);
				$phoneDigits = (string) $m[2];
			} else {
				return null;
			}
		} else {
			$prefix = null;
			$phoneDigits = null;
		}
		try {
			require_once 'modules/ServiceContracts/models/ModernService.php';
			ServiceContracts_ModernService::installSchema($this->db);
		} catch (Exception $e) {
			// continue — SHOW COLUMNS may still fail if module missing
		}
		if ($prefix !== null) {
			$res = $this->db->pquery(
				"SELECT p.servicecontractsid, p.affiliate_code, p.affiliate_tier_prefix, p.phone, sc.subject
				 FROM bace_sc_profile p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.servicecontractsid AND ce.deleted = 0
				 INNER JOIN vtiger_servicecontracts sc ON sc.servicecontractsid = p.servicecontractsid
				 WHERE p.affiliate_tier_prefix = ?
				   AND p.phone = ?
				 LIMIT 1",
				array($prefix, $phoneDigits)
			);
		} else {
			$res = $this->db->pquery(
				"SELECT p.servicecontractsid, p.affiliate_code, p.affiliate_tier_prefix, p.phone, sc.subject
				 FROM bace_sc_profile p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.servicecontractsid AND ce.deleted = 0
				 INNER JOIN vtiger_servicecontracts sc ON sc.servicecontractsid = p.servicecontractsid
				 WHERE UPPER(p.affiliate_code) = ?
				 LIMIT 1",
				array($code)
			);
		}
		if (!$res || $this->db->num_rows($res) === 0) {
			return null;
		}
		$row = $this->db->fetchByAssoc($res);
		$prefix = isset($row['affiliate_tier_prefix']) ? strtoupper(trim((string) $row['affiliate_tier_prefix'])) : 'D';
		if ($prefix === '' || !preg_match('/^[A-D]$/', $prefix)) {
			$prefix = 'D';
		}
		return array(
			'id' => (int) $row['servicecontractsid'],
			'affiliate_code' => strtoupper(trim((string) $row['affiliate_code'] ?: $affiliateCode)),
			'affiliate_tier_prefix' => $prefix,
			'full_name' => decode_html(isset($row['subject']) ? $row['subject'] : ''),
			'phone' => decode_html(isset($row['phone']) ? $row['phone'] : ''),
		);
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
	 * Kịch bản tiếp theo + khung thời gian (alert_days) từ Tag Rule khớp thẻ.
	 * Dùng chung Leads / Potentials.
	 *
	 * @param array $tags
	 * @param string|null $lastTouchRaw
	 * @param string $manualNextAction
	 * @return array
	 */
	public function resolveNextActionMeta(array $tags, $lastTouchRaw = null, $manualNextAction = '') {
		$meta = array(
			'next_action' => trim((string)$manualNextAction),
			'rule_id' => null,
			'rule_name' => null,
			'rule_alert_days' => null,
			'next_action_due_at' => null,
			'next_action_overdue' => false,
			'next_action_days_remaining' => null,
			'next_action_days_overdue' => null,
			'timeframe_label' => '',
		);
		$match = $this->matchRules($tags, true);
		$best = !empty($match['best']) ? $match['best'] : null;
		if (!$best) {
			return $meta;
		}
		$meta['rule_id'] = isset($best['id']) ? (string)$best['id'] : null;
		$meta['rule_name'] = isset($best['name']) ? (string)$best['name'] : null;
		if ($meta['next_action'] === '' && !empty($best['next_action'])) {
			$meta['next_action'] = (string)$best['next_action'];
		}
		if ($best['alert_days'] === null || (int)$best['alert_days'] <= 0) {
			return $meta;
		}
		$alertDays = (int)$best['alert_days'];
		$meta['rule_alert_days'] = $alertDays;
		$lastTs = $lastTouchRaw ? strtotime((string)$lastTouchRaw) : false;
		if (!$lastTs) {
			$meta['timeframe_label'] = 'Trong ' . $alertDays . ' ngày';
			return $meta;
		}
		$meta['next_action_due_at'] = date('c', strtotime('+' . $alertDays . ' days', $lastTs));
		$daysIdle = max(0, (int)floor((time() - $lastTs) / 86400));
		$remaining = $alertDays - $daysIdle;
		if ($remaining < 0) {
			$meta['next_action_overdue'] = true;
			$meta['next_action_days_overdue'] = -$remaining;
			$meta['timeframe_label'] = 'Quá hạn ' . (-$remaining) . ' ngày';
		} elseif ($remaining === 0) {
			$meta['next_action_days_remaining'] = 0;
			$meta['timeframe_label'] = 'Hôm nay';
		} else {
			$meta['next_action_days_remaining'] = $remaining;
			$meta['timeframe_label'] = 'Còn ' . $remaining . ' ngày';
		}
		return $meta;
	}

	public function getRecordTagLabels($moduleName, $recordId) {
		$recordId = (int)$recordId;
		$moduleName = (string)$moduleName;
		if ($recordId <= 0 || $moduleName === '') {
			return array();
		}
		$res = $this->db->pquery(
			"SELECT t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id = ?
			 ORDER BY fo.tagged_on ASC",
			array($moduleName, $recordId)
		);
		$tags = array();
		if ($res) {
			while ($row = $this->db->fetchByAssoc($res)) {
				$tags[] = decode_html($row['tag']);
			}
		}
		return $tags;
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

	/** Cảnh báo từ Lead: rule quá hạn + Cần CSKH (idle ≥ ngưỡng). */
	public function getCskhAlertDays() {
		$v = $this->getMeta('cskh_alert_days');
		if ($v !== null && $v !== '' && (int)$v > 0) {
			return (int)$v;
		}
		return self::CSKH_ALERT_DAYS_DEFAULT;
	}

	protected function getCskhRuleDefinition() {
		$days = $this->getCskhAlertDays();
		return array(
			'id' => self::CSKH_RULE_ID,
			'status_label' => 'Cần chăm sóc',
			'name' => 'Cần CSKH',
			'priority' => 5,
			'is_active' => true,
			'alert_days' => $days,
			'next_action' => 'Gọi / nhắn Zalo liên hệ lại khách — không tương tác ≥ ' . $days . ' ngày',
			'require_note' => false,
			'scenario_id' => null,
			'tag_ids' => array(),
			'alert_type' => 'cskh',
		);
	}

	protected function leadExcludedFromCskh(array $slugSet) {
		$blocked = array(
			'ngung_cham_soc',
			'dung_cham_soc',
			'khong_xac_nhan_tham_gia',
		);
		foreach ($blocked as $slug) {
			if (isset($slugSet[$slug])) {
				return true;
			}
		}
		return false;
	}

	protected function buildAlertRow($lid, array $lead, array $labels, array $slugs, array $rule, $days) {
		$firstname = decode_html(isset($lead['firstname']) ? $lead['firstname'] : '');
		$lastname = decode_html(isset($lead['lastname']) ? $lead['lastname'] : '');
		// CRM often stores full VN name as lastname + firstname
		$name = trim($lastname . ' ' . $firstname);
		if ($name === '') {
			$name = decode_html(isset($lead['company']) ? $lead['company'] : '') ?: ('Lead #' . $lid);
		}
		$phone = '';
		if (!empty($lead['phone'])) {
			$phone = decode_html($lead['phone']);
		} elseif (!empty($lead['mobile'])) {
			$phone = decode_html($lead['mobile']);
		}
		$row = array(
			'lead_id' => (int)$lid,
			'name' => $name,
			'phone' => $phone,
			'tags' => $labels,
			'tag_slugs' => $slugs,
			'rule' => $rule,
			'days_idle' => (int)$days,
			'next_action' => isset($rule['next_action']) ? $rule['next_action'] : '',
			'detail_url' => 'index.php?module=Leads&view=Detail&record=' . (int)$lid . '&app=SALES',
		);
		if (!empty($rule['alert_type'])) {
			$row['alert_type'] = $rule['alert_type'];
		}
		return $row;
	}

	public function getAlerts($userId, $limit = 100) {
		$userId = (int)$userId;
		$limit = max(1, (int)$limit);
		$activeRules = $this->getRules(true);
		$alertRules = array();
		foreach ($activeRules as $rule) {
			if ($rule['alert_days'] !== null && (int)$rule['alert_days'] > 0 && !empty($rule['tag_ids'])) {
				$alertRules[] = $rule;
			}
		}
		$cskhDays = $this->getCskhAlertDays();
		$cskhRule = $this->getCskhRuleDefinition();

		$dismissMap = array();
		$dres = $this->db->pquery(
			'SELECT lead_id, rule_id, snooze_until
			 FROM mk_tag_rule_dismissals
			 WHERE user_id = ?
			   AND (
			     snooze_until IS NULL OR snooze_until = \'\'
			     OR snooze_until > NOW()
			   )',
			array($userId)
		);
		if ($dres) {
			while ($drow = $this->db->fetchByAssoc($dres)) {
				// "Done" (snooze_until NULL) = hide forever; future date = snooze until then.
				// Past dates already filtered by SQL (only NULL or future).
				$key = (int)$drow['lead_id'] . ':' . $drow['rule_id'];
				$dismissMap[$key] = true;
			}
		}

		// Phone lives on vtiger_leadaddress (not leaddetails).
		// Avoid NULLIF(... '0000-00-00') — MySQL 8 strict mode rejects zero-dates.
		$leadRes = $this->db->pquery(
			"SELECT ld.leadid, ld.firstname, ld.lastname, ld.company,
			        la.phone AS phone, la.mobile AS mobile,
			        DATEDIFF(
			          NOW(),
			          COALESCE(p.last_touch, ce.modifiedtime, ce.createdtime)
			        ) AS days_idle
			 FROM vtiger_leaddetails ld
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0 AND ce.setype = 'Leads'
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = ld.leadid
			 LEFT JOIN bace_lead_profile p ON p.leadid = ld.leadid
			 ORDER BY days_idle DESC, ce.modifiedtime DESC
			 LIMIT 500",
			array()
		);
		$leads = array();
		$leadIds = array();
		if ($leadRes) {
			while ($row = $this->db->fetchByAssoc($leadRes)) {
				$lid = (int)$row['leadid'];
				if ($lid <= 0) {
					continue;
				}
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
		$leadHasRuleAlert = array();

		foreach ($leads as $lid => $lead) {
			$labels = isset($tagMap[$lid]) ? $tagMap[$lid] : array();
			$slugs = $this->normalizeTagList($labels);
			$slugSet = array_fill_keys($slugs, true);
			$days = (int)$lead['days_idle'];
			if ($days < 0) {
				$days = 0;
			}
			if (empty($alertRules) || empty($labels)) {
				continue;
			}
			foreach ($alertRules as $rule) {
				$ok = true;
				foreach ($rule['tag_ids'] as $tid) {
					if (!isset($slugSet[$tid])) {
						$ok = false;
						break;
					}
				}
				if (!$ok || $days < (int)$rule['alert_days']) {
					continue;
				}
				$dkey = $lid . ':' . $rule['id'];
				if (isset($dismissMap[$dkey])) {
					continue;
				}
				$alerts[] = $this->buildAlertRow($lid, $lead, $labels, $slugs, $rule, $days);
				$leadHasRuleAlert[$lid] = true;
				if (count($alerts) >= $limit) {
					return $alerts;
				}
			}
		}

		foreach ($leads as $lid => $lead) {
			if (isset($leadHasRuleAlert[$lid])) {
				continue;
			}
			$labels = isset($tagMap[$lid]) ? $tagMap[$lid] : array();
			$slugs = $this->normalizeTagList($labels);
			$slugSet = array_fill_keys($slugs, true);
			if ($this->leadExcludedFromCskh($slugSet)) {
				continue;
			}
			$days = (int)$lead['days_idle'];
			if ($days < 0) {
				$days = 0;
			}
			if ($days < $cskhDays) {
				continue;
			}
			$dkey = $lid . ':' . self::CSKH_RULE_ID;
			if (isset($dismissMap[$dkey])) {
				continue;
			}
			$alerts[] = $this->buildAlertRow($lid, $lead, $labels, $slugs, $cskhRule, $days);
			if (count($alerts) >= $limit) {
				break;
			}
		}

		usort($alerts, function ($a, $b) {
			$da = (int)$a['days_idle'];
			$db = (int)$b['days_idle'];
			if ($da !== $db) {
				return $db - $da;
			}
			return (int)$a['lead_id'] - (int)$b['lead_id'];
		});

		return array_slice($alerts, 0, $limit);
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
			array('id' => 'individual', 'name' => 'Cá nhân', 'category' => 'Dạng KH'),
			array('id' => 'company', 'name' => 'Công ty', 'category' => 'Dạng KH'),
			array('id' => 'tiem_nang', 'name' => 'Tiềm năng', 'category' => 'Nguyên liệu'),
			array('id' => 'co_quan', 'name' => 'CÓ QUÁN', 'category' => 'Dạng KH'),
			array('id' => 'chuan_bi_mo', 'name' => 'CHUẨN BỊ MỞ', 'category' => 'Dạng KH'),
			array('id' => 'gia_dinh', 'name' => 'GIA ĐÌNH', 'category' => 'Dạng KH'),
			array('id' => 'chua_hoc', 'name' => 'Chưa học', 'category' => 'Lớp học'),
			array('id' => 'da_hoc', 'name' => 'Đã học', 'category' => 'Lớp học'),
			array('id' => 'mien_phi_online', 'name' => 'Miễn phí Online', 'category' => 'Lớp học'),
			array('id' => 'mien_phi_offline', 'name' => 'Miễn phí Offline', 'category' => 'Lớp học'),
			array('id' => 'da_tg_free', 'name' => 'Đã TG FREE', 'category' => 'Lớp học'),
			array('id' => 'thu_3', 'name' => 'THỨ 3', 'category' => 'Lớp học'),
			array('id' => 'pcth', 'name' => 'PCTH', 'category' => 'Lớp học'),
			array('id' => 'chua_PCTH', 'name' => 'Chưa PCTH', 'category' => 'Lớp học'),
			array('id' => 'da_PCTH', 'name' => 'Đã PCTH', 'category' => 'Lớp học'),
			array('id' => 'van_hanh', 'name' => 'Vận hành', 'category' => 'Lớp học'),
			array('id' => 'mkt', 'name' => 'Marketing', 'category' => 'Lớp học'),
			array('id' => 'lop_khac', 'name' => 'Lớp khác', 'category' => 'Lớp học'),
			array('id' => 'nhuong_quyen', 'name' => 'Nhượng quyền', 'category' => 'Nhượng quyền'),
			array('id' => 'khong_nghe_may', 'name' => 'Không nghe máy', 'category' => 'Nhượng quyền'),
			array('id' => 'thue_bao', 'name' => 'Thuê bao', 'category' => 'Nhượng quyền'),
			array('id' => 'trung_so', 'name' => 'Trùng số / Sai số', 'category' => 'Lớp học'),
			array('id' => 'doi_lich', 'name' => 'Dời lịch', 'category' => 'Lớp học'),
			array('id' => 'xac_nhan_tham_gia', 'name' => 'Xác nhận tham gia', 'category' => 'Xác nhận'),
			array('id' => 'khong_xac_nhan_tham_gia', 'name' => 'Không tham gia', 'category' => 'Xác nhận'),
			array('id' => 'moi_lai', 'name' => 'Mời lại', 'category' => 'Lớp học'),
			array('id' => 'ngung_cham_soc', 'name' => 'Ngừng chăm sóc', 'category' => 'Lớp học'),
			array('id' => 'goi_lan_1', 'name' => 'Gọi lần 1', 'category' => 'Liên hệ'),
			array('id' => 'goi_lan_2', 'name' => 'Gọi lần 2', 'category' => 'Liên hệ'),
			array('id' => 'goi_lan_3', 'name' => 'Gọi lần 3', 'category' => 'Liên hệ'),
			array('id' => 'mua_lan_dau', 'name' => 'Mua lần đầu', 'category' => 'Nguyên liệu'),
			array('id' => 'mua_lai', 'name' => 'Mua lại', 'category' => 'Nguyên liệu'),
			array('id' => 'mua_on_dinh', 'name' => 'Mua ổn định', 'category' => 'Nguyên liệu'),
			array('id' => 'khong_mua', 'name' => 'Không mua', 'category' => 'Nguyên liệu'),
			array('id' => 'ngung_mua', 'name' => 'Ngưng mua', 'category' => 'Nguyên liệu'),
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
