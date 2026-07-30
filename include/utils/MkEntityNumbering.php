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
		'Quotes'     => array('prefix' => 'BG', 'width' => 5, 'start' => '00001'), // Báo giá
		'SalesOrder' => array('prefix' => 'DH', 'width' => 5, 'start' => '00001'), // Đơn hàng
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

	/**
	 * Ensure active prefix/padding for a module without resetting cur_id when already correct.
	 * Quotes: also migrate legacy QUO* → BG#####.
	 * SalesOrder: also migrate legacy SO* → DH#####.
	 */
	public static function ensureModuleSequence($module) {
		global $adb;

		if (!isset(self::$PADDED_MODULES[$module])) {
			return false;
		}

		$cfg = self::$PADDED_MODULES[$module];
		$prefix = $cfg['prefix'];
		$start = $cfg['start'];
		$width = (int) $cfg['width'];

		$active = $adb->pquery(
			'SELECT num_id, prefix, cur_id FROM vtiger_modentity_num WHERE semodule = ? AND active = 1 LIMIT 1',
			array($module)
		);
		$needsSwitch = true;
		$oldCurId = null;
		if ($active && $adb->num_rows($active) > 0) {
			$activePrefix = (string) $adb->query_result($active, 0, 'prefix');
			$oldCurId = $adb->query_result($active, 0, 'cur_id');
			if ($activePrefix === $prefix) {
				$needsSwitch = false;
			}
		}

		if ($needsSwitch) {
			$adb->pquery('UPDATE vtiger_modentity_num SET active = 0 WHERE semodule = ?', array($module));

			$check = $adb->pquery(
				'SELECT num_id, cur_id FROM vtiger_modentity_num WHERE semodule = ? AND prefix = ?',
				array($module, $prefix)
			);
			if ($check && $adb->num_rows($check) > 0) {
				$numId = $adb->query_result($check, 0, 'num_id');
				$curId = $adb->query_result($check, 0, 'cur_id');
				if ($curId === '' || $curId === null) {
					$curId = $start;
				}
				// Prefer continuing from the previous active sequence when switching (QUO → BG).
				if ($oldCurId !== null && $oldCurId !== '' && (int) $oldCurId > (int) $curId) {
					$curId = $oldCurId;
				}
				$adb->pquery(
					'UPDATE vtiger_modentity_num SET start_id = ?, cur_id = ?, active = 1 WHERE num_id = ?',
					array($start, $curId, $numId)
				);
			} else {
				$nextFromOld = $start;
				if ($oldCurId !== null && $oldCurId !== '') {
					$nextFromOld = self::nextSequenceId($oldCurId, $width);
				}
				$numId = $adb->getUniqueId('vtiger_modentity_num');
				$adb->pquery(
					'INSERT INTO vtiger_modentity_num (num_id, semodule, prefix, start_id, cur_id, active) VALUES (?,?,?,?,?,?)',
					array($numId, $module, $prefix, $start, $nextFromOld, 1)
				);
			}
		}

		if ($module === 'Quotes') {
			self::migrateQuotesQuoToBg($width);
		}
		if ($module === 'SalesOrder') {
			self::migrateSalesOrderSoToDh($width);
		}

		return true;
	}

	/**
	 * Rewrite legacy quote_no values QUO### → BG#####.
	 */
	protected static function migrateQuotesQuoToBg($width = 5) {
		global $adb;
		$width = max(1, (int) $width);
		$res = $adb->pquery(
			"SELECT quoteid, quote_no FROM vtiger_quotes
			 WHERE quote_no IS NOT NULL AND quote_no <> '' AND UPPER(quote_no) LIKE 'QUO%'",
			array()
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return;
		}
		$maxNum = 0;
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$quoteId = (int) $adb->query_result($res, $i, 'quoteid');
			$old = trim((string) $adb->query_result($res, $i, 'quote_no'));
			if (!preg_match('/^QUO\s*0*(\d+)$/i', $old, $m)) {
				continue;
			}
			$num = (int) $m[1];
			if ($num > $maxNum) {
				$maxNum = $num;
			}
			$newNo = self::formatNumber('BG', $num, $width);
			$adb->pquery('UPDATE vtiger_quotes SET quote_no = ? WHERE quoteid = ?', array($newNo, $quoteId));
		}
		if ($maxNum > 0) {
			$next = self::nextSequenceId($maxNum, $width);
			$adb->pquery(
				'UPDATE vtiger_modentity_num SET cur_id = ?
				 WHERE semodule = ? AND prefix = ? AND active = 1
				   AND CAST(cur_id AS UNSIGNED) <= ?',
				array($next, 'Quotes', 'BG', $maxNum)
			);
		}
	}

	/**
	 * Rewrite legacy salesorder_no values SO### → DH#####.
	 */
	protected static function migrateSalesOrderSoToDh($width = 5) {
		global $adb;
		$width = max(1, (int) $width);
		$res = $adb->pquery(
			"SELECT salesorderid, salesorder_no FROM vtiger_salesorder
			 WHERE salesorder_no IS NOT NULL AND salesorder_no <> '' AND UPPER(salesorder_no) LIKE 'SO%'",
			array()
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return;
		}
		$maxNum = 0;
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$soId = (int) $adb->query_result($res, $i, 'salesorderid');
			$old = trim((string) $adb->query_result($res, $i, 'salesorder_no'));
			if (!preg_match('/^SO\s*0*(\d+)$/i', $old, $m)) {
				continue;
			}
			$num = (int) $m[1];
			if ($num > $maxNum) {
				$maxNum = $num;
			}
			$newNo = self::formatNumber('DH', $num, $width);
			$adb->pquery('UPDATE vtiger_salesorder SET salesorder_no = ? WHERE salesorderid = ?', array($newNo, $soId));
		}
		if ($maxNum > 0) {
			$next = self::nextSequenceId($maxNum, $width);
			$adb->pquery(
				'UPDATE vtiger_modentity_num SET cur_id = ?
				 WHERE semodule = ? AND prefix = ? AND active = 1
				   AND CAST(cur_id AS UNSIGNED) <= ?',
				array($next, 'SalesOrder', 'DH', $maxNum)
			);
		}
	}

	/**
	 * Preview next auto number without consuming sequence (e.g. BG00001).
	 */
	public static function previewNextNumber($module) {
		global $adb;

		if (!isset(self::$PADDED_MODULES[$module])) {
			return '';
		}
		self::ensureModuleSequence($module);
		$cfg = self::$PADDED_MODULES[$module];
		$res = $adb->pquery(
			'SELECT prefix, cur_id FROM vtiger_modentity_num WHERE semodule = ? AND active = 1 LIMIT 1',
			array($module)
		);
		if (!$res || $adb->num_rows($res) === 0) {
			return self::formatNumber($cfg['prefix'], $cfg['start'], $cfg['width']);
		}
		$prefix = (string) $adb->query_result($res, 0, 'prefix');
		$curId = $adb->query_result($res, 0, 'cur_id');
		return self::formatNumber($prefix, $curId, $cfg['width']);
	}
}
