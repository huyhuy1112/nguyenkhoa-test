<?php
/*+***********************************************************************************
 * ServiceContracts Last Touch — Call (tối đa 3 lần, cách ~5 giờ).
 * Giống Leads nhưng không convert Opp (đã là khách nhượng quyền).
 *************************************************************************************/

require_once 'modules/ServiceContracts/models/ModernService.php';

class ServiceContracts_LastTouchCallService {

	const MODULE = 'ServiceContracts';
	const MAX_CALLS = 3;
	const GAP_HOURS = 5;
	const RESULT_ANSWERED = 'Nghe máy';
	const RESULT_MISSED = 'Không nghe máy';
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
	}

	public static function allowedResults() {
		return array(self::RESULT_MISSED, self::RESULT_ANSWERED);
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
		$count = count($calls);
		$nextN = $count + 1;
		$canAdd = $count < self::MAX_CALLS;
		$lastAt = $count > 0 ? $calls[$count - 1]['called_at'] : '';
		$lastResult = $count > 0 ? $calls[$count - 1]['result'] : '';
		$reminderAt = '';
		$hint = 'Call #1 → ' . self::GAP_HOURS . ' giờ → #2 → #3. Không nghe máy: nhắc sau '
			. self::GAP_HOURS . ' giờ. Nghe máy → dừng chuỗi gọi.';

		if ($count > 0 && $lastResult === self::RESULT_ANSWERED) {
			$canAdd = false;
			$hint = 'Đã nghe máy — kết thúc chuỗi Last Touch Call.';
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

	public static function logCall($recordId, $result, $note = '', $userId = null) {
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
			throw new Exception('Kết quả cuộc gọi phải là "Nghe máy" hoặc "Không nghe máy".');
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
		self::syncContactStatus($recordId, $result, $callN);
		self::syncInteractionNote($recordId, $callN, $now, $result, $note);

		$reminderActivityId = 0;
		if ($result === self::RESULT_ANSWERED) {
			self::cancelPendingNotifications($recordId);
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
			self::linkActivity($recordId, $reminderActivityId);
			$adb->pquery(
				'UPDATE bace_sc_last_touch_call SET reminder_activity_id = ? WHERE id = ?',
				array($reminderActivityId, $logId)
			);
			self::scheduleMainNotification($recordId, $userId, $callN, $nextN, $reminderAt);
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

	protected static function syncContactStatus($recordId, $result, $callN) {
		// Nghe máy → Đã gọi / đã tư vấn (không convert Opp).
		// Không nghe máy → Ko nghe Máy Lần N.
		$status = $result === self::RESULT_ANSWERED
			? 'Đã gửi tư vấn'
			: ('Ko nghe Máy Lần ' . (int) $callN);
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_sc_profile SET contact_status = ?, modified_at = ? WHERE servicecontractsid = ?',
			array($status, self::nowLocal(), (int) $recordId)
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

	protected static function scheduleMainNotification($recordId, $userId, $fromN, $nextN, $deliverAt) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			$name = self::recordDisplayName($recordId);
			$msg = 'Nhắc gọi Call #' . (int) $nextN . ' — Khách NQ: ' . $name
				. '. Không nghe máy lần ' . (int) $fromN
				. '. Đã đủ ' . self::GAP_HOURS . ' giờ, hãy gọi lại.';
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
