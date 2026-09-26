<?php
/*+***********************************************************************************
 * Company-wide R1 / callback reminder settings (admin-only).
 * Defaults: 08:00–16:30, gap 3h, max 3 attempts, Mon–Sun.
 *************************************************************************************/

class Vtiger_R1ReminderSettings {

	const TABLE = 'bace_r1_reminder_settings';
	const SETTINGS_KEY = 'company';

	public static function defaults() {
		return array(
			'work_start' => '08:00',
			'work_end' => '16:30',
			'gap_hours' => 3,
			'max_attempts' => 3,
			'work_days' => '1,2,3,4,5,6,7', // 1=Mon … 7=Sun
		);
	}

	public static function ensureSchema($adb = null) {
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE . " (
				settings_key VARCHAR(32) NOT NULL PRIMARY KEY,
				work_start VARCHAR(8) NOT NULL DEFAULT '08:00',
				work_end VARCHAR(8) NOT NULL DEFAULT '16:30',
				gap_hours TINYINT UNSIGNED NOT NULL DEFAULT 3,
				max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
				work_days VARCHAR(32) NOT NULL DEFAULT '1,2,3,4,5,6,7',
				updated_at DATETIME NULL,
				updated_by INT UNSIGNED NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
		$res = $adb->pquery(
			'SELECT settings_key, work_end, updated_by FROM ' . self::TABLE . ' WHERE settings_key = ?',
			array(self::SETTINGS_KEY)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			$d = self::defaults();
			$adb->pquery(
				'INSERT INTO ' . self::TABLE . '
					(settings_key, work_start, work_end, gap_hours, max_attempts, work_days, updated_at)
				 VALUES (?,?,?,?,?,?,?)',
				array(
					self::SETTINGS_KEY,
					$d['work_start'],
					$d['work_end'],
					$d['gap_hours'],
					$d['max_attempts'],
					$d['work_days'],
					date('Y-m-d H:i:s'),
				)
			);
		} else {
			// Migrate old default 16:00 → 16:30 only if admin chưa từng lưu tay.
			$curEnd = trim((string) $adb->query_result($res, 0, 'work_end'));
			$updBy = $adb->query_result($res, 0, 'updated_by');
			if ($curEnd === '16:00' && ($updBy === null || $updBy === '' || (int) $updBy <= 0)) {
				$adb->pquery(
					'UPDATE ' . self::TABLE . ' SET work_end = ?, updated_at = ? WHERE settings_key = ?',
					array('16:30', date('Y-m-d H:i:s'), self::SETTINGS_KEY)
				);
			}
		}
	}

	public static function get() {
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT work_start, work_end, gap_hours, max_attempts, work_days
			 FROM ' . self::TABLE . ' WHERE settings_key = ?',
			array(self::SETTINGS_KEY)
		);
		$d = self::defaults();
		if (!$res || $adb->num_rows($res) < 1) {
			return $d;
		}
		$row = $adb->query_result_rowdata($res, 0);
		$out = array(
			'work_start' => self::normalizeTime(isset($row['work_start']) ? $row['work_start'] : $d['work_start']),
			'work_end' => self::normalizeTime(isset($row['work_end']) ? $row['work_end'] : $d['work_end']),
			'gap_hours' => max(1, min(24, (int) (isset($row['gap_hours']) ? $row['gap_hours'] : $d['gap_hours']))),
			'max_attempts' => max(1, min(20, (int) (isset($row['max_attempts']) ? $row['max_attempts'] : $d['max_attempts']))),
			'work_days' => self::normalizeDays(isset($row['work_days']) ? $row['work_days'] : $d['work_days']),
		);
		return $out;
	}

	public static function save(array $input, $userId = 0) {
		self::ensureSchema();
		$cur = self::get();
		$workStart = self::normalizeTime(isset($input['work_start']) ? $input['work_start'] : $cur['work_start']);
		$workEnd = self::normalizeTime(isset($input['work_end']) ? $input['work_end'] : $cur['work_end']);
		$gap = max(1, min(24, (int) (isset($input['gap_hours']) ? $input['gap_hours'] : $cur['gap_hours'])));
		$max = max(1, min(20, (int) (isset($input['max_attempts']) ? $input['max_attempts'] : $cur['max_attempts'])));
		$days = self::normalizeDays(isset($input['work_days']) ? $input['work_days'] : $cur['work_days']);
		if (strcmp($workStart, $workEnd) >= 0) {
			throw new Exception('Giờ kết thúc phải sau giờ bắt đầu.');
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE ' . self::TABLE . ' SET
				work_start=?, work_end=?, gap_hours=?, max_attempts=?, work_days=?,
				updated_at=?, updated_by=?
			 WHERE settings_key=?',
			array(
				$workStart,
				$workEnd,
				$gap,
				$max,
				$days,
				date('Y-m-d H:i:s'),
				(int) $userId > 0 ? (int) $userId : null,
				self::SETTINGS_KEY,
			)
		);
		return self::get();
	}

	/**
	 * Add $hours from $fromYmdHis, then snap into next valid work window.
	 * If result falls after work_end → next work day at work_start.
	 */
	public static function addGapWithinBusinessHours($fromYmdHis = null, $hours = null) {
		$cfg = self::get();
		if ($hours === null) {
			$hours = (int) $cfg['gap_hours'];
		}
		$hours = max(0, (int) $hours);
		$from = $fromYmdHis ? strtotime($fromYmdHis) : time();
		if (!$from) {
			$from = time();
		}
		$candidate = $from + ($hours * 3600);
		return self::snapToBusinessHours(date('Y-m-d H:i:s', $candidate), $cfg);
	}

	public static function snapToBusinessHours($ymdHis, array $cfg = null) {
		if ($cfg === null) {
			$cfg = self::get();
		}
		$ts = strtotime($ymdHis);
		if (!$ts) {
			$ts = time();
		}
		$dayMap = self::workDaySet($cfg['work_days']);
		$startParts = explode(':', $cfg['work_start']);
		$endParts = explode(':', $cfg['work_end']);
		$startH = (int) $startParts[0];
		$startM = isset($startParts[1]) ? (int) $startParts[1] : 0;
		$endH = (int) $endParts[0];
		$endM = isset($endParts[1]) ? (int) $endParts[1] : 0;

		for ($i = 0; $i < 14; $i++) {
			$dow = (int) date('N', $ts); // 1–7
			$hm = ((int) date('H', $ts)) * 60 + (int) date('i', $ts);
			$startMin = $startH * 60 + $startM;
			$endMin = $endH * 60 + $endM;
			$isWorkDay = isset($dayMap[$dow]);

			if ($isWorkDay && $hm >= $startMin && $hm < $endMin) {
				return date('Y-m-d H:i:s', $ts);
			}
			// Move to next day's work_start (or today's work_start if before open).
			if ($isWorkDay && $hm < $startMin) {
				return date('Y-m-d', $ts) . sprintf(' %02d:%02d:00', $startH, $startM);
			}
			$ts = strtotime(date('Y-m-d', $ts) . ' +1 day');
			$ts = strtotime(date('Y-m-d', $ts) . sprintf(' %02d:%02d:00', $startH, $startM));
		}
		return date('Y-m-d H:i:s', $ts);
	}

	protected static function normalizeTime($raw) {
		$raw = trim((string) $raw);
		if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $raw, $m)) {
			$h = max(0, min(23, (int) $m[1]));
			$min = max(0, min(59, (int) $m[2]));
			return sprintf('%02d:%02d', $h, $min);
		}
		return '08:00';
	}

	protected static function normalizeDays($raw) {
		if (is_array($raw)) {
			$parts = $raw;
		} else {
			$parts = preg_split('/\s*,\s*/', trim((string) $raw));
		}
		$out = array();
		foreach ($parts as $p) {
			$n = (int) $p;
			if ($n >= 1 && $n <= 7) {
				$out[$n] = $n;
			}
		}
		if (!$out) {
			return '1,2,3,4,5,6,7';
		}
		ksort($out);
		return implode(',', array_values($out));
	}

	protected static function workDaySet($daysCsv) {
		$set = array();
		foreach (explode(',', (string) $daysCsv) as $p) {
			$n = (int) $p;
			if ($n >= 1 && $n <= 7) {
				$set[$n] = true;
			}
		}
		if (!$set) {
			for ($i = 1; $i <= 7; $i++) {
				$set[$i] = true;
			}
		}
		return $set;
	}

	public static function isAdminUser($user = null) {
		if ($user === null) {
			$user = Users_Record_Model::getCurrentUserModel();
		}
		if (!$user) {
			return false;
		}
		if (method_exists($user, 'isAdminUser') && $user->isAdminUser()) {
			return true;
		}
		if (method_exists($user, 'is_admin') && $user->is_admin === 'on') {
			return true;
		}
		return false;
	}
}
