<?php
/*+***********************************************************************************
 * Google Sheet → Leads realtime import (poll 1 phút, nhiều nguồn, force-create).
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';

class Leads_SheetImportService {

	const TABLE_SETTINGS = 'bace_lead_sheet_settings';
	const TABLE_SOURCES = 'bace_lead_sheet_sources';
	const TABLE_IMPORT = 'bace_lead_sheet_import';
	const TABLE_MERGE_LOG = 'bace_lead_merge_log';

	/**
	 * Install tables / ensure columns on bace_lead_profile.
	 */
	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}

		// Profile table must exist (created by ModernService::installSchema). Avoid re-call → recursion.

		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE_SETTINGS . " (
				setting_key VARCHAR(64) NOT NULL,
				setting_value MEDIUMTEXT,
				updated_at DATETIME DEFAULT NULL,
				updated_by INT(19) DEFAULT NULL,
				PRIMARY KEY (setting_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE_SOURCES . " (
				id INT(11) NOT NULL AUTO_INCREMENT,
				name VARCHAR(128) NOT NULL DEFAULT '',
				spreadsheet_id VARCHAR(128) NOT NULL DEFAULT '',
				sheet_range VARCHAR(128) NOT NULL DEFAULT 'Sheet1',
				column_map MEDIUMTEXT,
				source_tag VARCHAR(64) DEFAULT '',
				enabled TINYINT(1) NOT NULL DEFAULT 1,
				sort_order INT(11) NOT NULL DEFAULT 0,
				last_poll_at DATETIME DEFAULT NULL,
				last_error TEXT,
				last_result VARCHAR(512) DEFAULT NULL,
				created_at DATETIME DEFAULT NULL,
				modified_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				KEY idx_enabled_sort (enabled, sort_order, id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		self::ensureSourcesColumn($adb, 'target_module', "VARCHAR(32) NOT NULL DEFAULT 'leads'");

		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE_IMPORT . " (
				id INT(11) NOT NULL AUTO_INCREMENT,
				sheet_row_key VARCHAR(191) NOT NULL,
				leadid INT(19) NOT NULL,
				raw_json MEDIUMTEXT,
				imported_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_sheet_row (sheet_row_key),
				KEY idx_lead (leadid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		self::ensureImportColumn($adb, 'source_id', "INT(11) DEFAULT NULL");
		self::ensureImportIndex($adb, 'idx_source', 'source_id');

		require_once 'modules/Accounts/helpers/SheetIngestService.php';
		Accounts_SheetIngestService_Helper::installSchema($adb);

		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS " . self::TABLE_MERGE_LOG . " (
				id INT(11) NOT NULL AUTO_INCREMENT,
				keeper_id INT(19) NOT NULL,
				discarded_id INT(19) NOT NULL,
				merged_by INT(19) DEFAULT NULL,
				merged_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				KEY idx_keeper (keeper_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);

		// Only alter profile if present
		$prof = $adb->pquery("SHOW TABLES LIKE 'bace_lead_profile'", array());
		if ($prof && $adb->num_rows($prof) > 0) {
			self::ensureProfileColumn($adb, 'screening_result', "VARCHAR(32) DEFAULT NULL");
			self::ensureProfileColumn($adb, 'sheet_source', "TINYINT(1) DEFAULT 0");
			self::ensureProfileColumn($adb, 'sheet_row_key', "VARCHAR(191) DEFAULT NULL");
			self::ensureProfileColumn($adb, 'qa_raw', "MEDIUMTEXT DEFAULT NULL");
			self::ensureProfileColumn($adb, 'sheet_source_id', "INT(11) DEFAULT NULL");
			self::ensureProfileColumn($adb, 'sheet_source_name', "VARCHAR(128) DEFAULT NULL");
		}

		self::migrateLegacySingleSource($adb);
	}

	protected static function ensureImportColumn(PearDatabase $adb, $column, $definition) {
		$colRes = $adb->pquery('SHOW COLUMNS FROM ' . self::TABLE_IMPORT . ' LIKE ?', array($column));
		if (!$colRes || $adb->num_rows($colRes) < 1) {
			$adb->pquery('ALTER TABLE ' . self::TABLE_IMPORT . ' ADD COLUMN ' . $column . ' ' . $definition, array());
		}
	}

	protected static function ensureSourcesColumn(PearDatabase $adb, $column, $definition) {
		$colRes = $adb->pquery('SHOW COLUMNS FROM ' . self::TABLE_SOURCES . ' LIKE ?', array($column));
		if (!$colRes || $adb->num_rows($colRes) < 1) {
			$adb->pquery('ALTER TABLE ' . self::TABLE_SOURCES . ' ADD COLUMN ' . $column . ' ' . $definition, array());
		}
	}

	protected static function ensureImportIndex(PearDatabase $adb, $indexName, $column) {
		$idx = $adb->pquery('SHOW INDEX FROM ' . self::TABLE_IMPORT . ' WHERE Key_name = ?', array($indexName));
		if (!$idx || $adb->num_rows($idx) < 1) {
			$adb->pquery('ALTER TABLE ' . self::TABLE_IMPORT . ' ADD KEY ' . $indexName . ' (' . $column . ')', array());
		}
	}

	/**
	 * Read setting without installSchema (safe inside installSchema / migrate).
	 */
	protected static function readSettingRaw(PearDatabase $adb, $key, $default = '') {
		$key = trim((string) $key);
		if ($key === '') {
			return $default;
		}
		$res = $adb->pquery(
			'SELECT setting_value FROM ' . self::TABLE_SETTINGS . ' WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return $default;
		}
		$val = $adb->query_result($res, 0, 'setting_value');
		if ($val === null) {
			return $default;
		}
		$val = (string) $val;
		if (function_exists('decode_html')) {
			$val = decode_html($val);
		} else {
			$val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
		}
		return $val;
	}

	/**
	 * One-time: copy legacy single spreadsheet_* settings into sources table.
	 */
	protected static function migrateLegacySingleSource(PearDatabase $adb) {
		$countRes = $adb->pquery('SELECT COUNT(*) AS c FROM ' . self::TABLE_SOURCES, array());
		$count = ($countRes && $adb->num_rows($countRes) > 0) ? (int) $adb->query_result($countRes, 0, 'c') : 0;
		if ($count > 0) {
			return;
		}
		$spreadsheetId = trim((string) self::readSettingRaw($adb, 'spreadsheet_id', ''));
		if ($spreadsheetId === '') {
			return;
		}
		$range = trim((string) self::readSettingRaw($adb, 'sheet_range', 'Sheet1'));
		if ($range === '') {
			$range = 'Sheet1';
		}
		$mapRaw = self::readSettingRaw($adb, 'column_map', '{}');
		$map = json_decode($mapRaw, true);
		if (!is_array($map) || empty($map)) {
			$map = self::defaultColumnMap();
		}
		$enabled = self::readSettingRaw($adb, 'enabled', '0') === '1' ? 1 : 0;
		$now = date('Y-m-d H:i:s');
		$lastPoll = trim((string) self::readSettingRaw($adb, 'last_poll_at', ''));
		$lastError = trim((string) self::readSettingRaw($adb, 'last_error', ''));
		$lastResult = trim((string) self::readSettingRaw($adb, 'last_result', ''));
		$adb->pquery(
			'INSERT INTO ' . self::TABLE_SOURCES . '
				(name, spreadsheet_id, sheet_range, column_map, source_tag, enabled, sort_order,
				 last_poll_at, last_error, last_result, created_at, modified_at)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
			array(
				'Nguồn mặc định',
				$spreadsheetId,
				$range,
				json_encode($map, JSON_UNESCAPED_UNICODE),
				'',
				$enabled,
				0,
				$lastPoll !== '' ? $lastPoll : null,
				$lastError !== '' ? $lastError : null,
				$lastResult !== '' ? $lastResult : null,
				$now,
				$now,
			)
		);
	}

	protected static function ensureProfileColumn(PearDatabase $adb, $column, $definition) {
		$colRes = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($column));
		if (!$colRes || $adb->num_rows($colRes) < 1) {
			$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$column} {$definition}", array());
		}
	}

	public static function getSetting($key, $default = '') {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$key = trim((string) $key);
		if ($key === '') {
			return $default;
		}
		$res = $adb->pquery(
			'SELECT setting_value FROM ' . self::TABLE_SETTINGS . ' WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if ($res && $adb->num_rows($res) > 0) {
			// query_result() applies to_html(); reverse so JSON / PEM stays valid.
			$val = $adb->query_result($res, 0, 'setting_value');
			if ($val === null) {
				return $default;
			}
			$val = (string) $val;
			if (function_exists('decode_html')) {
				$val = decode_html($val);
			} else {
				$val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
			}
			return $val;
		}
		return $default;
	}

	public static function setSetting($key, $value, $userId = 0) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$key = trim((string) $key);
		if ($key === '') {
			return;
		}
		$val = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;
		$exists = $adb->pquery(
			'SELECT setting_key FROM ' . self::TABLE_SETTINGS . ' WHERE setting_key = ? LIMIT 1',
			array($key)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE ' . self::TABLE_SETTINGS . ' SET setting_value = ?, updated_at = ?, updated_by = ? WHERE setting_key = ?',
				array($val, $now, $userId > 0 ? $userId : null, $key)
			);
		} else {
			$adb->pquery(
				'INSERT INTO ' . self::TABLE_SETTINGS . ' (setting_key, setting_value, updated_at, updated_by) VALUES (?,?,?,?)',
				array($key, $val, $now, $userId > 0 ? $userId : null)
			);
		}
	}

	/**
	 * @return array
	 */
	public static function getSettings() {
		$sources = self::listSources();
		$primary = !empty($sources[0]) ? $sources[0] : null;
		$map = $primary && !empty($primary['column_map']) && is_array($primary['column_map'])
			? $primary['column_map']
			: self::defaultColumnMap();
		$lastPoll = '';
		$lastError = '';
		$lastResult = '';
		foreach ($sources as $src) {
			if (!empty($src['last_poll_at']) && ($lastPoll === '' || strcmp((string) $src['last_poll_at'], $lastPoll) > 0)) {
				$lastPoll = (string) $src['last_poll_at'];
			}
			if ($lastError === '' && !empty($src['last_error'])) {
				$lastError = (string) $src['last_error'];
			}
			if ($lastResult === '' && !empty($src['last_result'])) {
				$lastResult = (string) $src['last_result'];
			}
		}
		if ($lastPoll === '') {
			$lastPoll = self::getSetting('last_poll_at', '');
		}
		if ($lastError === '') {
			$lastError = self::getSetting('last_error', '');
		}
		if ($lastResult === '') {
			$lastResult = self::getSetting('last_result', '');
		}
		return array(
			'enabled' => self::getSetting('enabled', '0') === '1',
			'spreadsheet_id' => $primary ? (string) $primary['spreadsheet_id'] : self::getSetting('spreadsheet_id', ''),
			'sheet_range' => $primary ? (string) $primary['sheet_range'] : self::getSetting('sheet_range', 'Sheet1'),
			'column_map' => $map,
			'service_account_json' => self::getSetting('service_account_json', ''),
			'last_poll_at' => $lastPoll,
			'last_error' => $lastError,
			'last_result' => $lastResult,
			'sources' => $sources,
			'sources_count' => count($sources),
			'enabled_sources_count' => self::countEnabledSources($sources),
		);
	}

	/**
	 * @param array $payload
	 * @param int $userId
	 * @return array
	 */
	public static function saveSettings(array $payload, $userId = 0) {
		if (array_key_exists('enabled', $payload)) {
			$en = $payload['enabled'];
			self::setSetting('enabled', ($en === true || $en === 1 || $en === '1' || $en === 'true') ? '1' : '0', $userId);
		}
		if (array_key_exists('service_account_json', $payload)) {
			$json = trim((string) $payload['service_account_json']);
			// Prefer storing file path (avoids to_html corruption of PEM/JSON in DB).
			if ($json !== '' && isset($json[0]) && $json[0] === '{') {
				$stored = self::persistServiceAccountJson($json);
				if ($stored !== '') {
					$json = $stored;
				}
			}
			self::setSetting('service_account_json', $json, $userId);
		}

		// Multi-source bulk replace / upsert list
		if (array_key_exists('sources', $payload) && is_array($payload['sources'])) {
			self::replaceSourcesFromPayload($payload['sources'], $userId);
		} elseif (
			array_key_exists('spreadsheet_id', $payload)
			|| array_key_exists('sheet_range', $payload)
			|| array_key_exists('column_map', $payload)
			|| array_key_exists('source_name', $payload)
			|| array_key_exists('source_tag', $payload)
			|| array_key_exists('source_id', $payload)
		) {
			// Backward-compatible single-source save → upsert one source
			$sourcePayload = array();
			if (array_key_exists('source_id', $payload)) {
				$sourcePayload['id'] = (int) $payload['source_id'];
			}
			if (array_key_exists('source_name', $payload)) {
				$sourcePayload['name'] = $payload['source_name'];
			} elseif (array_key_exists('name', $payload) && !isset($payload['sources'])) {
				$sourcePayload['name'] = $payload['name'];
			}
			if (array_key_exists('spreadsheet_id', $payload)) {
				$sourcePayload['spreadsheet_id'] = self::parseSpreadsheetId($payload['spreadsheet_id']);
			}
			if (array_key_exists('sheet_range', $payload)) {
				$sourcePayload['sheet_range'] = $payload['sheet_range'];
			}
			if (array_key_exists('column_map', $payload)) {
				$sourcePayload['column_map'] = $payload['column_map'];
			}
			if (array_key_exists('source_tag', $payload)) {
				$sourcePayload['source_tag'] = $payload['source_tag'];
			}
			if (array_key_exists('source_enabled', $payload)) {
				$sourcePayload['enabled'] = $payload['source_enabled'];
			} elseif (array_key_exists('enabled', $payload) && empty($payload['sources'])) {
				// When editing the only/primary source from legacy UI, mirror master enabled onto that source.
				$sources = self::listSources();
				if (count($sources) <= 1) {
					$sourcePayload['enabled'] = $payload['enabled'];
				}
			}
			if (!empty($sourcePayload['spreadsheet_id']) || !empty($sourcePayload['id']) || count(self::listSources()) === 0) {
				if (empty($sourcePayload['name'])) {
					$sourcePayload['name'] = 'Nguồn mặc định';
				}
				self::saveSource($sourcePayload, $userId);
			}
			// Keep legacy keys in sync for anything still reading them
			$primary = self::getPrimarySource();
			if ($primary) {
				self::setSetting('spreadsheet_id', $primary['spreadsheet_id'], $userId);
				self::setSetting('sheet_range', $primary['sheet_range'], $userId);
				self::setSetting('column_map', json_encode($primary['column_map'], JSON_UNESCAPED_UNICODE), $userId);
			}
		}

		return self::getSettings();
	}

	public static function parseSpreadsheetId($input) {
		$s = trim((string) $input);
		if ($s === '') {
			return '';
		}
		if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $s, $m)) {
			return $m[1];
		}
		return trim(preg_replace('/[?#].*$/', '', $s));
	}

	protected static function countEnabledSources(array $sources) {
		$n = 0;
		foreach ($sources as $src) {
			if (!empty($src['enabled'])) {
				$n++;
			}
		}
		return $n;
	}

	/**
	 * @return array|null
	 */
	public static function getPrimarySource() {
		$sources = self::listSources();
		return !empty($sources[0]) ? $sources[0] : null;
	}

	/**
	 * @return array[]
	 */
	public static function listSources() {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT * FROM ' . self::TABLE_SOURCES . ' ORDER BY sort_order ASC, id ASC',
			array()
		);
		$out = array();
		if ($res) {
			$rows = $adb->num_rows($res);
			for ($i = 0; $i < $rows; $i++) {
				$out[] = self::hydrateSourceRow($adb, $res, $i);
			}
		}
		return $out;
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public static function getSource($id) {
		$id = (int) $id;
		if ($id <= 0) {
			return null;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery('SELECT * FROM ' . self::TABLE_SOURCES . ' WHERE id = ? LIMIT 1', array($id));
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return self::hydrateSourceRow($adb, $res, 0);
	}

	protected static function hydrateSourceRow(PearDatabase $adb, $res, $i) {
		$row = $adb->query_result_rowdata($res, $i);
		$mapRaw = isset($row['column_map']) ? (string) $row['column_map'] : '';
		$target = self::normalizeTargetModule(isset($row['target_module']) ? $row['target_module'] : 'leads');
		$map = json_decode($mapRaw, true);
		if (!is_array($map) || empty($map)) {
			$map = self::defaultColumnMapForTarget($target);
		}
		return array(
			'id' => isset($row['id']) ? (int) $row['id'] : 0,
			'name' => isset($row['name']) ? (string) $row['name'] : '',
			'spreadsheet_id' => isset($row['spreadsheet_id']) ? (string) $row['spreadsheet_id'] : '',
			'sheet_range' => isset($row['sheet_range']) ? (string) $row['sheet_range'] : 'Sheet1',
			'column_map' => $map,
			'source_tag' => isset($row['source_tag']) ? (string) $row['source_tag'] : '',
			'target_module' => $target,
			'enabled' => !empty($row['enabled']),
			'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
			'last_poll_at' => isset($row['last_poll_at']) ? (string) $row['last_poll_at'] : '',
			'last_error' => isset($row['last_error']) ? (string) $row['last_error'] : '',
			'last_result' => isset($row['last_result']) ? (string) $row['last_result'] : '',
		);
	}

	/**
	 * @param array $payload
	 * @param int $userId
	 * @return array
	 */
	public static function saveSource(array $payload, $userId = 0) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$id = isset($payload['id']) ? (int) $payload['id'] : 0;
		$name = trim((string) (isset($payload['name']) ? $payload['name'] : ''));
		$spreadsheetId = self::parseSpreadsheetId(isset($payload['spreadsheet_id']) ? $payload['spreadsheet_id'] : '');
		$range = trim((string) (isset($payload['sheet_range']) ? $payload['sheet_range'] : 'Sheet1'));
		if ($range === '') {
			$range = 'Sheet1';
		}
		$targetModule = self::normalizeTargetModule(
			isset($payload['target_module']) ? $payload['target_module'] : 'leads'
		);
		$map = isset($payload['column_map']) ? $payload['column_map'] : null;
		if (is_string($map)) {
			$decoded = json_decode($map, true);
			$map = is_array($decoded) ? $decoded : self::defaultColumnMapForTarget($targetModule);
		}
		if (!is_array($map) || empty($map)) {
			$map = self::defaultColumnMapForTarget($targetModule);
		}
		$sourceTag = trim((string) (isset($payload['source_tag']) ? $payload['source_tag'] : ''));
		$enabled = 1;
		if (array_key_exists('enabled', $payload)) {
			$en = $payload['enabled'];
			$enabled = ($en === true || $en === 1 || $en === '1' || $en === 'true') ? 1 : 0;
		}
		$sortOrder = isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0;
		$now = date('Y-m-d H:i:s');
		$mapJson = json_encode($map, JSON_UNESCAPED_UNICODE);

		if ($id > 0) {
			$existing = self::getSource($id);
			if (!$existing) {
				throw new Exception('Không tìm thấy nguồn Google Sheet #' . $id);
			}
			if ($name === '') {
				$name = $existing['name'] !== '' ? $existing['name'] : ('Nguồn #' . $id);
			}
			if ($spreadsheetId === '') {
				$spreadsheetId = $existing['spreadsheet_id'];
			}
			if (!array_key_exists('target_module', $payload) && !empty($existing['target_module'])) {
				$targetModule = self::normalizeTargetModule($existing['target_module']);
			}
			if ($sortOrder === 0 && isset($existing['sort_order'])) {
				$sortOrder = (int) $existing['sort_order'];
			}
			$adb->pquery(
				'UPDATE ' . self::TABLE_SOURCES . ' SET
					name=?, spreadsheet_id=?, sheet_range=?, column_map=?, source_tag=?,
					target_module=?, enabled=?, sort_order=?, modified_at=?
				 WHERE id=?',
				array(
					$name, $spreadsheetId, $range, $mapJson, $sourceTag,
					$targetModule, $enabled, $sortOrder, $now, $id,
				)
			);
		} else {
			if ($name === '') {
				if ($targetModule === 'servicecontracts') {
					$name = 'NQ tiềm năng ' . (count(self::listSources()) + 1);
				} elseif ($targetModule === 'accounts') {
					$name = 'Chủ quán ' . (count(self::listSources()) + 1);
				} else {
					$name = 'Nguồn ' . (count(self::listSources()) + 1);
				}
			}
			if ($spreadsheetId === '') {
				throw new Exception('Thiếu Spreadsheet ID / link cho nguồn mới.');
			}
			if ($sortOrder === 0) {
				$maxRes = $adb->pquery('SELECT MAX(sort_order) AS m FROM ' . self::TABLE_SOURCES, array());
				$sortOrder = ($maxRes && $adb->num_rows($maxRes) > 0)
					? ((int) $adb->query_result($maxRes, 0, 'm') + 10)
					: 10;
			}
			$adb->pquery(
				'INSERT INTO ' . self::TABLE_SOURCES . '
					(name, spreadsheet_id, sheet_range, column_map, source_tag, target_module, enabled, sort_order, created_at, modified_at)
				 VALUES (?,?,?,?,?,?,?,?,?,?)',
				array(
					$name, $spreadsheetId, $range, $mapJson, $sourceTag,
					$targetModule, $enabled, $sortOrder, $now, $now,
				)
			);
			$id = (int) $adb->getLastInsertID();
			if ($id <= 0) {
				$find = $adb->pquery(
					'SELECT id FROM ' . self::TABLE_SOURCES . ' WHERE spreadsheet_id = ? AND sheet_range = ? ORDER BY id DESC LIMIT 1',
					array($spreadsheetId, $range)
				);
				if ($find && $adb->num_rows($find) > 0) {
					$id = (int) $adb->query_result($find, 0, 'id');
				}
			}
		}

		$saved = self::getSource($id);
		if (!$saved) {
			throw new Exception('Lưu nguồn Google Sheet thất bại.');
		}
		return $saved;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public static function deleteSource($id) {
		$id = (int) $id;
		if ($id <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$adb->pquery('DELETE FROM ' . self::TABLE_SOURCES . ' WHERE id = ?', array($id));
		return true;
	}

	/**
	 * Replace-all style save used by Settings when posting full sources[].
	 * Existing IDs are updated; missing IDs are deleted; rows without id are inserted.
	 *
	 * @param array $rows
	 * @param int $userId
	 */
	protected static function replaceSourcesFromPayload(array $rows, $userId = 0) {
		if (empty($rows)) {
			// Empty list = no-op (avoid accidental wipe from partial payload).
			return;
		}
		$keepIds = array();
		$sort = 0;
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$sort += 10;
			$row['sort_order'] = isset($row['sort_order']) ? (int) $row['sort_order'] : $sort;
			$saved = self::saveSource($row, $userId);
			if (!empty($saved['id'])) {
				$keepIds[] = (int) $saved['id'];
			}
		}
		if (empty($keepIds)) {
			return;
		}
		$existing = self::listSources();
		foreach ($existing as $src) {
			$sid = (int) $src['id'];
			if ($sid > 0 && !in_array($sid, $keepIds, true)) {
				self::deleteSource($sid);
			}
		}
	}

	protected static function updateSourcePollMeta($sourceId, $lastError, $lastResult) {
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'UPDATE ' . self::TABLE_SOURCES . ' SET last_poll_at=?, last_error=?, last_result=?, modified_at=? WHERE id=?',
			array(
				$now,
				$lastError !== '' ? $lastError : null,
				$lastResult !== '' ? $lastResult : null,
				$now,
				(int) $sourceId,
			)
		);
		self::setSetting('last_poll_at', $now);
		self::setSetting('last_error', $lastError);
		self::setSetting('last_result', $lastResult);
	}

	/**
	 * Write SA JSON under storage/ (gitignored) and return absolute path.
	 * @param string $rawJson
	 * @return string path or empty on failure
	 */
	public static function persistServiceAccountJson($rawJson) {
		$rawJson = trim((string) $rawJson);
		if ($rawJson === '' || $rawJson[0] !== '{') {
			return '';
		}
		$creds = json_decode($rawJson, true);
		if (!is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
			return '';
		}
		// Normalize PEM newlines if user pasted Windows-style escapes twice
		if (strpos($creds['private_key'], "\\n") !== false && strpos($creds['private_key'], "\n") === false) {
			$creds['private_key'] = str_replace('\\n', "\n", $creds['private_key']);
		}
		$dir = 'storage';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$path = $dir . '/lead_sheet_sa.json';
		$encoded = json_encode($creds, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($encoded === false) {
			return '';
		}
		if (@file_put_contents($path, $encoded) === false) {
			return '';
		}
		@chmod($path, 0600);
		// Prefer absolute path so CLI cron and web both resolve
		$abs = realpath($path);
		return $abs !== false ? $abs : $path;
	}

	/**
	 * Safe settings for Admin UI (no private_key leakage).
	 * @return array
	 */
	public static function getSettingsForAdmin() {
		$s = self::getSettings();
		$sa = isset($s['service_account_json']) ? trim((string) $s['service_account_json']) : '';
		$email = '';
		$configured = ($sa !== '');
		if ($configured) {
			try {
				$creds = self::loadServiceAccount($sa);
				$email = isset($creds['client_email']) ? (string) $creds['client_email'] : '';
			} catch (Exception $e) {
				// keep configured=true if path exists but unreadable
			}
		}
		unset($s['service_account_json']);
		$s['service_account_configured'] = $configured;
		$s['service_account_email'] = $email;
		return $s;
	}

	public static function defaultColumnMap() {
		return array(
			'name' => 'name',
			'phone' => 'phone',
			'email' => 'email',
			'address' => 'address',
			'q1' => '',
			'q2' => '',
			'q3' => '',
			'region' => '',
			'screening' => '',
		);
	}

	public static function defaultColumnMapForTarget($targetModule) {
		$targetModule = self::normalizeTargetModule($targetModule);
		if ($targetModule === 'servicecontracts') {
			require_once 'modules/ServiceContracts/helpers/SheetIngestService.php';
			return ServiceContracts_SheetIngestService_Helper::defaultColumnMap();
		}
		if ($targetModule === 'accounts') {
			require_once 'modules/Accounts/helpers/SheetIngestService.php';
			return Accounts_SheetIngestService_Helper::defaultColumnMap();
		}
		return self::defaultColumnMap();
	}

	public static function normalizeTargetModule($target) {
		$t = strtolower(trim((string) $target));
		if ($t === 'servicecontracts' || $t === 'servicecontract' || $t === 'nq' || $t === 'sc') {
			return 'servicecontracts';
		}
		if ($t === 'accounts' || $t === 'account' || $t === 'tuibao' || $t === 'franchise') {
			return 'accounts';
		}
		return 'leads';
	}

	/**
	 * Folded header names treated as core/meta (không đưa vào qa_raw).
	 * @param array $colMap
	 * @return array folded => true
	 */
	protected static function coreHeaderFoldSet(array $colMap) {
		$core = array(
			'name' => true,
			'phone' => true,
			'email' => true,
			'address' => true,
			'screening' => true,
			'timestamp' => true,
			'thoi gian' => true,
			'submitted at' => true,
			'submission time' => true,
			'stt' => true,
			'row id' => true,
		);
		$fields = array('name', 'phone', 'email', 'address', 'screening', 'q1', 'q2', 'q3', 'region');
		foreach ($fields as $field) {
			if (!empty($colMap[$field]) && !is_array($colMap[$field])) {
				$core[self::fold($colMap[$field])] = true;
			}
		}
		foreach (self::fieldHeaderAliases() as $aliases) {
			foreach ($aliases as $alias) {
				$core[self::fold($alias)] = true;
			}
		}
		return $core;
	}

	/**
	 * Header aliases (folded) so import still works if column_map differs from sheet.
	 * @return array field => list of folded header names
	 */
	protected static function fieldHeaderAliases() {
		return array(
			'name' => array('name', 'ho ten', 'hoten', 'ten', 'full name', 'fullname', 'customer name', 'ten khach hang'),
			'phone' => array('phone', 'sdt', 'so dt', 'so dien thoai', 'mobile', 'dien thoai', 'tel', 'telephone', 'phone number'),
			'email' => array('email', 'e mail', 'mail'),
			'address' => array('address', 'dia chi', 'diachi', 'addr'),
			'q1' => array(
				'cau 1', 'cau1', 'q1',
				'hien tai anh chi dang o tinh trang nao',
				'tinh trang hien tai', 'tinh trang', 'muc dich dang ky',
			),
			'q2' => array(
				'cau 2', 'cau2', 'q2',
				'mo hinh anh chi du dinh trien khai hoac dang kinh doanh',
				'mo hinh', 'mo hinh kinh doanh',
			),
			'q3' => array(
				'cau 3', 'cau3', 'q3',
				'ngan sach toi da anh chi co the dau tu',
				'ngan sach', 'ngan sach toi da',
			),
			'region' => array('khu vuc', 'kv', 'region', 'area', 'nhom khu vuc'),
			'screening' => array('screening', 'ket qua so luoc', 'ket qua', 'result', 'trang thai', 'status', 'ket qua loc'),
		);
	}

	/**
	 * Pick cell by configured header, then by alias match.
	 * @param array $assoc
	 * @param array $colMap
	 * @param string $field
	 * @return string
	 */
	protected static function getMappedCell(array $assoc, array $colMap, $field) {
		$header = isset($colMap[$field]) ? $colMap[$field] : '';
		if (!is_array($header) && $header !== '' && $header !== null) {
			if (isset($assoc[$header])) {
				return trim((string) $assoc[$header]);
			}
			$want = self::fold($header);
			foreach ($assoc as $k => $v) {
				if (self::fold($k) === $want) {
					return trim((string) $v);
				}
			}
		}
		$aliases = self::fieldHeaderAliases();
		$wantList = isset($aliases[$field]) ? $aliases[$field] : array();
		foreach ($assoc as $k => $v) {
			$fk = self::fold($k);
			if ($fk === $field) {
				return trim((string) $v);
			}
			foreach ($wantList as $alias) {
				if ($fk === $alias) {
					return trim((string) $v);
				}
			}
		}
		return '';
	}

	/**
	 * Connectivity check (no lead import). Used by Settings → Tích hợp hệ thống.
	 * @param int|null $sourceId optional specific source; null = nguồn đầu / primary
	 * @return array
	 */
	public static function testConnection($sourceId = null) {
		$settings = self::getSettings();
		$saEmail = '';
		try {
			if (trim((string) $settings['service_account_json']) !== '') {
				$creds = self::loadServiceAccount($settings['service_account_json']);
				$saEmail = isset($creds['client_email']) ? (string) $creds['client_email'] : '';
			}
		} catch (Exception $e) {
			$err = self::humanizeSheetError($e->getMessage(), null, '');
			return array(
				'success' => false,
				'error' => $err,
				'error_code' => 'service_account',
				'message' => $err,
			);
		}
		if (trim((string) $settings['service_account_json']) === '') {
			$err = 'Chưa có Service Account JSON. Vào Tích hợp hệ thống → Google Sheet → Nâng cao, dán JSON (có client_email + private_key), rồi Lưu.';
			return array(
				'success' => false,
				'error' => $err,
				'error_code' => 'missing_sa',
				'message' => $err,
			);
		}
		$source = null;
		if ($sourceId !== null && (int) $sourceId > 0) {
			$source = self::getSource((int) $sourceId);
			if (!$source) {
				$err = 'Không tìm thấy nguồn Google Sheet #' . (int) $sourceId . '.';
				return array(
					'success' => false,
					'error' => $err,
					'error_code' => 'source_not_found',
					'source_id' => (int) $sourceId,
					'message' => $err,
				);
			}
		} else {
			$source = self::getPrimarySource();
		}
		$label = ($source && !empty($source['name']))
			? (string) $source['name']
			: (($source && !empty($source['id'])) ? ('#' . $source['id']) : 'nguồn mặc định');
		if (!$source || trim((string) $source['spreadsheet_id']) === '') {
			$err = 'Nguồn "' . $label . '" thiếu Spreadsheet ID / link Google Sheet.';
			if ($source && !empty($source['id'])) {
				self::updateSourcePollMeta((int) $source['id'], $err, 'test_fail');
			}
			return array(
				'success' => false,
				'error' => $err,
				'error_code' => 'missing_spreadsheet',
				'source_id' => $source ? (int) $source['id'] : 0,
				'source_name' => $label,
				'message' => $err,
			);
		}
		try {
			$rows = self::fetchSheetValues(
				$source['spreadsheet_id'],
				$source['sheet_range'],
				$settings['service_account_json']
			);
			$n = is_array($rows) ? count($rows) : 0;
			$msg = 'Kết nối "' . $label . '" thành công. Đọc được ' . $n . ' dòng (gồm header)'
				. ' · sheet_range=' . $source['sheet_range']
				. ($saEmail !== '' ? (' · SA=' . $saEmail) : '');
			if (!empty($source['id'])) {
				self::updateSourcePollMeta((int) $source['id'], '', 'test_ok:' . $n . ' rows');
			}
			return array(
				'success' => true,
				'message' => $msg,
				'rows' => $n,
				'source_id' => (int) $source['id'],
				'source_name' => $label,
				'spreadsheet_id' => (string) $source['spreadsheet_id'],
				'sheet_range' => (string) $source['sheet_range'],
				'service_account_email' => $saEmail,
			);
		} catch (Exception $e) {
			$err = self::humanizeSheetError($e->getMessage(), $source, $saEmail);
			if (!empty($source['id'])) {
				self::updateSourcePollMeta((int) $source['id'], $err, 'test_fail');
			}
			return array(
				'success' => false,
				'error' => $err,
				'error_code' => 'google_api',
				'source_id' => (int) $source['id'],
				'source_name' => $label,
				'spreadsheet_id' => (string) $source['spreadsheet_id'],
				'sheet_range' => (string) $source['sheet_range'],
				'service_account_email' => $saEmail,
				'raw_error' => $e->getMessage(),
				'message' => $err,
			);
		}
	}

	/**
	 * Test lần lượt mọi nguồn đã cấu hình (kể cả đang tắt) — báo cáo từng cái.
	 * @return array
	 */
	public static function testAllConnections() {
		$sources = self::listSources();
		if (empty($sources)) {
			$one = self::testConnection(null);
			$one['results'] = array($one);
			$one['tested'] = 1;
			$one['passed'] = !empty($one['success']) ? 1 : 0;
			$one['failed'] = !empty($one['success']) ? 0 : 1;
			return $one;
		}
		$results = array();
		$passed = 0;
		$failed = 0;
		foreach ($sources as $src) {
			$r = self::testConnection((int) $src['id']);
			$results[] = $r;
			if (!empty($r['success'])) {
				$passed++;
			} else {
				$failed++;
			}
		}
		$ok = ($failed === 0 && $passed > 0);
		$msg = $ok
			? ('Tất cả ' . $passed . ' nguồn kết nối OK.')
			: ($passed . '/' . ($passed + $failed) . ' nguồn OK · ' . $failed . ' nguồn lỗi — xem chi tiết từng dòng.');
		return array(
			'success' => $ok,
			'message' => $msg,
			'error' => $ok ? '' : $msg,
			'tested' => $passed + $failed,
			'passed' => $passed,
			'failed' => $failed,
			'results' => $results,
		);
	}

	/**
	 * Diễn giải lỗi Google / cấu hình cho Sales & Admin đọc được.
	 * @param string $raw
	 * @param array|null $source
	 * @param string $saEmail
	 * @return string
	 */
	public static function humanizeSheetError($raw, $source = null, $saEmail = '') {
		$raw = trim((string) $raw);
		$label = '';
		if (is_array($source)) {
			$label = !empty($source['name']) ? (string) $source['name'] : ('#' . (int) $source['id']);
		}
		$prefix = $label !== '' ? ('Nguồn "' . $label . '": ') : '';
		$low = mb_strtolower($raw, 'UTF-8');
		$hintSa = $saEmail !== '' ? $saEmail : 'email service account trong JSON đã lưu';

		if ($raw === '') {
			return $prefix . 'Lỗi không xác định khi đọc Google Sheet.';
		}
		if (strpos($low, 'service account') !== false && strpos($low, 'không hợp lệ') !== false) {
			return $prefix . 'Service Account JSON không hợp lệ (cần client_email + private_key). ' . $raw;
		}
		if (strpos($low, 'chưa cấu hình') !== false || (strpos($low, 'missing') !== false && strpos($low, 'service') !== false)) {
			return $prefix . 'Chưa cấu hình Service Account JSON.';
		}
		if (strpos($low, 'permission') !== false
			|| strpos($low, 'the caller does not have permission') !== false
			|| strpos($low, '403') !== false
			|| strpos($low, 'access_denied') !== false
			|| strpos($low, 'insufficient') !== false) {
			return $prefix . 'Không có quyền đọc spreadsheet. Mở Google Sheet → Share → thêm "'
				. $hintSa . '" với quyền Viewer. Chi tiết Google: ' . $raw;
		}
		if (strpos($low, 'not found') !== false
			|| strpos($low, '404') !== false
			|| strpos($low, 'unable to parse range') !== false
			|| strpos($low, 'unable to parse') !== false) {
			$range = is_array($source) && !empty($source['sheet_range']) ? $source['sheet_range'] : '';
			return $prefix . 'Không tìm thấy spreadsheet hoặc sai tên tab (sheet_range'
				. ($range !== '' ? ('="' . $range . '"') : '')
				. '). Kiểm tra link/ID và tên sheet. Chi tiết: ' . $raw;
		}
		if (strpos($low, 'invalid_grant') !== false || strpos($low, 'jwt') !== false || strpos($low, 'ký jwt') !== false) {
			return $prefix . 'Service Account không ký được token (private_key sai / JSON bị cắt). Dán lại JSON đầy đủ. Chi tiết: ' . $raw;
		}
		if (strpos($low, 'http') !== false && (strpos($low, 'failed') !== false || strpos($low, 'timed out') !== false)) {
			return $prefix . 'Máy chủ CRM không gọi được Google API (mạng / firewall). Chi tiết: ' . $raw;
		}
		return $prefix . ($raw !== '' ? $raw : 'Lỗi không xác định khi đọc Google Sheet.');
	}

	/**
	 * Poll all enabled sources (master enabled must be on).
	 * @return array
	 */
	public static function pollOnce() {
		return self::pollAllSources();
	}

	/**
	 * @return array
	 */
	public static function pollAllSources() {
		global $current_user;
		if (empty($current_user) || empty($current_user->id)) {
			$current_user = Users::getActiveAdminUser();
		}

		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$settings = self::getSettings();
		if (empty($settings['enabled'])) {
			return array('success' => true, 'skipped' => true, 'reason' => 'disabled', 'imported' => 0);
		}
		if (trim((string) $settings['service_account_json']) === '') {
			$msg = 'Chưa có Service Account JSON.';
			self::setSetting('last_error', $msg);
			return array('success' => false, 'error' => $msg, 'imported' => 0);
		}

		$sources = self::listSources();
		$enabled = array();
		foreach ($sources as $src) {
			if (!empty($src['enabled']) && $src['spreadsheet_id'] !== '') {
				$enabled[] = $src;
			}
		}
		if (empty($enabled)) {
			$msg = 'Chưa có nguồn Google Sheet nào được bật.';
			self::setSetting('last_error', $msg);
			self::setSetting('last_poll_at', date('Y-m-d H:i:s'));
			return array('success' => false, 'error' => $msg, 'imported' => 0, 'sources' => array());
		}

		$totalImported = 0;
		$totalSkipped = 0;
		$allErrors = array();
		$perSource = array();
		$ok = true;
		foreach ($enabled as $src) {
			$result = self::pollSource($src, $settings['service_account_json']);
			$perSource[] = array(
				'source_id' => (int) $src['id'],
				'name' => $src['name'],
				'target_module' => isset($src['target_module']) ? $src['target_module'] : 'leads',
				'imported' => isset($result['imported']) ? (int) $result['imported'] : 0,
				'skipped_existing' => isset($result['skipped_existing']) ? (int) $result['skipped_existing'] : 0,
				'error' => isset($result['error']) ? $result['error'] : '',
				'summary' => isset($result['summary']) ? $result['summary'] : '',
			);
			$totalImported += isset($result['imported']) ? (int) $result['imported'] : 0;
			$totalSkipped += isset($result['skipped_existing']) ? (int) $result['skipped_existing'] : 0;
			if (!empty($result['error'])) {
				$ok = false;
				$allErrors[] = $src['name'] . ': ' . $result['error'];
			} elseif (!empty($result['errors']) && is_array($result['errors'])) {
				foreach (array_slice($result['errors'], 0, 3) as $err) {
					$allErrors[] = $src['name'] . ': ' . $err;
				}
			}
		}

		$summary = 'sources=' . count($enabled) . '; imported=' . $totalImported
			. '; skipped_existing=' . $totalSkipped . '; errors=' . count($allErrors);
		$errText = count($allErrors) ? implode(' | ', array_slice($allErrors, 0, 8)) : '';
		self::setSetting('last_error', $errText);
		self::setSetting('last_poll_at', date('Y-m-d H:i:s'));
		self::setSetting('last_result', $summary);

		return array(
			'success' => $ok || $totalImported > 0 || empty($allErrors),
			'imported' => $totalImported,
			'skipped_existing' => $totalSkipped,
			'errors' => $allErrors,
			'summary' => $summary,
			'sources' => $perSource,
		);
	}

	/**
	 * Poll one source by id (respects master enabled).
	 * @param int $sourceId
	 * @return array
	 */
	public static function pollSourceById($sourceId) {
		global $current_user;
		if (empty($current_user) || empty($current_user->id)) {
			$current_user = Users::getActiveAdminUser();
		}
		$settings = self::getSettings();
		if (empty($settings['enabled'])) {
			return array('success' => true, 'skipped' => true, 'reason' => 'disabled', 'imported' => 0);
		}
		$source = self::getSource((int) $sourceId);
		if (!$source) {
			return array('success' => false, 'error' => 'Không tìm thấy nguồn #' . (int) $sourceId, 'imported' => 0);
		}
		if (empty($source['enabled'])) {
			return array('success' => true, 'skipped' => true, 'reason' => 'source_disabled', 'imported' => 0);
		}
		if (trim((string) $settings['service_account_json']) === '') {
			return array('success' => false, 'error' => 'Chưa có Service Account JSON.', 'imported' => 0);
		}
		return self::pollSource($source, $settings['service_account_json']);
	}

	/**
	 * @param array $source
	 * @param string $serviceAccountJsonOrPath
	 * @return array
	 */
	protected static function pollSource(array $source, $serviceAccountJsonOrPath) {
		$target = self::normalizeTargetModule(isset($source['target_module']) ? $source['target_module'] : 'leads');
		if ($target === 'servicecontracts') {
			require_once 'modules/ServiceContracts/helpers/SheetIngestService.php';
			return ServiceContracts_SheetIngestService_Helper::pollSource(
				$source,
				$serviceAccountJsonOrPath,
				array(__CLASS__, 'fetchSheetValues'),
				array(__CLASS__, 'makeRowKey'),
				array(__CLASS__, 'updateSourcePollMetaPublic')
			);
		}
		if ($target === 'accounts') {
			require_once 'modules/Accounts/helpers/SheetIngestService.php';
			return Accounts_SheetIngestService_Helper::pollSource(
				$source,
				$serviceAccountJsonOrPath,
				array(__CLASS__, 'fetchSheetValues'),
				array(__CLASS__, 'makeRowKey'),
				array(__CLASS__, 'updateSourcePollMetaPublic')
			);
		}
		return self::pollLeadsSource($source, $serviceAccountJsonOrPath);
	}

	/**
	 * Public wrapper for Accounts ingest callback.
	 */
	public static function updateSourcePollMetaPublic($sourceId, $lastError, $lastResult) {
		self::updateSourcePollMeta($sourceId, $lastError, $lastResult);
	}

	/**
	 * @param array $source
	 * @param string $serviceAccountJsonOrPath
	 * @return array
	 */
	protected static function pollLeadsSource(array $source, $serviceAccountJsonOrPath) {
		$sourceId = (int) $source['id'];
		$spreadsheetId = trim((string) $source['spreadsheet_id']);
		$range = trim((string) $source['sheet_range']);
		if ($range === '') {
			$range = 'Sheet1';
		}
		$colMap = isset($source['column_map']) && is_array($source['column_map'])
			? $source['column_map']
			: self::defaultColumnMap();

		try {
			$rows = self::fetchSheetValues($spreadsheetId, $range, $serviceAccountJsonOrPath);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			self::updateSourcePollMeta($sourceId, $msg, '');
			return array('success' => false, 'error' => $msg, 'imported' => 0, 'source_id' => $sourceId);
		}

		if (count($rows) < 2) {
			$summary = '0 rows (header only or empty)';
			self::updateSourcePollMeta($sourceId, '', $summary);
			return array(
				'success' => true,
				'imported' => 0,
				'skipped_existing' => 0,
				'errors' => array(),
				'summary' => $summary,
				'source_id' => $sourceId,
			);
		}

		$header = self::normalizeHeaderRow($rows[0]);
		$imported = 0;
		$skippedExisting = 0;
		$errors = array();

		for ($i = 1; $i < count($rows); $i++) {
			$row = $rows[$i];
			if (!is_array($row)) {
				continue;
			}
			$sheetRowNum = $i + 1;
			$rowKey = self::makeRowKey($spreadsheetId, $range, $sheetRowNum);
			if (self::importKeyExists($rowKey)) {
				$skippedExisting++;
				continue;
			}
			$assoc = self::rowToAssoc($header, $row);
			if (self::isEmptyDataRow($assoc)) {
				continue;
			}
			try {
				$payload = self::mapRowToLeadPayload($assoc, $colMap, $rowKey, $source);
				if ($payload['phone'] === '' || $payload['name'] === '') {
					$errors[] = "Row {$sheetRowNum}: thiếu tên hoặc SĐT";
					continue;
				}
				$lead = Leads_ModernService::saveLeadFromSheet($payload);
				$leadId = isset($lead['crmid']) ? (int) $lead['crmid'] : 0;
				if ($leadId <= 0) {
					$errors[] = "Row {$sheetRowNum}: không tạo được lead";
					continue;
				}
				require_once 'modules/Leads/models/SalesVerifyService.php';
				Leads_SalesVerifyService::seedFormAnswers(
					$leadId,
					isset($payload['_form_c1']) ? $payload['_form_c1'] : '',
					isset($payload['_form_c2']) ? $payload['_form_c2'] : '',
					isset($payload['_form_c3']) ? $payload['_form_c3'] : ''
				);
				self::recordImport($rowKey, $leadId, $assoc, $sourceId);
				$imported++;
			} catch (Exception $ex) {
				$errors[] = "Row {$sheetRowNum}: " . $ex->getMessage();
			}
		}

		$summary = "imported={$imported}; skipped_existing={$skippedExisting}; errors=" . count($errors);
		$errText = count($errors) ? implode(' | ', array_slice($errors, 0, 5)) : '';
		self::updateSourcePollMeta($sourceId, $errText, $summary);

		return array(
			'success' => true,
			'imported' => $imported,
			'skipped_existing' => $skippedExisting,
			'errors' => $errors,
			'summary' => $summary,
			'source_id' => $sourceId,
			'target_module' => 'leads',
		);
	}

	public static function makeRowKey($spreadsheetId, $range, $rowNum) {
		return sha1(trim((string) $spreadsheetId) . '|' . trim((string) $range) . '|r' . (int) $rowNum);
	}

	protected static function importKeyExists($rowKey) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT id FROM ' . self::TABLE_IMPORT . ' WHERE sheet_row_key = ? LIMIT 1',
			array($rowKey)
		);
		return ($res && $adb->num_rows($res) > 0);
	}

	protected static function recordImport($rowKey, $leadId, array $assoc, $sourceId = 0) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO ' . self::TABLE_IMPORT . ' (sheet_row_key, leadid, raw_json, imported_at, source_id) VALUES (?,?,?,?,?)',
			array(
				$rowKey,
				(int) $leadId,
				json_encode($assoc, JSON_UNESCAPED_UNICODE),
				date('Y-m-d H:i:s'),
				$sourceId > 0 ? (int) $sourceId : null,
			)
		);
	}

	protected static function normalizeHeaderRow(array $header) {
		$out = array();
		foreach ($header as $i => $h) {
			$out[$i] = trim((string) $h);
		}
		return $out;
	}

	protected static function rowToAssoc(array $header, array $row) {
		$assoc = array();
		foreach ($header as $i => $h) {
			if ($h === '') {
				continue;
			}
			$assoc[$h] = isset($row[$i]) ? trim((string) $row[$i]) : '';
		}
		return $assoc;
	}

	protected static function isEmptyDataRow(array $assoc) {
		foreach ($assoc as $v) {
			if (trim((string) $v) !== '') {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array $assoc header=>value
	 * @param array $colMap
	 * @param string $rowKey
	 * @param array|null $source optional source meta
	 * @return array lead payload
	 */
	public static function mapRowToLeadPayload(array $assoc, array $colMap, $rowKey, $source = null) {
		$name = self::getMappedCell($assoc, $colMap, 'name');
		$phone = preg_replace('/\D+/', '', self::getMappedCell($assoc, $colMap, 'phone'));
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		if (strlen($phone) > 11) {
			$phone = substr($phone, -10);
		}
		$email = self::getMappedCell($assoc, $colMap, 'email');
		$address = self::getMappedCell($assoc, $colMap, 'address');
		$q1Raw = self::getMappedCell($assoc, $colMap, 'q1');
		$q2Raw = self::getMappedCell($assoc, $colMap, 'q2');
		$q3Raw = self::getMappedCell($assoc, $colMap, 'q3');
		$c1 = self::parseFormQ1($q1Raw);
		$c2 = self::parseFormQ2($q2Raw);
		$c3 = self::parseFormQ3($q3Raw);
		// Legacy Q2 = G (học gia đình / sở thích): không còn trong C2 mới → đẩy sang C1 = C.
		if (self::isLegacyFamilyHobbyAnswer($q2Raw)) {
			$c1 = 'C';
			$c2 = '';
		}
		$screening = self::computeSoLuocResult($c1, $c2, $c3);
		if ($screening === '') {
			$screening = self::normalizeScreeningResult(self::getMappedCell($assoc, $colMap, 'screening'));
		}

		require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
		$businessModel = Vtiger_BusinessModel_Helper::fromFormAnswer($q2Raw !== '' ? $q2Raw : $c2);

		$regionRaw = self::getMappedCell($assoc, $colMap, 'region');
		$district = self::parseRegionDistrict($regionRaw);

		$qa = array();
		$coreFold = self::coreHeaderFoldSet($colMap);
		foreach ($assoc as $header => $val) {
			$val = trim((string) $val);
			if ($val === '') {
				continue;
			}
			$fk = self::fold($header);
			if ($fk === '' || isset($coreFold[$fk])) {
				continue;
			}
			$qa[$header] = $val;
		}
		if ($q1Raw !== '') {
			$qa['Câu 1 – Tình trạng'] = $q1Raw;
		}
		if ($q2Raw !== '') {
			$qa['Câu 2 – Mô hình'] = $q2Raw;
		}
		if ($q3Raw !== '') {
			$qa['Câu 3 – Ngân sách'] = $q3Raw;
		}

		$tags = array('other'); // Google Sheet → Nguồn = Khác
		$cust = self::customerTagFromQ1($c1);
		if ($cust !== '') {
			$tags[] = $cust;
		}
		require_once 'modules/Leads/models/OfflineGd11Service.php';
		$tags = Leads_OfflineGd11Service::ensureProgramTag($tags);

		$sourceId = 0;
		$sourceName = '';
		if (is_array($source)) {
			$sourceId = isset($source['id']) ? (int) $source['id'] : 0;
			$sourceName = isset($source['name']) ? trim((string) $source['name']) : '';
			if ($sourceName !== '') {
				$qa['_sheet_source'] = $sourceName;
			}
			if (!empty($source['source_tag'])) {
				$qa['_sheet_source_tag'] = trim((string) $source['source_tag']);
			}
		}

		return array(
			'name' => $name !== '' ? $name : ('KH ' . $phone),
			'phone' => $phone,
			'email' => $email,
			'address' => $address,
			'district' => $district,
			'business_model' => $businessModel,
			'tags' => $tags,
			'screening_result' => $screening,
			'sheet_source' => 1,
			'sheet_row_key' => $rowKey,
			'sheet_source_id' => $sourceId,
			'sheet_source_name' => $sourceName,
			'qa_raw' => $qa,
			'_form_c1' => $c1,
			'_form_c2' => $c2,
			'_form_c3' => $c3,
		);
	}

	/** @return string A|B|C|'' */
	public static function parseFormQ1($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code === 'D' || $code === 'G') {
			// Legacy D (Q1) / G (Q2 nhầm cột) = gia đình → C mới.
			return 'C';
		}
		if ($code === 'A' || $code === 'B' || $code === 'C') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false || strpos($f, 'hoc de biet') !== false || strpos($f, 'hoc pha che') !== false) {
			return 'C';
		}
		if (strpos($f, 'da co quan') !== false) {
			return 'B';
		}
		if (strpos($f, 'chuan bi mo') !== false || strpos($f, 'mo quan') !== false) {
			return 'A';
		}
		return '';
	}

	/**
	 * Legacy Q2 = G hoặc text học gia đình / sở thích (không còn trong C2 mới).
	 * @return bool
	 */
	public static function isLegacyFamilyHobbyAnswer($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code === 'G') {
			return true;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return false;
		}
		return (
			strpos($f, 'gia dinh') !== false
			|| strpos($f, 'so thich') !== false
			|| strpos($f, 'hoc pha che cho gia') !== false
			|| strpos($f, 'pha che cho gia dinh') !== false
		);
	}

	/** @return string A|B|'' */
	public static function parseFormQ2($raw) {
		if (self::isLegacyFamilyHobbyAnswer($raw)) {
			// Không map vào C2 — caller đẩy sang C1 = C.
			return '';
		}
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code === 'A' || $code === 'B') {
			// Legacy "A. Xe đẩy…" → B (vỉa hè); "A. Thuê mặt bằng…" → A.
			$f = self::fold($raw);
			if ($code === 'A' && $f !== '' && (
				strpos($f, 'xe day') !== false
				|| strpos($f, 'via he') !== false
				|| strpos($f, 'mang di') !== false
				|| (strpos($f, 'online') !== false && strpos($f, 'mat bang') === false)
			)) {
				return 'B';
			}
			return $code;
		}
		// Legacy A–G map → A (mặt bằng) / B (vỉa hè·online)
		if (in_array($code, array('C', 'D', 'E', 'F'), true)) {
			return 'A';
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'via he') !== false || strpos($f, 'online') !== false || strpos($f, 'xe day') !== false || strpos($f, 'mang di') !== false || strpos($f, 'tai nha') !== false) {
			return 'B';
		}
		if (strpos($f, 'mat bang') !== false || strpos($f, 'thue') !== false || strpos($f, 'may lanh') !== false || strpos($f, 'san vuon') !== false || strpos($f, 'topping') !== false || strpos($f, 'pha may') !== false) {
			return 'A';
		}
		return '';
	}

	/** @return string A–F|'' */
	public static function parseFormQ3($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$f = self::fold($raw);

		// Legacy sheet E = ≥500tr → F (trước khi trả E theo chữ cái).
		if ($code === 'E') {
			if ($f === '' || strpos($f, '500') !== false) {
				// "E. Từ 500…" hoặc "E" trần (sheet cũ) → F; "E. 400–500" vẫn E.
				if (strpos($f, '400') !== false && strpos($f, '500') !== false && strpos($f, 'tro len') === false && strpos($f, 'tu 500') === false) {
					return 'E';
				}
				return 'F';
			}
			return 'E';
		}

		if ($code === 'F') {
			return 'F';
		}

		// Chữ A–D: giữ mã; bổ sung remap khoảng sheet cũ qua text bên dưới nếu cần.
		if ($code !== '' && strpos('ABCD', $code) !== false) {
			$fromText = self::parseFormQ3FromBudgetText($f);
			// Sheet cũ: "A. Dưới 50" / "B. 50–100" / "C. 100–300" / "D. 300–500"
			if ($fromText !== '') {
				return $fromText;
			}
			return $code;
		}

		if ($f === '') {
			return '';
		}
		return self::parseFormQ3FromBudgetText($f);
	}

	/**
	 * Map mô tả ngân sách (kể cả thang sheet cũ) → mã A–F mới.
	 * @return string A–F|''
	 */
	protected static function parseFormQ3FromBudgetText($f) {
		$f = trim((string) $f);
		if ($f === '') {
			return '';
		}
		if (strpos($f, '500') !== false && (strpos($f, 'tro len') !== false || strpos($f, 'tu 500') !== false || strpos($f, 'tren 500') !== false || strpos($f, '>=') !== false)) {
			return 'F';
		}
		if ((strpos($f, '400') !== false && strpos($f, '500') !== false) || preg_match('/\b400\b.*\b500\b/', $f)) {
			return 'E';
		}
		// Sheet cũ: "Từ 300 đến dưới 500" (không có mốc 400) → D
		if ((strpos($f, '300') !== false && strpos($f, '500') !== false) || preg_match('/\b300\b.*\b500\b/', $f)) {
			return 'D';
		}
		if ((strpos($f, '300') !== false && strpos($f, '400') !== false) || preg_match('/\b300\b.*\b400\b/', $f)) {
			return 'D';
		}
		if ((strpos($f, '200') !== false && strpos($f, '300') !== false) || preg_match('/\b200\b.*\b300\b/', $f)) {
			return 'C';
		}
		// Sheet cũ: "Từ 100 đến dưới 300" → C (bao phủ nửa trên)
		if ((strpos($f, '100') !== false && strpos($f, '300') !== false) || preg_match('/\b100\b.*\b300\b/', $f)) {
			return 'C';
		}
		if ((strpos($f, '100') !== false && strpos($f, '200') !== false) || preg_match('/\b100\b.*\b200\b/', $f)) {
			return 'B';
		}
		// Sheet cũ: "Từ 50 đến dưới 100" → A (dưới 100 mới)
		if ((strpos($f, '50') !== false && strpos($f, '100') !== false) || preg_match('/\b50\b.*\b100\b/', $f)) {
			return 'A';
		}
		if (
			preg_match('/\bduoi 100\b/', $f)
			|| preg_match('/\bduoi 50\b/', $f)
			|| preg_match('/\b< ?100\b/', $f)
			|| preg_match('/\b< ?50\b/', $f)
			|| (strpos($f, 'duoi') !== false && strpos($f, '50') !== false)
			|| (strpos($f, 'duoi') !== false && strpos($f, '100') !== false && strpos($f, '200') === false)
		) {
			return 'A';
		}
		return '';
	}

	protected static function parseLeadingLetter($raw, $allowed) {
		$v = trim((string) $raw);
		if ($v === '') {
			return '';
		}
		if (preg_match('/^([A-Za-z])(?:\s|$|[.\-–—:).])/u', $v, $m)) {
			$c = strtoupper($m[1]);
			if (strpos($allowed, $c) !== false) {
				return $c;
			}
		}
		return '';
	}

	/**
	 * Sàng lọc sơ bộ từ form 3 câu (GD1.1 mới).
	 * @return string so_luoc_du_dk|so_luoc_khong_dk|''
	 */
	public static function computeSoLuocResult($c1, $c2, $c3) {
		$c1 = strtoupper(trim((string) $c1));
		$c2 = strtoupper(trim((string) $c2));
		$c3 = strtoupper(trim((string) $c3));
		if ($c1 === '' && $c2 === '' && $c3 === '') {
			return '';
		}
		require_once 'modules/Leads/models/SalesVerifyService.php';
		$result = Leads_SalesVerifyService::compute(array('c1' => $c1, 'c2' => $c2, 'c3' => $c3));
		if (empty($result['success'])) {
			return '';
		}
		if (isset($result['eligibility_result']) && $result['eligibility_result'] === 'khong_du_dk') {
			return 'so_luoc_khong_dk';
		}
		if (isset($result['eligibility_result']) && $result['eligibility_result'] === 'du_dk') {
			return 'so_luoc_du_dk';
		}
		return '';
	}

	public static function customerTagFromQ1($c1) {
		$c1 = strtoupper(trim((string) $c1));
		if ($c1 === 'A') {
			return 'chuan_bi_mo';
		}
		if ($c1 === 'B') {
			return 'co_quan';
		}
		if ($c1 === 'C' || $c1 === 'D') {
			return 'gia_dinh';
		}
		return '';
	}

	/** Map "Khu vực 1/2/3" → district field already used by applyRegionTags. */
	public static function parseRegionDistrict($raw) {
		$v = trim((string) $raw);
		if ($v === '') {
			return '';
		}
		if (preg_match('/khu\s*v[uư]c\s*([123])/iu', $v, $m) || preg_match('/\bkv\s*([123])\b/i', $v, $m) || preg_match('/^([123])$/', $v, $m)) {
			return 'Khu vực ' . $m[1];
		}
		return '';
	}

	/**
	 * Canonical screening codes (Bộ A + legacy).
	 */
	public static function normalizeScreeningResult($raw) {
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'xac minh muc dich') !== false || $f === 'can_xm_muc_dich' || $f === 'xm muc dich') {
			return 'can_xm_muc_dich';
		}
		if (strpos($f, 'xac minh mo hinh') !== false || $f === 'can_xm_mo_hinh') {
			return 'can_xm_mo_hinh';
		}
		if (strpos($f, 'so luoc khong') !== false || strpos($f, 'so luoc khong du') !== false) {
			return 'so_luoc_khong_dk';
		}
		if (strpos($f, 'so luoc du') !== false) {
			return 'so_luoc_du_dk';
		}
		if (
			strpos($f, 'khong dat') !== false
			|| strpos($f, 'khong du dieu kien') !== false
			|| $f === 'khongdat'
			|| $f === 'fail'
			|| $f === 'failed'
			|| $f === 'so_luoc_khong_dk'
		) {
			return 'so_luoc_khong_dk';
		}
		if (
			strpos($f, 'sieu tiem') !== false
			|| strpos($f, 'sieutiem') !== false
			|| $f === 'sieu_tiem_nang'
		) {
			return 'sieu_tiem_nang';
		}
		if (
			strpos($f, 'tiem nang') !== false
			|| $f === 'tiemnang'
			|| $f === 'tiem_nang'
			|| $f === 'potential'
		) {
			return 'tiem_nang';
		}
		if (strpos($f, 'du dieu kien') !== false || $f === 'pass' || $f === 'ok' || $f === 'eligible') {
			return 'so_luoc_du_dk';
		}
		return '';
	}

	public static function screeningLabel($code) {
		$map = array(
			'so_luoc_du_dk' => 'Sơ lược đủ điều kiện',
			'can_xm_muc_dich' => 'Cần xác minh mục đích',
			'can_xm_mo_hinh' => 'Cần xác minh mô hình',
			'so_luoc_khong_dk' => 'Sơ lược không đủ điều kiện',
			'khong_dat' => 'Không đạt',
			'tiem_nang' => 'Tiềm năng',
			'sieu_tiem_nang' => 'Siêu tiềm năng',
		);
		return isset($map[$code]) ? $map[$code] : '';
	}

	/** Fold Vietnamese + lowercase for fuzzy header/result match */
	public static function fold($s) {
		$s = trim(mb_strtolower((string) $s, 'UTF-8'));
		$map = array(
			'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
			'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
			'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
			'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
			'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
			'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
			'đ'=>'d',
		);
		$s = strtr($s, $map);
		$s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
		return trim(preg_replace('/\s+/', ' ', $s));
	}

	/**
	 * Fetch values via Google Sheets API v4 using service account.
	 * @return array rows (each row is list of cell strings)
	 */
	public static function fetchSheetValues($spreadsheetId, $range, $serviceAccountJsonOrPath) {
		$creds = self::loadServiceAccount($serviceAccountJsonOrPath);
		$token = self::fetchAccessToken($creds);
		$rangeEnc = rawurlencode($range);
		$url = 'https://sheets.googleapis.com/v4/spreadsheets/'
			. rawurlencode($spreadsheetId)
			. '/values/' . $rangeEnc
			. '?majorDimension=ROWS&valueRenderOption=FORMATTED_VALUE';
		$resp = self::httpGet($url, array('Authorization: Bearer ' . $token));
		$data = json_decode($resp, true);
		if (!is_array($data)) {
			throw new Exception('Google Sheets: invalid JSON response.');
		}
		if (!empty($data['error']['message'])) {
			throw new Exception('Google Sheets: ' . $data['error']['message']);
		}
		$values = isset($data['values']) && is_array($data['values']) ? $data['values'] : array();
		return $values;
	}

	protected static function loadServiceAccount($jsonOrPath) {
		$raw = trim((string) $jsonOrPath);
		if ($raw === '') {
			throw new Exception('Chưa cấu hình service_account_json.');
		}
		if ($raw[0] !== '{' && is_file($raw)) {
			$raw = file_get_contents($raw);
		}
		$creds = json_decode($raw, true);
		if (!is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
			throw new Exception('Service account JSON không hợp lệ (cần client_email + private_key).');
		}
		return $creds;
	}

	protected static function fetchAccessToken(array $creds) {
		$now = time();
		$header = self::base64Url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
		$claim = self::base64Url(json_encode(array(
			'iss' => $creds['client_email'],
			'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
			'aud' => 'https://oauth2.googleapis.com/token',
			'iat' => $now,
			'exp' => $now + 3600,
		)));
		$unsigned = $header . '.' . $claim;
		$pkey = openssl_pkey_get_private($creds['private_key']);
		if (!$pkey) {
			throw new Exception('Không đọc được private_key service account.');
		}
		$signature = '';
		$ok = openssl_sign($unsigned, $signature, $pkey, OPENSSL_ALGO_SHA256);
		if (function_exists('openssl_free_key')) {
			@openssl_free_key($pkey);
		}
		if (!$ok) {
			throw new Exception('Ký JWT service account thất bại.');
		}
		$jwt = $unsigned . '.' . self::base64Url($signature);
		$body = http_build_query(array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion' => $jwt,
		));
		$resp = self::httpPost('https://oauth2.googleapis.com/token', $body, array(
			'Content-Type: application/x-www-form-urlencoded',
		));
		$data = json_decode($resp, true);
		if (!is_array($data) || empty($data['access_token'])) {
			$msg = is_array($data) && !empty($data['error_description'])
				? $data['error_description']
				: (is_array($data) && !empty($data['error']) ? $data['error'] : 'token exchange failed');
			throw new Exception('Google OAuth token: ' . $msg);
		}
		return $data['access_token'];
	}

	protected static function base64Url($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	protected static function httpGet($url, array $headers = array()) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			$out = curl_exec($ch);
			$err = curl_error($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($out === false) {
				throw new Exception('HTTP GET failed: ' . $err);
			}
			if ($code >= 400) {
				throw new Exception('HTTP GET ' . $code . ': ' . substr($out, 0, 400));
			}
			return $out;
		}
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'header' => implode("\r\n", $headers),
				'timeout' => 45,
			),
		));
		$out = @file_get_contents($url, false, $context);
		if ($out === false) {
			throw new Exception('HTTP GET failed (file_get_contents).');
		}
		return $out;
	}

	protected static function httpPost($url, $body, array $headers = array()) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			$out = curl_exec($ch);
			$err = curl_error($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($out === false) {
				throw new Exception('HTTP POST failed: ' . $err);
			}
			if ($code >= 400) {
				throw new Exception('HTTP POST ' . $code . ': ' . substr($out, 0, 400));
			}
			return $out;
		}
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => $body,
				'timeout' => 45,
			),
		));
		$out = @file_get_contents($url, false, $context);
		if ($out === false) {
			throw new Exception('HTTP POST failed (file_get_contents).');
		}
		return $out;
	}

	/**
	 * Register 60s cron task (idempotent).
	 */
	public static function registerCron() {
		require_once 'vtlib/Vtiger/Cron.php';
		$name = 'LeadsSheetPoll';
		$handler = 'cron/modules/Leads/SheetPoll.service';
		$existing = Vtiger_Cron::getInstance($name);
		if ($existing) {
			return;
		}
		Vtiger_Cron::register($name, $handler, 60, 'Leads', 1, 0, 'Poll Google Sheets (multi-source) into modern Leads');
	}
}
