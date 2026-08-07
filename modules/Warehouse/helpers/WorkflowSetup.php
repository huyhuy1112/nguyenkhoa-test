<?php
/**
 * Warehouse management schema (master warehouses + links).
 */
class Warehouse_WorkflowSetup_Helper {

	public static function runAll() {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();
		self::ensureLinkedColumns($db);
	}

	public static function ensureSchema(PearDatabase $db) {
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse (
				warehouseid INT(19) NOT NULL,
				code VARCHAR(20) NOT NULL,
				name VARCHAR(255) NOT NULL,
				type VARCHAR(32) NOT NULL DEFAULT 'branch',
				address VARCHAR(255) DEFAULT NULL,
				manager VARCHAR(255) DEFAULT NULL,
				status VARCHAR(32) NOT NULL DEFAULT 'active',
				createdby INT(19) DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (warehouseid),
				UNIQUE KEY vtiger_warehouse_code_uq (code),
				KEY vtiger_warehouse_status_idx (status),
				KEY vtiger_warehouse_deleted_idx (deleted)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse_transfer (
				transferid INT(19) NOT NULL,
				code VARCHAR(20) NOT NULL,
				from_warehouse_id VARCHAR(20) NOT NULL,
				to_warehouse_id VARCHAR(20) NOT NULL,
				sku VARCHAR(64) DEFAULT NULL,
				product_name VARCHAR(255) DEFAULT NULL,
				lot VARCHAR(64) DEFAULT NULL,
				qty DECIMAL(25,8) NOT NULL DEFAULT 0,
				status VARCHAR(32) NOT NULL DEFAULT 'pending',
				approved_by VARCHAR(255) DEFAULT NULL,
				createdby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (transferid),
				UNIQUE KEY vtiger_wh_transfer_code_uq (code),
				KEY vtiger_wh_transfer_status_idx (status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		require_once 'modules/Warehouse/helpers/SettingsHelper.php';
		Warehouse_Settings_Helper::ensureTable($db);
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

	public static function ensureLinkedColumns(PearDatabase $db) {
		self::ensureColumn($db, 'vtiger_warehouse_stock', 'warehouse_id', "`warehouse_id` VARCHAR(20) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_warehouse_stock', 'warehouse_name', "`warehouse_name` VARCHAR(255) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_warehouse_stock', 'mfg_date', "`mfg_date` DATE DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsreceipt_items', 'mfg_date', "`mfg_date` DATE DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsreceipt', 'warehouse_id', "`warehouse_id` VARCHAR(20) DEFAULT NULL");
		self::ensureColumn($db, 'vtiger_goodsreceipt', 'status', "`status` VARCHAR(32) DEFAULT 'stored'");
		self::ensureColumn($db, 'vtiger_goodsreceipt', 'mk_meta_json', "`mk_meta_json` TEXT");
		self::ensureColumn($db, 'vtiger_goodsissue', 'mk_meta_json', "`mk_meta_json` TEXT");
	}

	public static function isInstalled(PearDatabase $db) {
		$res = $db->pquery("SHOW TABLES LIKE 'vtiger_warehouse'", array());
		if (!$res || $db->num_rows($res) < 1) {
			return false;
		}
		$cnt = $db->pquery('SELECT COUNT(*) AS c FROM vtiger_warehouse WHERE deleted = 0', array());
		return $cnt && (int) $db->query_result($cnt, 0, 'c') > 0;
	}
}

?>
