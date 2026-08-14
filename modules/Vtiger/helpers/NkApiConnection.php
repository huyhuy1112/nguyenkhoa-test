<?php
/*+***********************************************************************************
 * Registry for Settings → Tích hợp hệ thống.
 *
 * Usage (senior / other modules):
 *   NkApiConnection::adapter('misa')->transfer($salesOrder);
 *   NkApiConnection::adapter('google_sheet')->save($payload, $userId);
 *   $cfg = NkApiConnection::adapter('ecommerce')->getConfigForAdmin();
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';
require_once 'modules/Vtiger/helpers/NkApi/MisaAdapter.php';
require_once 'modules/Vtiger/helpers/NkApi/GoogleSheetAdapter.php';
require_once 'modules/Vtiger/helpers/NkApi/EcommerceAdapter.php';

class NkApiConnection {

	const TABLE = 'nk_api_connection';
	const MENU_NAME = 'LBL_NK_SYSTEM_INTEGRATIONS';
	const MENU_LINK = 'index.php?module=Vtiger&parent=Settings&view=Integrations';

	/**
	 * @var array
	 */
	protected static $adapterInstances = array();

	public static function ensureInstalled() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$adb = PearDatabase::getInstance();
		self::ensureTable($adb);
		self::seedRows($adb);
		self::registerSettingsMenu($adb);
	}

	protected static function ensureTable(PearDatabase $adb) {
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
				code VARCHAR(64) NOT NULL,
				enabled TINYINT(1) NOT NULL DEFAULT 0,
				base_url VARCHAR(512) DEFAULT NULL,
				credentials MEDIUMTEXT,
				extra MEDIUMTEXT,
				status VARCHAR(32) DEFAULT NULL,
				last_sync DATETIME DEFAULT NULL,
				last_error TEXT,
				updated_at DATETIME DEFAULT NULL,
				updated_by INT(19) DEFAULT NULL,
				PRIMARY KEY (code)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	protected static function seedRows(PearDatabase $adb) {
		foreach (array('misa', 'google_sheet', 'ecommerce') as $code) {
			$exists = $adb->pquery(
				'SELECT code FROM ' . self::TABLE . ' WHERE code = ? LIMIT 1',
				array($code)
			);
			if ($exists && $adb->num_rows($exists) > 0) {
				continue;
			}
			$adb->pquery(
				'INSERT INTO ' . self::TABLE . ' (code, enabled, status, updated_at) VALUES (?, 0, ?, ?)',
				array($code, 'not_configured', date('Y-m-d H:i:s'))
			);
		}
	}

	protected static function registerSettingsMenu(PearDatabase $adb) {
		$exists = $adb->pquery(
			'SELECT fieldid FROM vtiger_settings_field WHERE name = ? LIMIT 1',
			array(self::MENU_NAME)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}

		$blockid = 0;
		foreach (array('LBL_OTHER_SETTINGS', 'LBL_INTEGRATION', 'LBL_CONFIGURATION') as $label) {
			if (function_exists('getSettingsBlockId')) {
				$blockid = (int) getSettingsBlockId($label);
			}
			if ($blockid > 0) {
				break;
			}
		}
		if ($blockid <= 0) {
			$blockRes = $adb->pquery(
				'SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence ASC LIMIT 1',
				array()
			);
			if ($blockRes && $adb->num_rows($blockRes) > 0) {
				$blockid = (int) $adb->query_result($blockRes, 0, 'blockid');
			}
		}
		if ($blockid <= 0) {
			return;
		}

		$seq = 1;
		$seqRes = $adb->pquery(
			'SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?',
			array($blockid)
		);
		if ($seqRes && $adb->num_rows($seqRes) > 0) {
			$seq = (int) $adb->query_result($seqRes, 0, 'max_seq') + 1;
		}

		$fieldid = $adb->getUniqueID('vtiger_settings_field');
		$adb->pquery(
			'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
			 VALUES (?,?,?,?,?,?,?,?)',
			array(
				$fieldid,
				$blockid,
				self::MENU_NAME,
				'',
				'LBL_NK_SYSTEM_INTEGRATIONS_DESC',
				self::MENU_LINK,
				$seq,
				0,
			)
		);
	}

	/**
	 * @param string $code
	 * @return NkApi_Adapter
	 */
	public static function adapter($code) {
		self::ensureInstalled();
		$code = trim((string) $code);
		if (isset(self::$adapterInstances[$code])) {
			return self::$adapterInstances[$code];
		}
		$map = array(
			'misa' => 'NkApi_Misa_Adapter',
			'google_sheet' => 'NkApi_GoogleSheet_Adapter',
			'ecommerce' => 'NkApi_Ecommerce_Adapter',
		);
		if (!isset($map[$code]) || !class_exists($map[$code])) {
			throw new Exception('Không tìm thấy adapter: ' . $code);
		}
		$class = $map[$code];
		self::$adapterInstances[$code] = new $class();
		return self::$adapterInstances[$code];
	}

	/**
	 * @return array
	 */
	public static function catalogForAdmin() {
		self::ensureInstalled();
		$list = array();
		foreach (array('misa', 'google_sheet', 'ecommerce') as $code) {
			$list[] = self::adapter($code)->getConfigForAdmin();
		}
		return $list;
	}

	/**
	 * @param string $code
	 * @return array
	 */
	public static function getRow($code) {
		self::ensureInstalled();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT * FROM ' . self::TABLE . ' WHERE code = ? LIMIT 1',
			array($code)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return array(
				'code' => $code,
				'enabled' => 0,
				'base_url' => '',
				'credentials' => array(),
				'extra' => array(),
				'status' => 'not_configured',
				'last_sync' => '',
				'last_error' => '',
			);
		}
		$row = $adb->query_result_rowdata($res, 0);
		$row['enabled'] = !empty($row['enabled']);
		$row['credentials'] = self::decodeJson($row['credentials']);
		$row['extra'] = self::decodeJson($row['extra']);
		foreach (array('base_url', 'status', 'last_sync', 'last_error') as $k) {
			if (isset($row[$k]) && function_exists('decode_html')) {
				$row[$k] = decode_html($row[$k]);
			}
		}
		return $row;
	}

	/**
	 * @param string $code
	 * @param array $fields
	 * @param int $userId
	 */
	public static function saveRow($code, array $fields, $userId = 0) {
		self::ensureInstalled();
		$adb = PearDatabase::getInstance();
		$current = self::getRow($code);
		$enabled = array_key_exists('enabled', $fields) ? (!empty($fields['enabled']) ? 1 : 0) : ($current['enabled'] ? 1 : 0);
		$baseUrl = array_key_exists('base_url', $fields) ? trim((string) $fields['base_url']) : $current['base_url'];
		$creds = array_key_exists('credentials', $fields) ? $fields['credentials'] : $current['credentials'];
		$extra = array_key_exists('extra', $fields) ? $fields['extra'] : $current['extra'];
		$status = array_key_exists('status', $fields) ? (string) $fields['status'] : $current['status'];
		$lastSync = array_key_exists('last_sync', $fields) ? $fields['last_sync'] : $current['last_sync'];
		$lastError = array_key_exists('last_error', $fields) ? $fields['last_error'] : $current['last_error'];
		if ($lastSync === '') {
			$lastSync = null;
		}
		$now = date('Y-m-d H:i:s');
		$userId = (int) $userId;

		$exists = $adb->pquery('SELECT code FROM ' . self::TABLE . ' WHERE code = ? LIMIT 1', array($code));
		$values = array(
			$enabled,
			$baseUrl,
			self::encodeJson($creds),
			self::encodeJson($extra),
			$status,
			$lastSync,
			$lastError,
			$now,
			$userId > 0 ? $userId : null,
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			$values[] = $code;
			$adb->pquery(
				'UPDATE ' . self::TABLE . '
				 SET enabled = ?, base_url = ?, credentials = ?, extra = ?, status = ?, last_sync = ?, last_error = ?, updated_at = ?, updated_by = ?
				 WHERE code = ?',
				$values
			);
		} else {
			array_unshift($values, $code);
			$adb->pquery(
				'INSERT INTO ' . self::TABLE . '
				 (code, enabled, base_url, credentials, extra, status, last_sync, last_error, updated_at, updated_by)
				 VALUES (?,?,?,?,?,?,?,?,?,?)',
				$values
			);
		}
	}

	public static function statusLabel($status) {
		$map = array(
			'ok' => 'Đã kết nối',
			'idle' => 'Đã lưu',
			'error' => 'Lỗi',
			'not_configured' => 'Chưa cấu hình',
			'coming_soon' => 'Chưa implement',
			'disabled' => 'Đang tắt',
		);
		$status = (string) $status;
		return isset($map[$status]) ? $map[$status] : $status;
	}

	protected static function decodeJson($raw) {
		if (is_array($raw)) {
			return $raw;
		}
		$raw = trim((string) $raw);
		if ($raw === '') {
			return array();
		}
		if (function_exists('decode_html')) {
			$raw = decode_html($raw);
		} else {
			$raw = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : array();
	}

	protected static function encodeJson($value) {
		if (!is_array($value)) {
			$value = array();
		}
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}
}
