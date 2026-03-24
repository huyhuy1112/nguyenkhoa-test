<?php

class GoodsReceipt_WorkflowSetup_Helper {
	public static function ensureSchema(PearDatabase $db) {
		$queries = array(
			"CREATE TABLE IF NOT EXISTS vtiger_goodsreceipt (
				receiptid INT(19) NOT NULL,
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
				quantity DECIMAL(25,8) NOT NULL DEFAULT 0,
				unit_price DECIMAL(25,8) NOT NULL DEFAULT 0,
				line_note TEXT,
				PRIMARY KEY (itemid),
				KEY gri_receipt_idx (receiptid),
				KEY gri_product_idx (productid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			"CREATE TABLE IF NOT EXISTS vtiger_warehouse_stock (
				stockid INT(19) NOT NULL,
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
		);

		foreach ($queries as $sql) {
			$db->pquery($sql, array());
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
		self::ensureProfilePermissions($db, 'GoodsReceipt');
		self::ensureProfilePermissions($db, 'Warehouse');
		self::ensureProfilePermissions($db, 'GoodsIssue');
		self::ensureInventoryLabels($db);
	}
}

