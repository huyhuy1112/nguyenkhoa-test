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
require_once 'modules/Vtiger/helpers/NkApi/ZaloOaAdapter.php';
require_once 'modules/Vtiger/helpers/NkApi/EdubitAdapter.php';
require_once 'modules/Vtiger/helpers/NkApi/GenericAdapter.php';

class NkApiConnection
{

	const TABLE = 'nk_api_connection';
	const LOG_TABLE = 'nk_api_sync_log';
	const MENU_NAME = 'LBL_NK_SYSTEM_INTEGRATIONS';
	const MENU_LINK = 'index.php?module=Vtiger&parent=Settings&view=Integrations';
	const MENU_HUB_NAME = 'LBL_NK_INTEGRATION_HUB';
	const MENU_HUB_LINK = 'index.php?module=Vtiger&parent=Settings&view=IntegrationHub';

	/**
	 * @var array
	 */
	protected static $adapterInstances = array();

	public static function ensureInstalled()
	{
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$adb = PearDatabase::getInstance();
		self::ensureTable($adb);
		self::ensureLogTable($adb);
		self::seedRows($adb);
		self::registerSettingsMenu($adb);
		self::registerIntegrationHubMenu($adb);
	}

	protected static function ensureTable(PearDatabase $adb)
	{
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

	protected static function ensureLogTable(PearDatabase $adb)
	{
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS ' . self::LOG_TABLE . ' (
				logid INT(19) NOT NULL AUTO_INCREMENT,
				code VARCHAR(64) NOT NULL,
				event_type VARCHAR(32) NOT NULL,
				status VARCHAR(16) NOT NULL,
				title VARCHAR(255) NOT NULL,
				detail TEXT,
				records_count INT(11) DEFAULT 0,
				error_message TEXT,
				duration_ms INT(11) DEFAULT 0,
				created_at DATETIME NOT NULL,
				created_by INT(19) DEFAULT NULL,
				PRIMARY KEY (logid),
				KEY idx_code_created (code, created_at),
				KEY idx_created (created_at),
				KEY idx_event_type (event_type)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	protected static function seedRows(PearDatabase $adb)
	{
		foreach (array_keys(self::hubCatalogDefinitions()) as $code) {
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

	protected static function registerSettingsMenu(PearDatabase $adb)
	{
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

	protected static function registerIntegrationHubMenu(PearDatabase $adb)
	{
		$exists = $adb->pquery(
			'SELECT fieldid FROM vtiger_settings_field WHERE name = ? LIMIT 1',
			array(self::MENU_HUB_NAME)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}

		$blockid = 0;
		$sequence = 0;
		$parent = $adb->pquery(
			'SELECT blockid, sequence FROM vtiger_settings_field WHERE name = ? LIMIT 1',
			array(self::MENU_NAME)
		);
		if ($parent && $adb->num_rows($parent) > 0) {
			$blockid = (int) $adb->query_result($parent, 0, 'blockid');
			$sequence = (int) $adb->query_result($parent, 0, 'sequence') + 1;
		}

		if ($blockid <= 0) {
			foreach (array('LBL_OTHER_SETTINGS', 'LBL_INTEGRATION', 'LBL_CONFIGURATION') as $label) {
				if (function_exists('getSettingsBlockId')) {
					$blockid = (int) getSettingsBlockId($label);
				}
				if ($blockid > 0) {
					break;
				}
			}
		}
		if ($blockid <= 0) {
			return;
		}

		if ($sequence <= 0) {
			$seqRes = $adb->pquery(
				'SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?',
				array($blockid)
			);
			if ($seqRes && $adb->num_rows($seqRes) > 0) {
				$sequence = (int) $adb->query_result($seqRes, 0, 'max_seq') + 1;
			} else {
				$sequence = 1;
			}
		} else {
			$adb->pquery(
				'UPDATE vtiger_settings_field SET sequence = sequence + 1 WHERE blockid = ? AND sequence >= ?',
				array($blockid, $sequence)
			);
		}

		$fieldid = $adb->getUniqueID('vtiger_settings_field');
		$adb->pquery(
			'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
			 VALUES (?,?,?,?,?,?,?,?)',
			array(
				$fieldid,
				$blockid,
				self::MENU_HUB_NAME,
				'',
				'LBL_NK_INTEGRATION_HUB_DESC',
				self::MENU_HUB_LINK,
				$sequence,
				0,
			)
		);
	}

	/**
	 * @param string $code
	 * @return NkApi_Adapter
	 */
	public static function adapter($code)
	{
		self::ensureInstalled();
		$code = trim((string) $code);
		if (isset(self::$adapterInstances[$code])) {
			return self::$adapterInstances[$code];
		}
		$map = array(
			'misa' => 'NkApi_Misa_Adapter',
			'google_sheet' => 'NkApi_GoogleSheet_Adapter',
			'ecommerce' => 'NkApi_Ecommerce_Adapter',
			'zalo_oa' => 'NkApi_ZaloOa_Adapter',
			'edubit' => 'NkApi_Edubit_Adapter',
		);
		if (!isset($map[$code]) || !class_exists($map[$code])) {
			$generic = self::genericAdapterMeta($code);
			if ($generic !== null) {
				self::$adapterInstances[$code] = new NkApi_Generic_Adapter($generic);
				return self::$adapterInstances[$code];
			}
			throw new Exception('Không tìm thấy adapter: ' . $code);
		}
		$class = $map[$code];
		self::$adapterInstances[$code] = new $class();
		return self::$adapterInstances[$code];
	}

	/**
	 * @return array
	 */
	public static function catalogForAdmin()
	{
		self::ensureInstalled();
		$list = array();
		foreach (array_keys(self::hubCatalogDefinitions()) as $code) {
			try {
				$list[] = self::adapter($code)->getConfigForAdmin();
			} catch (Exception $e) {
				// skip broken adapter
			}
		}
		return $list;
	}

	/**
	 * Metadata for generic adapters (no dedicated PHP class).
	 * @param string $code
	 * @return array|null
	 */
	protected static function genericAdapterMeta($code)
	{
		$defs = self::hubCatalogDefinitions();
		if (!isset($defs[$code]) || !empty($defs[$code]['use_adapter'])) {
			return null;
		}
		$def = $defs[$code];
		return array(
			'code' => $code,
			'label' => $def['label'],
			'description' => $def['subtitle'],
			'icon' => $def['icon'],
			'hint' => isset($def['admin_hint']) ? $def['admin_hint'] : '',
		);
	}

	/**
	 * Hub dashboard catalog — merges DB/adapters with hub metadata.
	 * @return array
	 */
	public static function catalogForHub()
	{
		self::ensureInstalled();
		$list = array();
		foreach (self::hubCatalogDefinitions() as $code => $def) {
			$list[] = self::buildHubConnectionItem($code, $def);
		}
		return $list;
	}

	/**
	 * @return array
	 */
	public static function hubSummary()
	{
		self::ensureInstalled();
		$connections = self::catalogForHub();
		$total = count($connections);
		$active = 0;
		$warning = 0;
		$error = 0;
		foreach ($connections as $conn) {
			$hubStatus = isset($conn['hub_status']) ? (string) $conn['hub_status'] : 'inactive';
			if ($hubStatus === 'active') {
				$active++;
			} elseif ($hubStatus === 'warning') {
				$warning++;
			} elseif ($hubStatus === 'error') {
				$error++;
			}
		}
		$pct = function ($n) use ($total) {
			if ($total <= 0) {
				return 0;
			}
			return (int) round(($n / $total) * 100);
		};
		return array(
			'total' => $total,
			'active' => $active,
			'active_pct' => $pct($active),
			'warning' => $warning,
			'warning_pct' => $pct($warning),
			'error' => $error,
			'error_pct' => $pct($error),
			'synced_today' => self::countSyncedToday(),
		);
	}

	/**
	 * Đếm số bản ghi đã sync thành công trong hôm nay (từ log thật).
	 * @return int
	 */
	protected static function countSyncedToday()
	{
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT COALESCE(SUM(records_count), 0) AS total
			 FROM ' . self::LOG_TABLE . '
			 WHERE status = ?
			   AND DATE(created_at) = CURDATE()',
			array('success')
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'total');
		}
		return 0;
	}

	/**
	 * Ghi 1 sự kiện vào log hoạt động.
	 *
	 * @param string $code       Mã kết nối (google_sheet, misa, zalo_oa...)
	 * @param string $eventType  sync | test | config | connect | disconnect | webhook | token_refresh
	 * @param string $status     success | warning | error
	 * @param string $title
	 * @param string $detail
	 * @param array  $extra      records_count, error_message, duration_ms, user_id
	 */
	public static function logActivity($code, $eventType, $status, $title, $detail = '', array $extra = array())
	{
		self::ensureInstalled();
		$adb = PearDatabase::getInstance();

		$code = trim((string) $code);
		$eventType = trim((string) $eventType);
		$status = trim((string) $status);
		$title = trim((string) $title);
		$detail = (string) $detail;

		if ($code === '' || $title === '') {
			return;
		}

		// Chuẩn hoá status
		if (!in_array($status, array('success', 'warning', 'error'), true)) {
			$status = 'success';
		}

		// Cắt title theo độ dài cột
		if (function_exists('mb_substr')) {
			$title = mb_substr($title, 0, 255);
		} else {
			$title = substr($title, 0, 255);
		}

		$recordsCount = isset($extra['records_count']) ? (int) $extra['records_count'] : 0;
		$errorMessage = isset($extra['error_message']) ? (string) $extra['error_message'] : null;
		$durationMs   = isset($extra['duration_ms'])   ? (int) $extra['duration_ms']   : 0;
		$userId       = isset($extra['user_id'])       ? (int) $extra['user_id']       : 0;

		$adb->pquery(
			'INSERT INTO ' . self::LOG_TABLE . '
				(code, event_type, status, title, detail, records_count, error_message, duration_ms, created_at, created_by)
			 VALUES (?,?,?,?,?,?,?,?,?,?)',
			array(
				$code,
				$eventType,
				$status,
				$title,
				$detail,
				$recordsCount,
				$errorMessage,
				$durationMs,
				date('Y-m-d H:i:s'),
				$userId > 0 ? $userId : null,
			)
		);
	}

	/**
	 * Lấy hoạt động gần đây từ log thật.
	 * Fallback về demo data nếu bảng log trống.
	 *
	 * @param int    $limit
	 * @param string $code   Lọc theo mã kết nối ('' = tất cả)
	 * @return array
	 */
	public static function recentActivity($limit = 8, $code = '')
	{
		self::ensureInstalled();
		$adb = PearDatabase::getInstance();
		$limit = max(1, (int) $limit);
		$code = trim((string) $code);

		$sql = 'SELECT code, event_type, status, title, detail, records_count, created_at
				FROM ' . self::LOG_TABLE;
		$params = array();

		if ($code !== '') {
			$sql .= ' WHERE code = ?';
			$params[] = $code;
		}
		$sql .= ' ORDER BY created_at DESC, logid DESC LIMIT ?';
		$params[] = $limit;

		$res = $adb->pquery($sql, $params);

		$items = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$status = (string) $row['status'];
				$type = 'success';
				if ($status === 'error') {
					$type = 'error';
				} elseif ($status === 'warning') {
					$type = 'warning';
				}

				$items[] = array(
					'type'          => $type,
					'code'          => (string) $row['code'],
					'event_type'    => (string) $row['event_type'],
					'title'         => (string) $row['title'],
					'detail'        => (string) $row['detail'],
					'records_count' => (int) $row['records_count'],
					'time'          => self::formatRelativeTime($row['created_at']),
					'created_at'    => (string) $row['created_at'],
				);
			}
		}

		return $items;
	}


	/**
	 * Dọn log cũ hơn N ngày. Gọi từ cron task 1 lần/ngày.
	 *
	 * @param int $days
	 * @return int Số bản ghi đã xoá
	 */
	public static function pruneOldLogs($days = 90)
	{
		self::ensureInstalled();
		$days = max(1, (int) $days);
		$adb = PearDatabase::getInstance();

		$countRes = $adb->pquery(
			'SELECT COUNT(*) AS c FROM ' . self::LOG_TABLE . '
			 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
			array($days)
		);
		$deleted = 0;
		if ($countRes && $adb->num_rows($countRes) > 0) {
			$deleted = (int) $adb->query_result($countRes, 0, 'c');
		}

		if ($deleted > 0) {
			$adb->pquery(
				'DELETE FROM ' . self::LOG_TABLE . '
				 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
				array($days)
			);
		}
		return $deleted;
	}

	protected static function hubCatalogDefinitions()
	{
		return array(
			'google_sheet' => array(
				'label' => 'Google Sheet',
				'subtitle' => 'Google Sheets API',
				'icon' => 'google_sheet',
				'use_adapter' => true,
			),
			'zalo_oa' => array(
				'label' => 'Zalo OA',
				'subtitle' => 'Zalo Official Account',
				'icon' => 'zalo_oa',
				'use_adapter' => true,
				'admin_hint' => 'Nhập App ID, Secret Key, OA ID. Kết nối OAuth để lấy Refresh Token — hệ thống tự gia hạn access token.',
			),
			'edubit' => array(
				'label' => 'Edubit',
				'subtitle' => 'Edubit LMS API',
				'icon' => 'edubit',
				'use_adapter' => true,
				'admin_hint' => 'Token API + Base URL. Catalog khóa học để Sales chọn tay — không có course_id mặc định.',
			),
			'misa' => array(
				'label' => 'MISA',
				'subtitle' => 'MISA AMIS API',
				'icon' => 'misa',
				'use_adapter' => true,
			),
			'ecommerce' => array(
				'label' => 'Website',
				'subtitle' => 'Website / E-commerce',
				'icon' => 'website',
				'use_adapter' => true,
			),
			'shopee_express' => array(
				'label' => 'Shopee Express',
				'subtitle' => 'Shopee Logistics API',
				'icon' => 'shopee',
				'use_adapter' => false,
				'admin_hint' => 'Partner ID và API key từ Shopee Open Platform.',
			),
			'ghtk' => array(
				'label' => 'GHTK',
				'subtitle' => 'Giao Hàng Tiết Kiệm',
				'icon' => 'ghtk',
				'use_adapter' => false,
				'admin_hint' => 'Token API GHTK để tạo và theo dõi vận đơn.',
			),
			'email_smtp' => array(
				'label' => 'Email / SMTP',
				'subtitle' => 'Outgoing mail server',
				'icon' => 'email',
				'use_adapter' => false,
				'admin_hint' => 'Có thể cấu hình SMTP tại đây hoặc Cài đặt → Máy chủ gửi mail.',
			),
		);
	}

	protected static function buildHubConnectionItem($code, array $def)
	{
		$base = array(
			'code' => $code,
			'label' => $def['label'],
			'subtitle' => $def['subtitle'],
			'icon' => $def['icon'],
			'enabled' => false,
			'base_url' => '',
			'status' => 'not_configured',
			'status_label' => self::statusLabel('not_configured'),
			'hub_status' => isset($def['hub_status']) ? $def['hub_status'] : 'inactive',
			'hub_status_label' => self::hubStatusLabel(isset($def['hub_status']) ? $def['hub_status'] : 'inactive'),
			'last_sync' => '',
			'last_sync_hint' => isset($def['sync_hint']) ? $def['sync_hint'] : '—',
			'last_error' => '',
			'legacy_url' => self::MENU_LINK . '#code=' . rawurlencode($code),
			'configure_url' => self::MENU_LINK . '#code=' . rawurlencode($code),
			'two_way' => false,
			'webhook_url' => '',
			'api_key_masked' => '••••••••••••',
			'notes' => '',
		);

		if (!empty($def['use_adapter'])) {
			try {
				$cfg = self::adapter($code)->getConfigForAdmin();
				$base = array_merge($base, $cfg);
				$base['hub_status'] = self::mapHubStatus($cfg['status'], !empty($cfg['enabled']));
				$base['hub_status_label'] = self::hubStatusLabel($base['hub_status']);
				if (!empty($cfg['last_sync'])) {
					$base['last_sync_hint'] = self::formatRelativeTime($cfg['last_sync']);
				}
			} catch (Exception $e) {
				$base['hub_status'] = 'warning';
				$base['hub_status_label'] = self::hubStatusLabel('warning');
			}
		} else {
			$row = self::getRow($code);
			if (!empty($row['base_url'])) {
				$base['base_url'] = $row['base_url'];
			}
			$base['enabled'] = !empty($row['enabled']);
			if (!empty($row['last_sync'])) {
				$base['last_sync'] = $row['last_sync'];
				$base['last_sync_hint'] = self::formatRelativeTime($row['last_sync']);
			}
			if (!empty($row['last_error'])) {
				$base['last_error'] = $row['last_error'];
			}
		}

		if ($code === 'zalo_oa') {
			$base['base_url'] = 'https://openapi.zalo.me/v2.0';
			$base['webhook_url'] = 'https://crm.example.com/webhook/zalo';
			$base['two_way'] = true;
			$base['notes'] = 'Token OAuth cần gia hạn định kỳ.';
		} elseif ($code === 'email_smtp') {
			$base['base_url'] = 'smtp.gmail.com:587';
			$base['notes'] = 'Dùng cấu hình máy chủ gửi mail hệ thống.';
			$base['configure_url'] = 'index.php?module=Vtiger&parent=Settings&view=OutgoingServerDetail';
		}

		$base['hub_status_label'] = self::hubStatusLabel($base['hub_status']);
		return $base;
	}

	public static function mapHubStatus($status, $enabled = true)
	{
		$status = (string) $status;
		if ($status === 'error') {
			return 'error';
		}
		if ($status === 'coming_soon' || $status === 'not_configured') {
			return 'warning';
		}
		if ($status === 'ok' || $status === 'idle') {
			return 'active';
		}
		if (!$enabled) {
			return 'warning';
		}
		return 'inactive';
	}

	public static function hubStatusLabel($hubStatus)
	{
		$map = array(
			'active' => 'LBL_NK_HUB_STATUS_ACTIVE',
			'warning' => 'LBL_NK_HUB_STATUS_WARNING',
			'error' => 'LBL_NK_HUB_STATUS_ERROR',
			'inactive' => 'LBL_NK_HUB_STATUS_INACTIVE',
		);
		$key = isset($map[$hubStatus]) ? $map[$hubStatus] : 'LBL_NK_HUB_STATUS_INACTIVE';
		return vtranslate($key, 'Settings:Vtiger');
	}

	protected static function formatRelativeTime($datetime)
	{
		$ts = strtotime((string) $datetime);
		if (!$ts) {
			return (string) $datetime;
		}
		$diff = time() - $ts;
		if ($diff < 0) {
			return 'Vừa xong';
		}
		if ($diff < 60) {
			return 'Vừa xong';
		}
		if ($diff < 3600) {
			return (int) floor($diff / 60) . ' phút trước';
		}
		if ($diff < 86400) {
			return (int) floor($diff / 3600) . ' giờ trước';
		}
		return (int) floor($diff / 86400) . ' ngày trước';
	}

	/**
	 * @param string $code
	 * @return array
	 */
	public static function getRow($code)
	{
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
	public static function saveRow($code, array $fields, $userId = 0)
	{
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

	public static function statusLabel($status)
	{
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

	protected static function decodeJson($raw)
	{
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

	protected static function encodeJson($value)
	{
		if (!is_array($value)) {
			$value = array();
		}
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	public static function pipelineData()
	{
		self::ensureInstalled();
		$connections = self::catalogForHub();

		// Phân loại theo hướng sync
		$sources = array();   // nguồn vào CRM
		$externals = array(); // đích ra từ CRM
		foreach ($connections as $c) {
			if (in_array($c['code'], array('ecommerce', 'google_sheet', 'zalo_oa'), true)) {
				$sources[] = array(
					'code' => $c['code'],
					'label' => $c['label'],
					'icon' => $c['icon'],
					'status' => $c['hub_status'],
				);
			} else {
				$externals[] = array(
					'code' => $c['code'],
					'label' => $c['label'],
					'icon' => $c['icon'],
					'status' => $c['hub_status'],
				);
			}
		}

		// Đếm log theo event_type trong 24h
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT event_type, status, COUNT(*) AS c
         FROM ' . self::LOG_TABLE . '
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY event_type, status',
			array()
		);
		$byEvent = array();
		while ($res && ($row = $adb->fetchByAssoc($res))) {
			$byEvent[$row['event_type']][$row['status']] = (int) $row['c'];
		}

		return array(
			'sources' => $sources,
			'externals' => $externals,
			'events_24h' => $byEvent,
			'generated_at' => date('Y-m-d H:i:s'),
		);
	}
}
