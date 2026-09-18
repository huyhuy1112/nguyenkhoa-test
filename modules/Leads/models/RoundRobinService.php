<?php
/**
 * Round-robin phụ trách Lead (GD 1.1 / 1.2) — chung 1 pool role Sale.
 */
class Leads_RoundRobinService {

	const POOL_KEY = 'leads_sale';
	const TABLE = 'bace_lead_roundrobin';

	/**
	 * Owner khi tạo Lead mới: giữ phụ trách Sale thật; trống/admin → xoay vòng.
	 * @param int $candidateOwnerId
	 * @return int
	 */
	public static function resolveOwnerForNewLead($candidateOwnerId = 0) {
		$candidateOwnerId = (int) $candidateOwnerId;
		if ($candidateOwnerId > 0 && self::isEligiblePoolUser($candidateOwnerId)) {
			return $candidateOwnerId;
		}
		$next = self::nextOwnerId();
		if ($next > 0) {
			return $next;
		}
		return $candidateOwnerId > 0 ? $candidateOwnerId : 1;
	}

	/**
	 * User active thuộc persona Sale (role Sale / Sales / …).
	 * @return int[]
	 */
	public static function listSaleUserIds() {
		require_once 'modules/Home/helpers/RbacMatrix.php';
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT u.id, r.rolename
			 FROM vtiger_users u
			 INNER JOIN vtiger_user2role ur ON ur.userid = u.id
			 INNER JOIN vtiger_role r ON r.roleid = ur.roleid
			 WHERE u.status = 'Active' AND u.deleted = 0
			 ORDER BY u.id ASC",
			array()
		);
		$ids = array();
		if (!$res) {
			return $ids;
		}
		$rows = $adb->num_rows($res);
		for ($i = 0; $i < $rows; $i++) {
			$userId = (int) $adb->query_result($res, $i, 'id');
			$roleName = (string) $adb->query_result($res, $i, 'rolename');
			if ($userId <= 0) {
				continue;
			}
			if (Home_RbacMatrix_Helper::personaFromRoleName($roleName) !== Home_RbacMatrix_Helper::PERSONA_SALE) {
				continue;
			}
			$ids[] = $userId;
		}
		return array_values(array_unique($ids));
	}

	/**
	 * @return int 0 nếu pool trống
	 */
	public static function nextOwnerId() {
		$pool = self::listSaleUserIds();
		if (empty($pool)) {
			return 0;
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT cursor_index FROM ' . self::TABLE . ' WHERE pool_key = ?',
			array(self::POOL_KEY)
		);
		$cursor = 0;
		$hasRow = ($res && $adb->num_rows($res) > 0);
		if ($hasRow) {
			$cursor = (int) $adb->query_result($res, 0, 'cursor_index');
		}
		$count = count($pool);
		if ($cursor < 0 || $cursor >= $count) {
			$cursor = 0;
		}
		$ownerId = (int) $pool[$cursor];
		$next = ($cursor + 1) % $count;
		$now = date('Y-m-d H:i:s');
		if ($hasRow) {
			$adb->pquery(
				'UPDATE ' . self::TABLE . ' SET cursor_index = ?, modified_at = ? WHERE pool_key = ?',
				array($next, $now, self::POOL_KEY)
			);
		} else {
			$adb->pquery(
				'INSERT INTO ' . self::TABLE . ' (pool_key, cursor_index, modified_at) VALUES (?,?,?)',
				array(self::POOL_KEY, $next, $now)
			);
		}
		return $ownerId;
	}

	public static function isEligiblePoolUser($userId) {
		$userId = (int) $userId;
		if ($userId <= 0) {
			return false;
		}
		if (self::isAdminUserId($userId)) {
			return false;
		}
		return in_array($userId, self::listSaleUserIds(), true);
	}

	protected static function isAdminUserId($userId) {
		$userId = (int) $userId;
		if ($userId <= 0) {
			return false;
		}
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			if ($user && method_exists($user, 'isAdminUser') && $user->isAdminUser()) {
				return true;
			}
		} catch (Exception $e) {
			// fall through
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT is_admin FROM vtiger_users WHERE id = ? AND deleted = 0",
			array($userId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return strtolower(trim((string) $adb->query_result($res, 0, 'is_admin'))) === 'on';
		}
		return false;
	}

	public static function ensureSchema() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE . " (
				pool_key VARCHAR(64) NOT NULL PRIMARY KEY,
				cursor_index INT NOT NULL DEFAULT 0,
				modified_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
		self::ensureSettingsMenu();
	}

	/**
	 * Snapshot pool Sale + con trỏ hiện tại (cho Settings UI).
	 * @return array
	 */
	public static function getPoolSnapshot() {
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$ids = self::listSaleUserIds();
		$users = array();
		foreach ($ids as $uid) {
			try {
				$user = Users_Record_Model::getInstanceById($uid, 'Users');
				$name = $user ? trim($user->getName()) : '';
				$email = $user ? $user->get('email1') : '';
			} catch (Exception $e) {
				$name = '';
				$email = '';
			}
			$users[] = array(
				'id' => (int) $uid,
				'name' => $name !== '' ? $name : ('User #' . $uid),
				'email' => $email,
			);
		}

		$cursor = 0;
		$modifiedAt = '';
		$res = $adb->pquery(
			'SELECT cursor_index, modified_at FROM ' . self::TABLE . ' WHERE pool_key = ?',
			array(self::POOL_KEY)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$cursor = (int) $adb->query_result($res, 0, 'cursor_index');
			$modifiedAt = (string) $adb->query_result($res, 0, 'modified_at');
		}
		$count = count($users);
		if ($count > 0 && ($cursor < 0 || $cursor >= $count)) {
			$cursor = 0;
		}
		$next = null;
		if ($count > 0) {
			$next = $users[$cursor];
		}

		return array(
			'pool_key' => self::POOL_KEY,
			'users' => $users,
			'count' => $count,
			'cursor_index' => $cursor,
			'next_user' => $next,
			'modified_at' => $modifiedAt,
		);
	}

	/**
	 * Đặt lại con trỏ về đầu pool.
	 * @return bool
	 */
	public static function resetCursor() {
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$res = $adb->pquery(
			'SELECT pool_key FROM ' . self::TABLE . ' WHERE pool_key = ?',
			array(self::POOL_KEY)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$adb->pquery(
				'UPDATE ' . self::TABLE . ' SET cursor_index = 0, modified_at = ? WHERE pool_key = ?',
				array($now, self::POOL_KEY)
			);
		} else {
			$adb->pquery(
				'INSERT INTO ' . self::TABLE . ' (pool_key, cursor_index, modified_at) VALUES (?,?,?)',
				array(self::POOL_KEY, 0, $now)
			);
		}
		return true;
	}

	public static function ensureSettingsMenu() {
		static $menuDone = false;
		if ($menuDone) {
			return;
		}
		$menuDone = true;

		$adb = PearDatabase::getInstance();
		$name = 'LBL_NK_LEAD_ROUND_ROBIN';
		$exists = $adb->pquery('SELECT fieldid FROM vtiger_settings_field WHERE name = ? LIMIT 1', array($name));
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}

		$blockid = 0;
		foreach (array('LBL_MARKETING_SALES', 'LBL_OTHER_SETTINGS', 'LBL_USER_MANAGEMENT', 'LBL_CONFIGURATION') as $label) {
			if (function_exists('getSettingsBlockId')) {
				$blockid = (int) getSettingsBlockId($label);
			}
			if ($blockid > 0) {
				break;
			}
		}
		if ($blockid <= 0) {
			$blockRes = $adb->pquery('SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence ASC LIMIT 1', array());
			if ($blockRes && $adb->num_rows($blockRes) > 0) {
				$blockid = (int) $adb->query_result($blockRes, 0, 'blockid');
			}
		}
		if ($blockid <= 0) {
			return;
		}

		$seq = 1;
		$seqRes = $adb->pquery('SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?', array($blockid));
		if ($seqRes && $adb->num_rows($seqRes) > 0) {
			$seq = (int) $adb->query_result($seqRes, 0, 'max_seq') + 1;
		}

		$fieldid = $adb->getUniqueID('vtiger_settings_field');
		$adb->pquery(
			'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
			 VALUES (?,?,?,?,?,?,?,?)',
			array(
				$fieldid,
				$blockid,
				$name,
				'',
				'LBL_NK_LEAD_ROUND_ROBIN_DESC',
				'index.php?module=Vtiger&parent=Settings&view=LeadRoundRobin',
				$seq,
				0,
			)
		);
	}
}
