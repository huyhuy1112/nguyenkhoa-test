<?php
/*+***********************************************************************************
 * Lead × product-group pipeline (Model A). Stage lives on bace_lead_products.
 *************************************************************************************/

class Leads_LeadProductsService {

	const TABLE = 'bace_lead_products';
	const LOG_TABLE = 'bace_lead_product_stage_log';

	public static function groups() {
		return array(
			'online' => array(
				'code' => 'online',
				'label' => 'Online',
				'stages' => array('moi', 'dang_tu_van', 'da_bao_gia', 'da_chot', 'khong_mua'),
			),
			'offline' => array(
				'code' => 'offline',
				'label' => 'Offline',
				'stages' => array('moi', 'dang_tu_van', 'da_bao_gia', 'da_hen_lop', 'da_chot', 'khong_mua'),
			),
			'nvl' => array(
				'code' => 'nvl',
				'label' => 'NVL',
				'stages' => array('moi', 'dang_tu_van', 'da_bao_gia', 'da_chot', 'da_giao', 'khong_mua'),
			),
			'franchise' => array(
				'code' => 'franchise',
				'label' => 'Nhượng quyền',
				'stages' => array('moi', 'dang_tu_van', 'da_bao_gia', 'da_chot', 'khong_mua'),
			),
		);
	}

	public static function stageLabels() {
		return array(
			'moi' => 'Mới',
			'dang_tu_van' => 'Đang tư vấn',
			'da_bao_gia' => 'Đã báo giá',
			'da_hen_lop' => 'Đã hẹn lớp',
			'da_chot' => 'Đã chốt',
			'da_giao' => 'Đã giao',
			'khong_mua' => 'Không mua',
		);
	}

	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE . " (
				id INT(11) NOT NULL AUTO_INCREMENT,
				leadid INT(19) NOT NULL,
				group_code VARCHAR(32) NOT NULL,
				product_name VARCHAR(255) DEFAULT NULL,
				stage_code VARCHAR(32) NOT NULL DEFAULT 'moi',
				entered_stage_at DATETIME DEFAULT NULL,
				potential_id INT(19) DEFAULT NULL,
				pipeline_closed TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME DEFAULT NULL,
				modified_at DATETIME DEFAULT NULL,
				created_by INT(19) DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_lead_group (leadid, group_code),
				KEY idx_lead (leadid),
				KEY idx_group_stage (group_code, stage_code)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::LOG_TABLE . " (
				id INT(11) NOT NULL AUTO_INCREMENT,
				product_id INT(11) NOT NULL,
				leadid INT(19) NOT NULL,
				from_stage VARCHAR(32) DEFAULT NULL,
				to_stage VARCHAR(32) NOT NULL,
				changed_by INT(19) DEFAULT NULL,
				changed_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				KEY idx_product (product_id),
				KEY idx_lead (leadid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		self::unlinkProductOppsFromLeadProfile($adb);
	}

	/** Đã chốt / Đã giao must stay on Leads list — Opp lives on product row, not profile.potential_id. */
	protected static function unlinkProductOppsFromLeadProfile(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$col = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE 'potential_id'", array());
		if (!$col || $adb->num_rows($col) < 1) {
			return;
		}
		$adb->pquery(
			"UPDATE bace_lead_profile p
			 INNER JOIN " . self::TABLE . " lp ON lp.leadid = p.leadid AND lp.potential_id = p.potential_id
			 SET p.potential_id = NULL
			 WHERE p.potential_id IS NOT NULL AND p.potential_id > 0",
			array()
		);
	}

	public static function catalog() {
		$labels = self::stageLabels();
		$groups = array();
		foreach (self::groups() as $g) {
			$stages = array();
			foreach ($g['stages'] as $code) {
				$stages[] = array(
					'code' => $code,
					'label' => isset($labels[$code]) ? $labels[$code] : $code,
					'terminal' => ($code === 'khong_mua'),
					'won' => ($code === 'da_chot'),
				);
			}
			$groups[] = array(
				'code' => $g['code'],
				'label' => $g['label'],
				'stages' => $stages,
			);
		}
		return array(
			'groups' => $groups,
			'stage_labels' => $labels,
		);
	}

	public static function listForLeadIds(array $leadIds) {
		self::installSchema();
		$leadIds = array_values(array_filter(array_map('intval', $leadIds)));
		if (empty($leadIds)) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$sql = "SELECT * FROM " . self::TABLE . " WHERE leadid IN (" . generateQuestionMarks($leadIds) . ")
			ORDER BY FIELD(group_code,'online','offline','nvl','franchise'), id ASC";
		$res = $adb->pquery($sql, $leadIds);
		$map = array();
		foreach ($leadIds as $id) {
			$map[$id] = array();
		}
		if (!$res) {
			return $map;
		}
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$leadId = (int) $row['leadid'];
			$map[$leadId][] = self::formatRow($row);
		}
		return $map;
	}

	public static function upsertProduct($leadId, $groupCode, $productName, $userId = null) {
		global $current_user;
		self::installSchema();
		$leadId = (int) $leadId;
		$groupCode = self::normalizeGroup($groupCode);
		if ($leadId <= 0 || $groupCode === '') {
			throw new Exception('Thiếu Lead hoặc nhóm sản phẩm.');
		}
		self::assertCanEditLead($leadId, $userId);
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$name = trim((string) $productName);
		$uid = $userId !== null ? (int) $userId : (int) $current_user->id;

		$exists = $adb->pquery(
			"SELECT id FROM " . self::TABLE . " WHERE leadid = ? AND group_code = ?",
			array($leadId, $groupCode)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$id = (int) $adb->query_result($exists, 0, 'id');
			if ($name !== '') {
				$adb->pquery(
					"UPDATE " . self::TABLE . " SET product_name = ?, modified_at = ? WHERE id = ?",
					array($name, $now, $id)
				);
			}
			return self::getById($id);
		}

		$adb->pquery(
			"INSERT INTO " . self::TABLE . "
				(leadid, group_code, product_name, stage_code, entered_stage_at, created_at, modified_at, created_by)
				VALUES (?,?,?,?,?,?,?,?)",
			array($leadId, $groupCode, $name !== '' ? $name : null, 'moi', $now, $now, $now, $uid)
		);
		$id = (int) $adb->getLastInsertID();
		self::logStage($id, $leadId, null, 'moi', $uid, $now);
		self::refreshLeadPipelineClosed($leadId);
		return self::getById($id);
	}

	public static function removeProduct($productId, $userId = null) {
		self::installSchema();
		$row = self::getRaw((int) $productId);
		if (!$row) {
			throw new Exception('Không tìm thấy sản phẩm.');
		}
		$leadId = (int) $row['leadid'];
		self::assertCanEditLead($leadId, $userId);
		$adb = PearDatabase::getInstance();
		$adb->pquery("DELETE FROM " . self::LOG_TABLE . " WHERE product_id = ?", array((int) $productId));
		$adb->pquery("DELETE FROM " . self::TABLE . " WHERE id = ?", array((int) $productId));
		self::refreshLeadPipelineClosed($leadId);
		return $leadId;
	}

	public static function setStage($productId, $stageCode, $userId = null) {
		global $current_user;
		self::installSchema();
		$productId = (int) $productId;
		$row = self::getRaw($productId);
		if (!$row) {
			throw new Exception('Không tìm thấy sản phẩm.');
		}
		$leadId = (int) $row['leadid'];
		self::assertCanEditLead($leadId, $userId);
		$group = self::normalizeGroup($row['group_code']);
		$stageCode = self::normalizeStage($group, $stageCode);
		$from = (string) $row['stage_code'];
		if ($from === $stageCode) {
			return self::getById($productId);
		}

		$uid = $userId !== null ? (int) $userId : (int) $current_user->id;
		$now = date('Y-m-d H:i:s');
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE " . self::TABLE . " SET stage_code = ?, entered_stage_at = ?, modified_at = ? WHERE id = ?",
			array($stageCode, $now, $now, $productId)
		);
		self::logStage($productId, $leadId, $from, $stageCode, $uid, $now);

		if ($stageCode === 'da_chot' && (int) $row['potential_id'] <= 0) {
			try {
				$potentialId = self::createOpportunity($leadId, $row);
				if ($potentialId > 0) {
					$adb->pquery(
						"UPDATE " . self::TABLE . " SET potential_id = ? WHERE id = ?",
						array($potentialId, $productId)
					);
				}
			} catch (Exception $e) {
				error_log('[lead_products] create opportunity failed: ' . $e->getMessage());
			}
		}

		self::refreshLeadPipelineClosed($leadId);
		return self::getById($productId);
	}

	public static function canEditLead($leadId, $userId = null) {
		global $current_user;
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return false;
		}
		$currentId = (is_object($current_user) && isset($current_user->id)) ? (int) $current_user->id : 0;
		$user = $current_user;
		if ($userId && (int) $userId !== $currentId) {
			try {
				$user = Users_Record_Model::getInstanceById((int) $userId, 'Users');
			} catch (Exception $e) {
				$user = $current_user;
			}
		}
		if ($user && method_exists($user, 'isAdminUser') && $user->isAdminUser()) {
			return true;
		}
		if (is_object($user) && isset($user->is_admin) && ($user->is_admin === 'on' || $user->is_admin === 1 || $user->is_admin === '1')) {
			return true;
		}
		$ownerId = self::leadOwnerId($leadId);
		$uid = $currentId;
		if ($user instanceof Users_Record_Model && method_exists($user, 'getId')) {
			$uid = (int) $user->getId();
		} elseif (is_object($user) && isset($user->id)) {
			$uid = (int) $user->id;
		}
		if ($ownerId > 0 && $ownerId === $uid) {
			return true;
		}
		try {
			$priv = Users_Privileges_Model::getCurrentUserPrivilegesModel();
			if ($priv && $priv->hasGlobalWritePermission()) {
				return true;
			}
			$sub = $priv ? $priv->get('subordinate_roles_users') : null;
			if (is_array($sub)) {
				foreach ($sub as $users) {
					if (is_array($users) && in_array($ownerId, $users, false)) {
						return true;
					}
					if (is_array($users) && in_array((string) $ownerId, $users, true)) {
						return true;
					}
				}
			}
		} catch (Exception $e) {
			return $ownerId === $uid;
		}
		return false;
	}

	public static function groupsWantedFromTags(array $tags) {
		$wanted = array();
		foreach ($tags as $tag) {
			$key = strtolower(trim((string) $tag));
			if ($key === 'mien_phi_online') {
				$wanted['online'] = true;
			} elseif ($key === 'mien_phi_offline') {
				$wanted['offline'] = true;
			} elseif ($key === 'nhuong_quyen') {
				$wanted['franchise'] = true;
			} elseif ($key === 'mua_lan_dau' || $key === 'mua_lai') {
				$wanted['nvl'] = true;
			}
		}
		return array_keys($wanted);
	}

	/**
	 * Tag chương trình / tình trạng mua → nhóm sản phẩm.
	 * $removeMissing: true khi sale sửa thẻ (bỏ tag thì bỏ nhóm map).
	 */
	public static function syncFromTags($leadId, array $tags, $userId = null, $removeMissing = true) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return;
		}
		self::installSchema();
		$wanted = self::groupsWantedFromTags($tags);
		$wantedMap = array_flip($wanted);
		$existing = self::listForLeadIds(array($leadId));
		$have = array();
		foreach (isset($existing[$leadId]) ? $existing[$leadId] : array() as $row) {
			$have[$row['group']] = $row;
		}
		foreach ($wanted as $group) {
			if (!isset($have[$group])) {
				try {
					self::upsertProduct($leadId, $group, '', $userId);
				} catch (Exception $e) {
					error_log('[lead_products] tag sync upsert: ' . $e->getMessage());
				}
			}
		}
		if (!$removeMissing) {
			return;
		}
		foreach (array('online', 'offline', 'franchise', 'nvl') as $group) {
			if (!isset($wantedMap[$group]) && isset($have[$group])) {
				try {
					self::removeProduct($have[$group]['id'], $userId);
				} catch (Exception $e) {
					error_log('[lead_products] tag sync remove: ' . $e->getMessage());
				}
			}
		}
	}

	protected static function assertCanEditLead($leadId, $userId = null) {
		if (!self::canEditLead($leadId, $userId)) {
			throw new Exception('Bạn không có quyền kéo stage / gắn sản phẩm cho Lead này.');
		}
	}

	protected static function leadOwnerId($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT smownerid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0",
			array((int) $leadId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'smownerid');
		}
		return 0;
	}

	protected static function refreshLeadPipelineClosed($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT stage_code FROM " . self::TABLE . " WHERE leadid = ?",
			array((int) $leadId)
		);
		$closed = 0;
		if ($res && $adb->num_rows($res) > 0) {
			$allTerminal = true;
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$st = (string) $adb->query_result($res, $i, 'stage_code');
				if ($st !== 'da_chot' && $st !== 'khong_mua') {
					$allTerminal = false;
					break;
				}
			}
			$closed = $allTerminal ? 1 : 0;
		}
		$col = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE 'pipeline_closed'", array());
		if ($col && $adb->num_rows($col) > 0) {
			$adb->pquery(
				"UPDATE bace_lead_profile SET pipeline_closed = ?, modified_at = ? WHERE leadid = ?",
				array($closed, date('Y-m-d H:i:s'), (int) $leadId)
			);
		}
		if ($closed) {
			$allLost = true;
			$stRes = $adb->pquery(
				"SELECT stage_code FROM " . self::TABLE . " WHERE leadid = ?",
				array((int) $leadId)
			);
			for ($i = 0; $i < $adb->num_rows($stRes); $i++) {
				if ((string) $adb->query_result($stRes, $i, 'stage_code') !== 'khong_mua') {
					$allLost = false;
					break;
				}
			}
			if ($allLost) {
				$adb->pquery(
					"UPDATE vtiger_leaddetails SET leadstatus = ? WHERE leadid = ?",
					array('Lost Lead', (int) $leadId)
				);
			}
			// Keep lead on list/Kanban after Đã chốt / Đã giao (Opp is related on the product row only).
		} else {
			$linked = $adb->pquery(
				"SELECT p.potential_id AS pid FROM bace_lead_profile p
				 INNER JOIN " . self::TABLE . " lp ON lp.leadid = p.leadid AND lp.potential_id = p.potential_id
				 WHERE p.leadid = ? AND p.potential_id IS NOT NULL AND p.potential_id > 0",
				array((int) $leadId)
			);
			if ($linked && $adb->num_rows($linked) > 0) {
				$adb->pquery(
					"UPDATE bace_lead_profile SET potential_id = NULL WHERE leadid = ?",
					array((int) $leadId)
				);
			}
		}
	}

	protected static function createOpportunity($leadId, array $productRow) {
		require_once 'modules/Leads/models/ConvertService.php';
		$leadId = (int) $leadId;
		$record = Vtiger_Record_Model::getInstanceById($leadId, 'Leads');
		$ownerId = (int) $record->get('assigned_user_id');
		if ($ownerId <= 0) {
			$ownerId = self::leadOwnerId($leadId);
		}
		$group = self::normalizeGroup($productRow['group_code']);
		$groups = self::groups();
		$gLabel = isset($groups[$group]['label']) ? $groups[$group]['label'] : $group;
		$name = trim((string) $record->get('lastname') . ' ' . $record->get('firstname'));
		$name = trim(preg_replace('/\s+/', ' ', $name));
		$pname = trim((string) (isset($productRow['product_name']) ? $productRow['product_name'] : ''));
		$oppName = $name !== '' ? $name : ('Lead #' . $leadId);
		$oppName .= ' · ' . $gLabel;
		if ($pname !== '') {
			$oppName .= ' — ' . $pname;
		}
		return Leads_ConvertService::createRelatedPotential($leadId, $oppName, $ownerId);
	}

	protected static function logStage($productId, $leadId, $from, $to, $userId, $at) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"INSERT INTO " . self::LOG_TABLE . " (product_id, leadid, from_stage, to_stage, changed_by, changed_at)
				VALUES (?,?,?,?,?,?)",
			array((int) $productId, (int) $leadId, $from, $to, (int) $userId, $at)
		);
	}

	protected static function getRaw($id) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT * FROM " . self::TABLE . " WHERE id = ?", array((int) $id));
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return $adb->query_result_rowdata($res, 0);
	}

	public static function getById($id) {
		$row = self::getRaw($id);
		return $row ? self::formatRow($row) : null;
	}

	protected static function formatRow(array $row) {
		$group = self::normalizeGroup($row['group_code']);
		$stage = (string) $row['stage_code'];
		$labels = self::stageLabels();
		$groups = self::groups();
		$entered = !empty($row['entered_stage_at']) ? $row['entered_stage_at'] : null;
		$days = null;
		if ($stage !== 'khong_mua' && $entered) {
			$ts = strtotime($entered);
			if ($ts) {
				$days = (int) floor(max(0, (time() - $ts) / 86400));
			}
		}
		static $canEditCache = array();
		$leadId = (int) $row['leadid'];
		if (!isset($canEditCache[$leadId])) {
			$canEditCache[$leadId] = self::canEditLead($leadId);
		}
		return array(
			'id' => (int) $row['id'],
			'leadid' => $leadId,
			'group' => $group,
			'group_label' => isset($groups[$group]['label']) ? $groups[$group]['label'] : $group,
			'product_name' => isset($row['product_name']) ? (string) $row['product_name'] : '',
			'stage' => $stage,
			'stage_label' => isset($labels[$stage]) ? $labels[$stage] : $stage,
			'entered_stage_at' => $entered,
			'days_in_stage' => $days,
			'potential_id' => !empty($row['potential_id']) ? (int) $row['potential_id'] : null,
			'can_edit' => $canEditCache[$leadId],
		);
	}

	protected static function normalizeGroup($code) {
		$code = strtolower(trim((string) $code));
		$map = array(
			'online' => 'online',
			'offline' => 'offline',
			'nvl' => 'nvl',
			'nguyen_lieu' => 'nvl',
			'franchise' => 'franchise',
			'nhuong_quyen' => 'franchise',
		);
		return isset($map[$code]) ? $map[$code] : '';
	}

	protected static function normalizeStage($group, $stage) {
		$stage = strtolower(trim((string) $stage));
		$groups = self::groups();
		if (!isset($groups[$group])) {
			throw new Exception('Nhóm sản phẩm không hợp lệ.');
		}
		if (!in_array($stage, $groups[$group]['stages'], true)) {
			throw new Exception('Stage không thuộc nhóm ' . $groups[$group]['label'] . '.');
		}
		return $stage;
	}
}
