<?php
/*+***********************************************************************************
 * Lịch thông báo trì hoãn → chuông Modern Notifications (vtiger_notifications).
 * Flush khi API Notifications được poll (mỗi ~3s).
 *************************************************************************************/

class Vtiger_NotificationSchedule {

	public static function ensureSchema($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS bace_notification_schedule (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				userid INT UNSIGNED NOT NULL,
				module VARCHAR(64) NOT NULL,
				recordid INT UNSIGNED NOT NULL DEFAULT 0,
				message VARCHAR(512) NOT NULL,
				deliver_at DATETIME NOT NULL,
				source_key VARCHAR(128) DEFAULT NULL,
				delivered_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				KEY idx_due (delivered_at, deliver_at),
				KEY idx_user_due (userid, deliver_at),
				UNIQUE KEY uniq_source (source_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
	}

	/**
	 * Đặt lịch 1 thông báo. sourceKey unique → ghi đè lịch cũ cùng key.
	 */
	public static function schedule($userId, $module, $recordId, $message, $deliverAt, $sourceKey = null) {
		$userId = (int)$userId;
		$recordId = (int)$recordId;
		$module = trim((string)$module);
		$message = trim((string)$message);
		$deliverAt = trim((string)$deliverAt);
		if ($userId <= 0 || $module === '' || $message === '' || $deliverAt === '') {
			return 0;
		}
		if (function_exists('mb_substr')) {
			$message = mb_substr($message, 0, 500, 'UTF-8');
		} else {
			$message = substr($message, 0, 500);
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		if ($sourceKey !== null && $sourceKey !== '') {
			$adb->pquery(
				'DELETE FROM bace_notification_schedule WHERE source_key = ? AND delivered_at IS NULL',
				array($sourceKey)
			);
		}
		$adb->pquery(
			'INSERT INTO bace_notification_schedule
				(userid, module, recordid, message, deliver_at, source_key, created_at)
			 VALUES (?,?,?,?,?,?,?)',
			array($userId, $module, $recordId, $message, $deliverAt, $sourceKey, $now)
		);
		return (int)$adb->getLastInsertID();
	}

	public static function cancelBySourcePrefix($prefix) {
		$prefix = trim((string)$prefix);
		if ($prefix === '') {
			return;
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'DELETE FROM bace_notification_schedule
			 WHERE delivered_at IS NULL AND source_key LIKE ?',
			array($prefix . '%')
		);
	}

	/**
	 * Đưa các lịch đã đến hạn vào vtiger_notifications (chuông chính).
	 */
	public static function flushDue($limit = 50) {
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$limit = max(1, min((int)$limit, 200));
		$res = $adb->pquery(
			'SELECT id, userid, module, recordid, message
			 FROM bace_notification_schedule
			 WHERE delivered_at IS NULL AND deliver_at <= ?
			 ORDER BY deliver_at ASC
			 LIMIT ' . $limit,
			array($now)
		);
		if (!$res) {
			return 0;
		}
		$n = 0;
		while ($row = $adb->fetchByAssoc($res)) {
			$id = (int)$row['id'];
			$userId = (int)$row['userid'];
			$module = (string)$row['module'];
			$recordId = (int)$row['recordid'];
			$message = (string)$row['message'];
			try {
				$adb->pquery(
					'INSERT INTO vtiger_notifications (userid, module, recordid, message, is_read, created_at)
					 VALUES (?, ?, ?, ?, 0, ?)',
					array($userId, $module, $recordId, $message, $now)
				);
			} catch (Exception $e) {
				// fallback nếu thiếu cột is_read
				$adb->pquery(
					'INSERT INTO vtiger_notifications (userid, module, recordid, message, created_at)
					 VALUES (?, ?, ?, ?, ?)',
					array($userId, $module, $recordId, $message, $now)
				);
			}
			$adb->pquery(
				'UPDATE bace_notification_schedule SET delivered_at = ? WHERE id = ?',
				array($now, $id)
			);
			$n++;
		}
		return $n;
	}
}
