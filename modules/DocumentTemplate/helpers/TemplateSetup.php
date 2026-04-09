<?php
class DocumentTemplate_TemplateSetup_Helper {
	public static function runAll() {
		$db = PearDatabase::getInstance();
		self::ensureSchema($db);
		self::seedDefaultTemplates($db);
	}

	public static function ensureSchema(PearDatabase $db) {
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_documenttemplates (
				templateid INT(19) NOT NULL,
				templatename VARCHAR(255) NOT NULL,
				feature VARCHAR(50) NOT NULL,
				description TEXT,
				content LONGTEXT,
				version INT(10) NOT NULL DEFAULT 1,
				isdefault TINYINT(1) NOT NULL DEFAULT 0,
				createdby INT(19) DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (templateid),
				KEY dt_feature_idx (feature),
				KEY dt_deleted_idx (deleted),
				KEY dt_name_idx (templatename)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_documenttemplate_history (
				historyid INT(19) NOT NULL,
				templateid INT(19) NOT NULL,
				version INT(10) NOT NULL DEFAULT 1,
				editedby INT(19) DEFAULT NULL,
				editedtime DATETIME DEFAULT NULL,
				snapshot_name VARCHAR(255) DEFAULT NULL,
				snapshot_description TEXT,
				snapshot_content LONGTEXT,
				action_type VARCHAR(20) DEFAULT NULL,
				PRIMARY KEY (historyid),
				KEY dth_template_idx (templateid),
				KEY dth_time_idx (editedtime)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		// Feature record -> template binding for integrations (Invoice/Quote).
		$db->pquery(
			"CREATE TABLE IF NOT EXISTS vtiger_documenttemplate_bindings (
				bindingid INT(19) NOT NULL,
				module VARCHAR(50) NOT NULL,
				recordid INT(19) NOT NULL,
				templateid INT(19) NOT NULL,
				createdby INT(19) DEFAULT NULL,
				updatedby INT(19) DEFAULT NULL,
				createdtime DATETIME DEFAULT NULL,
				updatedtime DATETIME DEFAULT NULL,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (bindingid),
				UNIQUE KEY dtb_module_record_uq (module, recordid),
				KEY dtb_template_idx (templateid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
	}

	public static function recordHistory(PearDatabase $db, $templateId, $version, $userId, $editedTime, $actionType, array $snapshot) {
		$historyId = (int) $db->getUniqueID('vtiger_documenttemplate_history');
		$db->pquery(
			"INSERT INTO vtiger_documenttemplate_history
			 (historyid, templateid, version, editedby, editedtime, snapshot_name, snapshot_description, snapshot_content, action_type)
			 VALUES (?,?,?,?,?,?,?,?,?)",
			array(
				$historyId,
				(int) $templateId,
				(int) $version,
				(int) $userId,
				(string) $editedTime,
				isset($snapshot['templatename']) ? (string) $snapshot['templatename'] : '',
				isset($snapshot['description']) ? (string) $snapshot['description'] : '',
				isset($snapshot['content']) ? (string) $snapshot['content'] : '',
				(string) $actionType,
			)
		);
	}

	public static function seedDefaultTemplates(PearDatabase $db) {
		$userId = 1;
		$now = date('Y-m-d H:i:s');
		$defaults = array(
			array('feature' => 'Invoice', 'name' => 'Invoice - Default', 'description' => 'Default protected Invoice template.', 'content' => '<h2>Invoice</h2><p>Customer: {{customer_name}}</p><p>Total: {{total}}</p>'),
			array('feature' => 'Quote', 'name' => 'Quote - Default', 'description' => 'Default protected Quote template.', 'content' => '<h2>Quote</h2><p>Customer: {{customer_name}}</p><p>Quote Total: {{total}}</p>'),
			array('feature' => 'Contract', 'name' => 'Contract - Default', 'description' => 'Default protected Contract template.', 'content' => '<h2>Contract</h2><p>Party A: {{party_a}}</p><p>Party B: {{party_b}}</p>'),
		);

		foreach ($defaults as $d) {
			$exists = $db->pquery(
				"SELECT templateid FROM vtiger_documenttemplates
				 WHERE feature = ? AND isdefault = 1 AND deleted = 0
				 LIMIT 1",
				array($d['feature'])
			);
			if ($db->num_rows($exists) > 0) {
				continue;
			}
			$templateId = (int) $db->getUniqueID('vtiger_documenttemplates');
			$db->pquery(
				"INSERT INTO vtiger_documenttemplates
				 (templateid, templatename, feature, description, content, version, isdefault, createdby, updatedby, createdtime, updatedtime, deleted)
				 VALUES (?,?,?,?,?,1,1,?,?,?,?,0)",
				array($templateId, $d['name'], $d['feature'], $d['description'], $d['content'], $userId, $userId, $now, $now)
			);
			self::recordHistory($db, $templateId, 1, $userId, $now, 'create', array(
				'templatename' => $d['name'],
				'description' => $d['description'],
				'content' => $d['content'],
			));
		}
	}

	public static function getTemplateOptionsByFeature(PearDatabase $db, $feature) {
		$result = $db->pquery(
			"SELECT templateid, templatename, version, isdefault
			 FROM vtiger_documenttemplates
			 WHERE deleted = 0 AND feature = ?
			 ORDER BY isdefault DESC, updatedtime DESC, templateid DESC",
			array((string) $feature)
		);
		$options = array();
		while ($row = $db->fetchByAssoc($result)) {
			$options[] = array(
				'templateid' => (int) $row['templateid'],
				'templatename' => (string) $row['templatename'],
				'version' => (int) $row['version'],
				'isdefault' => (int) $row['isdefault'],
			);
		}
		return $options;
	}

	public static function getBoundTemplateId(PearDatabase $db, $module, $recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0) return 0;
		$result = $db->pquery(
			"SELECT templateid
			 FROM vtiger_documenttemplate_bindings
			 WHERE module = ? AND recordid = ? AND deleted = 0
			 LIMIT 1",
			array((string) $module, $recordId)
		);
		if ($db->num_rows($result) <= 0) return 0;
		return (int) $db->query_result($result, 0, 'templateid');
	}

	public static function upsertBinding(PearDatabase $db, $module, $recordId, $templateId, $userId) {
		$recordId = (int) $recordId;
		$templateId = (int) $templateId;
		$userId = (int) $userId;
		$now = date('Y-m-d H:i:s');
		if ($recordId <= 0) return;

		$exists = $db->pquery(
			"SELECT bindingid FROM vtiger_documenttemplate_bindings
			 WHERE module = ? AND recordid = ?
			 LIMIT 1",
			array((string) $module, $recordId)
		);
		if ($templateId <= 0) {
			if ($db->num_rows($exists) > 0) {
				$bindingId = (int) $db->query_result($exists, 0, 'bindingid');
				$db->pquery(
					"UPDATE vtiger_documenttemplate_bindings
					 SET deleted = 1, updatedby = ?, updatedtime = ?
					 WHERE bindingid = ?",
					array($userId, $now, $bindingId)
				);
			}
			return;
		}

		$feature = '';
		if ((string) $module === 'Invoice') $feature = 'Invoice';
		if ((string) $module === 'Quotes') $feature = 'Quote';
		$validSql = "SELECT templateid FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0";
		$params = array($templateId);
		if ($feature !== '') {
			$validSql .= " AND feature = ?";
			$params[] = $feature;
		}
		$valid = $db->pquery($validSql, $params);
		if ($db->num_rows($valid) <= 0) return;

		if ($db->num_rows($exists) > 0) {
			$bindingId = (int) $db->query_result($exists, 0, 'bindingid');
			$db->pquery(
				"UPDATE vtiger_documenttemplate_bindings
				 SET templateid = ?, deleted = 0, updatedby = ?, updatedtime = ?
				 WHERE bindingid = ?",
				array($templateId, $userId, $now, $bindingId)
			);
		} else {
			$bindingId = (int) $db->getUniqueID('vtiger_documenttemplate_bindings');
			$db->pquery(
				"INSERT INTO vtiger_documenttemplate_bindings
				 (bindingid, module, recordid, templateid, createdby, updatedby, createdtime, updatedtime, deleted)
				 VALUES (?,?,?,?,?,?,?,?,0)",
				array($bindingId, (string) $module, $recordId, $templateId, $userId, $userId, $now, $now)
			);
		}
	}

	public static function getHistoryByTemplateId(PearDatabase $db, $templateId) {
		$templateId = (int) $templateId;
		if ($templateId <= 0) return array();

		$result = $db->pquery(
			"SELECT historyid, templateid, version, editedby, editedtime, snapshot_name, snapshot_description, snapshot_content, action_type
			 FROM vtiger_documenttemplate_history
			 WHERE templateid = ?
			 ORDER BY editedtime DESC, historyid DESC",
			array($templateId)
		);

		$rows = array();
		while ($row = $db->fetchByAssoc($result)) {
			$rows[] = array(
				'historyid' => (int) $row['historyid'],
				'templateid' => (int) $row['templateid'],
				'version' => (int) $row['version'],
				'editedby' => (int) $row['editedby'],
				'editedtime' => (string) $row['editedtime'],
				'snapshot_name' => (string) $row['snapshot_name'],
				'snapshot_description' => (string) $row['snapshot_description'],
				'snapshot_content' => (string) $row['snapshot_content'],
				'action_type' => (string) $row['action_type'],
			);
		}

		return $rows;
	}
}
