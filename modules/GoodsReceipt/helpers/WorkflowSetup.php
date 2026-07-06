<?php

class GoodsReceipt_WorkflowSetup_Helper {
	public static function ensureSchema(PearDatabase $db) {
		$queries = array(
			"CREATE TABLE IF NOT EXISTS vtiger_goodsreceipt (
				receiptid INT(19) NOT NULL,
				code VARCHAR(50) DEFAULT NULL,
				subject VARCHAR(255) DEFAULT NULL,
				source_name VARCHAR(255) DEFAULT NULL,
				received_date DATE DEFAULT NULL,
				storage_location VARCHAR(255) DEFAULT NULL,
				note TEXT,
				createdby INT(19) DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (receiptid),
				KEY goodsreceipt_received_date_idx (received_date),
				KEY goodsreceipt_deleted_idx (deleted)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			"CREATE TABLE IF NOT EXISTS vtiger_goodsreceipt_items (
				itemid INT(19) NOT NULL,
				receiptid INT(19) NOT NULL,
				productid INT(19) DEFAULT NULL,
				product_name VARCHAR(255) NOT NULL,
				product_type VARCHAR(50) DEFAULT NULL,
				quantity DECIMAL(25,8) NOT NULL DEFAULT 0,
				unit_price DECIMAL(25,8) NOT NULL DEFAULT 0,
				line_note TEXT,
				PRIMARY KEY (itemid),
				KEY gri_receipt_idx (receiptid),
				KEY gri_product_idx (productid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse_stock (
				stockid INT(19) NOT NULL,
				code VARCHAR(50) DEFAULT NULL,
				product_key VARCHAR(300) NOT NULL,
				productid INT(19) DEFAULT NULL,
				product_name VARCHAR(255) NOT NULL,
				quantity DECIMAL(25,8) NOT NULL DEFAULT 0,
				last_price DECIMAL(25,8) NOT NULL DEFAULT 0,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				PRIMARY KEY (stockid),
				UNIQUE KEY warehouse_stock_product_key_uq (product_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			"CREATE TABLE IF NOT EXISTS vtiger_goodsreceipt_attachments (
				attachmentid INT(19) NOT NULL,
				receiptid INT(19) NOT NULL,
				filename VARCHAR(255) NOT NULL,
				stored_name VARCHAR(255) NOT NULL,
				filepath VARCHAR(500) NOT NULL,
				filetype VARCHAR(120) DEFAULT NULL,
				filesize BIGINT(20) DEFAULT 0,
				createdby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (attachmentid),
				KEY gra_receipt_idx (receiptid),
				KEY gra_deleted_idx (deleted)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		);

		foreach ($queries as $sql) {
			$db->pquery($sql, array());
		}
		self::ensureWarehouseStockExtendedColumns($db);
		self::ensureGoodsReceiptItemsExtendedColumns($db);
	}

	/**
	 * Warehouse stock table: add warehouse-editable fields (idempotent).
	 * Does not alter Inbound delta columns used by GoodsReceipt_Save_Action.
	 */
	public static function ensureWarehouseStockExtendedColumns(PearDatabase $db) {
		$table = 'vtiger_warehouse_stock';
		$cols = $db->getColumnNames($table);
		if (!is_array($cols)) {
			$cols = array();
		}
		$have = array();
		foreach ($cols as $c) {
			$have[strtolower($c)] = true;
		}
		$add = function ($column, $definition) use ($db, $table, &$have) {
			if (empty($have[strtolower($column)])) {
				$db->pquery("ALTER TABLE `{$table}` ADD COLUMN {$definition}", array());
				$have[strtolower($column)] = true;
			}
		};
		$add('shrinkage_qty', '`shrinkage_qty` DECIMAL(25,8) NOT NULL DEFAULT 0');
		$add('storage_location', '`storage_location` VARCHAR(255) DEFAULT NULL');
		$add('warehouse_note', '`warehouse_note` TEXT');
		$add('product_type', '`product_type` VARCHAR(50) DEFAULT NULL');
		$add('inbound_note', '`inbound_note` TEXT');
		$add('expired_date', '`expired_date` DATE DEFAULT NULL');
		$add('code', '`code` VARCHAR(50) DEFAULT NULL');
		$add('warehouse_name', '`warehouse_name` VARCHAR(255) DEFAULT NULL');
	}

	public static function ensureGoodsReceiptItemsExtendedColumns(PearDatabase $db) {
		$table = 'vtiger_goodsreceipt_items';
		$cols = $db->getColumnNames($table);
		if (!is_array($cols)) {
			$cols = array();
		}
		$have = array();
		foreach ($cols as $c) {
			$have[strtolower($c)] = true;
		}
		if (empty($have['product_type'])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN `product_type` VARCHAR(50) DEFAULT NULL", array());
		}
		if (empty($have['serial_number'])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN `serial_number` VARCHAR(255) DEFAULT NULL", array());
		}
		if (empty($have['expired_date'])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN `expired_date` DATE DEFAULT NULL", array());
		}
		if (empty($have['storage_location'])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN `storage_location` VARCHAR(255) DEFAULT NULL", array());
		}
	}

	public static function ensureGoodsReceiptExtendedColumns(PearDatabase $db) {
		$table = 'vtiger_goodsreceipt';
		$cols = $db->getColumnNames($table);
		if (!is_array($cols)) {
			$cols = array();
		}
		$have = array();
		foreach ($cols as $c) {
			$have[strtolower($c)] = true;
		}
		if (empty($have['code'])) {
			$db->pquery("ALTER TABLE `{$table}` ADD COLUMN `code` VARCHAR(50) DEFAULT NULL", array());
		}
	}

	protected static function nextInboundCodeForDate(PearDatabase $db, $dateYmd) {
		$day = trim((string) $dateYmd);
		if ($day === '') {
			$day = date('Y-m-d');
		}
		$dayKey = str_replace('-', '', $day);
		$prefix = 'INB-' . $dayKey . '-';
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 14) AS UNSIGNED)) AS max_seq
			 FROM vtiger_goodsreceipt
			 WHERE code LIKE ?",
			array($prefix . '%')
		);
		$maxSeq = 0;
		if ($db->num_rows($rs) > 0) {
			$maxSeq = (int) $db->query_result($rs, 0, 'max_seq');
		}
		$next = $maxSeq + 1;
		return 'INB-' . $dayKey . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
	}

	protected static function nextStorageCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_seq
			 FROM vtiger_warehouse_stock
			 WHERE code LIKE 'STK-%'",
			array()
		);
		$maxSeq = 0;
		if ($db->num_rows($rs) > 0) {
			$maxSeq = (int) $db->query_result($rs, 0, 'max_seq');
		}
		$next = $maxSeq + 1;
		return 'STK-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
	}

	public static function backfillGoodsReceiptCodes(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT receiptid, received_date
			 FROM vtiger_goodsreceipt
			 WHERE COALESCE(TRIM(code), '') = ''
			 ORDER BY receiptid ASC",
			array()
		);
		while ($row = $db->fetchByAssoc($rs)) {
			$receiptId = (int) $row['receiptid'];
			$day = isset($row['received_date']) ? (string) $row['received_date'] : '';
			$code = self::nextInboundCodeForDate($db, $day);
			$db->pquery(
				"UPDATE vtiger_goodsreceipt SET code = ?
				 WHERE receiptid = ? AND COALESCE(TRIM(code), '') = ''",
				array($code, $receiptId)
			);
		}
	}

	public static function backfillWarehouseStockCodes(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT stockid
			 FROM vtiger_warehouse_stock
			 WHERE COALESCE(TRIM(code), '') = ''
			 ORDER BY stockid ASC",
			array()
		);
		while ($row = $db->fetchByAssoc($rs)) {
			$stockId = (int) $row['stockid'];
			$code = self::nextStorageCode($db);
			$db->pquery(
				"UPDATE vtiger_warehouse_stock SET code = ?
				 WHERE stockid = ? AND COALESCE(TRIM(code), '') = ''",
				array($code, $stockId)
			);
		}
	}

	public static function ensureProfilePermissions(PearDatabase $db, $moduleName) {
		$tabId = (int) getTabid($moduleName);
		if ($tabId <= 0) {
			return;
		}

		$profiles = $db->pquery("SELECT profileid FROM vtiger_profile", array());
		while ($profile = $db->fetchByAssoc($profiles)) {
			$profileId = (int) $profile['profileid'];
			$check = $db->pquery(
				"SELECT 1 FROM vtiger_profile2tab WHERE profileid = ? AND tabid = ?",
				array($profileId, $tabId)
			);
			if ($db->num_rows($check) > 0) {
				$db->pquery(
					"UPDATE vtiger_profile2tab SET permissions = 0 WHERE profileid = ? AND tabid = ?",
					array($profileId, $tabId)
				);
			} else {
				$db->pquery(
					"INSERT INTO vtiger_profile2tab(profileid, tabid, permissions) VALUES(?,?,0)",
					array($profileId, $tabId)
				);
			}
		}
	}

	public static function ensureInventoryLabels(PearDatabase $db) {
		$labels = array(
			'GoodsReceipt' => 'Inbound',
			'Warehouse' => 'Storage',
			'GoodsIssue' => 'Outbound',
		);
		foreach ($labels as $moduleName => $tabLabel) {
			$db->pquery(
				"UPDATE vtiger_tab SET tablabel = ? WHERE name = ?",
				array($tabLabel, $moduleName)
			);
		}
	}

	public static function runAll() {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		self::ensureGoodsReceiptExtendedColumns($db);
		self::ensureProfilePermissions($db, 'GoodsReceipt');
		self::ensureProfilePermissions($db, 'Warehouse');
		self::ensureProfilePermissions($db, 'GoodsIssue');
		self::ensureInventoryLabels($db);
		self::backfillGoodsReceiptCodes($db);
		self::backfillWarehouseStockCodes($db);
	}
}

