<?php
/**
 * Lịch sử tác động chăm sóc. Thông báo chỉ nên tắt sau khi đã ghi một dòng ở đây.
 */
class Vtiger_CareActivityService {

	public static function install() {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS mk_care_activity (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				module VARCHAR(32) NOT NULL,
				record_id INT UNSIGNED NOT NULL,
				step_code VARCHAR(32) NOT NULL,
				result_label VARCHAR(255) NOT NULL,
				note TEXT NULL,
				user_id INT UNSIGNED NULL,
				user_name VARCHAR(128) NULL,
				created_at DATETIME NOT NULL,
				KEY idx_care_record (module, record_id, created_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
	}

	public static function log($module, $recordId, $stepCode, $resultLabel, $note = '', $userId = null) {
		$recordId = (int) $recordId;
		$module = trim((string) $module);
		$resultLabel = trim((string) $resultLabel);
		if ($recordId <= 0 || $module === '' || $resultLabel === '') {
			return 0;
		}
		global $current_user;
		if ($userId === null || (int) $userId <= 0) {
			$userId = (!empty($current_user) && !empty($current_user->id)) ? (int) $current_user->id : 0;
		}
		self::install();
		$adb = PearDatabase::getInstance();
		$userName = '';
		if ((int) $userId > 0) {
			$res = $adb->pquery('SELECT first_name, last_name, user_name FROM vtiger_users WHERE id = ?', array((int) $userId));
			if ($res && $adb->num_rows($res) > 0) {
				$userName = trim($adb->query_result($res, 0, 'first_name') . ' ' . $adb->query_result($res, 0, 'last_name'));
				if ($userName === '') {
					$userName = (string) $adb->query_result($res, 0, 'user_name');
				}
			}
		}
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'INSERT INTO mk_care_activity (module, record_id, step_code, result_label, note, user_id, user_name, created_at)
			 VALUES (?,?,?,?,?,?,?,?)',
			array($module, $recordId, substr((string) $stepCode, 0, 32), $resultLabel, trim((string) $note), (int) $userId, $userName, $now)
		);
		return (int) $adb->getLastInsertID();
	}

	public static function listFor($module, $recordId, $limit = 30) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return array();
		}
		self::install();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(80, (int) $limit));
		$res = $adb->pquery(
			'SELECT step_code, result_label, note, user_name, created_at
			 FROM mk_care_activity
			 WHERE module = ? AND record_id = ?
			 ORDER BY created_at DESC, id DESC
			 LIMIT ' . $limit,
			array($module, $recordId)
		);
		$rows = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$rows[] = array(
					'step' => $row['step_code'],
					'result' => decode_html((string) $row['result_label']),
					'note' => decode_html((string) $row['note']),
					'user' => decode_html((string) $row['user_name']),
					'at' => $row['created_at'],
				);
			}
		}
		return $rows;
	}
}
