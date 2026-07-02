<?php
/**
 * Idempotent schema setup for GoodsIssue (Outbound).
 *
 * SAFETY:
 * - Does NOT touch user_privileges/* or sharing_privileges/*
 * - Does NOT clear privilege caches
 * - Does NOT modify vtiger_warehouse_stock schema
 */
class GoodsIssue_WorkflowSetup_Helper {

	public static function runAll() {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
	}

	public static function ensureSchema(PearDatabase $db) {
		// Header table
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_goodsissue (
				issueid INT(19) NOT NULL,
				code VARCHAR(50) DEFAULT NULL,
				subject VARCHAR(255) DEFAULT NULL,
				issued_by VARCHAR(255) DEFAULT NULL,
				issued_date DATE DEFAULT NULL,
				destination VARCHAR(255) DEFAULT NULL,
				storage_location VARCHAR(255) DEFAULT NULL,
				note TEXT,
				createdby INT(19) DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (issueid),
				KEY goodsissue_issued_date_idx (issued_date),
				KEY goodsissue_deleted_idx (deleted)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		// Items table
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_goodsissue_items (
				itemid INT(19) NOT NULL,
				issueid INT(19) NOT NULL,
				productid INT(19) DEFAULT NULL,
				product_name VARCHAR(255) NOT NULL,
				product_type VARCHAR(50) DEFAULT NULL,
				quantity DECIMAL(25,8) NOT NULL DEFAULT 0,
				unit_price DECIMAL(25,8) NOT NULL DEFAULT 0,
				line_note TEXT,
				PRIMARY KEY (itemid),
				KEY gii_issue_idx (issueid),
				KEY gii_product_idx (productid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		// Idempotent column adds (safe upgrades)
		self::ensureColumn($db, 'vtiger_goodsissue', 'code', "`code` VARCHAR(50) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsissue', 'issued_by', "`issued_by` VARCHAR(255) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsissue', 'storage_location', "`storage_location` VARCHAR(255) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsissue', 'status', "`status` VARCHAR(50) NOT NULL DEFAULT 'completed'");
		self::ensureColumn($db, 'vtiger_goodsissue', 'salesorder_id', "`salesorder_id` INT(19) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsissue', 'warehouse_id', "`warehouse_id` VARCHAR(20) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsissue', 'mk_meta_json', "`mk_meta_json` TEXT");
		self::ensureColumn($db, 'vtiger_goodsissue_items', 'unit_price', "`unit_price` DECIMAL(25,8) NOT NULL DEFAULT 0");
		self::ensureColumn($db, 'vtiger_goodsissue_items', 'line_note', "`line_note` TEXT");
		self::ensureColumn($db, 'vtiger_goodsissue_items', 'discount_percent', "`discount_percent` DECIMAL(10,4) NOT NULL DEFAULT 0");
		self::ensureColumn($db, 'vtiger_goodsissue_items', 'serial_number', "`serial_number` VARCHAR(255) DEFAULT NULL");

		// Outbound attachments (additive, does not affect stock logic).
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_goodsissue_attachments (
				attachmentid INT(19) NOT NULL,
				issueid INT(19) NOT NULL,
				filename VARCHAR(255) NOT NULL,
				stored_name VARCHAR(255) NOT NULL,
				filepath VARCHAR(500) NOT NULL,
				filetype VARCHAR(120) DEFAULT NULL,
				filesize BIGINT(20) DEFAULT 0,
				createdby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (attachmentid),
				KEY gia_issue_idx (issueid),
				KEY gia_deleted_idx (deleted)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
	}

	protected static function ensureColumn(PearDatabase $db, $table, $col, $definition) {
		$cols = $db->getColumnNames($table);
		if (!is_array($cols)) {
			return;
		}
		$have = array();
		foreach ($cols as $c) {
			$have[strtolower($c)] = true;
		}
		if (empty($have[strtolower($col)])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN {$definition}", array());
		}
	}
}

?>

