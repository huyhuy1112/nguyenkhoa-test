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
		// Matches common form headers (EN keys on test sheet + VI labels).
		return array(
			'name' => 'name',
			'phone' => 'phone',
			'email' => 'email',
			'address' => 'address',
			'screening' => 'screening',
			'qa' => array('qa_1', 'qa_2', 'qa_3'),
		);
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
			'screening' => array('screening', 'ket qua', 'result', 'trang thai', 'status', 'ket qua loc'),
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
		// Sheet often strips leading 0 from phone number cells
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		if (strlen($phone) > 11) {
			// keep last 10 VN-style mobile
			$phone = substr($phone, -10);
		}
		$email = self::getMappedCell($assoc, $colMap, 'email');
		$address = self::getMappedCell($assoc, $colMap, 'address');
		$screeningRaw = self::getMappedCell($assoc, $colMap, 'screening');
		$screening = self::normalizeScreeningResult($screeningRaw);

		$qa = array();
		$qaHeaders = array();
		if (!empty($colMap['qa']) && is_array($colMap['qa'])) {
			foreach ($colMap['qa'] as $qHeader) {
				$qHeader = trim((string) $qHeader);
				if ($qHeader !== '') {
					$qaHeaders[] = $qHeader;
				}
			}
		}
		// Auto-pick qa_1 / Câu 1 style headers if map empty or incomplete
		if (empty($qaHeaders)) {
			foreach (array_keys($assoc) as $k) {
				$fk = self::fold($k);
				if (preg_match('/^(qa|cau)\s*[0-9]+$/', $fk) || preg_match('/^cau\s*[0-9]+$/', $fk)) {
					$qaHeaders[] = $k;
				}
			}
			sort($qaHeaders);
		}
		foreach ($qaHeaders as $qHeader) {
			$val = '';
			if (isset($assoc[$qHeader])) {
				$val = trim((string) $assoc[$qHeader]);
			} else {
				$want = self::fold($qHeader);
				foreach ($assoc as $k => $v) {
					if (self::fold($k) === $want) {
						$val = trim((string) $v);
						break;
					}
				}
			}
			$qa[$qHeader] = $val;
		}

		$tags = array();
		if ($screening === 'tiem_nang') {
			$tags[] = 'tiem_nang';
		} elseif ($screening === 'sieu_tiem_nang') {
			$tags[] = 'sieu_tiem_nang';
		}

		return array(
			'name' => $name !== '' ? $name : ('KH ' . $phone),
			'phone' => $phone,
			'email' => $email,
			'address' => $address,
			'tags' => $tags,
			'screening_result' => $screening,
			'sheet_source' => 1,
			'sheet_row_key' => $rowKey,
			'qa_raw' => $qa,
			// Prefer not auto-link source tag unless configured — keep empty
		);
	}

	/**
	 * Canonical: khong_dat | tiem_nang | sieu_tiem_nang | ''
	 */
	public static function normalizeScreeningResult($raw) {
		$f = self::fold($raw);
		if ($f === '') {
			return '';
		}
		// Không đạt
		if (
			strpos($f, 'khong dat') !== false
			|| strpos($f, 'khong du') !== false
			|| $f === 'khongdat'
			|| $f === 'fail'
			|| $f === 'failed'
		) {
			return 'khong_dat';
		}
		// Siêu tiềm năng (check before tiem_nang)
		if (
			strpos($f, 'sieu tiem') !== false
			|| strpos($f, 'sieutiem') !== false
			|| strpos($f, 'super') !== false
			|| $f === 'sieu_tiem_nang'
		) {
			return 'sieu_tiem_nang';
		}
		// Tiềm năng / đủ điều kiện (test form language)
		if (
			strpos($f, 'tiem nang') !== false
			|| $f === 'tiemnang'
			|| $f === 'tiem_nang'
			|| $f === 'potential'
			|| strpos($f, 'du dieu kien') !== false
			|| $f === 'pass'
			|| $f === 'ok'
			|| $f === 'eligible'
		) {
			return 'tiem_nang';
		}
		return '';
	}

	public static function screeningLabel($code) {
		if ($code === 'khong_dat') {
			return 'Không đạt';
		}
		if ($code === 'tiem_nang') {
			return 'Tiềm năng';
		}
		if ($code === 'sieu_tiem_nang') {
			return 'Siêu tiềm năng';
		}
		return '';
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
