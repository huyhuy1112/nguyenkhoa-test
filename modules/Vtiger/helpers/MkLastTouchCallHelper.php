<?php
/*+***********************************************************************************
 * Shared Last Touch Call core (max 3, ~5h gap). Module wrappers supply table / column.
 *************************************************************************************/

class Vtiger_MkLastTouchCallHelper {

	const MAX_CALLS = 3;
	const GAP_HOURS = 5;
	const RESULT_ANSWERED = 'Nghe máy';
	const RESULT_MISSED = 'Không nghe máy';
	const TZ = 'Asia/Ho_Chi_Minh';

	/**
	 * @param array $cfg module, table, record_col, notif_prefix, display_name_cb|null, on_bump_cb|null, stop_hint
	 */
	public static function ensureSchema(array $cfg) {
		static $done = array();
		$key = $cfg['table'];
		if (!empty($done[$key])) {
			return;
		}
		$done[$key] = true;
		$adb = PearDatabase::getInstance();
		$table = $cfg['table'];
		$col = $cfg['record_col'];
		$uniq = 'uniq_' . preg_replace('/[^a-z0-9_]/i', '', $col) . '_call_n';
		$idx = 'idx_' . preg_replace('/[^a-z0-9_]/i', '', $col) . '_called';
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				{$col} INT UNSIGNED NOT NULL,
				call_n TINYINT UNSIGNED NOT NULL,
				called_at DATETIME NOT NULL,
				result_label VARCHAR(64) NOT NULL,
				note TEXT NULL,
				activity_id INT UNSIGNED NULL,
				reminder_activity_id INT UNSIGNED NULL,
				created_by INT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				UNIQUE KEY {$uniq} ({$col}, call_n),
				KEY {$idx} ({$col}, called_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
	}

	public static function allowedResults() {
		return array(self::RESULT_MISSED, self::RESULT_ANSWERED);
	}

	public static function emptySummary($stopHint = '') {
		return array(
			'calls' => array(),
			'count' => 0,
			'next_n' => 1,
			'can_add' => true,
			'max_calls' => self::MAX_CALLS,
			'gap_hours' => self::GAP_HOURS,
			'last_at' => '',
			'last_at_label' => '',
			'reminder_at' => '',
			'reminder_at_label' => '',
			'results' => self::allowedResults(),
			'hint' => $stopHint !== '' ? $stopHint : self::defaultFlowHint(''),
		);
	}

	public static function defaultFlowHint($answeredNote = '') {
		$base = 'Call #1 → ' . self::GAP_HOURS . ' giờ → #2 → #3. Không nghe máy: nhắc sau '
			. self::GAP_HOURS . ' giờ.';
		if ($answeredNote !== '') {
			return $base . ' ' . $answeredNote;
		}
		return $base . ' Nghe máy → dừng chuỗi gọi.';
	}

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

	public static function getCalls(array $cfg, $recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return array();
		}
		self::ensureSchema($cfg);
		$adb = PearDatabase::getInstance();
		$col = $cfg['record_col'];
		$table = $cfg['table'];
		$res = $adb->pquery(
			"SELECT id, call_n, called_at, result_label, note, activity_id, reminder_activity_id
			 FROM {$table}
			 WHERE {$col} = ?
			 ORDER BY call_n ASC",
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

	public static function summaryFromCalls(array $calls, $stopHint = '', $answeredHint = '') {
		$count = count($calls);
		$nextN = $count + 1;
		$canAdd = $count < self::MAX_CALLS;
		$lastAt = $count > 0 ? $calls[$count - 1]['called_at'] : '';
		$lastResult = $count > 0 ? $calls[$count - 1]['result'] : '';
		$reminderAt = '';
		$hint = $stopHint !== '' ? $stopHint : self::defaultFlowHint('');
		if ($count > 0 && $lastResult === self::RESULT_ANSWERED) {
			$canAdd = false;
			$hint = $answeredHint !== ''
				? $answeredHint
				: 'Đã nghe máy — kết thúc chuỗi Last Touch Call.';
		} elseif ($count >= self::MAX_CALLS) {
			$canAdd = false;
			$hint = 'Đã đủ ' . self::MAX_CALLS . ' lần gọi Last Touch.';
		} elseif ($count > 0 && $canAdd) {
			$reminderTs = strtotime($lastAt . ' +' . self::GAP_HOURS . ' hours');
			$reminderAt = date('Y-m-d H:i:s', $reminderTs);
			$hint = 'Còn quyền gọi lần ' . $nextN . '. Sau ' . self::GAP_HOURS
				. ' giờ hệ thống báo chuông Thông báo (~ ' . self::formatStamp($reminderAt) . ').';
		}
		return array(
			'calls' => $calls,
			'count' => $count,
			'next_n' => $canAdd ? $nextN : 0,
			'can_add' => $canAdd,
			'max_calls' => self::MAX_CALLS,
			'gap_hours' => self::GAP_HOURS,
			'last_at' => $lastAt,
			'last_at_label' => self::formatStamp($lastAt),
			'reminder_at' => $reminderAt,
			'reminder_at_label' => self::formatStamp($reminderAt),
			'results' => self::allowedResults(),
			'hint' => $hint,
		);
	}

	public static function getSummary(array $cfg, $recordId) {
		return self::summaryFromCalls(
			self::getCalls($cfg, $recordId),
			isset($cfg['flow_hint']) ? $cfg['flow_hint'] : '',
			isset($cfg['answered_hint']) ? $cfg['answered_hint'] : ''
		);
	}

	/**
	 * Batch summaries for list APIs.
	 * @return array int recordId => summary
	 */
	public static function getSummariesForIds(array $cfg, array $ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$map = array();
		foreach ($ids as $id) {
			$map[$id] = self::emptySummary(isset($cfg['flow_hint']) ? $cfg['flow_hint'] : '');
		}
		if (empty($ids)) {
			return $map;
		}
		self::ensureSchema($cfg);
		$adb = PearDatabase::getInstance();
		$col = $cfg['record_col'];
		$table = $cfg['table'];
		$res = $adb->pquery(
			"SELECT id, {$col} AS rid, call_n, called_at, result_label, note, activity_id, reminder_activity_id
			 FROM {$table}
			 WHERE {$col} IN (" . generateQuestionMarks($ids) . ")
			 ORDER BY {$col} ASC, call_n ASC",
			$ids
		);
		$byId = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$rid = (int) $row['rid'];
				$calledAt = (string) $row['called_at'];
				$n = (int) $row['call_n'];
				$result = decode_html((string) $row['result_label']);
				$note = trim(decode_html((string) $row['note']));
				if (!isset($byId[$rid])) {
					$byId[$rid] = array();
				}
				$byId[$rid][] = array(
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
		foreach ($ids as $id) {
			$calls = isset($byId[$id]) ? $byId[$id] : array();
			$map[$id] = self::summaryFromCalls(
				$calls,
				isset($cfg['flow_hint']) ? $cfg['flow_hint'] : '',
				isset($cfg['answered_hint']) ? $cfg['answered_hint'] : ''
			);
		}
		return $map;
	}

	public static function logCall(array $cfg, $recordId, $result, $note = '', $userId = null) {
		global $current_user;
		$recordId = (int) $recordId;
		$module = $cfg['module'];
		if ($recordId <= 0) {
			throw new Exception('Bản ghi không hợp lệ.');
		}
		if (!Users_Privileges_Model::isPermitted($module, 'EditView', $recordId)
			&& !Users_Privileges_Model::isPermitted($module, 'Save', $recordId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$result = trim((string) $result);
		if (!in_array($result, self::allowedResults(), true)) {
			throw new Exception('Kết quả cuộc gọi phải là "Nghe máy" hoặc "Không nghe máy".');
		}
		$note = trim((string) $note);
		if ($userId === null || (int) $userId <= 0) {
			$userId = (int) $current_user->id;
		}

		self::ensureSchema($cfg);
		$summary = self::getSummary($cfg, $recordId);
		if (empty($summary['can_add'])) {
			throw new Exception($summary['hint'] !== '' ? $summary['hint'] : 'Không thể ghi thêm cuộc gọi Last Touch.');
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
		self::linkActivity($module, $recordId, $activityId);

		$adb = PearDatabase::getInstance();
		$col = $cfg['record_col'];
		$table = $cfg['table'];
		$adb->pquery(
			"INSERT INTO {$table}
				({$col}, call_n, called_at, result_label, note, activity_id, created_by, created_at)
			 VALUES (?,?,?,?,?,?,?,?)",
			array($recordId, $callN, $now, $result, $note !== '' ? $note : null, $activityId, (int) $userId, $now)
		);
		$logId = (int) $adb->getLastInsertID();

		if (!empty($cfg['on_bump']) && is_callable($cfg['on_bump'])) {
			call_user_func($cfg['on_bump'], $recordId, $now);
		}

		$reminderActivityId = 0;
		$notifPrefix = isset($cfg['notif_prefix']) ? $cfg['notif_prefix'] : 'mk_lt_call_';
		if ($result === self::RESULT_ANSWERED) {
			self::cancelPendingNotifications($notifPrefix, $recordId);
		} elseif ($callN < self::MAX_CALLS) {
			$reminderAt = self::addHours($now, self::GAP_HOURS);
			$nextN = $callN + 1;
			$reminderSubject = 'Nhắc gọi Call #' . $nextN . ' (sau ' . self::GAP_HOURS . ' giờ)';
			$reminderActivityId = self::createCallActivity(
				$recordId,
				$reminderSubject,
				'Last Touch: gọi lại lần ' . $nextN . ' sau khi không nghe máy lần ' . $callN . '.',
				$reminderAt,
				$userId,
				false
			);
			self::linkActivity($module, $recordId, $reminderActivityId);
			$adb->pquery(
				"UPDATE {$table} SET reminder_activity_id = ? WHERE id = ?",
				array($reminderActivityId, $logId)
			);
			self::scheduleMainNotification($cfg, $recordId, $userId, $callN, $nextN, $reminderAt);
		}

		$out = self::getSummary($cfg, $recordId);
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

	protected static function scheduleMainNotification(array $cfg, $recordId, $userId, $fromN, $nextN, $deliverAt) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			$name = self::recordDisplayName($cfg, $recordId);
			$label = isset($cfg['label_short']) ? $cfg['label_short'] : $cfg['module'];
			$msg = 'Nhắc gọi Call #' . (int) $nextN . ' — ' . $label . ': ' . $name
				. '. Không nghe máy lần ' . (int) $fromN
				. '. Đã đủ ' . self::GAP_HOURS . ' giờ, hãy gọi lại.';
			$prefix = isset($cfg['notif_prefix']) ? $cfg['notif_prefix'] : 'mk_lt_call_';
			Vtiger_NotificationSchedule::schedule(
				(int) $userId,
				$cfg['module'],
				(int) $recordId,
				$msg,
				$deliverAt,
				$prefix . (int) $recordId . '_n' . (int) $nextN
			);
		} catch (Exception $e) {
			// best-effort
		}
	}

	protected static function cancelPendingNotifications($prefix, $recordId) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			Vtiger_NotificationSchedule::cancelBySourcePrefix($prefix . (int) $recordId . '_');
		} catch (Exception $e) {
			// ignore
		}
	}

	protected static function recordDisplayName(array $cfg, $recordId) {
		if (!empty($cfg['display_name']) && is_callable($cfg['display_name'])) {
			return (string) call_user_func($cfg['display_name'], $recordId);
		}
		try {
			$rec = Vtiger_Record_Model::getInstanceById((int) $recordId, $cfg['module']);
			$name = trim((string) $rec->getName());
			if ($name !== '') {
				return $name;
			}
		} catch (Exception $e) {
			// fall through
		}
		return $cfg['module'] . ' #' . (int) $recordId;
	}

	public static function nowLocal() {
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

	protected static function linkActivity($module, $recordId, $activityId) {
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
			array($recordId, $module, $activityId, 'Calendar')
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
