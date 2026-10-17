<?php
/**
 * Warehouse global key/value settings (e.g. allow negative stock).
 */
class Warehouse_Settings_Helper {

	const KEY_ALLOW_NEGATIVE_STOCK = 'wh_allow_negative_stock';
	const KEY_EXPIRY_WARN_DAYS = 'wh_expiry_warn_days';
	const DEFAULT_EXPIRY_WARN_DAYS = 90;

	/** Slow-moving inventory (tồn lâu ngày) */
	const KEY_SLOW_WINDOW_DAYS = 'wh_slow_window_days';
	const KEY_SLOW_DOI_THRESHOLD = 'wh_doi_threshold';
	const KEY_SLOW_DSI_THRESHOLD = 'wh_dsi_threshold';
	const KEY_SLOW_AGE_MAX = 'wh_age_max';
	const KEY_SLOW_W1 = 'wh_risk_w1';
	const KEY_SLOW_W2 = 'wh_risk_w2';
	const KEY_SLOW_W3 = 'wh_risk_w3';
	const KEY_SLOW_W4 = 'wh_risk_w4';

	const DEFAULT_SLOW_WINDOW_DAYS = 30;
	const DEFAULT_DOI_THRESHOLD = 60;
	const DEFAULT_DSI_THRESHOLD = 30;
	const DEFAULT_AGE_MAX = 90;
	const DEFAULT_W1 = 0.35;
	const DEFAULT_W2 = 0.30;
	const DEFAULT_W3 = 0.20;
	const DEFAULT_W4 = 0.15;

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
		self::seedIfMissing($db, self::KEY_SLOW_WINDOW_DAYS, (string) self::DEFAULT_SLOW_WINDOW_DAYS);
		self::seedIfMissing($db, self::KEY_SLOW_DOI_THRESHOLD, (string) self::DEFAULT_DOI_THRESHOLD);
		self::seedIfMissing($db, self::KEY_SLOW_DSI_THRESHOLD, (string) self::DEFAULT_DSI_THRESHOLD);
		self::seedIfMissing($db, self::KEY_SLOW_AGE_MAX, (string) self::DEFAULT_AGE_MAX);
		self::seedIfMissing($db, self::KEY_SLOW_W1, (string) self::DEFAULT_W1);
		self::seedIfMissing($db, self::KEY_SLOW_W2, (string) self::DEFAULT_W2);
		self::seedIfMissing($db, self::KEY_SLOW_W3, (string) self::DEFAULT_W3);
		self::seedIfMissing($db, self::KEY_SLOW_W4, (string) self::DEFAULT_W4);
	}

	protected static function seedIfMissing(PearDatabase $db, $key, $value) {
		$rs = $db->pquery(
			'SELECT setting_key FROM vtiger_wh_settings WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			$db->pquery(
				'INSERT INTO vtiger_wh_settings (setting_key, setting_value, updatedtime, updatedby) VALUES (?,?,?,NULL)',
				array($key, $value, date('Y-m-d H:i:s'))
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

	/**
	 * Slow-moving alert config (window X + thresholds + weights).
	 * @return array
	 */
	public static function slowMovingConfig() {
		self::ensureTable();
		$window = (int) self::get(self::KEY_SLOW_WINDOW_DAYS, (string) self::DEFAULT_SLOW_WINDOW_DAYS);
		if ($window < 7) {
			$window = self::DEFAULT_SLOW_WINDOW_DAYS;
		}
		if ($window > 365) {
			$window = 365;
		}
		$doi = (float) self::get(self::KEY_SLOW_DOI_THRESHOLD, (string) self::DEFAULT_DOI_THRESHOLD);
		$dsi = (float) self::get(self::KEY_SLOW_DSI_THRESHOLD, (string) self::DEFAULT_DSI_THRESHOLD);
		$age = (float) self::get(self::KEY_SLOW_AGE_MAX, (string) self::DEFAULT_AGE_MAX);
		$w1 = (float) self::get(self::KEY_SLOW_W1, (string) self::DEFAULT_W1);
		$w2 = (float) self::get(self::KEY_SLOW_W2, (string) self::DEFAULT_W2);
		$w3 = (float) self::get(self::KEY_SLOW_W3, (string) self::DEFAULT_W3);
		$w4 = (float) self::get(self::KEY_SLOW_W4, (string) self::DEFAULT_W4);
		if ($doi <= 0) {
			$doi = self::DEFAULT_DOI_THRESHOLD;
		}
		if ($dsi <= 0) {
			$dsi = self::DEFAULT_DSI_THRESHOLD;
		}
		if ($age <= 0) {
			$age = self::DEFAULT_AGE_MAX;
		}
		$sum = $w1 + $w2 + $w3 + $w4;
		if ($sum <= 0) {
			$w1 = self::DEFAULT_W1;
			$w2 = self::DEFAULT_W2;
			$w3 = self::DEFAULT_W3;
			$w4 = self::DEFAULT_W4;
		} else {
			// Normalize if admin drifted slightly off 1.0
			$w1 = $w1 / $sum;
			$w2 = $w2 / $sum;
			$w3 = $w3 / $sum;
			$w4 = $w4 / $sum;
		}
		return array(
			'window_days' => $window,
			'doi_threshold' => $doi,
			'dsi_threshold' => $dsi,
			'age_max' => $age,
			'w1' => round($w1, 4),
			'w2' => round($w2, 4),
			'w3' => round($w3, 4),
			'w4' => round($w4, 4),
			'wh_slow_window_days' => $window,
			'wh_doi_threshold' => $doi,
			'wh_dsi_threshold' => $dsi,
			'wh_age_max' => $age,
		);
	}

	/**
	 * @param array $partial keys from public settings / form
	 * @param int $userId
	 */
	public static function setSlowMovingConfig(array $partial, $userId = 0) {
		$map = array(
			'wh_slow_window_days' => self::KEY_SLOW_WINDOW_DAYS,
			'wh_doi_threshold' => self::KEY_SLOW_DOI_THRESHOLD,
			'wh_dsi_threshold' => self::KEY_SLOW_DSI_THRESHOLD,
			'wh_age_max' => self::KEY_SLOW_AGE_MAX,
			'wh_risk_w1' => self::KEY_SLOW_W1,
			'wh_risk_w2' => self::KEY_SLOW_W2,
			'wh_risk_w3' => self::KEY_SLOW_W3,
			'wh_risk_w4' => self::KEY_SLOW_W4,
			'window_days' => self::KEY_SLOW_WINDOW_DAYS,
			'doi_threshold' => self::KEY_SLOW_DOI_THRESHOLD,
			'dsi_threshold' => self::KEY_SLOW_DSI_THRESHOLD,
			'age_max' => self::KEY_SLOW_AGE_MAX,
		);
		foreach ($map as $inKey => $dbKey) {
			if (!array_key_exists($inKey, $partial)) {
				continue;
			}
			$raw = $partial[$inKey];
			if ($raw === null || $raw === '') {
				continue;
			}
			if (strpos($dbKey, 'wh_risk_w') === 0) {
				$val = (float) $raw;
				if ($val < 0) {
					$val = 0;
				}
				if ($val > 1) {
					$val = 1;
				}
				self::set($dbKey, (string) $val, $userId);
				continue;
			}
			$n = (int) round((float) $raw);
			if ($dbKey === self::KEY_SLOW_WINDOW_DAYS) {
				if ($n < 7) {
					$n = 7;
				}
				if ($n > 365) {
					$n = 365;
				}
			} else {
				if ($n < 1) {
					$n = 1;
				}
				if ($n > 730) {
					$n = 730;
				}
			}
			self::set($dbKey, (string) $n, $userId);
		}
	}
}
