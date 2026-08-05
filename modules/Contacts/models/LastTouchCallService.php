<?php
/*+***********************************************************************************
 * Contacts (Khách hàng) Last Touch — Call (max 3, ~5h). No auto-convert.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/MkLastTouchCallHelper.php';

class Contacts_LastTouchCallService {

	const MODULE = 'Contacts';
	const MAX_CALLS = 3;
	const GAP_HOURS = 5;
	const RESULT_ANSWERED = 'Nghe máy';
	const RESULT_MISSED = 'Không nghe máy';

	protected static function cfg() {
		return array(
			'module' => self::MODULE,
			'table' => 'bace_contact_last_touch_call',
			'record_col' => 'contactid',
			'notif_prefix' => 'ct_lt_call_',
			'label_short' => 'Khách hàng',
			'flow_hint' => Vtiger_MkLastTouchCallHelper::defaultFlowHint('Nghe máy → dừng chuỗi gọi.'),
			'answered_hint' => 'Đã nghe máy — kết thúc chuỗi Last Touch Call.',
			'on_bump' => null,
			'display_name' => array('Contacts_LastTouchCallService', 'recordDisplayName'),
		);
	}

	public static function ensureSchema($adb = null) {
		Vtiger_MkLastTouchCallHelper::ensureSchema(self::cfg());
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

	public static function recordDisplayName($recordId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT firstname, lastname FROM vtiger_contactdetails WHERE contactid = ?',
			array((int) $recordId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$fn = trim(decode_html((string) $adb->query_result($res, 0, 'firstname')));
			$ln = trim(decode_html((string) $adb->query_result($res, 0, 'lastname')));
			$name = trim($ln . ' ' . $fn);
			if ($name === '' || $name === '.') {
				$name = trim($fn . ' ' . $ln);
			}
			if ($name !== '') {
				return $name;
			}
		}
		return 'KH #' . (int) $recordId;
	}
}
