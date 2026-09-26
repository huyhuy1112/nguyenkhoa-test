<?php
/*+***********************************************************************************
 * Modern ServiceContracts (Khách chuyển nhượng) — list + affiliate AFF-xxxxxx.
 *************************************************************************************/

class ServiceContracts_ModernService {

	const MODULE = 'ServiceContracts';

	/** Whitelist tag keys (same BA buckets as Contacts / Leads list). */
	protected static $allowedTags = array(
		'moi_quen', 'da_co_quan_he', 'co_quan', 'chuan_bi_mo', 'gia_dinh',
		'chua_mqbh', 'da_tg_free', 'da_tg_fb1', 'thu_3', 'pcth', 'van_hanh', 'mkt', 'lop_khac',
		'tiem_nang', 'mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'dang_cham_soc',
		'dang_tu_van', 'kh_can_nhac', 'khong_mua', 'ngung_mua',
		'nhuong_quyen', 'da_ky_quy',
		'vang', 'bac', 'dong',
	);

	/** Picklists — DATA KHÁCH HÀNG NHƯỢNG QUYỀN spreadsheet. */
	public static function franchisePicklists() {
		return array(
			'franchise_status' => array(
				'Quan Tâm/Tham Khảo',
				'Không đủ tài chính',
				'Đã Kí Quỹ',
				'Đang chăm sóc',
				'Chuyển sang Nguyên Khoa',
			),
			'data_source' => array(
				// BA: nguồn = kênh (Website/Zalo/Facebook/TikTok) hoặc tự động "Được giới thiệu" khi nhập mã.
				'Website',
				'Zalo',
				'Facebook',
				'TikTok',
				'Được giới thiệu',
			),
			'contact_status' => array(
				'Chưa gọi',
				'Đã gửi tư vấn',
				'Thuê bao',
				'Ko nghe Máy Lần 1',
				'Ko nghe Máy Lần 2',
				'Ko nghe Máy Lần 3',
			),
			'interaction' => array(
				'Chưa liên hệ',
				'Đang liên hệ',
				'Đã liên hệ',
				'Không nghe máy',
				'Thuê bao',
				'Hẹn gọi lại',
				'Đã chốt',
			),
		);
	}

	public static function installSchema(PearDatabase $adb = null) {
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_sc_profile (
			servicecontractsid INT(19) NOT NULL,
			affiliate_code VARCHAR(32) DEFAULT NULL,
			phone VARCHAR(64) DEFAULT NULL,
			email VARCHAR(128) DEFAULT NULL,
			cccd VARCHAR(32) DEFAULT NULL,
			segment VARCHAR(64) DEFAULT NULL,
			district VARCHAR(128) DEFAULT NULL,
			address_line VARCHAR(255) DEFAULT NULL,
			area VARCHAR(255) DEFAULT NULL,
			sc_value DECIMAL(18,2) DEFAULT 0,
			last_touch DATETIME DEFAULT NULL,
			next_action VARCHAR(255) DEFAULT NULL,
			customer_type VARCHAR(32) DEFAULT NULL,
			received_date DATE DEFAULT NULL,
			business_note TEXT,
			franchise_status VARCHAR(128) DEFAULT NULL,
			fanpage VARCHAR(128) DEFAULT NULL,
			data_source VARCHAR(128) DEFAULT NULL,
			referrer VARCHAR(255) DEFAULT NULL,
			contact_status VARCHAR(128) DEFAULT NULL,
			interaction_1 TEXT,
			interaction_2 TEXT,
			interaction_3 TEXT,
			interaction_materials TEXT,
			is_modern TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT NULL,
			modified_at DATETIME DEFAULT NULL,
			PRIMARY KEY (servicecontractsid),
			UNIQUE KEY uniq_affiliate_code (affiliate_code),
			KEY idx_last_touch (last_touch)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		self::ensureFranchiseColumns($adb);

		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_sc_segments (
			id INT(11) NOT NULL AUTO_INCREMENT,
			userid INT(11) NOT NULL,
			name VARCHAR(128) NOT NULL,
			filters_json TEXT NOT NULL,
			created_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_user (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
	}

	/** ALTER existing installs to add franchise spreadsheet columns. */
	protected static function ensureFranchiseColumns(PearDatabase $adb) {
		$cols = array(
			'received_date' => 'DATE DEFAULT NULL',
			'business_note' => 'TEXT',
			'franchise_status' => 'VARCHAR(128) DEFAULT NULL',
			'fanpage' => 'VARCHAR(128) DEFAULT NULL',
			'data_source' => 'VARCHAR(128) DEFAULT NULL',
			'referrer' => 'VARCHAR(255) DEFAULT NULL',
			'contact_status' => 'VARCHAR(128) DEFAULT NULL',
			'interaction_1' => 'TEXT',
			'interaction_2' => 'TEXT',
			'interaction_3' => 'TEXT',
			'interaction_materials' => 'TEXT',
			'referral_code' => 'VARCHAR(64) DEFAULT NULL',
			'referral_tier_name' => 'VARCHAR(100) DEFAULT NULL',
			'referral_reward_amount' => 'DECIMAL(18,2) DEFAULT NULL',
			'registration_date' => 'DATE DEFAULT NULL',
			'duplicate_check_result' => 'VARCHAR(64) DEFAULT NULL',
			'retention_expires_at' => 'DATE DEFAULT NULL',
			'sale_owner' => 'VARCHAR(255) DEFAULT NULL',
			'customer_status' => 'VARCHAR(128) DEFAULT NULL',
			'contract_signed_date' => 'DATE DEFAULT NULL',
			'store_count' => 'INT(11) DEFAULT NULL',
			'payment_condition' => 'VARCHAR(255) DEFAULT NULL',
			'payment_date' => 'DATE DEFAULT NULL',
			/** Hạng A/B/C/D của chính khách này — dùng khi người khác nhập mã AFF của họ. */
			'affiliate_tier_prefix' => "CHAR(1) DEFAULT 'D'",
			'affiliate_visible' => 'TINYINT(1) NOT NULL DEFAULT 1',
		);
		foreach ($cols as $name => $def) {
			$check = $adb->pquery("SHOW COLUMNS FROM bace_sc_profile LIKE ?", array($name));
			if ($check && $adb->num_rows($check) > 0) {
				continue;
			}
			$adb->pquery("ALTER TABLE bace_sc_profile ADD COLUMN `{$name}` {$def}", array());
		}
		// Backfill hạng AFF mặc định Standard (D).
		try {
			$adb->pquery(
				"UPDATE bace_sc_profile SET affiliate_tier_prefix = 'D'
				 WHERE affiliate_tier_prefix IS NULL OR affiliate_tier_prefix = ''",
				array()
			);
		} catch (Exception $e) {
			// column may not exist yet on older DB mid-migration
		}
	}

	public static function isInstalled(PearDatabase $adb = null) {
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$res = $adb->pquery("SHOW TABLES LIKE ?", array('bace_sc_profile'));
		return ($res && $adb->num_rows($res) > 0);
	}

	/**
	 * Ensure profile row exists WITHOUT auto-generating affiliate code.
	 * AFF code is created only via generateAffiliateCode() (explicit button).
	 */
	public static function ensureProfileRow($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);

		$res = $adb->pquery(
			'SELECT servicecontractsid FROM bace_sc_profile WHERE servicecontractsid = ?',
			array($contractId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return true;
		}

		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'INSERT INTO bace_sc_profile (servicecontractsid, affiliate_code, affiliate_tier_prefix, last_touch, is_modern, created_at, modified_at)
			 VALUES (?, NULL, ?, ?, 1, ?, ?)',
			array($contractId, 'D', $now, $now, $now)
		);
		return true;
	}

	/**
	 * Read existing affiliate code (empty if not created yet). Does NOT auto-generate.
	 * @return string
	 */
	public static function getAffiliateCode($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			return '';
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT affiliate_code FROM bace_sc_profile WHERE servicecontractsid = ?',
			array($contractId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return trim((string) $adb->query_result($res, 0, 'affiliate_code'));
		}
		return '';
	}

	/**
	 * Build affiliate/referral code (mã giới thiệu) for a customer.
	 * Format: [TierLetter][10-digit-phone] e.g. A0906345551
	 * @return string empty if phone/tier invalid
	 */
	protected static function buildAffiliateCode($tierPrefix, $phone) {
		$tierPrefix = strtoupper(trim((string) $tierPrefix));
		if ($tierPrefix === '' || !preg_match('/^[A-D]$/', $tierPrefix)) {
			return '';
		}
		$digits = self::normalizePhoneDigits($phone);
		if ($digits === '') {
			return '';
		}
		$digits = substr($digits, 0, 10);
		if (strlen($digits) !== 10) {
			return '';
		}
		return $tierPrefix . $digits;
	}

	/**
	 * Explicitly generate/store affiliate code for a customer (lazy minting).
	 * If code already exists and matches current phone, return it.
	 * @return string affiliate code
	 */
	public static function generateAffiliateCode($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'CreateView')) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		// DetailView alone is not enough to mint codes
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfileRow($contractId);

		$now = date('Y-m-d H:i:s');
		$res = $adb->pquery(
			'SELECT affiliate_code, affiliate_tier_prefix, phone FROM bace_sc_profile WHERE servicecontractsid = ?',
			array($contractId)
		);
		if (!$res || $adb->num_rows($res) <= 0) {
			throw new Exception('Record not found.');
		}
		$existing = trim((string) $adb->query_result($res, 0, 'affiliate_code'));
		$tierPrefix = strtoupper(trim((string) $adb->query_result($res, 0, 'affiliate_tier_prefix')));
		if ($tierPrefix === '' || !preg_match('/^[A-D]$/', $tierPrefix)) {
			$tierPrefix = 'D';
		}
		$phone = (string) $adb->query_result($res, 0, 'phone');
		$computed = self::buildAffiliateCode($tierPrefix, $phone);
		if ($computed === '') {
			throw new Exception('Thiếu SĐT 10 số hợp lệ để tạo mã giới thiệu.');
		}

		if ($existing !== '' && strcasecmp($existing, $computed) === 0) {
			return strtoupper($computed);
		}

		$adb->pquery(
			'UPDATE bace_sc_profile SET affiliate_code = ?, modified_at = ? WHERE servicecontractsid = ?',
			array($computed, $now, $contractId)
		);

		return strtoupper($computed);
	}

	/**
	 * Toggle AFF: ON = tạo/hiện mã; OFF = xóa mã khỏi DB (mint lại khi bật).
	 */
	public static function setAffiliateVisible($contractId, $visible) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfileRow($contractId);
		$flag = $visible ? 1 : 0;
		$now = date('Y-m-d H:i:s');
		$code = '';

		if ($flag === 1) {
			$code = self::generateAffiliateCode($contractId);
			$adb->pquery(
				'UPDATE bace_sc_profile SET affiliate_visible = 1, modified_at = ? WHERE servicecontractsid = ?',
				array($now, $contractId)
			);
		} else {
			$adb->pquery(
				'UPDATE bace_sc_profile SET affiliate_code = NULL, affiliate_visible = 0, modified_at = ? WHERE servicecontractsid = ?',
				array($now, $contractId)
			);
			$code = '';
		}

		$contract = self::getFranchise($contractId);
		return array(
			'success' => true,
			'affiliate_code' => $code,
			'affiliate_visible' => $flag,
			'contract' => $contract,
		);
	}

	public static function isAffiliateVisible($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT affiliate_visible, affiliate_code FROM bace_sc_profile WHERE servicecontractsid = ?',
			array($contractId)
		);
		if (!$res || $adb->num_rows($res) <= 0) {
			return false;
		}
		$code = trim((string) $adb->query_result($res, 0, 'affiliate_code'));
		$vis = $adb->query_result($res, 0, 'affiliate_visible');
		if ($vis === null || $vis === '') {
			$vis = 1;
		}
		return $code !== '' && (int) $vis !== 0;
	}

	/**
	 * @deprecated Prefer ensureProfileRow + generateAffiliateCode.
	 * Kept for safety: only creates profile row, never auto-mints AFF codes.
	 * @return string existing code or empty
	 */
	public static function ensureAffiliateCode($contractId) {
		self::ensureProfileRow($contractId);
		return self::getAffiliateCode($contractId);
	}

	/**
	 * @param PearDatabase $adb
	 * @param int $offset skip codes already claimed in this run
	 */
	protected static function nextAffiliateCode(PearDatabase $adb, $offset = 0) {
		$max = 0;
		$res = $adb->pquery(
			"SELECT affiliate_code FROM bace_sc_profile WHERE affiliate_code LIKE 'AFF-%'",
			array()
		);
		if ($res) {
			$n = $adb->num_rows($res);
			for ($i = 0; $i < $n; $i++) {
				$code = (string) $adb->query_result($res, $i, 'affiliate_code');
				if (preg_match('/^AFF-(\d+)$/i', $code, $m)) {
					$num = (int) $m[1];
					if ($num > $max) {
						$max = $num;
					}
				}
			}
		}
		$next = $max + 1 + max(0, (int) $offset);
		return 'AFF-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
	}

	/** Backfill empty profile rows for alive ServiceContracts (no AFF auto-mint). */
	public static function ensureProfilesForAlive() {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			"SELECT sc.servicecontractsid
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			 WHERE p.servicecontractsid IS NULL",
			array()
		);
		if (!$res) {
			return;
		}
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			self::ensureProfileRow((int) $adb->query_result($res, $i, 'servicecontractsid'));
		}
	}

	public static function listContracts($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfilesForAlive();

		$sql = "SELECT sc.servicecontractsid, sc.subject, sc.contract_no, sc.contract_status, sc.contract_type,
				sc.priority, sc.sc_related_to, sc.start_date, sc.end_date,
				p.affiliate_code, p.affiliate_visible, p.phone, p.email, p.cccd, p.segment, p.district, p.address_line, p.area,
				p.sc_value, p.last_touch, p.next_action, p.customer_type,
				p.received_date, p.business_note, p.franchise_status, p.fanpage, p.data_source, p.referrer,
				p.referral_code, p.contact_status, p.interaction_1, p.interaction_2, p.interaction_3, p.interaction_materials,
				ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description,
				acc.accountname
			FROM vtiger_servicecontracts sc
			INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			LEFT JOIN vtiger_account acc ON acc.accountid = sc.sc_related_to
			ORDER BY ce.createdtime DESC, sc.servicecontractsid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$ids = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$ids[] = (int) $row['servicecontractsid'];
			$rows[] = $row;
		}
		$tagsById = self::getTagsForIds($ids, $userId);
		$ltById = self::getLastTouchSummariesForIds($ids);
		$out = array();
		foreach ($rows as $row) {
			$id = (int) $row['servicecontractsid'];
			$tags = isset($tagsById[$id]) ? $tagsById[$id] : array();
			$item = self::composeCacheRow($row, $tags);
			$item['lastTouchCalls'] = isset($ltById[$id]) ? $ltById[$id] : array(
				'calls' => array(),
				'count' => 0,
				'next_n' => 1,
				'can_add' => true,
				'max_calls' => 3,
				'hint' => '',
			);
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * @param int[] $ids
	 * @return array<int,array>
	 */
	protected static function getLastTouchSummariesForIds(array $ids) {
		$out = array();
		$ids = array_values(array_filter(array_map('intval', $ids)));
		if (!$ids) {
			return $out;
		}
		try {
			require_once 'modules/ServiceContracts/models/LastTouchCallService.php';
			ServiceContracts_LastTouchCallService::ensureSchema();
		} catch (Exception $e) {
			return $out;
		}
		$adb = PearDatabase::getInstance();
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$res = $adb->pquery(
			"SELECT servicecontractsid, call_n, called_at, result_label, note
			 FROM bace_sc_last_touch_call
			 WHERE servicecontractsid IN ($placeholders)
			 ORDER BY servicecontractsid ASC, call_n ASC",
			$ids
		);
		$byId = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$id = (int) $row['servicecontractsid'];
				if (!isset($byId[$id])) {
					$byId[$id] = array();
				}
				$n = (int) $row['call_n'];
				$calledAt = (string) $row['called_at'];
				$result = decode_html((string) $row['result_label']);
				$note = trim(decode_html((string) $row['note']));
				$byId[$id][] = array(
					'n' => $n,
					'called_at' => $calledAt,
					'called_at_label' => ServiceContracts_LastTouchCallService::formatStamp($calledAt),
					'result' => $result,
					'note' => $note,
					'label' => ServiceContracts_LastTouchCallService::formatLogLine($n, $calledAt, $result, $note),
				);
			}
		}
		foreach ($ids as $id) {
			$calls = isset($byId[$id]) ? $byId[$id] : array();
			$count = count($calls);
			$lastResult = $count > 0 ? $calls[$count - 1]['result'] : '';
			$canAdd = $count < 3 && $lastResult !== 'Nghe máy';
			$out[$id] = array(
				'calls' => $calls,
				'count' => $count,
				'next_n' => $canAdd ? ($count + 1) : 0,
				'can_add' => $canAdd,
				'max_calls' => 3,
				'hint' => $canAdd
					? ('Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → dừng chuỗi gọi.')
					: ($lastResult === 'Nghe máy'
						? 'Đã nghe máy — kết thúc chuỗi Last Touch Call.'
						: 'Đã đủ 3 lần gọi Last Touch.'),
			);
		}
		return $out;
	}

	protected static function composeCacheRow(array $row, array $tags) {
		$id = (int) $row['servicecontractsid'];
		$name = self::decodeText(isset($row['subject']) ? $row['subject'] : '');
		if ($name === '' || $name === '--') {
			$contractNo = self::decodeText(isset($row['contract_no']) ? $row['contract_no'] : '');
			$name = $contractNo !== '' ? $contractNo : ('#' . $id);
		}
		$ownerName = self::getOwnerLabel((int) $row['smownerid']);
		$lastRaw = !empty($row['last_touch']) ? $row['last_touch'] : (isset($row['modifiedtime']) ? $row['modifiedtime'] : '');
		$lastTouch = $lastRaw ? date('c', strtotime($lastRaw)) : date('c');
		$createdTime = '';
		if (!empty($row['createdtime'])) {
			$ts = strtotime($row['createdtime']);
			if ($ts) {
				$createdTime = date('c', $ts);
			}
		}
		$storedNext = self::decodeText(isset($row['next_action']) ? $row['next_action'] : '');
		$ruleMeta = self::resolveRuleNextActionMeta($tags, $lastRaw, $storedNext);
		$affiliate = self::decodeText(isset($row['affiliate_code']) ? $row['affiliate_code'] : '');
		$account = self::decodeText(isset($row['accountname']) ? $row['accountname'] : '');
		if ($account === '-' || $account === '--') {
			$account = '';
		}
		$phone = self::decodeText(isset($row['phone']) ? $row['phone'] : '');
		$email = self::decodeText(isset($row['email']) ? $row['email'] : '');
		$businessNote = self::decodeText(isset($row['business_note']) ? $row['business_note'] : '');
		if ($businessNote === '') {
			$businessNote = self::decodeText(isset($row['address_line']) ? $row['address_line'] : '');
		}
		$receivedDate = '';
		if (!empty($row['received_date']) && $row['received_date'] !== '0000-00-00') {
			$receivedDate = (string) $row['received_date'];
		}

		return array(
			'id' => (string) $id,
			'crmid' => $id,
			'name' => $name,
			'contract_no' => self::decodeText(isset($row['contract_no']) ? $row['contract_no'] : ''),
			'affiliate_code' => $affiliate,
			'affiliate_visible' => ($affiliate !== '' && (isset($row['affiliate_visible']) ? (int) $row['affiliate_visible'] : 1) !== 0) ? 1 : 0,
			'phone' => ($phone === '' || $phone === '--') ? '' : $phone,
			'email' => ($email === '' || $email === '--') ? '' : $email,
			'cccd' => self::decodeText(isset($row['cccd']) ? $row['cccd'] : ''),
			'account' => $account,
			'tags' => array_values($tags),
			'owner' => $ownerName,
			'value' => (float) (isset($row['sc_value']) ? $row['sc_value'] : 0),
			'last_touch' => $lastTouch,
			'createdtime' => $createdTime,
			'received_date' => $receivedDate,
			'business_note' => $businessNote,
			'franchise_status' => self::decodeText(isset($row['franchise_status']) ? $row['franchise_status'] : ''),
			'data_source' => self::resolveDataSourceDisplay($row),
			'referrer' => self::decodeText(isset($row['referrer']) ? $row['referrer'] : ''),
			'referral_code' => self::decodeText(isset($row['referral_code']) ? $row['referral_code'] : ''),
			'contact_status' => self::decodeText(isset($row['contact_status']) ? $row['contact_status'] : ''),
			'interaction_1' => self::decodeText(isset($row['interaction_1']) ? $row['interaction_1'] : ''),
			'interaction_2' => self::decodeText(isset($row['interaction_2']) ? $row['interaction_2'] : ''),
			'interaction_3' => self::decodeText(isset($row['interaction_3']) ? $row['interaction_3'] : ''),
			'interaction_materials' => self::decodeText(isset($row['interaction_materials']) ? $row['interaction_materials'] : ''),
			'next_action' => $ruleMeta['next_action'],
			'rule_id' => $ruleMeta['rule_id'],
			'rule_name' => $ruleMeta['rule_name'],
			'rule_alert_days' => $ruleMeta['rule_alert_days'],
			'next_action_due_at' => $ruleMeta['next_action_due_at'],
			'next_action_overdue' => $ruleMeta['next_action_overdue'],
			'next_action_days_remaining' => $ruleMeta['next_action_days_remaining'],
			'next_action_days_overdue' => $ruleMeta['next_action_days_overdue'],
			'segment' => self::decodeText(isset($row['segment']) ? $row['segment'] : ''),
			'district' => self::decodeText(isset($row['district']) ? $row['district'] : ''),
			'address' => self::decodeText(isset($row['address_line']) ? $row['address_line'] : ''),
			'area' => self::decodeText(isset($row['area']) ? $row['area'] : ''),
			'customer_type' => self::decodeText(isset($row['customer_type']) ? $row['customer_type'] : ''),
			'contract_status' => self::decodeText(isset($row['contract_status']) ? $row['contract_status'] : ''),
			'contract_type' => self::decodeText(isset($row['contract_type']) ? $row['contract_type'] : ''),
			'notes' => self::decodeText(isset($row['description']) ? $row['description'] : ''),
		);
	}

	/**
	 * True when customer was introduced via Affiliate (mã GT / người giới thiệu).
	 */
	protected static function hasAffiliateIntroduction(array $row) {
		$referralCode = self::decodeText(isset($row['referral_code']) ? $row['referral_code'] : '');
		if ($referralCode !== '' && $referralCode !== '—') {
			return true;
		}
		$referrer = self::decodeText(isset($row['referrer']) ? $row['referrer'] : '');
		if ($referrer !== '' && $referrer !== '—' && $referrer !== '-') {
			return true;
		}
		$dataSource = self::decodeText(isset($row['data_source']) ? $row['data_source'] : '');
		if ($dataSource !== '') {
			$dsLower = function_exists('mb_strtolower')
				? mb_strtolower($dataSource, 'UTF-8')
				: strtolower($dataSource);
			if (
				strpos($dsLower, 'giới thiệu') !== false
				|| strpos($dsLower, 'gioi thieu') !== false
				|| strpos($dsLower, 'affiliate') !== false
			) {
				return true;
			}
		}
		// Legacy fanpage labels for referrals
		$fanpage = self::decodeText(isset($row['fanpage']) ? $row['fanpage'] : '');
		if ($fanpage !== '') {
			$fpLower = function_exists('mb_strtolower')
				? mb_strtolower($fanpage, 'UTF-8')
				: strtolower($fanpage);
			if (strpos($fpLower, 'giới thiệu') !== false || strpos($fpLower, 'gioi thieu') !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Nguồn data (BA):
	 * - Affiliate → "Được giới thiệu"
	 * - Không giới thiệu → giữ giá trị channel đã lưu (Website/Zalo/Facebook/TikTok)
	 */
	protected static function resolveDataSourceDisplay(array $row) {
		if (self::hasAffiliateIntroduction($row)) {
			return 'Được giới thiệu';
		}
		$ds = self::decodeText(isset($row['data_source']) ? $row['data_source'] : '');
		if ($ds === '-' || $ds === '--') {
			$ds = '';
		}
		return $ds;
	}

	/**
	 * Persist data_source from referral / explicit picklist (BA).
	 */
	protected static function resolveDataSourceForSave($referralCode, $referrer, $picked) {
		$referralCode = strtoupper(trim((string) $referralCode));
		$referrer = trim((string) $referrer);
		$picked = trim((string) $picked);
		if ($referralCode !== '' || ($referrer !== '' && $referrer !== '—' && $referrer !== '-')) {
			return 'Được giới thiệu';
		}
		$picklists = self::franchisePicklists();
		return self::normalizePick($picked, $picklists['data_source']);
	}

	protected static function resolveRuleNextActionMeta(array $tags, $lastTouchRaw, $manualNextAction) {
		$meta = array(
			'next_action' => (string) $manualNextAction,
			'rule_id' => null,
			'rule_name' => null,
			'rule_alert_days' => null,
			'next_action_due_at' => null,
			'next_action_overdue' => false,
			'next_action_days_remaining' => null,
			'next_action_days_overdue' => null,
		);
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$ruleMatch = HelpDesk_TagRuleEngineService::getInstance()->matchRules($tags, true);
			$best = !empty($ruleMatch['best']) ? $ruleMatch['best'] : null;
			if (!$best) {
				return $meta;
			}
			$meta['rule_id'] = isset($best['id']) ? (string) $best['id'] : null;
			$meta['rule_name'] = isset($best['name']) ? (string) $best['name'] : null;
			if ($meta['next_action'] === '' && !empty($best['next_action'])) {
				$meta['next_action'] = (string) $best['next_action'];
			}
			if ($best['alert_days'] === null || (int) $best['alert_days'] <= 0) {
				return $meta;
			}
			$alertDays = (int) $best['alert_days'];
			$meta['rule_alert_days'] = $alertDays;
			$lastTs = $lastTouchRaw ? strtotime((string) $lastTouchRaw) : false;
			if (!$lastTs) {
				return $meta;
			}
			$meta['next_action_due_at'] = date('c', strtotime('+' . $alertDays . ' days', $lastTs));
			$daysIdle = max(0, (int) floor((time() - $lastTs) / 86400));
			$remaining = $alertDays - $daysIdle;
			if ($remaining < 0) {
				$meta['next_action_overdue'] = true;
				$meta['next_action_days_overdue'] = -$remaining;
			} else {
				$meta['next_action_days_remaining'] = $remaining;
			}
		} catch (Exception $e) {
			// best-effort
		}
		return $meta;
	}

	/**
	 * Load franchise Create/Edit payload (13 spreadsheet fields + AFF).
	 */
	public static function getFranchise($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $contractId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfileRow($contractId);

		$res = $adb->pquery(
			"SELECT sc.subject, p.affiliate_code, p.affiliate_visible, p.affiliate_tier_prefix, p.phone, p.email, p.received_date, p.business_note,
				p.franchise_status, p.fanpage, p.data_source, p.referrer, p.contact_status,
				p.interaction_1, p.interaction_2, p.interaction_3, p.interaction_materials,
				p.referral_code, p.referral_tier_name, p.referral_reward_amount,
				p.registration_date, p.duplicate_check_result, p.retention_expires_at,
				p.sale_owner, p.customer_status, p.contract_signed_date, p.store_count,
				p.payment_condition, p.payment_date, ce.description
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			 WHERE sc.servicecontractsid = ?",
			array($contractId)
		);
		if (!$res || $adb->num_rows($res) <= 0) {
			throw new Exception('Record not found.');
		}
		$row = $adb->query_result_rowdata($res, 0);
		$received = isset($row['received_date']) ? (string) $row['received_date'] : '';
		if ($received === '0000-00-00') {
			$received = '';
		}
		$regDate = isset($row['registration_date']) ? (string) $row['registration_date'] : '';
		if ($regDate === '0000-00-00') {
			$regDate = '';
		}
		$retExp = isset($row['retention_expires_at']) ? (string) $row['retention_expires_at'] : '';
		if ($retExp === '0000-00-00') {
			$retExp = '';
		}
		$signed = isset($row['contract_signed_date']) ? (string) $row['contract_signed_date'] : '';
		if ($signed === '0000-00-00') {
			$signed = '';
		}
		$paid = isset($row['payment_date']) ? (string) $row['payment_date'] : '';
		if ($paid === '0000-00-00') {
			$paid = '';
		}
		$ownerId = 0;
		$ownerRes = $adb->pquery(
			'SELECT smownerid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
			array($contractId)
		);
		if ($ownerRes && $adb->num_rows($ownerRes) > 0) {
			$ownerId = (int) $adb->query_result($ownerRes, 0, 'smownerid');
		}
		$tagsMap = self::getTagsForIds(array($contractId));
		$tags = isset($tagsMap[$contractId]) ? $tagsMap[$contractId] : array();
		$ownTierPrefix = isset($row['affiliate_tier_prefix']) ? strtoupper(trim((string) $row['affiliate_tier_prefix'])) : 'D';
		if ($ownTierPrefix === '' || !preg_match('/^[A-D]$/', $ownTierPrefix)) {
			$ownTierPrefix = 'D';
		}
		$ownTier = self::resolveTierByPrefix($ownTierPrefix);
		$referralCode = self::decodeText(isset($row['referral_code']) ? $row['referral_code'] : '');
		$affiliateCode = self::decodeText(isset($row['affiliate_code']) ? $row['affiliate_code'] : '');
		$lastTouchCalls = array(
			'calls' => array(),
			'count' => 0,
			'next_n' => 1,
			'can_add' => true,
			'max_calls' => 3,
			'hint' => '',
		);
		try {
			require_once 'modules/ServiceContracts/models/LastTouchCallService.php';
			$lastTouchCalls = ServiceContracts_LastTouchCallService::getSummary($contractId);
		} catch (Exception $e) {
			// keep defaults
		}
		return array(
			'id' => (string) $contractId,
			'crmid' => $contractId,
			'affiliate_code' => $affiliateCode,
			'affiliate_visible' => ($affiliateCode !== '' && (isset($row['affiliate_visible']) ? (int) $row['affiliate_visible'] : 1) !== 0) ? 1 : 0,
			'affiliate_tier_prefix' => $ownTierPrefix,
			'affiliate_tier_name' => $ownTier ? $ownTier['tier_name'] : '',
			'affiliate_reward_amount' => $ownTier ? (float) $ownTier['reward_amount'] : null,
			'full_name' => self::decodeText(isset($row['subject']) ? $row['subject'] : ''),
			'phone' => self::decodeText(isset($row['phone']) ? $row['phone'] : ''),
			'email' => self::decodeText(isset($row['email']) ? $row['email'] : ''),
			'received_date' => $received,
			'business_note' => self::decodeText(isset($row['business_note']) ? $row['business_note'] : ''),
			'franchise_status' => self::decodeText(isset($row['franchise_status']) ? $row['franchise_status'] : ''),
			'data_source' => self::resolveDataSourceDisplay($row),
			'referrer' => self::decodeText(isset($row['referrer']) ? $row['referrer'] : ''),
			'contact_status' => self::decodeText(isset($row['contact_status']) ? $row['contact_status'] : ''),
			'interaction_1' => self::decodeText(isset($row['interaction_1']) ? $row['interaction_1'] : ''),
			'interaction_2' => self::decodeText(isset($row['interaction_2']) ? $row['interaction_2'] : ''),
			'interaction_3' => self::decodeText(isset($row['interaction_3']) ? $row['interaction_3'] : ''),
			'interaction_materials' => self::decodeText(isset($row['interaction_materials']) ? $row['interaction_materials'] : ''),
			'referral_code' => $referralCode !== '' ? $referralCode : '',
			'referral_tier_name' => self::decodeText(isset($row['referral_tier_name']) ? $row['referral_tier_name'] : ''),
			'referral_reward_amount' => isset($row['referral_reward_amount']) && $row['referral_reward_amount'] !== null
				? (float) $row['referral_reward_amount'] : null,
			'registration_date' => $regDate,
			'duplicate_check_result' => self::decodeText(isset($row['duplicate_check_result']) ? $row['duplicate_check_result'] : ''),
			'retention_expires_at' => $retExp,
			'sale_owner' => self::decodeText(isset($row['sale_owner']) ? $row['sale_owner'] : ''),
			'sale_owner_id' => $ownerId > 0 ? (string) $ownerId : '',
			'contract_signed_date' => $signed,
			'store_count' => isset($row['store_count']) && $row['store_count'] !== null && $row['store_count'] !== ''
				? (int) $row['store_count'] : null,
			'payment_condition' => self::decodeText(isset($row['payment_condition']) ? $row['payment_condition'] : ''),
			'payment_date' => $paid,
			'notes' => self::decodeText(isset($row['description']) ? $row['description'] : ''),
			'description' => self::decodeText(isset($row['description']) ? $row['description'] : ''),
			'tags' => array_values($tags),
			'lastTouchCalls' => $lastTouchCalls,
			'picklists' => self::franchisePicklists(),
		);
	}

	/**
	 * Create or update franchise customer from spreadsheet form.
	 * @param array $payload
	 * @param int|null $userId
	 * @return array getFranchise()-shaped result
	 */
	public static function saveFranchise(array $payload, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);

		$contractId = isset($payload['id']) ? (int) $payload['id'] : 0;
		if ($contractId <= 0 && isset($payload['record'])) {
			$contractId = (int) $payload['record'];
		}

		$fullName = trim(self::decodeText(isset($payload['full_name']) ? $payload['full_name'] : ''));
		$phone = trim(self::decodeText(isset($payload['phone']) ? $payload['phone'] : ''));
		if ($fullName === '' || $phone === '') {
			throw new Exception('Họ tên và SĐT là bắt buộc.');
		}

		$picklists = self::franchisePicklists();
		$franchiseStatus = self::normalizePick(
			isset($payload['franchise_status']) ? $payload['franchise_status'] : '',
			$picklists['franchise_status']
		);
		// Fanpage retired — keep column cleared; Nguồn data is the channel field.
		$fanpage = '';
		$contactStatus = self::normalizePick(
			isset($payload['contact_status']) ? $payload['contact_status'] : '',
			$picklists['contact_status']
		);
		$receivedDate = self::normalizeDate(isset($payload['received_date']) ? $payload['received_date'] : '');
		$businessNote = trim(self::decodeText(isset($payload['business_note']) ? $payload['business_note'] : ''));
		$referrer = trim(self::decodeText(isset($payload['referrer']) ? $payload['referrer'] : ''));
		$interaction1 = self::normalizeInteraction(
			isset($payload['interaction_1']) ? $payload['interaction_1'] : ''
		);
		$interaction2 = self::normalizeInteraction(
			isset($payload['interaction_2']) ? $payload['interaction_2'] : ''
		);
		$interaction3 = self::normalizeInteraction(
			isset($payload['interaction_3']) ? $payload['interaction_3'] : ''
		);
		$interactionMaterials = self::normalizeInteraction(
			isset($payload['interaction_materials']) ? $payload['interaction_materials'] : ''
		);

		$email = trim(self::decodeText(isset($payload['email']) ? $payload['email'] : ''));

		$referralCode = strtoupper(trim(self::decodeText(isset($payload['referral_code']) ? $payload['referral_code'] : '')));
		$pickedDataSource = isset($payload['data_source']) ? $payload['data_source'] : '';
		// Nguồn data: có giới thiệu Affiliate → "Được giới thiệu"; không → giữ channel đã chọn
		$dataSource = self::resolveDataSourceForSave(
			$referralCode,
			$referrer,
			$pickedDataSource
		);
		$affiliateTierPrefix = strtoupper(trim(self::decodeText(
			isset($payload['affiliate_tier_prefix']) ? $payload['affiliate_tier_prefix'] : 'D'
		)));
		if ($affiliateTierPrefix === '' || !preg_match('/^[A-D]$/', $affiliateTierPrefix)) {
			$affiliateTierPrefix = 'D';
		}
		$registrationDate = self::normalizeDate(isset($payload['registration_date']) ? $payload['registration_date'] : '');
		if ($registrationDate === '') {
			$registrationDate = $receivedDate !== '' ? $receivedDate : date('Y-m-d');
		}
		$saleOwner = trim(self::decodeText(isset($payload['sale_owner']) ? $payload['sale_owner'] : ''));
		$saleOwnerId = isset($payload['sale_owner_id']) ? (int) $payload['sale_owner_id'] : 0;
		if ($saleOwnerId > 0) {
			foreach (self::listAssignableUsers() as $u) {
				if ((int) $u['id'] === $saleOwnerId) {
					$saleOwner = isset($u['label']) ? (string) $u['label'] : $saleOwner;
					break;
				}
			}
		}
		// Mirror franchise_status into customer_status column for legacy storage only.
		$customerStatus = $franchiseStatus;
		$contractSignedDate = self::normalizeDate(isset($payload['contract_signed_date']) ? $payload['contract_signed_date'] : '');
		$storeCountRaw = isset($payload['store_count']) ? $payload['store_count'] : '';
		$storeCount = ($storeCountRaw === '' || $storeCountRaw === null) ? null : (int) $storeCountRaw;
		$paymentAllowed = array('Chuyển khoản', 'Tiền mặt', 'Thẻ', 'Ví');
		$paymentCondition = self::normalizePick(
			isset($payload['payment_condition']) ? $payload['payment_condition'] : 'Chuyển khoản',
			$paymentAllowed
		);
		if ($paymentCondition === '') {
			$paymentCondition = 'Chuyển khoản';
		}
		$paymentDate = self::normalizeDate(isset($payload['payment_date']) ? $payload['payment_date'] : '');

		$resolved = self::resolveReferralTier($referralCode, $registrationDate);
		// If referral code is invalid / unmapped: do not persist referral_code/referrer and keep chosen data_source.
		if (!$resolved && $referralCode !== '') {
			$referralCode = '';
			$referrer = '';
			$dataSource = self::resolveDataSourceForSave('', '', $pickedDataSource);
		}
		$referralTierName = $resolved ? $resolved['tier_name'] : '';
		$referralReward = $resolved ? (float) $resolved['reward_amount'] : null;
		$retentionDays = $resolved ? (int) $resolved['retention_days'] : 180;
		$retentionExpires = '';
		if ($registrationDate !== '') {
			$retentionExpires = date('Y-m-d', strtotime($registrationDate . ' +' . $retentionDays . ' days'));
		}

		$dupCheck = self::checkDuplicateByPhone($phone, $contractId > 0 ? $contractId : null);
		$duplicateResult = $dupCheck['result'];
		// BA: trùng còn hiệu lực bảo lưu → không cho lưu (tránh tranh chấp quyền GT / tiền thưởng).
		if (!empty($dupCheck['in_retention'])) {
			$match = isset($dupCheck['match']) ? $dupCheck['match'] : array();
			$msg = 'Trùng còn hiệu lực. Không được lưu khách mới.';
			if (!empty($match['referral_code']) || !empty($match['referrer'])) {
				$msg .= ' Người giới thiệu hiện tại: ' . (!empty($match['referral_code']) ? $match['referral_code'] : $match['referrer']) . '.';
			}
			if (!empty($match['sale_owner'])) {
				$msg .= ' Sale phụ trách: ' . $match['sale_owner'] . '.';
			}
			throw new Exception($msg);
		}

		if ($contractId > 0) {
			if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)) {
				throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
			}
			$recordModel = Vtiger_Record_Model::getInstanceById($contractId, self::MODULE);
			$recordModel->set('id', $contractId);
			$recordModel->set('mode', 'edit');
		} else {
			if (!Users_Privileges_Model::isPermitted(self::MODULE, 'CreateView')) {
				throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
			}
			$recordModel = Vtiger_Record_Model::getCleanInstance(self::MODULE);
			$recordModel->set('assigned_user_id', $saleOwnerId > 0 ? $saleOwnerId : $userId);
			$recordModel->set('contract_status', 'In Progress');
			$recordModel->set('contract_type', 'Support');
		}

		$recordModel->set('subject', $fullName);
		if ($saleOwnerId > 0) {
			$recordModel->set('assigned_user_id', $saleOwnerId);
		}
		$recordModel->save();
		$contractId = (int) $recordModel->getId();
		if ($contractId <= 0) {
			throw new Exception('Không lưu được khách chuyển nhượng.');
		}

		self::ensureProfileRow($contractId);
		$now = date('Y-m-d H:i:s');

		// If affiliate_code has been minted before: keep it in sync with phone/tier changes.
		// (Lazy minting: we do NOT auto-create affiliate_code for all customers.)
		$existingAffiliateCode = self::getAffiliateCode($contractId);
		$computedAffiliateCode = self::buildAffiliateCode($affiliateTierPrefix, $phone);
		if ($existingAffiliateCode !== '' && strcasecmp($existingAffiliateCode, $computedAffiliateCode) !== 0) {
			$adb->pquery(
				'UPDATE bace_sc_profile SET affiliate_code = ?, modified_at = ? WHERE servicecontractsid = ?',
				array($computedAffiliateCode !== '' ? $computedAffiliateCode : null, $now, $contractId)
			);
		}

		$receivedSql = $receivedDate !== '' ? $receivedDate : null;
		$regSql = $registrationDate !== '' ? $registrationDate : null;
		$retSql = $retentionExpires !== '' ? $retentionExpires : null;
		$signedSql = $contractSignedDate !== '' ? $contractSignedDate : null;
		$paidSql = $paymentDate !== '' ? $paymentDate : null;
		$adb->pquery(
			'UPDATE bace_sc_profile SET
				phone = ?,
				email = ?,
				received_date = ?,
				business_note = ?,
				franchise_status = ?,
				fanpage = ?,
				data_source = ?,
				referrer = ?,
				contact_status = ?,
				interaction_1 = ?,
				interaction_2 = ?,
				interaction_3 = ?,
				interaction_materials = ?,
				address_line = ?,
				referral_code = ?,
				referral_tier_name = ?,
				referral_reward_amount = ?,
				registration_date = ?,
				duplicate_check_result = ?,
				retention_expires_at = ?,
				sale_owner = ?,
				customer_status = ?,
				contract_signed_date = ?,
				store_count = ?,
				payment_condition = ?,
				payment_date = ?,
				affiliate_tier_prefix = ?,
				last_touch = COALESCE(last_touch, ?),
				modified_at = ?,
				is_modern = 1
			 WHERE servicecontractsid = ?',
			array(
				$phone,
				$email !== '' ? $email : null,
				$receivedSql,
				$businessNote,
				$franchiseStatus,
				$fanpage,
				$dataSource,
				$referrer,
				$contactStatus,
				$interaction1,
				$interaction2,
				$interaction3,
				$interactionMaterials,
				$businessNote,
				$referralCode !== '' ? $referralCode : null,
				$referralTierName !== '' ? $referralTierName : null,
				$referralReward,
				$regSql,
				$duplicateResult,
				$retSql,
				$saleOwner !== '' ? $saleOwner : null,
				$customerStatus !== '' ? $customerStatus : null,
				$signedSql,
				$storeCount,
				$paymentCondition !== '' ? $paymentCondition : null,
				$paidSql,
				$affiliateTierPrefix,
				$now,
				$now,
				$contractId,
			)
		);

		if (isset($payload['tags']) && is_array($payload['tags'])) {
			self::saveTags($contractId, $payload['tags'], $userId);
		}

		return self::getFranchise($contractId);
	}

	/**
	 * Resolve referral code via Tag Rule Engine (AFF-###### → hạng của khách đó).
	 * @return array|null
	 */
	public static function resolveReferralTier($code, $asOfDate = null) {
		$code = trim((string) $code);
		if ($code === '') {
			return null;
		}
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$svc = HelpDesk_TagRuleEngineService::getInstance();
			return $svc->resolveAffiliateReward($code, $asOfDate);
		} catch (Exception $e) {
			return null;
		}
	}

	/** Resolve Rule tier by single-letter prefix A–D. */
	public static function resolveTierByPrefix($prefix, $asOfDate = null) {
		$prefix = strtoupper(trim((string) $prefix));
		if ($prefix === '' || !preg_match('/^[A-D]$/', $prefix)) {
			return null;
		}
		$asOf = $asOfDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $asOfDate) ? (string) $asOfDate : date('Y-m-d');
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			// Ensure mk_affiliate_reward_tiers exists + seeded (best-effort).
			HelpDesk_TagRuleEngineService::getInstance();
		} catch (Exception $e) {
			return null;
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			'SELECT tier_name, reward_amount, retention_days
			 FROM mk_affiliate_reward_tiers
			 WHERE prefix = ? AND status = ? AND effective_from <= ?
			 ORDER BY effective_from DESC
			 LIMIT 1',
			array($prefix, 'active', $asOf)
		);
		if (!$res || $adb->num_rows($res) <= 0) {
			return null;
		}
		$row = $adb->fetchByAssoc($res);
		if (!$row) {
			return null;
		}
		return array(
			'tier_name' => isset($row['tier_name']) ? decode_html((string) $row['tier_name']) : '',
			'reward_amount' => isset($row['reward_amount']) ? (float) $row['reward_amount'] : 0,
			'retention_days' => isset($row['retention_days']) ? (int) $row['retention_days'] : 180,
		);
	}

	/**
	 * Danh sách mã AFF có thể chọn làm người giới thiệu (+ hạng/tiền thưởng theo Rule).
	 * @param int|null $excludeId
	 * @return array
	 */
	public static function listReferrerOptions($excludeId = null) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$sql = "SELECT p.servicecontractsid, p.affiliate_code, p.affiliate_tier_prefix, p.phone, p.email, p.sale_owner, sc.subject
			FROM bace_sc_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.servicecontractsid AND ce.deleted = 0
			INNER JOIN vtiger_servicecontracts sc ON sc.servicecontractsid = p.servicecontractsid
			WHERE p.phone IS NOT NULL AND p.phone <> ''";
		$params = array();
		if ($excludeId) {
			$sql .= ' AND p.servicecontractsid <> ?';
			$params[] = (int) $excludeId;
		}
		$sql .= ' ORDER BY p.affiliate_tier_prefix ASC, p.phone ASC';
		$res = $adb->pquery($sql, $params);
		$out = array();
		if (!$res) {
			return $out;
		}
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$prefix = isset($row['affiliate_tier_prefix']) ? strtoupper(trim((string) $row['affiliate_tier_prefix'])) : 'D';
			if ($prefix === '' || !preg_match('/^[A-D]$/', $prefix)) {
				$prefix = 'D';
			}
			$phoneDigits = self::normalizePhoneDigits(isset($row['phone']) ? $row['phone'] : '');
			$phoneDigits = substr($phoneDigits, 0, 10);
			if (strlen($phoneDigits) !== 10) {
				continue;
			}
			$code = self::buildAffiliateCode($prefix, $phoneDigits);
			if ($code === '') {
				continue;
			}
			$tier = self::resolveTierByPrefix($prefix);
			$out[] = array(
				'id' => (string) ((int) $row['servicecontractsid']),
				'affiliate_code' => $code,
				'full_name' => self::decodeText(isset($row['subject']) ? $row['subject'] : ''),
				'phone' => self::decodeText(isset($row['phone']) ? $row['phone'] : ''),
				'email' => self::decodeText(isset($row['email']) ? $row['email'] : ''),
				'sale_owner' => self::decodeText(isset($row['sale_owner']) ? $row['sale_owner'] : ''),
				'affiliate_tier_prefix' => $prefix,
				'tier_name' => $tier ? $tier['tier_name'] : '',
				'reward_amount' => $tier ? (float) $tier['reward_amount'] : null,
				'retention_days' => $tier ? (int) $tier['retention_days'] : 180,
				// Spec: filter người giới thiệu ưu tiên hiển thị tên (không ép luôn phải kèm mã).
				'label' => self::decodeText(isset($row['subject']) ? $row['subject'] : '') ?: $code,
			);
		}
		return $out;
	}

	/**
	 * Danh sách hạng A/B/C/D từ Rule Engine (để chọn hạng cho khách này).
	 * @return array
	 */
	public static function listAffiliateTiers() {
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$svc = HelpDesk_TagRuleEngineService::getInstance();
			$tiers = $svc->getAffiliateTiers();
			$out = array();
			foreach ($tiers as $t) {
				if (isset($t['status']) && $t['status'] !== 'active') {
					continue;
				}
				$out[] = array(
					'prefix' => isset($t['prefix']) ? $t['prefix'] : '',
					'tier_name' => isset($t['tier_name']) ? $t['tier_name'] : '',
					'reward_amount' => isset($t['reward_amount']) ? (float) $t['reward_amount'] : 0,
					'retention_days' => isset($t['retention_days']) ? (int) $t['retention_days'] : 180,
				);
			}
			return $out;
		} catch (Exception $e) {
			return array(
				array('prefix' => 'A', 'tier_name' => 'Diamond', 'reward_amount' => 30000000, 'retention_days' => 180),
				array('prefix' => 'B', 'tier_name' => 'Gold', 'reward_amount' => 20000000, 'retention_days' => 180),
				array('prefix' => 'C', 'tier_name' => 'Silver', 'reward_amount' => 10000000, 'retention_days' => 180),
				array('prefix' => 'D', 'tier_name' => 'Standard', 'reward_amount' => 5000000, 'retention_days' => 180),
			);
		}
	}

	/**
	 * Patch nhẹ từ panel list "Thông tin".
	 * @param int $contractId
	 * @param array $payload
	 * @param int|null $userId
	 * @return array
	 */
	public static function saveInlineFranchise($contractId, array $payload, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfileRow($contractId);

		$franchise = self::getFranchise($contractId);
		$picklists = self::franchisePicklists();
		$franchiseStatus = array_key_exists('franchise_status', $payload)
			? self::normalizePick($payload['franchise_status'], $picklists['franchise_status'])
			: $franchise['franchise_status'];
		$contactStatus = array_key_exists('contact_status', $payload)
			? self::normalizePick($payload['contact_status'], $picklists['contact_status'])
			: $franchise['contact_status'];

		// Referral code (one-time when source empty): set data_source + referrer from AFF
		$existingReferral = isset($franchise['referral_code'])
			? strtoupper(trim((string) $franchise['referral_code']))
			: '';
		$referralCode = $existingReferral;
		if (array_key_exists('referral_code', $payload) && $existingReferral === '') {
			$referralCode = strtoupper(trim(self::decodeText($payload['referral_code'])));
		}
		$referrer = isset($franchise['referrer']) ? trim((string) $franchise['referrer']) : '';
		$dataSource = isset($franchise['data_source']) ? trim((string) $franchise['data_source']) : '';
		if ($referralCode !== '' && $existingReferral === '') {
			$resolved = self::resolveReferralTier($referralCode);
			if ($resolved && !empty($resolved['referrer_name'])) {
				$referrer = trim((string) $resolved['referrer_name']);
				$dataSource = 'Được giới thiệu';
			} else {
				// Invalid / unknown AFF code — do not save fake referral
				$referralCode = $existingReferral;
			}
		} elseif ($existingReferral !== '') {
			$dataSource = self::resolveDataSourceForSave($existingReferral, $referrer, $dataSource);
		}

		$phone = array_key_exists('phone', $payload)
			? trim(self::decodeText($payload['phone']))
			: $franchise['phone'];
		$phoneDigits = self::normalizePhoneDigits($phone);
		if ($phoneDigits !== '') {
			$phoneDigits = substr($phoneDigits, 0, 10);
			$phone = $phoneDigits;
		}

		// Keep minted affiliate_code in sync with phone changes (lazy: only update if already minted).
		$existingAffiliateCode = self::getAffiliateCode($contractId);
		$ownTierPrefix = strtoupper(trim(isset($franchise['affiliate_tier_prefix']) ? (string) $franchise['affiliate_tier_prefix'] : 'D'));
		if ($ownTierPrefix === '' || !preg_match('/^[A-D]$/', $ownTierPrefix)) {
			$ownTierPrefix = 'D';
		}
		$computedAffiliateCode = self::buildAffiliateCode($ownTierPrefix, $phone);
		if ($existingAffiliateCode !== '' && strcasecmp($existingAffiliateCode, $computedAffiliateCode) !== 0) {
			$adb->pquery(
				'UPDATE bace_sc_profile SET affiliate_code = ?, modified_at = ? WHERE servicecontractsid = ?',
				array($computedAffiliateCode !== '' ? $computedAffiliateCode : null, date('Y-m-d H:i:s'), $contractId)
			);
		}

		$businessNote = array_key_exists('business_note', $payload)
			? trim(self::decodeText($payload['business_note']))
			: $franchise['business_note'];
		$interaction1 = array_key_exists('interaction_1', $payload)
			? self::normalizeInteraction($payload['interaction_1'])
			: $franchise['interaction_1'];
		$interaction2 = array_key_exists('interaction_2', $payload)
			? self::normalizeInteraction($payload['interaction_2'])
			: $franchise['interaction_2'];
		$interaction3 = array_key_exists('interaction_3', $payload)
			? self::normalizeInteraction($payload['interaction_3'])
			: $franchise['interaction_3'];
		$interactionMaterials = array_key_exists('interaction_materials', $payload)
			? self::normalizeInteraction($payload['interaction_materials'])
			: $franchise['interaction_materials'];

		$adb->pquery(
			'UPDATE bace_sc_profile SET franchise_status = ?, contact_status = ?, data_source = ?,
				phone = ?, business_note = ?, address_line = ?,
				referral_code = ?, referrer = ?,
				interaction_1 = ?, interaction_2 = ?, interaction_3 = ?, interaction_materials = ?,
				customer_status = ?, modified_at = ? WHERE servicecontractsid = ?',
			array(
				$franchiseStatus !== '' ? $franchiseStatus : null,
				$contactStatus !== '' ? $contactStatus : null,
				$dataSource !== '' ? $dataSource : null,
				$phone !== '' ? $phone : null,
				$businessNote !== '' ? $businessNote : null,
				$businessNote !== '' ? $businessNote : null,
				$referralCode !== '' ? $referralCode : null,
				$referrer !== '' ? $referrer : null,
				$interaction1 !== '' ? $interaction1 : null,
				$interaction2 !== '' ? $interaction2 : null,
				$interaction3 !== '' ? $interaction3 : null,
				$interactionMaterials !== '' ? $interactionMaterials : null,
				$franchiseStatus !== '' ? $franchiseStatus : null,
				date('Y-m-d H:i:s'),
				$contractId,
			)
		);

		$recordModel = Vtiger_Record_Model::getInstanceById($contractId, self::MODULE);
		$recordModel->set('id', $contractId);
		$recordModel->set('mode', 'edit');
		if (array_key_exists('assigned_user_id', $payload) && (int) $payload['assigned_user_id'] > 0) {
			$recordModel->set('assigned_user_id', (int) $payload['assigned_user_id']);
		}
		if (array_key_exists('description', $payload)) {
			$recordModel->set('description', $payload['description']);
		}
		if (array_key_exists('start_date', $payload)) {
			$sd = self::normalizeDate($payload['start_date']);
			if ($sd !== '') {
				$recordModel->set('start_date', $sd);
			}
		}
		$recordModel->save();

		return self::getFranchise($contractId);
	}

	protected static function normalizePhoneDigits($phone) {
		return preg_replace('/\D+/', '', (string) $phone);
	}

	/**
	 * Duplicate / retention check by phone (default BA criterion).
	 * Results: Không trùng | Trùng còn hiệu lực | Trùng nhưng đã hết hạn
	 *
	 * @param string $phone
	 * @param int|null $excludeId
	 * @return array{result:string,in_retention:bool,match:?array}
	 */
	public static function checkDuplicateByPhone($phone, $excludeId = null) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$digits = self::normalizePhoneDigits($phone);
		$empty = array(
			'result' => 'Không trùng',
			'in_retention' => false,
			'match' => null,
		);
		if ($digits === '' || strlen($digits) < 8) {
			return $empty;
		}

		$sql = "SELECT p.servicecontractsid, p.phone, p.referrer, p.referral_code, p.referral_tier_name,
				p.referral_reward_amount, p.registration_date, p.retention_expires_at, p.sale_owner, sc.subject
			FROM bace_sc_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.servicecontractsid AND ce.deleted = 0
			INNER JOIN vtiger_servicecontracts sc ON sc.servicecontractsid = p.servicecontractsid
			WHERE p.phone IS NOT NULL AND p.phone <> ''";
		$params = array();
		if ($excludeId) {
			$sql .= ' AND p.servicecontractsid <> ?';
			$params[] = (int) $excludeId;
		}
		$sql .= ' ORDER BY COALESCE(p.registration_date, p.received_date, ce.createdtime) DESC';
		$res = $adb->pquery($sql, $params);
		if (!$res) {
			return $empty;
		}
		$today = date('Y-m-d');
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$rowDigits = self::normalizePhoneDigits(isset($row['phone']) ? $row['phone'] : '');
			if ($rowDigits === '' || $rowDigits !== $digits) {
				continue;
			}
			$expires = isset($row['retention_expires_at']) ? (string) $row['retention_expires_at'] : '';
			if ($expires === '0000-00-00') {
				$expires = '';
			}
			// Fallback: registration + 180 if expires missing but registration exists.
			if ($expires === '' && !empty($row['registration_date']) && $row['registration_date'] !== '0000-00-00') {
				$expires = date('Y-m-d', strtotime($row['registration_date'] . ' +180 days'));
			}
			$match = array(
				'id' => (int) $row['servicecontractsid'],
				'full_name' => self::decodeText(isset($row['subject']) ? $row['subject'] : ''),
				'referrer' => self::decodeText(isset($row['referrer']) ? $row['referrer'] : ''),
				'referral_code' => self::decodeText(isset($row['referral_code']) ? $row['referral_code'] : ''),
				'referral_tier_name' => self::decodeText(isset($row['referral_tier_name']) ? $row['referral_tier_name'] : ''),
				'referral_reward_amount' => isset($row['referral_reward_amount']) && $row['referral_reward_amount'] !== null
					? (float) $row['referral_reward_amount'] : null,
				'registration_date' => isset($row['registration_date']) && $row['registration_date'] !== '0000-00-00'
					? (string) $row['registration_date'] : '',
				'retention_expires_at' => $expires,
				'sale_owner' => self::decodeText(isset($row['sale_owner']) ? $row['sale_owner'] : ''),
			);
			if ($expires !== '' && $expires >= $today) {
				return array(
					'result' => 'Trùng còn hiệu lực',
					'in_retention' => true,
					'match' => $match,
				);
			}
			return array(
				'result' => 'Trùng nhưng đã hết hạn',
				'in_retention' => false,
				'match' => $match,
			);
		}
		return $empty;
	}

	protected static function normalizePick($value, array $allowed) {
		$value = trim(self::decodeText($value));
		if ($value === '') {
			return '';
		}
		foreach ($allowed as $opt) {
			if (strcasecmp($opt, $value) === 0) {
				return $opt;
			}
		}
		return '';
	}

	/** Interaction: map to preset when possible, else keep free text (legacy). */
	protected static function normalizeInteraction($value) {
		$value = trim(self::decodeText($value));
		if ($value === '') {
			return '';
		}
		$pick = self::franchisePicklists();
		$allowed = isset($pick['interaction']) ? $pick['interaction'] : array();
		foreach ($allowed as $opt) {
			if (strcasecmp($opt, $value) === 0) {
				return $opt;
			}
		}
		return $value;
	}

	protected static function normalizeDate($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return '';
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}
		if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $raw, $m)) {
			return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
		}
		$ts = strtotime($raw);
		if ($ts) {
			return date('Y-m-d', $ts);
		}
		return '';
	}

	public static function saveNextAction($contractId, $nextAction) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		self::ensureProfileRow($contractId);
		$text = trim(decode_html((string) $nextAction));
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'UPDATE bace_sc_profile SET next_action = ?, modified_at = ? WHERE servicecontractsid = ?',
			array($text, $now, $contractId)
		);
		return $text;
	}

	public static function listAssignableUsers() {
		$userModel = Users_Record_Model::getCurrentUserModel();
		$assignableUsers = $userModel->getAccessibleUsersForModule(self::MODULE);
		if (!is_array($assignableUsers)) {
			$assignableUsers = array();
		}
		$userOptions = array();
		foreach ($assignableUsers as $id => $label) {
			$userOptions[] = array(
				'id' => (string) $id,
				'label' => decode_html((string) $label),
			);
		}
		return $userOptions;
	}

	public static function saveTags($contractId, array $tagNames, $userId = null) {
		global $current_user;
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contractId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';

		$clean = array();
		$allowed = array_flip(self::$allowedTags);
		foreach ($tagNames as $name) {
			$key = self::normalizeTagKey($name);
			if ($key === '' || !isset($allowed[$key])) {
				continue;
			}
			$clean[] = $key;
		}
		$clean = array_values(array_unique($clean));

		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $contractId);
		$existingByName = array();
		$existingIds = array();
		foreach ($existing as $tagModel) {
			$name = decode_html((string) $tagModel->getName());
			$existingByName[strtolower($name)] = (int) $tagModel->getId();
			$existingIds[] = (int) $tagModel->getId();
		}
		$targetIds = array();
		foreach ($clean as $name) {
			$lk = strtolower($name);
			if (isset($existingByName[$lk])) {
				$targetIds[] = $existingByName[$lk];
				continue;
			}
			$tagModel = Vtiger_Tag_Model::getInstanceByName($name, $userId);
			if ($tagModel) {
				$targetIds[] = (int) $tagModel->getId();
				continue;
			}
			$newTag = new Vtiger_Tag_Model();
			$newTag->setName($name)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$targetIds[] = (int) $newTag->create();
		}
		$targetIds = array_values(array_unique(array_filter($targetIds)));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($contractId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($contractId, $toRemove, $userId, self::MODULE);
		}
		$tagsMap = self::getTagsForIds(array($contractId), $userId);
		$raw = isset($tagsMap[$contractId]) ? array_values($tagsMap[$contractId]) : array();
		return array(
			'success' => true,
			'tags' => $raw,
		);
	}

	public static function deleteContract($contractId) {
		$contractId = (int) $contractId;
		if ($contractId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'Delete', $contractId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($contractId, self::MODULE);
		$recordModel->delete();
		return true;
	}

	protected static function getTagsForIds(array $ids, $userId = null) {
		if (empty($ids)) {
			return array();
		}
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT fo.object_id, t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($ids) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $ids)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$oid = (int) $adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$oid])) {
				$map[$oid] = array();
			}
			$map[$oid][] = $tag;
		}
		return $map;
	}

	protected static function normalizeTagKey($name) {
		$name = trim(decode_html((string) $name));
		if ($name === '') {
			return '';
		}
		if (isset($name[0]) && $name[0] === '#') {
			$name = substr($name, 1);
		}
		$key = strtolower($name);
		$key = preg_replace('/[^a-z0-9_]+/', '_', $key);
		$key = trim($key, '_');
		$aliases = array(
			'gold' => 'vang',
			'silver' => 'bac',
			'bronze' => 'dong',
			'da_co_quan' => 'co_quan',
			'chua_co_quan' => 'chuan_bi_mo',
		);
		if (isset($aliases[$key])) {
			return $aliases[$key];
		}
		return $key;
	}

	protected static function getOwnerLabel($userId) {
		$userId = (int) $userId;
		if ($userId <= 0) {
			return '';
		}
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			$label = trim((string) $user->get('first_name') . ' ' . (string) $user->get('last_name'));
			if ($label === '') {
				$label = (string) $user->get('userlabel');
			}
			return decode_html($label);
		} catch (Exception $e) {
			return '';
		}
	}

	protected static function decodeText($raw) {
		return decode_html(trim((string) $raw));
	}
}
