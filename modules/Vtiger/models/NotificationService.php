<?php
/*+***********************************************************************************
 * Chuông Modern Notifications: mention, gộp CSKH, preference âm thanh (mọi máy).
 *************************************************************************************/

class Vtiger_NotificationService {

	const CSKH_ID_PREFIX = 'cskh:';
	const CSKH_CACHE_TTL = 45;

	/**
	 * @return PearDatabase
	 */
	protected static function db() {
		return PearDatabase::getInstance();
	}

	public static function ensurePrefSchema() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		self::db()->pquery(
			"CREATE TABLE IF NOT EXISTS bace_user_notification_pref (
				userid INT UNSIGNED NOT NULL PRIMARY KEY,
				sound_enabled TINYINT(1) NOT NULL DEFAULT 1,
				volume DECIMAL(3,2) NOT NULL DEFAULT 0.70,
				updated_at DATETIME NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
		// Per-user channel toggles (JSON object of channel => 0|1)
		try {
			$cols = self::db()->pquery("SHOW COLUMNS FROM bace_user_notification_pref LIKE 'channels_json'", array());
			if (!$cols || self::db()->num_rows($cols) === 0) {
				self::db()->pquery(
					'ALTER TABLE bace_user_notification_pref ADD COLUMN channels_json TEXT NULL',
					array()
				);
			}
		} catch (Exception $e) {
			// ignore — column may already exist
		}
	}

	/**
	 * Channel keys users can toggle in Settings.
	 * @return array<string,array{label:string,group:string,default:bool}>
	 */
	public static function channelCatalog() {
		return array(
			'leads_assign' => array('label' => 'Lead mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'potentials_assign' => array('label' => 'Cơ hội mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'potentials_reminder' => array('label' => 'Cơ hội sắp đến hạn', 'group' => 'Bán hàng', 'default' => true),
			'accounts_assign' => array('label' => 'Khách hàng (Tổ chức) mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'contacts_assign' => array('label' => 'Liên hệ mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'quotes_assign' => array('label' => 'Báo giá mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'salesorder_assign' => array('label' => 'Đơn hàng mới / được giao', 'group' => 'Bán hàng', 'default' => true),
			'helpdesk_assign' => array('label' => 'Ticket hỗ trợ mới / được giao', 'group' => 'Hỗ trợ', 'default' => true),
			'cskh' => array('label' => 'Cảnh báo CSKH / chăm sóc', 'group' => 'Hỗ trợ', 'default' => true),
			'mention' => array('label' => 'Được @mention trong ghi chú', 'group' => 'Chung', 'default' => true),
			'calendar_reminder' => array('label' => 'Nhắc lịch / sự kiện', 'group' => 'Chung', 'default' => true),
			'project_assign' => array('label' => 'Dự án / task được giao', 'group' => 'Quản lý', 'default' => true),
			'project_reminder' => array('label' => 'Dự án / task sắp hết hạn', 'group' => 'Quản lý', 'default' => true),
			'warehouse_expiry' => array('label' => 'Kho — cảnh báo hết hạn', 'group' => 'Kho', 'default' => true),
		);
	}

	/**
	 * Map CRM module + notif type → preference channel.
	 */
	public static function channelForModule($module, $notifType = 'assign') {
		$module = (string) $module;
		$notifType = (string) $notifType;
		if ($notifType === 'mention') {
			return 'mention';
		}
		if ($notifType === 'cskh') {
			return 'cskh';
		}
		$map = array(
			'Leads' => array('assign' => 'leads_assign'),
			'Potentials' => array('assign' => 'potentials_assign', 'reminder' => 'potentials_reminder'),
			'Accounts' => array('assign' => 'accounts_assign'),
			'Contacts' => array('assign' => 'contacts_assign'),
			'Quotes' => array('assign' => 'quotes_assign'),
			'SalesOrder' => array('assign' => 'salesorder_assign'),
			'HelpDesk' => array('assign' => 'helpdesk_assign'),
			'Calendar' => array('assign' => 'calendar_reminder', 'reminder' => 'calendar_reminder'),
			'Events' => array('assign' => 'calendar_reminder', 'reminder' => 'calendar_reminder'),
			'Project' => array('assign' => 'project_assign', 'reminder' => 'project_reminder'),
			'ProjectTask' => array('assign' => 'project_assign', 'reminder' => 'project_reminder'),
			'Warehouse' => array('assign' => 'warehouse_expiry', 'reminder' => 'warehouse_expiry', 'other' => 'warehouse_expiry'),
		);
		if (isset($map[$module][$notifType])) {
			return $map[$module][$notifType];
		}
		if (isset($map[$module]['assign'])) {
			return $map[$module]['assign'];
		}
		return '';
	}

	/**
	 * @param int $userId
	 * @return array<string,bool> all catalog keys
	 */
	public static function getChannelPrefs($userId) {
		$userId = (int) $userId;
		$catalog = self::channelCatalog();
		$prefs = array();
		foreach ($catalog as $key => $meta) {
			$prefs[$key] = !empty($meta['default']);
		}
		if ($userId <= 0) {
			return $prefs;
		}
		self::ensurePrefSchema();
		$res = self::db()->pquery(
			'SELECT channels_json FROM bace_user_notification_pref WHERE userid = ?',
			array($userId)
		);
		if ($res && self::db()->num_rows($res) > 0) {
			$raw = self::db()->query_result($res, 0, 'channels_json');
			$decoded = $raw ? json_decode((string) $raw, true) : null;
			if (is_array($decoded)) {
				foreach ($catalog as $key => $meta) {
					if (array_key_exists($key, $decoded)) {
						$prefs[$key] = !empty($decoded[$key]) && $decoded[$key] !== '0' && $decoded[$key] !== 0;
					}
				}
			}
		}
		return $prefs;
	}

	/**
	 * @param int $userId
	 * @param string $channel
	 * @return bool
	 */
	public static function isChannelEnabled($userId, $channel) {
		$channel = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $channel));
		if ($channel === '') {
			return true;
		}
		$prefs = self::getChannelPrefs($userId);
		if (!array_key_exists($channel, $prefs)) {
			return true;
		}
		return !empty($prefs[$channel]);
	}

	/**
	 * @param int $userId
	 * @param array $channels channel => bool
	 * @return array<string,bool>
	 */
	public static function setChannelPrefs($userId, array $channels) {
		$userId = (int) $userId;
		if ($userId <= 0) {
			return self::getChannelPrefs(0);
		}
		self::ensurePrefSchema();
		$catalog = self::channelCatalog();
		$current = self::getChannelPrefs($userId);
		foreach ($channels as $key => $val) {
			$key = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $key));
			if (!isset($catalog[$key])) {
				continue;
			}
			$current[$key] = !($val === false || $val === 0 || $val === '0' || $val === 'false');
		}
		$store = array();
		foreach ($catalog as $key => $_meta) {
			$store[$key] = !empty($current[$key]) ? 1 : 0;
		}
		$json = json_encode($store);
		$sound = self::getSoundPref($userId);
		$now = date('Y-m-d H:i:s');
		self::db()->pquery(
			'INSERT INTO bace_user_notification_pref (userid, sound_enabled, volume, channels_json, updated_at)
			 VALUES (?,?,?,?,?)
			 ON DUPLICATE KEY UPDATE channels_json = VALUES(channels_json), updated_at = VALUES(updated_at)',
			array($userId, $sound['enabled'] ? 1 : 0, $sound['volume'], $json, $now)
		);
		return self::getChannelPrefs($userId);
	}

	/**
	 * Create notification only if user enabled the channel.
	 *
	 * @param int $userId
	 * @param string $module
	 * @param int $recordId
	 * @param string $message
	 * @param string $notifType
	 * @param string $channel empty → derive from module+type
	 * @return int
	 */
	public static function createIfEnabled($userId, $module, $recordId, $message, $notifType = 'other', $channel = '') {
		if ($channel === '') {
			$channel = self::channelForModule($module, $notifType);
		}
		if ($channel !== '' && !self::isChannelEnabled($userId, $channel)) {
			return 0;
		}
		return self::create($userId, $module, $recordId, $message, $notifType);
	}

	/**
	 * Register event handlers + Settings menu (idempotent).
	 */
	public static function ensureInstalled() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		self::ensurePrefSchema();
		self::ensureHandlersRegistered();
		self::ensureSettingsMenu();
	}

	public static function ensureHandlersRegistered() {
		$adb = self::db();
		$handlers = array(
			array('AccountsHandler', 'modules/Accounts/AccountsHandler.php'),
			array('ContactsHandler', 'modules/Contacts/ContactsHandler.php'),
			array('PotentialsHandler', 'modules/Potentials/PotentialsHandler.php'),
			array('HelpDeskHandler', 'modules/HelpDesk/HelpDeskHandler.php'),
			array('LeadsAssignHandler', 'modules/Leads/handlers/LeadsAssignHandler.php'),
			array('QuotesAssignHandler', 'modules/Quotes/QuotesAssignHandler.php'),
			array('SalesOrderAssignHandler', 'modules/SalesOrder/SalesOrderAssignHandler.php'),
		);
		foreach ($handlers as $h) {
			$class = $h[0];
			$path = $h[1];
			$exists = $adb->pquery(
				'SELECT eventhandler_id, is_active FROM vtiger_eventhandlers WHERE handler_class = ? AND event_name = ? LIMIT 1',
				array($class, 'vtiger.entity.aftersave.final')
			);
			if ($exists && $adb->num_rows($exists) > 0) {
				$id = (int) $adb->query_result($exists, 0, 'eventhandler_id');
				$active = (int) $adb->query_result($exists, 0, 'is_active');
				if ($active !== 1) {
					$adb->pquery('UPDATE vtiger_eventhandlers SET is_active = 1, handler_path = ? WHERE eventhandler_id = ?', array($path, $id));
				}
				continue;
			}
			$handlerId = $adb->getUniqueId('vtiger_eventhandlers');
			$adb->pquery(
				'INSERT INTO vtiger_eventhandlers (eventhandler_id, event_name, handler_path, handler_class, cond, is_active, dependent_on)
				 VALUES (?,?,?,?,?,?,?)',
				array($handlerId, 'vtiger.entity.aftersave.final', $path, $class, '', 1, '[]')
			);
		}
	}

	public static function ensureSettingsMenu() {
		$adb = self::db();
		$name = 'LBL_NK_NOTIFICATION_PREFS';
		$exists = $adb->pquery('SELECT fieldid FROM vtiger_settings_field WHERE name = ? LIMIT 1', array($name));
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}
		$blockid = 0;
		foreach (array('LBL_USER_MANAGEMENT', 'LBL_OTHER_SETTINGS', 'LBL_CONFIGURATION') as $label) {
			if (function_exists('getSettingsBlockId')) {
				$blockid = (int) getSettingsBlockId($label);
			}
			if ($blockid > 0) {
				break;
			}
		}
		if ($blockid <= 0) {
			$blockRes = $adb->pquery('SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence ASC LIMIT 1', array());
			if ($blockRes && $adb->num_rows($blockRes) > 0) {
				$blockid = (int) $adb->query_result($blockRes, 0, 'blockid');
			}
		}
		if ($blockid <= 0) {
			return;
		}
		$seq = 1;
		$seqRes = $adb->pquery('SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?', array($blockid));
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
				$name,
				'',
				'LBL_NK_NOTIFICATION_PREFS_DESC',
				'index.php?module=Vtiger&parent=Settings&view=NotificationPrefs',
				$seq,
				0,
			)
		);
	}

	/**
	 * @param int $userId
	 * @return array{enabled:bool,volume:float}
	 */
	public static function getSoundPref($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return array('enabled' => true, 'volume' => 0.7);
		}
		self::ensurePrefSchema();
		$res = self::db()->pquery(
			'SELECT sound_enabled, volume FROM bace_user_notification_pref WHERE userid = ?',
			array($userId)
		);
		if ($res && self::db()->num_rows($res) > 0) {
			return array(
				'enabled' => ((int)self::db()->query_result($res, 0, 'sound_enabled')) === 1,
				'volume' => max(0.0, min(1.0, (float)self::db()->query_result($res, 0, 'volume'))),
			);
		}
		return array('enabled' => true, 'volume' => 0.7);
	}

	/**
	 * @param int $userId
	 * @param bool $enabled
	 * @param float|null $volume
	 * @return array{enabled:bool,volume:float}
	 */
	public static function setSoundPref($userId, $enabled, $volume = null) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return self::getSoundPref(0);
		}
		self::ensurePrefSchema();
		$current = self::getSoundPref($userId);
		$vol = $volume === null ? $current['volume'] : max(0.0, min(1.0, (float)$volume));
		$en = $enabled ? 1 : 0;
		$now = date('Y-m-d H:i:s');
		self::db()->pquery(
			'INSERT INTO bace_user_notification_pref (userid, sound_enabled, volume, updated_at)
			 VALUES (?,?,?,?)
			 ON DUPLICATE KEY UPDATE sound_enabled = VALUES(sound_enabled),
			   volume = VALUES(volume), updated_at = VALUES(updated_at)',
			array($userId, $en, $vol, $now)
		);
		return array('enabled' => (bool)$en, 'volume' => $vol);
	}

	/**
	 * @param int $userId
	 * @param string $module
	 * @param int $recordId
	 * @param string $message
	 * @param string $notifType mention|assign|cskh|reminder|other
	 * @return int insert id
	 */
	public static function create($userId, $module, $recordId, $message, $notifType = 'other') {
		$userId = (int)$userId;
		$recordId = (int)$recordId;
		$module = trim((string)$module);
		$message = trim((string)$message);
		if ($userId <= 0 || $module === '' || $message === '') {
			return 0;
		}
		if (function_exists('mb_substr')) {
			$message = mb_substr($message, 0, 500, 'UTF-8');
		} else {
			$message = substr($message, 0, 500);
		}
		// Type encoded as zero-width prefix so old UI still works; stripped on read.
		$message = self::encodeTypePrefix($notifType) . $message;
		self::db()->pquery(
			'INSERT INTO vtiger_notifications (userid, module, recordid, message, is_read, created_at)
			 VALUES (?,?,?,?,0,NOW())',
			array($userId, $module, $recordId, $message)
		);
		return (int)self::db()->getLastInsertID();
	}

	protected static function encodeTypePrefix($type) {
		$type = preg_replace('/[^a-z_]/', '', strtolower((string)$type));
		if ($type === '' || $type === 'other') {
			return '';
		}
		return '[t:' . $type . ']';
	}

	/**
	 * @param string $message
	 * @return array{type:string,message:string}
	 */
	public static function decodeMessage($message) {
		$message = (string)$message;
		$type = 'other';
		if (preg_match('/^\[t:([a-z_]+)\]/i', $message, $m)) {
			$type = strtolower($m[1]);
			$message = substr($message, strlen($m[0]));
		} else {
			$type = self::inferTypeFromText($message);
		}
		return array('type' => $type, 'message' => $message);
	}

	protected static function inferTypeFromText($message) {
		$m = (string)$message;
		if (stripos($m, 'được assign') !== false || stripos($m, 'assigned') !== false || stripos($m, 'được giao') !== false) {
			return 'assign';
		}
		if (stripos($m, 'nhắc đến bạn') !== false || stripos($m, 'mention') !== false || stripos($m, '@') === 0) {
			return 'mention';
		}
		if (stripos($m, 'Cần CSKH') !== false || stripos($m, 'Cần chăm sóc') !== false || stripos($m, 'sắp đến hạn') !== false) {
			return 'cskh';
		}
		if (stripos($m, 'sắp hết hạn') !== false || stripos($m, 'deadline') !== false) {
			return 'reminder';
		}
		return 'other';
	}

	/**
	 * Parse @Name tokens and create mention notifications.
	 *
	 * @param string $commentContent
	 * @param int $commentId
	 * @param int $relatedToCrmId parent record
	 * @param int $authorUserId
	 * @return int number of notifications created
	 */
	public static function notifyMentionsFromComment($commentContent, $commentId, $relatedToCrmId, $authorUserId) {
		$commentContent = html_entity_decode(strip_tags((string)$commentContent), ENT_QUOTES, 'UTF-8');
		$commentId = (int)$commentId;
		$relatedToCrmId = (int)$relatedToCrmId;
		$authorUserId = (int)$authorUserId;
		if ($commentContent === '' || $relatedToCrmId <= 0) {
			return 0;
		}

		$mentionedIds = self::resolveMentionedUserIds($commentContent, $authorUserId);
		if (empty($mentionedIds)) {
			return 0;
		}

		$parentModule = 'Vtiger';
		try {
			$setype = getSalesEntityType($relatedToCrmId);
			if (!empty($setype)) {
				$parentModule = $setype;
			}
		} catch (Exception $e) {
			// keep default
		}

		$authorName = 'Ai đó';
		try {
			if (function_exists('getUserFullName')) {
				$n = getUserFullName($authorUserId);
				if ($n) {
					$authorName = $n;
				}
			}
		} catch (Exception $e) {
			// ignore
		}

		$preview = $commentContent;
		if (function_exists('mb_substr')) {
			$preview = mb_substr($preview, 0, 120, 'UTF-8');
			if (mb_strlen($commentContent, 'UTF-8') > 120) {
				$preview .= '…';
			}
		} else {
			$preview = substr($preview, 0, 120);
			if (strlen($commentContent) > 120) {
				$preview .= '…';
			}
		}

		$parentLabel = '';
		try {
			$names = getEntityName($parentModule, array($relatedToCrmId));
			if (is_array($names) && !empty($names[$relatedToCrmId])) {
				$parentLabel = $names[$relatedToCrmId];
			}
		} catch (Exception $e) {
			// ignore
		}

		$moduleLabel = $parentModule;
		$msgParts = array($authorName . ' đã nhắc đến bạn trong ghi chú');
		if ($parentLabel !== '') {
			$msgParts[] = $moduleLabel . ': ' . $parentLabel;
		}
		$msgParts[] = $preview;
		$message = implode("\n", $msgParts);

		$created = 0;
		foreach ($mentionedIds as $uid) {
			$dup = self::db()->pquery(
				"SELECT id FROM vtiger_notifications
				 WHERE userid = ? AND module = ? AND recordid = ?
				   AND message LIKE ?
				   AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
				 LIMIT 1",
				array($uid, $parentModule, $relatedToCrmId, '%[t:mention]%đã nhắc đến bạn%')
			);
			if ($dup && self::db()->num_rows($dup) > 0) {
				continue;
			}
			$id = self::createIfEnabled($uid, $parentModule, $relatedToCrmId, $message, 'mention', 'mention');
			if ($id > 0) {
				$created++;
			}
		}
		return $created;
	}

	/**
	 * @param string $text
	 * @param int $excludeUserId
	 * @return int[]
	 */
	public static function resolveMentionedUserIds($text, $excludeUserId = 0) {
		$excludeUserId = (int)$excludeUserId;
		$tokens = array();
		if (preg_match_all('/@([^\s@<>\[\]{}()]+)/u', $text, $matches)) {
			foreach ($matches[1] as $raw) {
				$token = trim($raw, ".,;:!?\"'`");
				if ($token !== '') {
					$tokens[] = $token;
				}
			}
		}
		if (empty($tokens)) {
			return array();
		}

		$adb = self::db();
		$res = $adb->pquery(
			"SELECT id, user_name, first_name, last_name
			 FROM vtiger_users
			 WHERE status = 'Active' AND deleted = 0",
			array()
		);
		if (!$res) {
			return array();
		}

		$userKeys = array(); // normalized key => id
		while ($row = $adb->fetchByAssoc($res)) {
			$uid = (int)$row['id'];
			if ($uid <= 0 || $uid === $excludeUserId) {
				continue;
			}
			$first = decode_html(isset($row['first_name']) ? $row['first_name'] : '');
			$last = decode_html(isset($row['last_name']) ? $row['last_name'] : '');
			$userName = decode_html(isset($row['user_name']) ? $row['user_name'] : '');
			$full = trim($first . ' ' . $last);
			$compact = preg_replace('/\s+/u', '', $full);
			$variants = array(
				self::normKey($userName),
				self::normKey($full),
				self::normKey($compact),
				self::normKey($first . $last),
				self::normKey($last . $first),
				self::normKey($last . ' ' . $first),
			);
			foreach ($variants as $k) {
				if ($k !== '') {
					$userKeys[$k] = $uid;
				}
			}
		}

		$found = array();
		foreach ($tokens as $token) {
			$k = self::normKey($token);
			if ($k !== '' && isset($userKeys[$k])) {
				$found[$userKeys[$k]] = true;
			}
		}
		return array_map('intval', array_keys($found));
	}

	protected static function normKey($s) {
		$s = decode_html((string)$s);
		$s = trim($s);
		if ($s === '') {
			return '';
		}
		// Vietnamese-friendly lowercase
		if (function_exists('mb_strtolower')) {
			$s = mb_strtolower($s, 'UTF-8');
		} else {
			$s = strtolower($s);
		}
		$s = preg_replace('/\s+/u', '', $s);
		return $s;
	}

	/**
	 * @param int $userId
	 * @return array[]
	 */
	public static function fetchCskhAlerts($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return array();
		}
		if (!self::isChannelEnabled($userId, 'cskh')) {
			return array();
		}
		// Session cache — poll every ~3s would otherwise be heavy.
		if (session_status() === PHP_SESSION_ACTIVE) {
			$cache = isset($_SESSION['mk_cskh_notif_cache']) ? $_SESSION['mk_cskh_notif_cache'] : null;
			if (is_array($cache)
				&& isset($cache['uid'], $cache['t'], $cache['rows'])
				&& (int)$cache['uid'] === $userId
				&& (time() - (int)$cache['t']) < self::CSKH_CACHE_TTL
			) {
				return $cache['rows'];
			}
		}

		$rows = array();
		try {
			$path = 'modules/HelpDesk/models/TagRuleEngineService.php';
			if (!is_file($path)) {
				return array();
			}
			require_once $path;
			if (!class_exists('HelpDesk_TagRuleEngineService')) {
				return array();
			}
			$svc = HelpDesk_TagRuleEngineService::getInstance();
			if (!method_exists($svc, 'getAlerts')) {
				return array();
			}
			$alerts = $svc->getAlerts($userId, 40);
			foreach ($alerts as $a) {
				$leadId = (int)(isset($a['lead_id']) ? $a['lead_id'] : 0);
				$rule = isset($a['rule']) && is_array($a['rule']) ? $a['rule'] : array();
				$ruleId = isset($rule['id']) ? (string)$rule['id'] : 'rule-cskh';
				$name = isset($a['name']) ? $a['name'] : ('Lead #' . $leadId);
				$days = isset($a['days_idle']) ? (int)$a['days_idle'] : 0;
				$ruleName = isset($rule['name']) ? $rule['name'] : 'Cảnh báo';
				$next = isset($a['next_action']) ? $a['next_action'] : '';
				$title = $ruleName . ' · ' . $name;
				$body = 'Không tương tác ~' . $days . ' ngày';
				if ($next !== '') {
					$body .= "\n" . $next;
				}
					$virtualId = self::CSKH_ID_PREFIX . $leadId . ':' . $ruleId;
				// Stable timestamp (day grain) so poll doesn't re-render text/time every few seconds.
				$ts = time() - max(0, $days) * 86400;
				$rows[] = array(
					'id' => $virtualId,
					'module' => 'Leads',
					'recordid' => $leadId,
					'message' => $title . "\n" . $body,
					'created_at' => date('Y-m-d 12:00:00', $ts),
					'is_read' => 0,
					'notif_type' => 'cskh',
					'detail_url' => isset($a['detail_url'])
						? $a['detail_url']
						: ('index.php?module=Leads&view=Detail&record=' . $leadId . '&app=SALES'),
					'cskh_rule_id' => $ruleId,
					'cskh_lead_id' => $leadId,
					'days_idle' => $days,
				);
			}
		} catch (Exception $e) {
			$rows = array();
		}

		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION['mk_cskh_notif_cache'] = array(
				'uid' => $userId,
				't' => time(),
				'rows' => $rows,
			);
		}
		return $rows;
	}

	public static function bustCskhCache() {
		if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['mk_cskh_notif_cache'])) {
			unset($_SESSION['mk_cskh_notif_cache']);
		}
	}

	/**
	 * @param string $virtualId
	 * @return array{lead_id:int,rule_id:string}|null
	 */
	public static function parseCskhId($virtualId) {
		$virtualId = (string)$virtualId;
		if (strpos($virtualId, self::CSKH_ID_PREFIX) !== 0) {
			return null;
		}
		$rest = substr($virtualId, strlen(self::CSKH_ID_PREFIX));
		// leadId:ruleId — rule id may contain colons rarely; first numeric segment = lead
		if (!preg_match('/^(\d+):(.+)$/', $rest, $m)) {
			return null;
		}
		return array(
			'lead_id' => (int)$m[1],
			'rule_id' => $m[2],
		);
	}

	/**
	 * Mark CSKH alert as done for this user (hide from list).
	 */
	public static function dismissCskh($userId, $virtualId) {
		$parsed = self::parseCskhId($virtualId);
		if (!$parsed) {
			return false;
		}
		$path = 'modules/HelpDesk/models/TagRuleEngineService.php';
		if (!is_file($path)) {
			return false;
		}
		require_once $path;
		if (!class_exists('HelpDesk_TagRuleEngineService')) {
			return false;
		}
		$svc = HelpDesk_TagRuleEngineService::getInstance();
		if (method_exists($svc, 'upsertDismissal')) {
			$svc->upsertDismissal((int)$userId, $parsed['lead_id'], $parsed['rule_id'], null);
			self::bustCskhCache();
			return true;
		}
		return false;
	}

	/**
	 * Dismiss all CSKH currently visible to the user.
	 */
	public static function dismissAllCskh($userId) {
		$alerts = self::fetchCskhAlerts($userId);
		foreach ($alerts as $row) {
			if (!empty($row['id'])) {
				self::dismissCskh($userId, $row['id']);
			}
		}
		self::bustCskhCache();
	}

	/**
	 * Enrich DB row for API.
	 */
	public static function normalizeDbRow(array $row) {
		$decoded = self::decodeMessage(isset($row['message']) ? decode_html($row['message']) : '');
		$row['message'] = $decoded['message'];
		$row['notif_type'] = $decoded['type'];
		$row['is_read'] = isset($row['is_read']) ? (int)$row['is_read'] : 0;
		$mod = isset($row['module']) ? $row['module'] : 'Vtiger';
		$rid = isset($row['recordid']) ? (int)$row['recordid'] : 0;
		if ($rid > 0 && empty($row['detail_url'])) {
			$row['detail_url'] = 'index.php?module=' . $mod . '&view=Detail&record=' . $rid;
		}
		return $row;
	}

	/**
	 * Merged list: CSKH virtual (unread-only) + DB rows.
	 *
	 * @param int $userId
	 * @param string $type all|read|unread
	 * @param int $limit
	 * @return array
	 */
	public static function getMergedList($userId, $type = 'all', $limit = 30) {
		$userId = (int)$userId;
		$limit = max(5, min(80, (int)$limit));
		$type = in_array($type, array('all', 'read', 'unread'), true) ? $type : 'unread';

		$adb = self::db();
		$params = array($userId);
		$where = 'userid = ?';
		if ($type === 'read') {
			$where .= ' AND is_read = 1';
		} elseif ($type === 'unread') {
			$where .= ' AND is_read = 0';
		}

		$sql = "SELECT id, module, recordid, message, created_at, is_read
				FROM vtiger_notifications
				WHERE $where
				ORDER BY created_at DESC
				LIMIT " . (int)$limit;
		$result = $adb->pquery($sql, $params);
		$list = array();
		if ($result) {
			while ($row = $adb->fetchByAssoc($result)) {
				$list[] = self::normalizeDbRow($row);
			}
		}

		// CSKH only on unread/all — they have no "read" state until dismissed.
		if ($type === 'all' || $type === 'unread') {
			$cskh = self::fetchCskhAlerts($userId);
			// Prefer CSKH first for CSKH team priority
			$list = array_merge($cskh, $list);
		}

		// Re-sort: unread first, then created_at desc for DB; CSKH first among unreads
		usort($list, function ($a, $b) {
			$ra = (int)(isset($a['is_read']) ? $a['is_read'] : 0);
			$rb = (int)(isset($b['is_read']) ? $b['is_read'] : 0);
			if ($ra !== $rb) {
				return $ra - $rb; // unread 0 first
			}
			$ta = isset($a['notif_type']) ? $a['notif_type'] : 'other';
			$tb = isset($b['notif_type']) ? $b['notif_type'] : 'other';
			if ($ta === 'cskh' && $tb !== 'cskh') {
				return -1;
			}
			if ($tb === 'cskh' && $ta !== 'cskh') {
				return 1;
			}
			$ca = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
			$cb = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
			return $cb - $ca;
		});

		return array_slice($list, 0, $limit);
	}

	/**
	 * Unread = DB unread + open CSKH alerts.
	 */
	public static function countUnread($userId) {
		$userId = (int)$userId;
		$adb = self::db();
		$count = 0;
		$res = $adb->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_notifications WHERE userid = ? AND is_read = 0',
			array($userId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$count = (int)$adb->query_result($res, 0, 'c');
		}
		$count += count(self::fetchCskhAlerts($userId));
		return $count;
	}

	/**
	 * Mark one notification read (DB id or CSKH virtual).
	 */
	public static function markRead($userId, $notificationId) {
		$userId = (int)$userId;
		$id = (string)$notificationId;
		if (strpos($id, self::CSKH_ID_PREFIX) === 0) {
			return self::dismissCskh($userId, $id);
		}
		if (!ctype_digit($id) && !is_numeric($id)) {
			return false;
		}
		self::db()->pquery(
			'UPDATE vtiger_notifications SET is_read = 1, read_at = NOW()
			 WHERE id = ? AND userid = ? AND is_read = 0',
			array((int)$id, $userId)
		);
		return true;
	}

	public static function markAllRead($userId) {
		$userId = (int)$userId;
		self::db()->pquery(
			'UPDATE vtiger_notifications SET is_read = 1, read_at = NOW()
			 WHERE userid = ? AND is_read = 0',
			array($userId)
		);
		self::dismissAllCskh($userId);
		return true;
	}

	/**
	 * @param int $userId
	 * @param array $ids mixed numeric + cskh virtual
	 */
	public static function deleteIds($userId, array $ids) {
		$userId = (int)$userId;
		$numeric = array();
		foreach ($ids as $id) {
			$id = (string)$id;
			if (strpos($id, self::CSKH_ID_PREFIX) === 0) {
				self::dismissCskh($userId, $id);
			} elseif (is_numeric($id)) {
				$numeric[] = (int)$id;
			}
		}
		if (!empty($numeric)) {
			$placeholders = implode(',', array_fill(0, count($numeric), '?'));
			$params = $numeric;
			$params[] = $userId;
			self::db()->pquery(
				"DELETE FROM vtiger_notifications WHERE id IN ($placeholders) AND userid = ?",
				$params
			);
		}
		return true;
	}

	public static function deleteAll($userId) {
		$userId = (int)$userId;
		self::db()->pquery('DELETE FROM vtiger_notifications WHERE userid = ?', array($userId));
		self::dismissAllCskh($userId);
		return true;
	}
}
