<?php
/*+***********************************************************************************
 * Leads Last Touch — chỉ dành cho Call (tối đa 3 lần, cách ~5 giờ).
 * Call #N + kết quả; "Nghe máy" → convert Opp; "Không nghe máy" → nhắc lần sau.
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';
require_once 'modules/Leads/models/CommerceService.php';
require_once 'modules/Leads/models/ConvertService.php';

class Leads_LastTouchCallService {

	const MODULE = 'Leads';
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
			"CREATE TABLE IF NOT EXISTS bace_lead_last_touch_call (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				leadid INT UNSIGNED NOT NULL,
				call_n TINYINT UNSIGNED NOT NULL,
				called_at DATETIME NOT NULL,
				result_label VARCHAR(64) NOT NULL,
				note TEXT NULL,
				activity_id INT UNSIGNED NULL,
				reminder_activity_id INT UNSIGNED NULL,
				created_by INT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				UNIQUE KEY uniq_lead_call_n (leadid, call_n),
				KEY idx_lead_called (leadid, called_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
	}

	public static function allowedResults() {
		return array(self::RESULT_MISSED, self::RESULT_ANSWERED);
	}

	public static function countCalls($leadId) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return 0;
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT COUNT(*) AS c FROM bace_lead_last_touch_call WHERE leadid = ?',
			array($leadId)
		);
		return ($res && $adb->num_rows($res) > 0) ? (int)$adb->query_result($res, 0, 'c') : 0;
	}

	public static function getCalls($leadId) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return array();
		}
		self::ensureSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT id, call_n, called_at, result_label, note, activity_id, reminder_activity_id
			 FROM bace_lead_last_touch_call
			 WHERE leadid = ?
			 ORDER BY call_n ASC',
			array($leadId)
		);
		$rows = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$calledAt = (string)$row['called_at'];
				$n = (int)$row['call_n'];
				$result = decode_html((string)$row['result_label']);
				$note = trim(decode_html((string)$row['note']));
				$rows[] = array(
					'id' => (int)$row['id'],
					'n' => $n,
					'called_at' => $calledAt,
					'called_at_label' => self::formatStamp($calledAt),
					'result' => $result,
					'note' => $note,
					'activity_id' => $row['activity_id'] !== null ? (int)$row['activity_id'] : 0,
					'reminder_activity_id' => $row['reminder_activity_id'] !== null ? (int)$row['reminder_activity_id'] : 0,
					'label' => self::formatLogLine($n, $calledAt, $result, $note),
				);
			}
		}
		return $rows;
	}

	public static function getSummary($leadId) {
		$calls = self::getCalls($leadId);
		$count = count($calls);
		$nextN = $count + 1;
		$canAdd = $count < self::MAX_CALLS;
		$lastAt = $count > 0 ? $calls[$count - 1]['called_at'] : '';
		$lastResult = $count > 0 ? $calls[$count - 1]['result'] : '';
		$reminderAt = '';
		$hint = 'Last Touch chỉ dành cho Call. Gọi lần 1 → gắn Call #1; khoảng '
			. self::GAP_HOURS . ' giờ sau gọi lần 2; lần 3 nhắc sau '
			. self::GAP_HOURS . ' giờ (chuông Thông báo). Kết quả "Nghe máy" → chuyển Opp.';

		if ($count > 0 && $lastResult === self::RESULT_ANSWERED) {
			$canAdd = false;
			$hint = 'Đã nghe máy — Lead sẽ / đã chuyển sang Opportunity.';
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

	public static function formatStamp($ymdHis) {
		$ymdHis = trim((string)$ymdHis);
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
		$line = self::formatStamp($calledAt) . ' Call #' . (int)$n . ' Kết quả: ' . $result;
		$note = trim((string)$note);
		if ($note !== '') {
			$line .= ' Ghi chú: ' . $note;
		}
		return $line;
	}

	public static function logCall($leadId, $result, $note = '', $userId = null) {
		global $current_user;
		$leadId = (int)Leads_ModernService::resolveLeadRecordId($leadId);
		if ($leadId <= 0) {
			throw new Exception('Lead không hợp lệ.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $leadId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'Save', $leadId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$result = trim((string)$result);
		if (!in_array($result, self::allowedResults(), true)) {
			throw new Exception('Kết quả cuộc gọi phải là "Nghe máy" hoặc "Không nghe máy".');
		}
		$note = trim((string)$note);
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}

		// Không chặn theo Opp liên kết sẵn — "Không nghe máy" luôn được ghi.
		// Chỉ convert khi kết quả = "Nghe máy".

		self::ensureSchema();
		$summary = self::getSummary($leadId);
		if (empty($summary['can_add'])) {
			throw new Exception($summary['hint'] !== '' ? $summary['hint'] : 'Không thể ghi thêm cuộc gọi Last Touch.');
		}
		$callN = (int)$summary['next_n'];
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

		$activityId = self::createCallActivity($leadId, $subject, $note, $now, $userId, true);
		Leads_CommerceService::linkActivityToLead($leadId, $activityId);

		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO bace_lead_last_touch_call
				(leadid, call_n, called_at, result_label, note, activity_id, created_by, created_at)
			 VALUES (?,?,?,?,?,?,?,?)',
			array($leadId, $callN, $now, $result, $note !== '' ? $note : null, $activityId, (int)$userId, $now)
		);
		$logId = (int)$adb->getLastInsertID();

		self::bumpLastTouch($leadId, $now);
		self::syncGoiLanTag($leadId, $callN, $userId);

		$convert = null;
		$reminderActivityId = 0;
		if ($result === self::RESULT_ANSWERED) {
			self::cancelPendingNotifications($leadId);
			// Không ghi đè "Hành động tiếp theo" — đó là ghi chú tự do của user.
			try {
				$convert = Leads_ConvertService::convertLead($leadId, array(
					'create_account' => false,
					'order_category' => 'Internal',
				));
			} catch (Exception $e) {
				throw new Exception('Đã ghi Call #' . $callN . ' (Nghe máy) nhưng convert Opp lỗi: ' . $e->getMessage());
			}
		} else {
			// Không nghe máy: log + nhắc Calendar + chuông — không đụng next_action.
			if ($callN < self::MAX_CALLS) {
				$reminderAt = self::addHours($now, self::GAP_HOURS);
				$nextN = $callN + 1;
				$reminderSubject = 'Nhắc gọi Call #' . $nextN . ' (sau ' . self::GAP_HOURS . ' giờ)';
				$reminderActivityId = self::createCallActivity(
					$leadId,
					$reminderSubject,
					'Last Touch: gọi lại lần ' . $nextN . ' sau khi không nghe máy lần ' . $callN . '.',
					$reminderAt,
					$userId,
					false
				);
				Leads_CommerceService::linkActivityToLead($leadId, $reminderActivityId);
				$adb->pquery(
					'UPDATE bace_lead_last_touch_call SET reminder_activity_id = ? WHERE id = ?',
					array($reminderActivityId, $logId)
				);
				self::scheduleMainNotification($leadId, $userId, $callN, $nextN, $reminderAt);
			}
		}

		$out = self::getSummary($leadId);
		$out['lead'] = Leads_ModernService::getLead($leadId, $userId);
		$out['convert'] = $convert;
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

	protected static function scheduleMainNotification($leadId, $userId, $fromN, $nextN, $deliverAt) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			$leadName = self::leadDisplayName($leadId);
			$msg = 'Nhắc gọi Call #' . (int)$nextN . ' — Lead: ' . $leadName
				. '. Không nghe máy lần ' . (int)$fromN
				. '. Đã đủ ' . self::GAP_HOURS . ' giờ, hãy gọi lại.';
			Vtiger_NotificationSchedule::schedule(
				(int)$userId,
				'Leads',
				(int)$leadId,
				$msg,
				$deliverAt,
				'lead_lt_call_' . (int)$leadId . '_n' . (int)$nextN
			);
		} catch (Exception $e) {
			// best-effort
		}
	}

	protected static function cancelPendingNotifications($leadId) {
		try {
			require_once 'modules/Vtiger/models/NotificationSchedule.php';
			Vtiger_NotificationSchedule::cancelBySourcePrefix('lead_lt_call_' . (int)$leadId . '_');
		} catch (Exception $e) {
			// ignore
		}
	}

	protected static function leadDisplayName($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT firstname, lastname FROM vtiger_leaddetails WHERE leadid = ?',
			array((int)$leadId)
		);
		if (!$res || $adb->num_rows($res) === 0) {
			return 'Lead #' . (int)$leadId;
		}
		$fn = trim((string)$adb->query_result($res, 0, 'firstname'));
		$ln = trim((string)$adb->query_result($res, 0, 'lastname'));
		$name = trim($ln . ' ' . $fn);
		if ($name === '' || $name === '.') {
			$name = trim($fn . ' ' . $ln);
		}
		return $name !== '' ? $name : ('Lead #' . (int)$leadId);
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
			$dt->modify('+' . (int)$hours . ' hours');
			return $dt->format('Y-m-d H:i:s');
		} catch (Exception $e) {
			return date('Y-m-d H:i:s', strtotime($ymdHis . ' +' . (int)$hours . ' hours'));
		}
	}

	protected static function bumpLastTouch($leadId, $when) {
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery('SELECT leadid FROM bace_lead_profile WHERE leadid = ?', array($leadId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_lead_profile SET last_touch = ?, modified_at = ? WHERE leadid = ?',
				array($when, $when, $leadId)
			);
		} else {
			$adb->pquery(
				'INSERT INTO bace_lead_profile (leadid, last_touch, is_modern, created_at, modified_at)
				 VALUES (?,?,1,?,?)',
				array($leadId, $when, $when, $when)
			);
		}
	}

	protected static function syncGoiLanTag($leadId, $callN, $userId) {
		Leads_ModernService::setGoiLanTag($leadId, $callN, $userId);
	}

	/**
	 * Tạo Calendar Call (đã gọi = Held; nhắc = Planned).
	 */
	protected static function createCallActivity($leadId, $subject, $description, $whenYmdHis, $userId, $held) {
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
		$record->set('assigned_user_id', (int)$userId);
		$record->set('parent_id', (int)$leadId);
		$record->set('visibility', 'Public');
		$record->set('description', (string)$description);
		if ($held) {
			$record->set('eventstatus', 'Held');
			$record->set('taskstatus', 'Completed');
		} else {
			$record->set('eventstatus', 'Planned');
			$record->set('taskstatus', 'Not Started');
			$record->set('taskpriority', 'High');
			// Nhắc popup ~5 phút trước giờ gọi
			$record->set('set_reminder', 'Yes');
			$record->set('remdays', '0');
			$record->set('remhrs', '0');
			$record->set('remmin', '5');
		}
		$record->save();
		$activityId = (int)$record->getId();
		if ($activityId <= 0) {
			throw new Exception('Không tạo được activity Call trên Calendar.');
		}
		return $activityId;
	}
}
