<?php
/**
 * Warehouse global key/value settings (e.g. allow negative stock).
 */
class Warehouse_Settings_Helper {

	const KEY_ALLOW_NEGATIVE_STOCK = 'wh_allow_negative_stock';
	const KEY_EXPIRY_WARN_DAYS = 'wh_expiry_warn_days';
	const DEFAULT_EXPIRY_WARN_DAYS = 90;

	public static function ensureTable(PearDatabase $db = null) {
		if (!$db) {
			$db = PearDatabase::getInstance();
		}
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_wh_settings (
				setting_key VARCHAR(64) NOT NULL,
				setting_value TEXT,
				updatedtime DATETIME DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				PRIMARY KEY (setting_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		// Seed default once: allow negative stock (BA: oversell while restocking is fast).
		$rs = $db->pquery(
			'SELECT setting_key FROM vtiger_wh_settings WHERE setting_key = ? LIMIT 1',
			array(self::KEY_ALLOW_NEGATIVE_STOCK)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			$db->pquery(
				'INSERT INTO vtiger_wh_settings (setting_key, setting_value, updatedtime, updatedby) VALUES (?,?,?,NULL)',
				array(self::KEY_ALLOW_NEGATIVE_STOCK, '1', date('Y-m-d H:i:s'))
			);
		}
		$expRs = $db->pquery(
			'SELECT setting_key FROM vtiger_wh_settings WHERE setting_key = ? LIMIT 1',
			array(self::KEY_EXPIRY_WARN_DAYS)
		);
		if (!$expRs || $db->num_rows($expRs) < 1) {
			$db->pquery(
				'INSERT INTO vtiger_wh_settings (setting_key, setting_value, updatedtime, updatedby) VALUES (?,?,?,NULL)',
				array(self::KEY_EXPIRY_WARN_DAYS, (string) self::DEFAULT_EXPIRY_WARN_DAYS, date('Y-m-d H:i:s'))
			);
		}
	}

	/**
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public static function get($key, $default = '') {
		$db = PearDatabase::getInstance();
		self::ensureTable($db);
		$key = trim((string) $key);
		if ($key === '') {
			return $default;
		}
		$rs = $db->pquery(
			'SELECT setting_value FROM vtiger_wh_settings WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if ($rs && $db->num_rows($rs) > 0) {
			return (string) $db->query_result($rs, 0, 'setting_value');
		}
		return $default;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $userId
	 */
	public static function set($key, $value, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureTable($db);
		$key = trim((string) $key);
		if ($key === '') {
			return;
		}
		$val = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;
		$exists = $db->pquery(
			'SELECT setting_key FROM vtiger_wh_settings WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if ($exists && $db->num_rows($exists) > 0) {
			$db->pquery(
				'UPDATE vtiger_wh_settings SET setting_value = ?, updatedtime = ?, updatedby = ? WHERE setting_key = ?',
				array($val, $now, $userId > 0 ? $userId : null, $key)
			);
		} else {
			$db->pquery(
				'INSERT INTO vtiger_wh_settings (setting_key, setting_value, updatedtime, updatedby) VALUES (?,?,?,?)',
				array($key, $val, $now, $userId > 0 ? $userId : null)
			);
		}
	}

	/**
	 * When true (default): confirm SO / xuất kho may drive on-hand below 0
	 * (e.g. tồn 10, xuất 11 → tồn -1). Toggle lives on Warehouse dashboard.
	 *
	 * @return bool
	 */
	public static function allowNegativeStock() {
		// Company default: allow short-pick / oversell while inbound is restocked quickly.
		$raw = strtolower(trim(self::get(self::KEY_ALLOW_NEGATIVE_STOCK, '1')));
		return in_array($raw, array('1', 'true', 'yes', 'on'), true);
	}

	/**
	 * @param bool $allow
	 * @param int $userId
	 */
	public static function setAllowNegativeStock($allow, $userId = 0) {
		self::set(self::KEY_ALLOW_NEGATIVE_STOCK, $allow ? '1' : '0', $userId);
	}

	/**
	 * System-wide days-before-expiry warning window (product field can override).
	 * @return int
	 */
	public static function expiryWarnDays() {
		$raw = (int) self::get(self::KEY_EXPIRY_WARN_DAYS, (string) self::DEFAULT_EXPIRY_WARN_DAYS);
		if ($raw <= 0) {
			return self::DEFAULT_EXPIRY_WARN_DAYS;
		}
		if ($raw > 730) {
			return 730;
		}
		return $raw;
	}

	/**
	 * @param int $days
	 * @param int $userId
	 */
	public static function setExpiryWarnDays($days, $userId = 0) {
		$days = (int) $days;
		if ($days <= 0) {
			$days = self::DEFAULT_EXPIRY_WARN_DAYS;
		}
		if ($days > 730) {
			$days = 730;
		}
		self::set(self::KEY_EXPIRY_WARN_DAYS, (string) $days, $userId);
	}
}
