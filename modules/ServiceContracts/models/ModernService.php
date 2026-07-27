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
			'fanpage' => array(
				'FB Nhượng quyền TaiRao',
				'Hotline',
				'Nguyên Khoa F&B',
				'Chủ quán giới thiệu',
			),
			'data_source' => array(
				'Ads Nhượng Quyền',
				'Lớp Học Miễn Phí',
				'FCTH',
			),
			'contact_status' => array(
				'Chưa gọi',
				'Đã gửi tư vấn',
				'Thuê bao',
				'Ko nghe Máy Lần 1',
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
		);
		foreach ($cols as $name => $def) {
			$check = $adb->pquery("SHOW COLUMNS FROM bace_sc_profile LIKE ?", array($name));
			if ($check && $adb->num_rows($check) > 0) {
				continue;
			}
			$adb->pquery("ALTER TABLE bace_sc_profile ADD COLUMN `{$name}` {$def}", array());
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
	 * Ensure profile row + unique AFF-xxxxxx for a contract.
	 * @return string affiliate code
	 */
	public static function ensureAffiliateCode($contractId) {
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
			$code = trim((string) $adb->query_result($res, 0, 'affiliate_code'));
			if ($code !== '') {
				return $code;
			}
			$code = self::nextAffiliateCode($adb);
			$adb->pquery(
				'UPDATE bace_sc_profile SET affiliate_code = ?, modified_at = ? WHERE servicecontractsid = ?',
				array($code, date('Y-m-d H:i:s'), $contractId)
			);
			return $code;
		}

		$code = self::nextAffiliateCode($adb);
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'INSERT INTO bace_sc_profile (servicecontractsid, affiliate_code, last_touch, is_modern, created_at, modified_at)
			 VALUES (?, ?, ?, 1, ?, ?)',
			array($contractId, $code, $now, $now, $now)
		);
		return $code;
	}

	protected static function nextAffiliateCode(PearDatabase $adb) {
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
		return 'AFF-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
	}

	/** Backfill profiles for all alive ServiceContracts missing a row. */
	public static function ensureProfilesForAlive() {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$res = $adb->pquery(
			"SELECT sc.servicecontractsid
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			 WHERE p.servicecontractsid IS NULL OR p.affiliate_code IS NULL OR p.affiliate_code = ''",
			array()
		);
		if (!$res) {
			return;
		}
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			self::ensureAffiliateCode((int) $adb->query_result($res, $i, 'servicecontractsid'));
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
				p.affiliate_code, p.phone, p.email, p.cccd, p.segment, p.district, p.address_line, p.area,
				p.sc_value, p.last_touch, p.next_action, p.customer_type,
				ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description,
				acc.accountname
			FROM vtiger_servicecontracts sc
			INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			LEFT JOIN vtiger_account acc ON acc.accountid = sc.sc_related_to
			ORDER BY COALESCE(p.last_touch, ce.modifiedtime) DESC, sc.servicecontractsid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$ids = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$ids[] = (int) $row['servicecontractsid'];
			$rows[] = $row;
		}
		$tagsById = self::getTagsForIds($ids, $userId);
		$out = array();
		foreach ($rows as $row) {
			$id = (int) $row['servicecontractsid'];
			$tags = isset($tagsById[$id]) ? $tagsById[$id] : array();
			$out[] = self::composeCacheRow($row, $tags);
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

		return array(
			'id' => (string) $id,
			'crmid' => $id,
			'name' => $name,
			'contract_no' => self::decodeText(isset($row['contract_no']) ? $row['contract_no'] : ''),
			'affiliate_code' => $affiliate,
			'phone' => ($phone === '' || $phone === '--') ? '' : $phone,
			'email' => ($email === '' || $email === '--') ? '' : $email,
			'cccd' => self::decodeText(isset($row['cccd']) ? $row['cccd'] : ''),
			'account' => $account,
			'tags' => array_values($tags),
			'owner' => $ownerName,
			'value' => (float) (isset($row['sc_value']) ? $row['sc_value'] : 0),
			'last_touch' => $lastTouch,
			'createdtime' => $createdTime,
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
		self::ensureAffiliateCode($contractId);

		$res = $adb->pquery(
			"SELECT sc.subject, p.affiliate_code, p.phone, p.received_date, p.business_note,
				p.franchise_status, p.fanpage, p.data_source, p.referrer, p.contact_status,
				p.interaction_1, p.interaction_2, p.interaction_3, p.interaction_materials
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
		return array(
			'id' => (string) $contractId,
			'crmid' => $contractId,
			'affiliate_code' => self::decodeText(isset($row['affiliate_code']) ? $row['affiliate_code'] : ''),
			'full_name' => self::decodeText(isset($row['subject']) ? $row['subject'] : ''),
			'phone' => self::decodeText(isset($row['phone']) ? $row['phone'] : ''),
			'received_date' => $received,
			'business_note' => self::decodeText(isset($row['business_note']) ? $row['business_note'] : ''),
			'franchise_status' => self::decodeText(isset($row['franchise_status']) ? $row['franchise_status'] : ''),
			'fanpage' => self::decodeText(isset($row['fanpage']) ? $row['fanpage'] : ''),
			'data_source' => self::decodeText(isset($row['data_source']) ? $row['data_source'] : ''),
			'referrer' => self::decodeText(isset($row['referrer']) ? $row['referrer'] : ''),
			'contact_status' => self::decodeText(isset($row['contact_status']) ? $row['contact_status'] : ''),
			'interaction_1' => self::decodeText(isset($row['interaction_1']) ? $row['interaction_1'] : ''),
			'interaction_2' => self::decodeText(isset($row['interaction_2']) ? $row['interaction_2'] : ''),
			'interaction_3' => self::decodeText(isset($row['interaction_3']) ? $row['interaction_3'] : ''),
			'interaction_materials' => self::decodeText(isset($row['interaction_materials']) ? $row['interaction_materials'] : ''),
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
		$fanpage = self::normalizePick(isset($payload['fanpage']) ? $payload['fanpage'] : '', $picklists['fanpage']);
		$dataSource = self::normalizePick(
			isset($payload['data_source']) ? $payload['data_source'] : '',
			$picklists['data_source']
		);
		$contactStatus = self::normalizePick(
			isset($payload['contact_status']) ? $payload['contact_status'] : '',
			$picklists['contact_status']
		);
		$receivedDate = self::normalizeDate(isset($payload['received_date']) ? $payload['received_date'] : '');
		$businessNote = trim(self::decodeText(isset($payload['business_note']) ? $payload['business_note'] : ''));
		$referrer = trim(self::decodeText(isset($payload['referrer']) ? $payload['referrer'] : ''));
		$interaction1 = trim(self::decodeText(isset($payload['interaction_1']) ? $payload['interaction_1'] : ''));
		$interaction2 = trim(self::decodeText(isset($payload['interaction_2']) ? $payload['interaction_2'] : ''));
		$interaction3 = trim(self::decodeText(isset($payload['interaction_3']) ? $payload['interaction_3'] : ''));
		$interactionMaterials = trim(self::decodeText(
			isset($payload['interaction_materials']) ? $payload['interaction_materials'] : ''
		));

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
			$recordModel->set('assigned_user_id', $userId);
			$recordModel->set('contract_status', 'In Progress');
			$recordModel->set('contract_type', 'Support');
		}

		$recordModel->set('subject', $fullName);
		$recordModel->save();
		$contractId = (int) $recordModel->getId();
		if ($contractId <= 0) {
			throw new Exception('Không lưu được khách chuyển nhượng.');
		}

		self::ensureAffiliateCode($contractId);
		$now = date('Y-m-d H:i:s');
		$receivedSql = $receivedDate !== '' ? $receivedDate : null;
		$adb->pquery(
			'UPDATE bace_sc_profile SET
				phone = ?,
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
				last_touch = COALESCE(last_touch, ?),
				modified_at = ?,
				is_modern = 1
			 WHERE servicecontractsid = ?',
			array(
				$phone,
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
				$now,
				$now,
				$contractId,
			)
		);

		return self::getFranchise($contractId);
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
		self::ensureAffiliateCode($contractId);
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
