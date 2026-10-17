<?php
/*+***********************************************************************************
 * ServiceContracts Last Touch — R1 nhượng quyền (mỗi tag tối đa 3, tổng tối đa 9).
 * Không nghe máy nhắc sau 3 giờ. Nghe máy gắn Đã gọi/Đã tư vấn, không sang Opp.
 *************************************************************************************/

require_once 'modules/ServiceContracts/models/ModernService.php';

class ServiceContracts_LastTouchCallService {

	const MODULE = 'ServiceContracts';
	const MAX_CALLS = 9;
	const TAG_MAX = 3;
	const GAP_HOURS = 3;
	const BAD_GAP_HOURS = 24;
	const RESULT_ANSWERED = 'Nghe máy';
	const RESULT_MISSED = 'Không nghe máy';
	const RESULT_BAD = 'Thuê bao/Sai số';
	const RESULT_CALLBACK = 'Gọi lại sau';
	const TZ = 'Asia/Ho_Chi_Minh';

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
			"CREATE TABLE IF NOT EXISTS bace_sc_last_touch_call (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				servicecontractsid INT UNSIGNED NOT NULL,
				call_n TINYINT UNSIGNED NOT NULL,
				called_at DATETIME NOT NULL,
				result_label VARCHAR(64) NOT NULL,
				note TEXT NULL,
				activity_id INT UNSIGNED NULL,
				reminder_activity_id INT UNSIGNED NULL,
				created_by INT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				UNIQUE KEY uniq_sc_call_n (servicecontractsid, call_n),
				KEY idx_sc_called (servicecontractsid, called_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
		self::ensureR1Columns($adb);
	}

	protected static function ensureR1Columns(PearDatabase $adb) {
		$exists = $adb->pquery("SHOW TABLES LIKE 'bace_sc_profile'", array());
		if (!$exists || $adb->num_rows($exists) < 1) {
			return;
		}
		$cols = array(
			'nq_r1_miss' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
			'nq_r1_bad' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
			'nq_r1_callback' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
			'nq_r1_stopped' => 'TINYINT(1) NOT NULL DEFAULT 0',
			'nq_callback_at' => 'DATETIME DEFAULT NULL',
		);
		foreach ($cols as $name => $def) {
			$check = $adb->pquery('SHOW COLUMNS FROM bace_sc_profile LIKE ?', array($name));
			if ($check && $adb->num_rows($check) > 0) {
				continue;
			}
			$adb->pquery("ALTER TABLE bace_sc_profile ADD COLUMN `{$name}` {$def}", array());
		}
	}

	public static function allowedResults() {
		return array(self::RESULT_MISSED, self::RESULT_BAD, self::RESULT_CALLBACK, self::RESULT_ANSWERED);
	}

	public static function countCalls($recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return 0;
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT COUNT(*) AS c FROM bace_sc_last_touch_call WHERE servicecontractsid = ?',
			array($recordId)
		);
		return ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'c') : 0;
	}

	public static function getCalls($recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return array();
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT id, call_n, called_at, result_label, note, activity_id, reminder_activity_id
			 FROM bace_sc_last_touch_call
			 WHERE servicecontractsid = ?
			 ORDER BY call_n ASC',
			array($recordId)
		);
		$rows = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$calledAt = (string) $row['called_at'];
				$n = (int) $row['call_n'];
				$result = decode_html((string) $row['result_label']);
				$note = trim(decode_html((string) $row['note']));
				$rows[] = array(
					'id' => (int) $row['id'],
					'n' => $n,
					'called_at' => $calledAt,
					'called_at_label' => self::formatStamp($calledAt),
					'result' => $result,
					'note' => $note,
					'activity_id' => $row['activity_id'] !== null ? (int) $row['activity_id'] : 0,
					'reminder_activity_id' => $row['reminder_activity_id'] !== null ? (int) $row['reminder_activity_id'] : 0,
					'label' => self::formatLogLine($n, $calledAt, $result, $note),
				);
			}
		}
		return $rows;
	}

	public static function getSummary($recordId) {
		$calls = self::getCalls($recordId);
		$stored = self::readStoredR1($recordId);
		return self::summaryFromCalls($calls, $stored);
	}

	/**
	 * @param array $calls
	 * @param array $stored miss,bad,callback,stopped
	 */
	public static function summaryFromCalls(array $calls, array $stored) {
		$r1 = self::effectiveR1($stored, $calls);
		$count = count($calls);
		$lastAt = $count > 0 ? $calls[$count - 1]['called_at'] : '';
		$lastResult = $count > 0 ? $calls[$count - 1]['result'] : '';
		$answered = $lastResult === self::RESULT_ANSWERED;
		$canAdd = !$answered && empty($r1['stopped']) && $count < self::MAX_CALLS;
		$hint = 'R1 · Chưa liên hệ được ' . $r1['miss'] . '/3 · Thuê bao/Sai số ' . $r1['bad']
			. '/3 · Gọi lại sau ' . $r1['callback'] . '/3. Không nghe máy nhắc sau '
			. self::GAP_HOURS . ' giờ. Nghe máy → Đã gọi/Đã tư vấn.';
		if ($answered) {
			$hint = 'Đã nghe máy — Liên hệ = Đã gọi/Đã tư vấn.';
		} elseif (!empty($r1['stopped'])) {
			$hint = 'Ngưng chăm sóc · Hết lượt liên hệ (một tag R1 đã đủ 3).';
		} elseif ($count >= self::MAX_CALLS) {
			$hint = 'Đã đủ ' . self::MAX_CALLS . ' lần gọi.';
		}
		return array(
			'calls' => $calls,
			'count' => $count,
			'next_n' => $canAdd ? ($count + 1) : 0,
			'can_add' => $canAdd,
			'max_calls' => self::MAX_CALLS,
			'gap_hours' => self::GAP_HOURS,
			'last_at' => $lastAt,
			'last_at_label' => self::formatStamp($lastAt),
			'reminder_at' => '',
			'reminder_at_label' => '',
			'results' => self::allowedResults(),
			'r1' => $r1,
			'hint' => $hint,
		);
	}

	/** Chuẩn ngày giờ hiển thị: dd/mm/yyyy HH:mm */
	public static function formatStamp($ymdHis) {
		$ymdHis = trim((string) $ymdHis);
		if ($ymdHis === '') {
			return '';
		}
		$ts = strtotime($ymdHis);
		if ($ts === false) {
			return $ymdHis;
		}
		return date('d/m/Y H:i', $ts);
	}

	public static function formatLogLine($n, $calledAt, $result, $note = '') {
		$line = self::formatStamp($calledAt) . ' Call #' . (int) $n . ' Kết quả: ' . $result;
		$note = trim((string) $note);
		if ($note !== '') {
			$line .= ' Ghi chú: ' . $note;
		}
		return $line;
	}

	public static function logCall($recordId, $result, $note = '', $userId = null, $callbackAt = '') {
		global $current_user;
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			throw new Exception('Khách nhượng quyền không hợp lệ.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $recordId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'Save', $recordId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$result = trim((string) $result);
		if (!in_array($result, self::allowedResults(), true)) {
			throw new Exception('Kết quả cuộc gọi phải là Nghe máy, Không nghe máy, Thuê bao/Sai số hoặc Gọi lại sau.');
		}
		$callbackAt = self::normalizeCallback($callbackAt);
		if ($result === self::RESULT_CALLBACK && $callbackAt === '') {
			throw new Exception('Gọi lại sau cần giờ hẹn cụ thể.');
		}
		$note = trim((string) $note);
		if ($userId === null || (int) $userId <= 0) {
			$userId = (int) $current_user->id;
		}

		self::ensureSchema();
		$summary = self::getSummary($recordId);
		if (empty($summary['can_add'])) {
			throw new Exception($summary['hint'] !== '' ? $summary['hint'] : 'Không thể ghi thêm cuộc gọi Last Touch.');
		}
		$before = isset($summary['r1']) ? $summary['r1'] : array('miss' => 0, 'bad' => 0, 'callback' => 0, 'stopped' => false);
		if ($result !== self::RESULT_ANSWERED) {
			$bucket = self::r1Bucket($result);
			if ($bucket !== '' && (int) $before[$bucket] >= self::TAG_MAX) {
				throw new Exception('Tag này đã đủ 3 lượt. Chọn kết quả R1 khác hoặc hồ sơ đã ngưng chăm sóc.');
			}
		}
		$callN = (int) $summary['next_n'];
		$now = self::nowLocal();

		$subject = 'Call #' . $callN . ' Kết quả: ' . $result;
		if ($note !== '') {
			$subject .= ' Ghi chú: ' . $note;
		}
		if (function_exists('mb_substr')) {
			$subject = mb_substr($subject, 0, 250, 'UTF-8');
		} else {
			$subject = substr($subject, 0, 250);
		}

		$activityId = self::createCallActivity($recordId, $subject, $note, $now, $userId, true);
		self::linkActivity($recordId, $activityId);

		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO bace_sc_last_touch_call
				(servicecontractsid, call_n, called_at, result_label, note, activity_id, created_by, created_at)
			 VALUES (?,?,?,?,?,?,?,?)',
			array($recordId, $callN, $now, $result, $note !== '' ? $note : null, $activityId, (int) $userId, $now)
		);
		$logId = (int) $adb->getLastInsertID();

		self::bumpLastTouch($recordId, $now);
		$r1After = self::writeR1($recordId, $result, $before, $callbackAt);
		self::syncContactStatus($recordId, $result, $r1After);
		self::syncInteractionNote($recordId, $callN, $now, $result, $note);

		$reminderActivityId = 0;
		self::cancelPendingNotifications($recordId);
		if ($result !== self::RESULT_ANSWERED && empty($r1After['stopped'])) {
			$reminderAt = self::reminderAtFor($result, $now, $callbackAt);
			$nextN = $callN + 1;
			$reminderSubject = 'Nhắc gọi Call #' . $nextN . ' · ' . self::contactLabel($result, $r1After);
			$reminderActivityId = self::createCallActivity(
				$recordId,
				$reminderSubject,
				self::contactLabel($result, $r1After),
				$reminderAt,
				$userId,
				false
			);
			self::linkActivity($recordId, $reminderActivityId);
			$adb->pquery(
				'UPDATE bace_sc_last_touch_call SET reminder_activity_id = ? WHERE id = ?',
				array($reminderActivityId, $logId)
			);
			self::scheduleMainNotification($recordId, $userId, $callN, $nextN, $reminderAt, $result, $r1After);
		}

		$out = self::getSummary($recordId);
		try {
			$out['contract'] = ServiceContracts_ModernService::getFranchise($recordId);
		} catch (Exception $e) {
			$out['contract'] = null;
		}
		$out['logged'] = array(
			'n' => $callN,
			'called_at' => $now,
			'called_at_label' => self::formatStamp($now),
			'result' => $result,
			'note' => $note,
			'label' => self::formatLogLine($callN, $now, $result, $note),
			'activity_id' => $activityId,
			'reminder_activity_id' => $reminderActivityId,
		);
		return $out;
	}

	protected static function syncContactStatus($recordId, $result, array $r1) {
		$status = !empty($r1['stopped'])
			? 'Ngưng chăm sóc'
			: ($result === self::RESULT_ANSWERED ? 'Đã gọi/Đã tư vấn' : self::tagLabel($result));
		$next = !empty($r1['stopped'])
			? 'Ngưng chăm sóc · Hết lượt liên hệ'
			: self::contactLabel($result, $r1);
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_sc_profile SET contact_status = ?, next_action = ?, modified_at = ? WHERE servicecontractsid = ?',
			array($status, $next, self::nowLocal(), (int) $recordId)
		);
	}

	/**
	 * Ghi chú Call #N → cột tương tác lần N (interaction_1/2/3).
	 */
	protected static function syncInteractionNote($recordId, $callN, $calledAt, $result, $note) {
		$callN = (int) $callN;
		if ($callN < 1 || $callN > 3) {
			return;
		}
		$col = 'interaction_' . $callN;
		$line = self::formatStamp($calledAt) . ' — ' . $result;
		$note = trim((string) $note);
		if ($note !== '') {
			$line .= "\n" . $note;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE bace_sc_profile SET {$col} = ?, modified_at = ? WHERE servicecontractsid = ?",
			array($line, self::nowLocal(), (int) $recordId)
		);
	}

	protected static function scheduleMainNotification($recordId, $userId, $fromN, $nextN, $deliverAt, $result, array $r1) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			$name = self::recordDisplayName($recordId);
			$msg = 'Nhắc gọi Call #' . (int) $nextN . ' — Khách NQ: ' . $name
				. '. ' . self::contactLabel($result, $r1);
			Vtiger_NotificationSchedule::schedule(
				(int) $userId,
				'ServiceContracts',
				(int) $recordId,
				$msg,
				$deliverAt,
				'sc_lt_call_' . (int) $recordId . '_n' . (int) $nextN
			);
		} catch (Exception $e) {
			// best-effort
		}
	}

	protected static function cancelPendingNotifications($recordId) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			Vtiger_NotificationSchedule::cancelBySourcePrefix('sc_lt_call_' . (int) $recordId . '_');
		} catch (Exception $e) {
			// ignore
		}
	}

	protected static function recordDisplayName($recordId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT subject FROM vtiger_servicecontracts WHERE servicecontractsid = ?',
			array((int) $recordId)
		);
		if (!$res || $adb->num_rows($res) === 0) {
			return 'KH NQ #' . (int) $recordId;
		}
		$name = trim(decode_html((string) $adb->query_result($res, 0, 'subject')));
		return $name !== '' ? $name : ('KH NQ #' . (int) $recordId);
	}

	protected static function r1Bucket($result) {
		if ($result === self::RESULT_MISSED) {
			return 'miss';
		}
		if ($result === self::RESULT_BAD) {
			return 'bad';
		}
		if ($result === self::RESULT_CALLBACK) {
			return 'callback';
		}
		return '';
	}

	protected static function tagLabel($result) {
		if ($result === self::RESULT_MISSED) {
			return 'Chưa liên hệ được';
		}
		if ($result === self::RESULT_BAD) {
			return 'Thuê bao/Sai số';
		}
		if ($result === self::RESULT_CALLBACK) {
			return 'Gọi lại sau';
		}
		return 'Đã gọi/Đã tư vấn';
	}

	protected static function contactLabel($result, array $r1) {
		if (!empty($r1['stopped'])) {
			return 'Ngưng chăm sóc · Hết lượt liên hệ';
		}
		if ($result === self::RESULT_ANSWERED) {
			return 'Đã gọi/Đã tư vấn';
		}
		$bucket = self::r1Bucket($result);
		$n = $bucket !== '' && isset($r1[$bucket]) ? (int) $r1[$bucket] : 0;
		if ($result === self::RESULT_MISSED) {
			return 'Gọi lại sau 3 giờ — chưa liên hệ được (R1 ' . $n . '/3)';
		}
		if ($result === self::RESULT_BAD) {
			return 'Xin lại số đúng (R1 · Thuê bao/Sai số ' . $n . '/3)';
		}
		return 'Gọi lại theo hẹn (R1 · Gọi lại sau ' . $n . '/3)';
	}

	protected static function readStoredR1($recordId) {
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT nq_r1_miss AS miss, nq_r1_bad AS bad, nq_r1_callback AS callback, nq_r1_stopped AS stopped
			 FROM bace_sc_profile WHERE servicecontractsid = ?',
			array((int) $recordId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array('miss' => 0, 'bad' => 0, 'callback' => 0, 'stopped' => 0);
		}
		return array(
			'miss' => (int) $adb->query_result($res, 0, 'miss'),
			'bad' => (int) $adb->query_result($res, 0, 'bad'),
			'callback' => (int) $adb->query_result($res, 0, 'callback'),
			'stopped' => (int) $adb->query_result($res, 0, 'stopped'),
		);
	}

	protected static function effectiveR1(array $stored, array $calls) {
		$miss = isset($stored['miss']) ? (int) $stored['miss'] : 0;
		$bad = isset($stored['bad']) ? (int) $stored['bad'] : 0;
		$callback = isset($stored['callback']) ? (int) $stored['callback'] : 0;
		$stopped = !empty($stored['stopped']);
		if (!$stopped && ($miss + $bad + $callback) === 0 && count($calls) > 0) {
			foreach ($calls as $call) {
				$result = isset($call['result']) ? (string) $call['result'] : '';
				if ($result === self::RESULT_MISSED) {
					$miss++;
				} elseif ($result === self::RESULT_BAD) {
					$bad++;
				} elseif ($result === self::RESULT_CALLBACK) {
					$callback++;
				}
			}
		}
		$miss = min(self::TAG_MAX, $miss);
		$bad = min(self::TAG_MAX, $bad);
		$callback = min(self::TAG_MAX, $callback);
		if ($miss >= self::TAG_MAX || $bad >= self::TAG_MAX || $callback >= self::TAG_MAX) {
			$stopped = true;
		}
		return array(
			'miss' => $miss,
			'bad' => $bad,
			'callback' => $callback,
			'stopped' => $stopped,
			'max' => self::TAG_MAX,
		);
	}

	protected static function writeR1($recordId, $result, array $before, $callbackAt) {
		$miss = (int) $before['miss'];
		$bad = (int) $before['bad'];
		$callback = (int) $before['callback'];
		if ($result === self::RESULT_MISSED) {
			$miss++;
		} elseif ($result === self::RESULT_BAD) {
			$bad++;
		} elseif ($result === self::RESULT_CALLBACK) {
			$callback++;
		}
		$miss = min(self::TAG_MAX, $miss);
		$bad = min(self::TAG_MAX, $bad);
		$callback = min(self::TAG_MAX, $callback);
		$stopped = ($result !== self::RESULT_ANSWERED) && ($miss >= self::TAG_MAX || $bad >= self::TAG_MAX || $callback >= self::TAG_MAX);
		$adb = PearDatabase::getInstance();
		$params = array($miss, $bad, $callback, $stopped ? 1 : 0, self::nowLocal());
		$sql = 'UPDATE bace_sc_profile
			 SET nq_r1_miss = ?, nq_r1_bad = ?, nq_r1_callback = ?, nq_r1_stopped = ?, modified_at = ?';
		if ($result === self::RESULT_CALLBACK && $callbackAt !== '') {
			$sql .= ', nq_callback_at = ?';
			$params[] = $callbackAt;
		}
		$sql .= ' WHERE servicecontractsid = ?';
		$params[] = (int) $recordId;
		$adb->pquery($sql, $params);
		return array(
			'miss' => $miss,
			'bad' => $bad,
			'callback' => $callback,
			'stopped' => $stopped,
			'max' => self::TAG_MAX,
		);
	}

	protected static function normalizeCallback($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return '';
		}
		$ts = strtotime(str_replace('T', ' ', $raw));
		if (!$ts) {
			return '';
		}
		$stamp = date('Y-m-d H:i:s', $ts);
		try {
			require_once 'modules/Vtiger/models/R1ReminderSettings.php';
			return Vtiger_R1ReminderSettings::snapToBusinessHours($stamp);
		} catch (Exception $e) {
			return $stamp;
		}
	}

	protected static function reminderAtFor($result, $now, $callbackAt) {
		if ($result === self::RESULT_CALLBACK && $callbackAt !== '') {
			return $callbackAt;
		}
		$hours = $result === self::RESULT_BAD ? self::BAD_GAP_HOURS : self::GAP_HOURS;
		try {
			require_once 'modules/Vtiger/models/R1ReminderSettings.php';
			return Vtiger_R1ReminderSettings::addGapWithinBusinessHours($now, $hours);
		} catch (Exception $e) {
			return self::addHours($now, $hours);
		}
	}

	protected static function nowLocal() {
		try {
			$dt = new DateTime('now', new DateTimeZone(self::TZ));
			return $dt->format('Y-m-d H:i:s');
		} catch (Exception $e) {
			return date('Y-m-d H:i:s');
		}
	}

	protected static function addHours($ymdHis, $hours) {
		try {
			$dt = new DateTime($ymdHis, new DateTimeZone(self::TZ));
			$dt->modify('+' . (int) $hours . ' hours');
			return $dt->format('Y-m-d H:i:s');
		} catch (Exception $e) {
			return date('Y-m-d H:i:s', strtotime($ymdHis . ' +' . (int) $hours . ' hours'));
		}
	}

	protected static function bumpLastTouch($recordId, $when) {
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			'SELECT servicecontractsid FROM bace_sc_profile WHERE servicecontractsid = ?',
			array((int) $recordId)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_sc_profile SET last_touch = ?, modified_at = ? WHERE servicecontractsid = ?',
				array($when, $when, (int) $recordId)
			);
		}
	}

	protected static function linkActivity($recordId, $activityId) {
		$recordId = (int) $recordId;
		$activityId = (int) $activityId;
		if ($recordId <= 0 || $activityId <= 0) {
			return;
		}
		$adb = PearDatabase::getInstance();
		$check = $adb->pquery(
			'SELECT 1 FROM vtiger_crmentityrel
			 WHERE (crmid = ? AND relcrmid = ?) OR (crmid = ? AND relcrmid = ?) LIMIT 1',
			array($recordId, $activityId, $activityId, $recordId)
		);
		if ($check && $adb->num_rows($check) > 0) {
			return;
		}
		$adb->pquery(
			'INSERT INTO vtiger_crmentityrel (crmid, module, relcrmid, relmodule) VALUES (?,?,?,?)',
			array($recordId, self::MODULE, $activityId, 'Calendar')
		);
	}

	protected static function createCallActivity($recordId, $subject, $description, $whenYmdHis, $userId, $held) {
		$date = date('Y-m-d', strtotime($whenYmdHis));
		$timeStart = date('H:i:s', strtotime($whenYmdHis));
		$endTs = strtotime($whenYmdHis) + 15 * 60;
		$timeEnd = date('H:i:s', $endTs);
		$dueDate = date('Y-m-d', $endTs);

		$record = Vtiger_Record_Model::getCleanInstance('Calendar');
		$record->set('mode', '');
		$record->set('subject', $subject);
		$record->set('activitytype', 'Call');
		$record->set('date_start', $date);
		$record->set('time_start', $timeStart);
		$record->set('due_date', $dueDate);
		$record->set('time_end', $timeEnd);
		$record->set('assigned_user_id', (int) $userId);
		$record->set('parent_id', (int) $recordId);
		$record->set('visibility', 'Public');
		$record->set('description', (string) $description);
		if ($held) {
			$record->set('eventstatus', 'Held');
			$record->set('taskstatus', 'Completed');
		} else {
			$record->set('eventstatus', 'Planned');
			$record->set('taskstatus', 'Not Started');
			$record->set('taskpriority', 'High');
			$record->set('set_reminder', 'Yes');
			$record->set('remdays', '0');
			$record->set('remhrs', '0');
			$record->set('remmin', '5');
		}
		$record->save();
		$activityId = (int) $record->getId();
		if ($activityId <= 0) {
			throw new Exception('Không tạo được activity Call trên Calendar.');
		}
		return $activityId;
	}
}
