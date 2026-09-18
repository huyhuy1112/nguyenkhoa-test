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
	}
}
