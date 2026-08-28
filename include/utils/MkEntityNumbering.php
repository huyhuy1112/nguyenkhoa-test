<?php
/*+**********************************************************************************
 * B-ACE: padded entity codes for Order, Contact, Organization (5 digits).
 ***********************************************************************************/

class MkEntityNumbering {

	/** @var array<string, array{prefix: string, width: int, start: string}> */
	public static $PADDED_MODULES = array(
		'Potentials' => array('prefix' => 'CH', 'width' => 5, 'start' => '00001'), // Cơ hội (Opportunity)
		'Contacts'   => array('prefix' => 'LH', 'width' => 5, 'start' => '00001'), // Liên hệ
		'Accounts'   => array('prefix' => 'KH', 'width' => 5, 'start' => '00001'), // Khách hàng / Tổ chức
	);

	public static function getPaddingWidth($module) {
		if (isset(self::$PADDED_MODULES[$module])) {
			return (int) self::$PADDED_MODULES[$module]['width'];
		}
		return 0;
	}

	public static function formatNumber($prefix, $curId, $width) {
		$num = (int) $curId;
		return $prefix . str_pad((string) $num, $width, '0', STR_PAD_LEFT);
	}

	public static function nextSequenceId($curId, $width) {
		$num = (int) $curId + 1;
		return str_pad((string) $num, $width, '0', STR_PAD_LEFT);
	}

	/**
	 * Reset active sequence for one module (idempotent).
	 */
	public static function resetModuleSequence($module) {
		global $adb;

		if (!isset(self::$PADDED_MODULES[$module])) {
			return false;
		}

		$cfg = self::$PADDED_MODULES[$module];
		$prefix = $cfg['prefix'];
		$start = $cfg['start'];

		$adb->pquery('UPDATE vtiger_modentity_num SET active = 0 WHERE semodule = ?', array($module));

		$check = $adb->pquery(
			'SELECT num_id FROM vtiger_modentity_num WHERE semodule = ? AND prefix = ?',
			array($module, $prefix)
		);

		if ($check && $adb->num_rows($check) > 0) {
			$numId = $adb->query_result($check, 0, 'num_id');
			$adb->pquery(
				'UPDATE vtiger_modentity_num SET start_id = ?, cur_id = ?, active = 1 WHERE num_id = ?',
				array($start, $start, $numId)
			);
		} else {
			$numId = $adb->getUniqueId('vtiger_modentity_num');
			$adb->pquery(
				'INSERT INTO vtiger_modentity_num (num_id, semodule, prefix, start_id, cur_id, active) VALUES (?,?,?,?,?,?)',
				array($numId, $module, $prefix, $start, $start, 1)
			);
		}

		return true;
	}

	public static function resetAll() {
		$results = array();
		foreach (array_keys(self::$PADDED_MODULES) as $module) {
			$results[$module] = self::resetModuleSequence($module);
		}
		return $results;
	}
}
