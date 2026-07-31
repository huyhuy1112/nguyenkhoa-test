<?php
/**
 * Ensure SalesOrder has mk_list_note (Ghi chú list) separate from description (Ghi chú hợp đồng).
 */
class SalesOrder_ListNoteField_Helper {

	const FIELD_NAME = 'mk_list_note';
	const FIELD_LABEL = 'Ghi chú';

	public static function ensure() {
		static $done = false;
		if ($done) {
			return self::FIELD_NAME;
		}
		$done = true;

		try {
			$adb = PearDatabase::getInstance();
			$module = Vtiger_Module::getInstance('SalesOrder');
			if (!$module) {
				return self::FIELD_NAME;
			}

			$colRes = $adb->pquery("SHOW COLUMNS FROM vtiger_salesorder LIKE ?", array(self::FIELD_NAME));
			if (!$adb->num_rows($colRes)) {
				$adb->pquery(
					'ALTER TABLE vtiger_salesorder ADD COLUMN ' . self::FIELD_NAME . ' TEXT NULL',
					array()
				);
			}

			$field = Vtiger_Field::getInstance(self::FIELD_NAME, $module);
			if (!$field) {
				$block = Vtiger_Block::getInstance('LBL_SO_INFORMATION', $module);
				if (!$block) {
					$blocks = Vtiger_Block::getAllForModule($module);
					$block = !empty($blocks) ? $blocks[0] : null;
				}
				if ($block) {
					$field = new Vtiger_Field();
					$field->name = self::FIELD_NAME;
					$field->label = self::FIELD_LABEL;
					$field->table = 'vtiger_salesorder';
					$field->column = self::FIELD_NAME;
					$field->columntype = 'TEXT';
					$field->uitype = 19;
					$field->typeofdata = 'V~O';
					$field->displaytype = 1;
					$field->quickcreate = 1;
					$field->summaryfield = 0;
					$block->addField($field);
				}
			}
		} catch (Exception $e) {
			// ignore — UI still injects a client-side field when missing
		}

		return self::FIELD_NAME;
	}
}
