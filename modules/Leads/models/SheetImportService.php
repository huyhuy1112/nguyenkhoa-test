<?php
/*+***********************************************************************************
 * Google Sheet → Leads realtime import (poll 1 phút, 1 nguồn, config, force-create).
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';

class Leads_SheetImportService {

	const TABLE_SETTINGS = 'bace_lead_sheet_settings';
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
		}
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
		$mapRaw = self::getSetting('column_map', '{}');
		$map = json_decode($mapRaw, true);
		if (!is_array($map) || empty($map)) {
			$map = self::defaultColumnMap();
		}
		return array(
			'enabled' => self::getSetting('enabled', '0') === '1',
			'spreadsheet_id' => self::getSetting('spreadsheet_id', ''),
			'sheet_range' => self::getSetting('sheet_range', 'Sheet1'),
			'column_map' => $map,
			'service_account_json' => self::getSetting('service_account_json', ''),
			'last_poll_at' => self::getSetting('last_poll_at', ''),
			'last_error' => self::getSetting('last_error', ''),
			'last_result' => self::getSetting('last_result', ''),
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
		if (array_key_exists('spreadsheet_id', $payload)) {
			self::setSetting('spreadsheet_id', trim((string) $payload['spreadsheet_id']), $userId);
		}
		if (array_key_exists('sheet_range', $payload)) {
			$range = trim((string) $payload['sheet_range']);
			if ($range === '') {
				$range = 'Sheet1';
			}
			self::setSetting('sheet_range', $range, $userId);
		}
		if (array_key_exists('column_map', $payload)) {
			$map = $payload['column_map'];
			if (is_string($map)) {
				$decoded = json_decode($map, true);
				$map = is_array($decoded) ? $decoded : self::defaultColumnMap();
			}
			if (!is_array($map)) {
				$map = self::defaultColumnMap();
			}
			self::setSetting('column_map', json_encode($map, JSON_UNESCAPED_UNICODE), $userId);
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
		return self::getSettings();
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
	 * @return array
	 */
	public static function testConnection() {
		$settings = self::getSettings();
		if ($settings['spreadsheet_id'] === '') {
			return array('success' => false, 'error' => 'Thiếu Spreadsheet ID / link.');
		}
		if (trim((string) $settings['service_account_json']) === '') {
			return array('success' => false, 'error' => 'Chưa có Service Account JSON.');
		}
		try {
			$rows = self::fetchSheetValues(
				$settings['spreadsheet_id'],
				$settings['sheet_range'],
				$settings['service_account_json']
			);
			$n = is_array($rows) ? count($rows) : 0;
			return array(
				'success' => true,
				'message' => 'Kết nối Google Sheet thành công. Đọc được ' . $n . ' dòng (gồm header).',
				'rows' => $n,
			);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * Poll sheet once; only new rows create leads.
	 * @return array
	 */
	public static function pollOnce() {
		global $current_user;
		// CRMEntity::save requires a valid user (cron / sheet_poll_now without session).
		if (empty($current_user) || empty($current_user->id)) {
			$current_user = Users::getActiveAdminUser();
		}

		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$settings = self::getSettings();
		if (empty($settings['enabled'])) {
			return array('success' => true, 'skipped' => true, 'reason' => 'disabled', 'imported' => 0);
		}
		if ($settings['spreadsheet_id'] === '') {
			$msg = 'Thiếu spreadsheet_id.';
			self::setSetting('last_error', $msg);
			return array('success' => false, 'error' => $msg, 'imported' => 0);
		}

		try {
			$rows = self::fetchSheetValues($settings['spreadsheet_id'], $settings['sheet_range'], $settings['service_account_json']);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			self::setSetting('last_error', $msg);
			self::setSetting('last_poll_at', date('Y-m-d H:i:s'));
			return array('success' => false, 'error' => $msg, 'imported' => 0);
		}

		if (count($rows) < 2) {
			self::setSetting('last_error', '');
			self::setSetting('last_poll_at', date('Y-m-d H:i:s'));
			self::setSetting('last_result', '0 rows (header only or empty)');
			return array('success' => true, 'imported' => 0, 'skipped_existing' => 0, 'errors' => array());
		}

		$header = self::normalizeHeaderRow($rows[0]);
		$colMap = $settings['column_map'];
		$imported = 0;
		$skippedExisting = 0;
		$errors = array();
		$spreadsheetId = $settings['spreadsheet_id'];

		for ($i = 1; $i < count($rows); $i++) {
			$row = $rows[$i];
			if (!is_array($row)) {
				continue;
			}
			// Row index in sheet is 1-based; data starts at sheet row 2
			$sheetRowNum = $i + 1;
			$rowKey = self::makeRowKey($spreadsheetId, $settings['sheet_range'], $sheetRowNum);
			if (self::importKeyExists($rowKey)) {
				$skippedExisting++;
				continue;
			}
			$assoc = self::rowToAssoc($header, $row);
			if (self::isEmptyDataRow($assoc)) {
				continue;
			}
			try {
				$payload = self::mapRowToLeadPayload($assoc, $colMap, $rowKey);
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
				self::recordImport($rowKey, $leadId, $assoc);
				$imported++;
			} catch (Exception $ex) {
				$errors[] = "Row {$sheetRowNum}: " . $ex->getMessage();
			}
		}

		$summary = "imported={$imported}; skipped_existing={$skippedExisting}; errors=" . count($errors);
		self::setSetting('last_error', count($errors) ? implode(' | ', array_slice($errors, 0, 5)) : '');
		self::setSetting('last_poll_at', date('Y-m-d H:i:s'));
		self::setSetting('last_result', $summary);

		return array(
			'success' => true,
			'imported' => $imported,
			'skipped_existing' => $skippedExisting,
			'errors' => $errors,
			'summary' => $summary,
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

	protected static function recordImport($rowKey, $leadId, array $assoc) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO ' . self::TABLE_IMPORT . ' (sheet_row_key, leadid, raw_json, imported_at) VALUES (?,?,?,?)',
			array($rowKey, (int) $leadId, json_encode($assoc, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'))
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
	 * @return array lead payload
	 */
	public static function mapRowToLeadPayload(array $assoc, array $colMap, $rowKey) {
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

		$tags = array();
		$cust = self::customerTagFromQ1($c1);
		if ($cust !== '') {
			$tags[] = $cust;
		}
		require_once 'modules/Leads/models/OfflineGd11Service.php';
		$tags = Leads_OfflineGd11Service::ensureProgramTag($tags);

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
			'qa_raw' => $qa,
			'_form_c1' => $c1,
			'_form_c2' => $c2,
			'_form_c3' => $c3,
		);
	}

	/** @return string A|B|C|D|'' */
	public static function parseFormQ1($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false || strpos($f, 'hoc de biet') !== false) {
			return 'D';
		}
		if (strpos($f, 'gap van de') !== false || strpos($f, 'cai thien') !== false) {
			return 'C';
		}
		if (strpos($f, 'da co quan') !== false || strpos($f, 'cap nhat') !== false) {
			return 'B';
		}
		if (strpos($f, 'chuan bi mo') !== false || strpos($f, 'mo quan') !== false) {
			return 'A';
		}
		return '';
	}

	/** @return string A–G|'' */
	public static function parseFormQ2($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false) {
			return 'G';
		}
		if (strpos($f, 'xe day') !== false) {
			return 'A';
		}
		if (strpos($f, 'topping') !== false) {
			return 'B';
		}
		if (strpos($f, 'pha may') !== false) {
			return 'C';
		}
		if (strpos($f, 'san vuon') !== false) {
			return 'E';
		}
		if (strpos($f, 'khong gian mo') !== false) {
			return 'F';
		}
		if (strpos($f, 'may lanh') !== false || strpos($f, 'ca phe may') !== false) {
			return 'D';
		}
		return '';
	}

	/** @return string A–E|'' */
	public static function parseFormQ3($raw) {
		$code = self::parseLeadingLetter($raw, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		if ($code !== '') {
			return $code;
		}
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'duoi 50') !== false || preg_match('/\b< ?50\b/', $f) || $f === 'a') {
			return 'A';
		}
		if (strpos($f, '500') !== false && (strpos($f, 'tro len') !== false || strpos($f, 'tro len') !== false || strpos($f, 'tu 500') !== false)) {
			return 'E';
		}
		if (strpos($f, '300') !== false && strpos($f, '500') !== false) {
			return 'D';
		}
		// Zalo short form: "300-500 triệu" / "300 – 500"
		if (preg_match('/\b300\b/', $f) && preg_match('/\b500\b/', $f)) {
			return 'D';
		}
		if (strpos($f, '100') !== false && strpos($f, '300') !== false) {
			return 'C';
		}
		if ((strpos($f, '50') !== false && strpos($f, '100') !== false) || strpos($f, '50 100') !== false) {
			return 'B';
		}
		if (strpos($f, 'tu 500') !== false || strpos($f, 'tren 500') !== false) {
			return 'E';
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
	 * Bộ A – 6 rule, dừng khi khớp.
	 * @return string so_luoc_du_dk|can_xm_muc_dich|can_xm_mo_hinh|so_luoc_khong_dk|''
	 */
	public static function computeSoLuocResult($c1, $c2, $c3) {
		$c1 = strtoupper(trim((string) $c1));
		$c2 = strtoupper(trim((string) $c2));
		$c3 = strtoupper(trim((string) $c3));
		if ($c1 === '' && $c2 === '' && $c3 === '') {
			return '';
		}
		$family = ($c1 === 'D' || $c2 === 'G');
		$highBudget = in_array($c3, array('C', 'D', 'E'), true);
		if ($family && $highBudget) {
			return 'can_xm_muc_dich';
		}
		if ($family) {
			return 'so_luoc_khong_dk';
		}
		if ($c3 === 'A') {
			return 'so_luoc_khong_dk';
		}
		if ($c2 === 'A' && $c3 === 'B') {
			return 'so_luoc_khong_dk';
		}
		if ($c2 === 'A' && $highBudget) {
			return 'can_xm_mo_hinh';
		}
		if ($c1 !== '' && $c2 !== '' && $c3 !== '') {
			return 'so_luoc_du_dk';
		}
		return '';
	}

	public static function customerTagFromQ1($c1) {
		$c1 = strtoupper(trim((string) $c1));
		if ($c1 === 'A') {
			return 'chuan_bi_mo';
		}
		if ($c1 === 'B' || $c1 === 'C') {
			return 'co_quan';
		}
		if ($c1 === 'D') {
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
		Vtiger_Cron::register($name, $handler, 60, 'Leads', 1, 0, 'Poll Google Sheet into modern Leads');
	}
}
