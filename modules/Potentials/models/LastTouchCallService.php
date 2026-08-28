<?php
/*+***********************************************************************************
 * Potentials Last Touch — Call (max 3, ~5h). No auto-convert (already Opp).
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/MkLastTouchCallHelper.php';
require_once 'modules/Potentials/models/ModernService.php';

class Potentials_LastTouchCallService {

	const MODULE = 'Potentials';
	const MAX_CALLS = 3;
	const GAP_HOURS = 5;
	const RESULT_ANSWERED = 'Nghe máy';
	const RESULT_MISSED = 'Không nghe máy';

	protected static function cfg() {
		return array(
			'module' => self::MODULE,
			'table' => 'bace_potential_last_touch_call',
			'record_col' => 'potentialid',
			'notif_prefix' => 'opp_lt_call_',
			'label_short' => 'Cơ hội',
			'flow_hint' => Vtiger_MkLastTouchCallHelper::defaultFlowHint('Nghe máy → dừng chuỗi gọi.'),
			'answered_hint' => 'Đã nghe máy — kết thúc chuỗi Last Touch Call.',
			'on_bump' => array('Potentials_LastTouchCallService', 'bumpLastTouch'),
			'display_name' => array('Potentials_LastTouchCallService', 'recordDisplayName'),
		);
	}

	public static function ensureSchema($adb = null) {
		Vtiger_MkLastTouchCallHelper::ensureSchema(self::cfg());
		Potentials_ModernService::ensureProfileSchema($adb);
		self::ensureProfileLastTouchColumn($adb);
	}

	protected static function ensureProfileLastTouchColumn($adb = null) {
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$res = $adb->pquery("SHOW COLUMNS FROM bace_potential_profile LIKE 'last_touch'", array());
		if ($res && $adb->num_rows($res) > 0) {
			return;
		}
		$adb->pquery('ALTER TABLE bace_potential_profile ADD COLUMN last_touch DATETIME NULL', array());
	}

	public static function countCalls($recordId) {
		return count(self::getCalls($recordId));
	}

	public static function getCalls($recordId) {
		self::ensureSchema();
		return Vtiger_MkLastTouchCallHelper::getCalls(self::cfg(), $recordId);
	}

	public static function getSummary($recordId) {
		self::ensureSchema();
		return Vtiger_MkLastTouchCallHelper::getSummary(self::cfg(), $recordId);
	}

	public static function getSummariesForIds(array $ids) {
		self::ensureSchema();
		return Vtiger_MkLastTouchCallHelper::getSummariesForIds(self::cfg(), $ids);
	}

	public static function formatStamp($ymdHis) {
		return Vtiger_MkLastTouchCallHelper::formatStamp($ymdHis);
	}

	public static function formatLogLine($n, $calledAt, $result, $note = '') {
		return Vtiger_MkLastTouchCallHelper::formatLogLine($n, $calledAt, $result, $note);
	}

	public static function logCall($recordId, $result, $note = '', $userId = null) {
		self::ensureSchema();
		return Vtiger_MkLastTouchCallHelper::logCall(self::cfg(), $recordId, $result, $note, $userId);
	}

	public static function bumpLastTouch($recordId, $when) {
		$adb = PearDatabase::getInstance();
		self::ensureProfileLastTouchColumn($adb);
		$recordId = (int) $recordId;
		$exists = $adb->pquery(
			'SELECT potentialid FROM bace_potential_profile WHERE potentialid = ?',
			array($recordId)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_potential_profile SET last_touch = ?, modified_at = ? WHERE potentialid = ?',
				array($when, $when, $recordId)
			);
		} else {
			$adb->pquery(
				'INSERT INTO bace_potential_profile (potentialid, last_touch, modified_at) VALUES (?,?,?)',
				array($recordId, $when, $when)
			);
		}
	}

	public static function recordDisplayName($recordId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT potentialname FROM vtiger_potential WHERE potentialid = ?',
			array((int) $recordId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$name = trim(decode_html((string) $adb->query_result($res, 0, 'potentialname')));
			if ($name !== '') {
				return $name;
			}
		}
		return 'Opp #' . (int) $recordId;
	}
}
