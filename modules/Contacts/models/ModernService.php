<?php
/*+***********************************************************************************
 * Modern Contacts list — SALES UI (vtiger Contacts + freetags).
 *************************************************************************************/

class Contacts_ModernService {

	const MODULE = 'Contacts';

	/** Lớp học — mỗi lớp đếm Lần 1, Lần 2, Học lại riêng. */
	const CLASS_REG_CODES = array(
		'mqbb' => 'MQBB',
		'pcth' => 'PCTH',
	);

	public static function listContacts($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		self::ensureEventTimeColumns($adb);
		self::ensureBusinessModelSchema($adb);
		$sql = "SELECT cd.contactid, cd.firstname, cd.lastname, cd.title, cd.email, cd.phone, cd.mobile,
				cd.accountid, ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description,
				acc.accountname,
				ca.mailingstreet, ca.mailingcity, ca.mailingstate, ca.mailingcountry,
				cf.thoigian_dangky, cf.thoigian_pcth, cf.thoigian_mqbb,
				cf.da_cap_bang, cf.da_cap_tai_khoan,
				cp.business_model AS contact_business_model
			FROM vtiger_contactdetails cd
			INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = cd.accountid
			LEFT JOIN vtiger_contactaddress ca ON ca.contactaddressid = cd.contactid
			LEFT JOIN vtiger_contactscf cf ON cf.contactid = cd.contactid
			LEFT JOIN bace_contact_profile cp ON cp.contactid = cd.contactid
			ORDER BY ce.modifiedtime DESC, cd.contactid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$contactIds = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$contactIds[] = (int)$row['contactid'];
			$rows[] = $row;
		}
		$tagsByContact = self::getTagsForContactIds($contactIds, $userId);
		$segmentsByContact = self::getLeadSegmentsForContactIds($contactIds);
		$bizByContact = self::getLeadBusinessModelsForContactIds($contactIds);
		$ltById = array();
		try {
			require_once 'modules/Contacts/models/LastTouchCallService.php';
			$ltById = Contacts_LastTouchCallService::getSummariesForIds($contactIds);
		} catch (Exception $e) {
			$ltById = array();
		}
		$out = array();
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		foreach ($rows as $row) {
			$contactId = (int)$row['contactid'];
			$rawTags = $tagsByContact[$contactId] ?? array();
			$segment = isset($segmentsByContact[$contactId]) ? (string)$segmentsByContact[$contactId] : '';
			if ($segment !== '' && Contacts_ContactTagCatalog::isAllowed($segment)) {
				$rawTags[] = $segment;
			}
			$tags = Contacts_ContactTagCatalog::filterTagNames($rawTags);
			if (empty($row['contact_business_model']) && isset($bizByContact[$contactId])) {
				$row['lead_business_model'] = $bizByContact[$contactId];
			}
			$out[] = self::composeCacheRow(
				$row,
				$tags,
				isset($ltById[$contactId]) ? $ltById[$contactId] : null
			);
		}
		return $out;
	}

	/**
	 * Ensure BA event-time columns + vtiger_field rows exist (safe before migrate script).
	 */
	public static function ensureEventTimeColumns($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$specs = array(
			'thoigian_dangky' => 'Thời gian Đăng Ký',
			'thoigian_pcth' => 'Thời gian tham gia PCTH',
			'thoigian_mqbb' => 'Thời gian tham gia MQBB',
		);
		foreach ($specs as $col => $label) {
			$check = $adb->pquery("SHOW COLUMNS FROM vtiger_contactscf LIKE ?", array($col));
			if (!$check || $adb->num_rows($check) === 0) {
				$adb->pquery("ALTER TABLE vtiger_contactscf ADD COLUMN `{$col}` DATETIME NULL", array());
			}
		}

		$tabRes = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array('Contacts'));
		if (!$tabRes || $adb->num_rows($tabRes) === 0) {
			return;
		}
		$tabid = (int)$adb->query_result($tabRes, 0, 'tabid');
		$blockRes = $adb->pquery(
			"SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence ASC LIMIT 1",
			array($tabid)
		);
		if (!$blockRes || $adb->num_rows($blockRes) === 0) {
			return;
		}
		$blockid = (int)$adb->query_result($blockRes, 0, 'blockid');

		foreach ($specs as $col => $label) {
			$exists = $adb->pquery(
				"SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?",
				array($tabid, $col)
			);
			if ($exists && $adb->num_rows($exists) > 0) {
				continue;
			}
			$maxRes = $adb->pquery('SELECT MAX(fieldid) AS mid FROM vtiger_field', array());
			$fieldid = ($maxRes && $adb->num_rows($maxRes) > 0) ? ((int)$adb->query_result($maxRes, 0, 'mid') + 1) : 1;
			$seqRes = $adb->pquery(
				'SELECT MAX(sequence) AS seq FROM vtiger_field WHERE block = ?',
				array($blockid)
			);
			$sequence = ($seqRes && $adb->num_rows($seqRes) > 0) ? ((int)$adb->query_result($seqRes, 0, 'seq') + 1) : 1;
			$adb->pquery(
				"INSERT INTO vtiger_field
					(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel,
					 readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata,
					 quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield, headerfield)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
				array(
					$tabid, $fieldid, $col, 'vtiger_contactscf', 2, '70', $col, $label,
					0, 2, '', 100, $sequence, $blockid, 1, 'DT~O',
					1, null, 'BAS', 1, '', 0, 0,
				)
			);
			$adb->pquery(
				'INSERT IGNORE INTO vtiger_def_org_field (tabid, fieldid, visible, readonly) VALUES (?,?,?,?)',
				array($tabid, $fieldid, 0, 0)
			);
			$profRes = $adb->pquery('SELECT DISTINCT profileid FROM vtiger_profile2field WHERE tabid = ?', array($tabid));
			if ($profRes) {
				while ($prow = $adb->fetchByAssoc($profRes)) {
					$pid = (int)$prow['profileid'];
					$adb->pquery(
						'INSERT IGNORE INTO vtiger_profile2field (profileid, tabid, fieldid, visible, readonly) VALUES (?,?,?,?,?)',
						array($pid, $tabid, $fieldid, 0, 0)
					);
				}
			}
		}

		self::ensureCredentialFields($adb, $tabid, $blockid);
	}

	public static function ensureBusinessModelSchema($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS bace_contact_profile (
				contactid INT UNSIGNED NOT NULL PRIMARY KEY,
				business_model VARCHAR(80) DEFAULT NULL,
				modified_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
	}

	/**
	 * Dedicated picklist: Mô hình kinh doanh (not a tag).
	 */
	public static function upsertBusinessModel($contactId, $businessModel) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			return '';
		}
		require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
		$businessModel = Vtiger_BusinessModel_Helper::normalize($businessModel);
		$adb = PearDatabase::getInstance();
		self::ensureBusinessModelSchema($adb);
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT contactid FROM bace_contact_profile WHERE contactid = ?', array($contactId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_contact_profile SET business_model = ?, modified_at = ? WHERE contactid = ?',
				array($businessModel !== '' ? $businessModel : null, $now, $contactId)
			);
		} else {
			$adb->pquery(
				'INSERT INTO bace_contact_profile (contactid, business_model, modified_at) VALUES (?,?,?)',
				array($contactId, $businessModel !== '' ? $businessModel : null, $now)
			);
		}
		return $businessModel;
	}

	/**
	 * Đã cấp bằng / Đã cấp tài khoản — hiện trên Create/Edit (displaytype=1) + List.
	 */
	public static function ensureCredentialFields($adb = null, $tabid = null, $blockid = null) {
		static $done = false;
		if ($done) {
			return;
		}
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		if ($tabid === null) {
			$tabRes = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array('Contacts'));
			if (!$tabRes || $adb->num_rows($tabRes) === 0) {
				return;
			}
			$tabid = (int)$adb->query_result($tabRes, 0, 'tabid');
		}
		if ($blockid === null) {
			$blockRes = $adb->pquery(
				"SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence ASC LIMIT 1",
				array($tabid)
			);
			if (!$blockRes || $adb->num_rows($blockRes) === 0) {
				return;
			}
			$blockid = (int)$adb->query_result($blockRes, 0, 'blockid');
		}

		$specs = array(
			'da_cap_bang' => array(
				'label' => 'Đã cấp bằng',
				'values' => array('Chưa cấp', 'Đã cấp'),
				'default' => 'Chưa cấp',
			),
			'da_cap_tai_khoan' => array(
				'label' => 'Đã cấp tài khoản',
				'values' => array('Chưa cấp tài khoản', 'Đã cấp tài khoản'),
				'default' => 'Chưa cấp tài khoản',
			),
		);

		foreach ($specs as $col => $meta) {
			$check = $adb->pquery("SHOW COLUMNS FROM vtiger_contactscf LIKE ?", array($col));
			if (!$check || $adb->num_rows($check) === 0) {
				$adb->pquery("ALTER TABLE vtiger_contactscf ADD COLUMN `{$col}` VARCHAR(64) NULL", array());
			}
			$exists = $adb->pquery(
				"SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?",
				array($tabid, $col)
			);
			$fieldid = 0;
			if ($exists && $adb->num_rows($exists) > 0) {
				$fieldid = (int)$adb->query_result($exists, 0, 'fieldid');
				// Editable on Create/Edit stock form (was 3 = list-only, blocked EditView).
				$adb->pquery(
					'UPDATE vtiger_field SET displaytype = 1, summaryfield = 0, presence = 2 WHERE fieldid = ?',
					array($fieldid)
				);
			} else {
				$maxRes = $adb->pquery('SELECT MAX(fieldid) AS mid FROM vtiger_field', array());
				$fieldid = ($maxRes && $adb->num_rows($maxRes) > 0) ? ((int)$adb->query_result($maxRes, 0, 'mid') + 1) : 1;
				$seqRes = $adb->pquery(
					'SELECT MAX(sequence) AS seq FROM vtiger_field WHERE block = ?',
					array($blockid)
				);
				$sequence = ($seqRes && $adb->num_rows($seqRes) > 0) ? ((int)$adb->query_result($seqRes, 0, 'seq') + 1) : 1;
				$adb->pquery(
					"INSERT INTO vtiger_field
						(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel,
						 readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata,
						 quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield, headerfield)
					 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
					array(
						$tabid, $fieldid, $col, 'vtiger_contactscf', 2, '15', $col, $meta['label'],
						0, 2, $meta['default'], 100, $sequence, $blockid, 1, 'V~O',
						1, null, 'BAS', 1, '', 0, 0,
					)
				);
				$adb->pquery(
					'INSERT IGNORE INTO vtiger_def_org_field (tabid, fieldid, visible, readonly) VALUES (?,?,?,?)',
					array($tabid, $fieldid, 0, 0)
				);
				$profRes = $adb->pquery('SELECT DISTINCT profileid FROM vtiger_profile2field WHERE tabid = ?', array($tabid));
				if ($profRes) {
					while ($prow = $adb->fetchByAssoc($profRes)) {
						$pid = (int)$prow['profileid'];
						$adb->pquery(
							'INSERT IGNORE INTO vtiger_profile2field (profileid, tabid, fieldid, visible, readonly) VALUES (?,?,?,?,?)',
							array($pid, $tabid, $fieldid, 0, 0)
						);
					}
				}
			}

			try {
				require_once 'vtlib/Vtiger/Module.php';
				require_once 'vtlib/Vtiger/Field.php';
				$module = Vtiger_Module::getInstance('Contacts');
				$fieldInst = $module ? Vtiger_Field::getInstance($col, $module) : false;
				if ($fieldInst) {
					$fieldInst->setPicklistValues($meta['values']);
				}
			} catch (Exception $e) {
				// ignore — picklist may already exist
			}
		}
		$done = true;
	}

	/**
	 * Pear/vtiger often returns HTML entities (m&aacute;y) — normalize for UI + save.
	 */
	protected static function decodeCredentialText($value) {
		$value = trim((string)$value);
		if ($value === '') {
			return '';
		}
		$prev = null;
		for ($i = 0; $i < 3 && $value !== $prev; $i++) {
			$prev = $value;
			$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
			if (function_exists('decode_html')) {
				$value = decode_html($value);
			}
			$value = trim($value);
		}
		return $value;
	}

	protected static function normalizeCredentialPick($value, array $allowed, $default) {
		$value = self::decodeCredentialText($value);
		if ($value === '') {
			return $default;
		}
		foreach ($allowed as $opt) {
			$plain = self::decodeCredentialText($opt);
			if ($value === $plain || $value === $opt) {
				return $plain;
			}
		}
		return $default;
	}

	/**
	 * Giá trị hiện tại + options cho panel Detail / Create.
	 */
	public static function getCredentialState($contactId) {
		$out = array(
			'da_cap_bang' => 'Chưa cấp',
			'da_cap_tai_khoan' => 'Chưa cấp tài khoản',
			'bang_options' => array('Chưa cấp', 'Đã cấp'),
			'tk_options' => array('Chưa cấp tài khoản', 'Đã cấp tài khoản'),
		);
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return $out;
		}
		self::ensureCredentialFields();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT da_cap_bang, da_cap_tai_khoan FROM vtiger_contactscf WHERE contactid = ?',
			array($contactId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$bang = self::decodeCredentialText($adb->query_result($res, 0, 'da_cap_bang'));
			$tk = self::decodeCredentialText($adb->query_result($res, 0, 'da_cap_tai_khoan'));
			if ($bang !== '') {
				$out['da_cap_bang'] = self::normalizeCredentialPick($bang, $out['bang_options'], $out['da_cap_bang']);
			}
			if ($tk !== '') {
				$out['da_cap_tai_khoan'] = self::normalizeCredentialPick($tk, $out['tk_options'], $out['da_cap_tai_khoan']);
			}
		}
		return $out;
	}

	public static function saveCredentialFields($contactId, $daCapBang, $daCapTaiKhoan) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact không hợp lệ.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contactId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'Save', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		self::ensureCredentialFields();
		$allowedBang = array('Chưa cấp', 'Đã cấp');
		$allowedTk = array('Chưa cấp tài khoản', 'Đã cấp tài khoản');
		$daCapBang = self::normalizeCredentialPick($daCapBang, $allowedBang, 'Chưa cấp');
		$daCapTaiKhoan = self::normalizeCredentialPick($daCapTaiKhoan, $allowedTk, 'Chưa cấp tài khoản');

		try {
			$recordModel = Vtiger_Record_Model::getInstanceById($contactId, self::MODULE);
			$recordModel->set('mode', 'edit');
			$recordModel->set('id', $contactId);
			$recordModel->set('da_cap_bang', $daCapBang);
			$recordModel->set('da_cap_tai_khoan', $daCapTaiKhoan);
			$recordModel->save();
		} catch (Exception $e) {
			$adb = PearDatabase::getInstance();
			$existsCf = $adb->pquery('SELECT contactid FROM vtiger_contactscf WHERE contactid = ?', array($contactId));
			if ($existsCf && $adb->num_rows($existsCf) > 0) {
				$adb->pquery(
					'UPDATE vtiger_contactscf SET da_cap_bang = ?, da_cap_tai_khoan = ? WHERE contactid = ?',
					array($daCapBang, $daCapTaiKhoan, $contactId)
				);
			} else {
				$adb->pquery('INSERT INTO vtiger_contactscf (contactid) VALUES (?)', array($contactId));
				$adb->pquery(
					'UPDATE vtiger_contactscf SET da_cap_bang = ?, da_cap_tai_khoan = ? WHERE contactid = ?',
					array($daCapBang, $daCapTaiKhoan, $contactId)
				);
			}
		}
		return self::getCredentialState($contactId);
	}

	/**
	 * Map contactId → lead segment (co_quan / chuan_bi_mo / gia_dinh) from linked Lead profile.
	 */
	protected static function getLeadSegmentsForContactIds(array $contactIds) {
		$map = array();
		if (empty($contactIds)) {
			return $map;
		}
		$adb = PearDatabase::getInstance();
		try {
			require_once 'modules/Leads/models/ModernService.php';
			Leads_ModernService::installSchema($adb);
		} catch (Exception $e) {
			return $map;
		}
		$allowed = array('co_quan', 'chuan_bi_mo', 'gia_dinh');
		$res = $adb->pquery(
			"SELECT contact_id, segment FROM bace_lead_profile
			 WHERE contact_id IN (" . generateQuestionMarks($contactIds) . ")
			   AND contact_id > 0 AND segment IS NOT NULL AND segment <> ''
			 ORDER BY leadid DESC",
			$contactIds
		);
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$cid = (int)$adb->query_result($res, $i, 'contact_id');
				$seg = strtolower(trim((string)$adb->query_result($res, $i, 'segment')));
				if ($cid <= 0 || isset($map[$cid])) {
					continue;
				}
				if (in_array($seg, $allowed, true)) {
					$map[$cid] = $seg;
				}
			}
		}

		$missing = array();
		foreach ($contactIds as $cid) {
			$cid = (int)$cid;
			if ($cid > 0 && !isset($map[$cid])) {
				$missing[] = $cid;
			}
		}
		if (empty($missing)) {
			return $map;
		}

		// Fallback: Lead ↔ Contact relation when contact_id chưa ghi vào profile.
		$res2 = $adb->pquery(
			"SELECT rel.relcrmid AS contact_id, p.segment
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile p ON p.leadid = rel.crmid
			 WHERE rel.module = 'Leads' AND rel.relmodule = 'Contacts'
			   AND rel.relcrmid IN (" . generateQuestionMarks($missing) . ")
			   AND p.segment IS NOT NULL AND p.segment <> ''
			 ORDER BY p.leadid DESC",
			$missing
		);
		if ($res2) {
			for ($i = 0; $i < $adb->num_rows($res2); $i++) {
				$cid = (int)$adb->query_result($res2, $i, 'contact_id');
				$seg = strtolower(trim((string)$adb->query_result($res2, $i, 'segment')));
				if ($cid <= 0 || isset($map[$cid])) {
					continue;
				}
				if (in_array($seg, $allowed, true)) {
					$map[$cid] = $seg;
				}
			}
		}
		return $map;
	}

	/**
	 * Map contactId → lead business_model when contact profile is empty.
	 */
	protected static function getLeadBusinessModelsForContactIds(array $contactIds) {
		$map = array();
		if (empty($contactIds)) {
			return $map;
		}
		$adb = PearDatabase::getInstance();
		try {
			require_once 'modules/Leads/models/ModernService.php';
			Leads_ModernService::installSchema($adb);
		} catch (Exception $e) {
			return $map;
		}
		require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
		$res = $adb->pquery(
			"SELECT contact_id, business_model FROM bace_lead_profile
			 WHERE contact_id IN (" . generateQuestionMarks($contactIds) . ")
			   AND contact_id > 0 AND business_model IS NOT NULL AND TRIM(business_model) <> ''
			 ORDER BY leadid DESC",
			$contactIds
		);
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$cid = (int)$adb->query_result($res, $i, 'contact_id');
				$biz = Vtiger_BusinessModel_Helper::normalize($adb->query_result($res, $i, 'business_model'));
				if ($cid <= 0 || isset($map[$cid]) || $biz === '') {
					continue;
				}
				$map[$cid] = $biz;
			}
		}
		return $map;
	}

	/**
	 * CCCD entered on Lead (bace_lead_profile) for a converted Contact.
	 *
	 * @param int $contactId
	 * @return string
	 */
	public static function getLeadCccdForContact($contactId) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return '';
		}
		$adb = PearDatabase::getInstance();
		try {
			require_once 'modules/Leads/models/ModernService.php';
			Leads_ModernService::installSchema($adb);
		} catch (Exception $e) {
			return '';
		}

		$res = $adb->pquery(
			"SELECT cccd FROM bace_lead_profile
			 WHERE contact_id = ? AND contact_id > 0
			   AND cccd IS NOT NULL AND TRIM(cccd) <> ''
			 ORDER BY leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$cccd = trim(decode_html((string)$adb->query_result($res, 0, 'cccd')));
			if ($cccd !== '') {
				return $cccd;
			}
		}

		$res2 = $adb->pquery(
			"SELECT p.cccd
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile p ON p.leadid = rel.crmid
			 WHERE rel.module = 'Leads' AND rel.relmodule = 'Contacts'
			   AND rel.relcrmid = ?
			   AND p.cccd IS NOT NULL AND TRIM(p.cccd) <> ''
			 ORDER BY p.leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res2 && $adb->num_rows($res2) > 0) {
			return trim(decode_html((string)$adb->query_result($res2, 0, 'cccd')));
		}

		$res3 = $adb->pquery(
			"SELECT p.cccd
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile p ON p.leadid = rel.relcrmid
			 WHERE rel.module = 'Contacts' AND rel.relmodule = 'Leads'
			   AND rel.crmid = ?
			   AND p.cccd IS NOT NULL AND TRIM(p.cccd) <> ''
			 ORDER BY p.leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res3 && $adb->num_rows($res3) > 0) {
			return trim(decode_html((string)$adb->query_result($res3, 0, 'cccd')));
		}

		return '';
	}

	protected static function composeCacheRow(array $row, array $tags, $lastTouchCalls = null) {
		$contactId = (int)$row['contactid'];
		$first = decode_html((string)$row['firstname']);
		$last = decode_html((string)$row['lastname']);
		$name = trim($first . ' ' . $last);
		if ($name === '' || $name === '.') {
			$name = $last !== '' ? $last : ($first !== '' ? $first : '—');
		}
		$phone = decode_html((string)$row['phone']);
		if ($phone === '' || $phone === '--') {
			$phone = decode_html((string)$row['mobile']);
		}
		$email = decode_html((string)$row['email']);
		$accountName = decode_html((string)$row['accountname']);
		$modified = !empty($row['modifiedtime']) ? date('c', strtotime($row['modifiedtime'])) : date('c');
		if (!is_array($lastTouchCalls)) {
			$lastTouchCalls = array(
				'calls' => array(),
				'count' => 0,
				'can_add' => true,
				'next_n' => 1,
				'max_calls' => 3,
				'hint' => '',
			);
		}
		$ltLastAt = !empty($lastTouchCalls['last_at']) ? $lastTouchCalls['last_at'] : '';
		$lastTouchIso = $ltLastAt !== '' ? date('c', strtotime($ltLastAt)) : $modified;
		$addressParts = array();
		foreach (array('mailingstreet', 'mailingcity', 'mailingstate', 'mailingcountry') as $addrKey) {
			$part = decode_html(trim((string)(isset($row[$addrKey]) ? $row[$addrKey] : '')));
			if ($part !== '' && $part !== '-' && $part !== '--') {
				$addressParts[] = $part;
			}
		}
		$address = implode(', ', $addressParts);
		$convertedAt = !empty($row['createdtime']) ? date('c', strtotime($row['createdtime'])) : '';
		require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
		$businessModel = Vtiger_BusinessModel_Helper::normalize(
			!empty($row['contact_business_model'])
				? $row['contact_business_model']
				: (isset($row['lead_business_model']) ? $row['lead_business_model'] : '')
		);

		return array(
			'id' => (string)$contactId,
			'crmid' => $contactId,
			'name' => $name,
			'firstname' => $first,
			'lastname' => $last,
			'title' => decode_html((string)$row['title']),
			'email' => ($email === '' || $email === '--') ? '' : $email,
			'phone' => ($phone === '' || $phone === '--') ? '' : $phone,
			'account' => ($accountName === '' || $accountName === '-') ? '' : $accountName,
			'address' => $address,
			'business_model' => $businessModel,
			'converted_at' => $convertedAt,
			'owner' => self::getOwnerLabel((int)$row['smownerid']),
			'tags' => array_values($tags),
			'last_touch' => $lastTouchIso,
			'lastTouchCalls' => $lastTouchCalls,
			'thoigian_dangky' => self::toIsoDateTime(isset($row['thoigian_dangky']) ? $row['thoigian_dangky'] : ''),
			'thoigian_pcth' => self::toIsoDateTime(isset($row['thoigian_pcth']) ? $row['thoigian_pcth'] : ''),
			'thoigian_mqbb' => self::toIsoDateTime(isset($row['thoigian_mqbb']) ? $row['thoigian_mqbb'] : ''),
			'da_cap_bang' => self::normalizeCredentialPick(
				self::decodeCredentialText(isset($row['da_cap_bang']) ? $row['da_cap_bang'] : ''),
				array('Chưa cấp', 'Đã cấp'),
				'Chưa cấp'
			),
			'da_cap_tai_khoan' => self::normalizeCredentialPick(
				self::decodeCredentialText(isset($row['da_cap_tai_khoan']) ? $row['da_cap_tai_khoan'] : ''),
				array('Chưa cấp tài khoản', 'Đã cấp tài khoản'),
				'Chưa cấp tài khoản'
			),
			'notes' => decode_html(trim((string)(isset($row['description']) ? $row['description'] : ''))),
		);
	}

	protected static function toIsoDateTime($raw) {
		$raw = trim((string)$raw);
		if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
			return '';
		}
		$ts = strtotime($raw);
		if ($ts === false) {
			return '';
		}
		return date('c', $ts);
	}

	/**
	 * Log đăng ký đi học:
	 * - Chỉ "Đăng ký học lần 1" được Học lại 1 lần trong 1 năm.
	 * - Lần 2, 3, 4… chỉ là đăng ký mới — không có quyền Học lại.
	 */
	public static function ensureClassRegSchema($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->query("CREATE TABLE IF NOT EXISTS bace_contact_class_reg_log (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			contactid INT UNSIGNED NOT NULL,
			registered_on DATE NOT NULL,
			entry_kind VARCHAR(16) NOT NULL DEFAULT 'register',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_by INT UNSIGNED NULL,
			KEY idx_contact_reg (contactid, registered_on)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		// Migrate older installs that lack entry_kind.
		$col = $adb->pquery("SHOW COLUMNS FROM bace_contact_class_reg_log LIKE 'entry_kind'", array());
		if (!$col || $adb->num_rows($col) === 0) {
			$adb->query("ALTER TABLE bace_contact_class_reg_log
				ADD COLUMN entry_kind VARCHAR(16) NOT NULL DEFAULT 'register' AFTER registered_on");
			self::backfillClassRegEntryKinds($adb);
		}

		$colClass = $adb->pquery("SHOW COLUMNS FROM bace_contact_class_reg_log LIKE 'class_code'", array());
		if (!$colClass || $adb->num_rows($colClass) === 0) {
			$adb->query("ALTER TABLE bace_contact_class_reg_log
				ADD COLUMN class_code VARCHAR(16) NOT NULL DEFAULT 'mqbb' AFTER contactid");
		}
	}

	public static function normalizeClassRegCode($raw, $strict = true) {
		$code = strtolower(trim((string)$raw));
		if ($code === '') {
			$code = 'mqbb';
		}
		if (isset(self::CLASS_REG_CODES[$code])) {
			return $code;
		}
		if ($strict) {
			throw new Exception('Lớp học không hợp lệ. Chọn MQBB hoặc PCTH.');
		}
		return 'mqbb';
	}

	public static function getClassRegCodeLabel($code) {
		$code = self::normalizeClassRegCode($code, false);
		return self::CLASS_REG_CODES[$code];
	}

	public static function getClassRegOptions() {
		$out = array();
		foreach (self::CLASS_REG_CODES as $code => $label) {
			$out[] = array('code' => $code, 'label' => $label);
		}
		return $out;
	}

	protected static function fetchClassRegRawRows($contactId, $classCode = null) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		self::ensureClassRegSchema($adb);
		$params = array($contactId);
		$sql = 'SELECT id, class_code, registered_on, entry_kind, created_at, created_by
			 FROM bace_contact_class_reg_log
			 WHERE contactid = ?';
		if ($classCode !== null && $classCode !== '') {
			$sql .= ' AND class_code = ?';
			$params[] = self::normalizeClassRegCode($classCode, true);
		}
		$sql .= ' ORDER BY registered_on ASC, id ASC';
		$res = $adb->pquery($sql, $params);
		$raw = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$raw[] = array(
					'id' => (int)$row['id'],
					'class_code' => self::normalizeClassRegCode(isset($row['class_code']) ? $row['class_code'] : 'mqbb', false),
					'registered_on' => (string)$row['registered_on'],
					'entry_kind' => isset($row['entry_kind']) ? (string)$row['entry_kind'] : 'register',
					'created_at' => $row['created_at'] !== null ? (string)$row['created_at'] : '',
				);
			}
		}
		return $raw;
	}

	/**
	 * One-time: infer retake rows from legacy date-window rule.
	 */
	protected static function backfillClassRegEntryKinds($adb) {
		$res = $adb->pquery(
			'SELECT id, contactid, registered_on
			 FROM bace_contact_class_reg_log
			 ORDER BY contactid ASC, registered_on ASC, id ASC',
			array()
		);
		if (!$res) {
			return;
		}
		$byContact = array();
		while ($row = $adb->fetchByAssoc($res)) {
			$cid = (int)$row['contactid'];
			if (!isset($byContact[$cid])) {
				$byContact[$cid] = array();
			}
			$byContact[$cid][] = array(
				'id' => (int)$row['id'],
				'registered_on' => (string)$row['registered_on'],
			);
		}
		foreach ($byContact as $rows) {
			$cycle = 0;
			$anchor = '';
			$until = '';
			$retakeUsed = false;
			foreach ($rows as $raw) {
				$on = $raw['registered_on'];
				$kind = 'register';
				if ($cycle <= 0) {
					$cycle = 1;
					$anchor = $on;
					$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
					$retakeUsed = false;
				} elseif (!$retakeUsed && $until !== '' && $on <= $until) {
					$kind = 'retake';
					$retakeUsed = true;
				} else {
					$cycle++;
					$anchor = $on;
					$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
					$retakeUsed = false;
				}
				$adb->pquery(
					'UPDATE bace_contact_class_reg_log SET entry_kind = ? WHERE id = ?',
					array($kind, (int)$raw['id'])
				);
			}
		}
	}

	public static function formatClassRegDate($ymd) {
		$ymd = trim((string)$ymd);
		if ($ymd === '') {
			return '';
		}
		$ts = strtotime($ymd);
		if ($ts === false) {
			return $ymd;
		}
		return date('d/m/Y', $ts);
	}

	public static function normalizeClassRegDate($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		// d/m/Y or d-m-Y
		if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
			$d = (int)$m[1];
			$mo = (int)$m[2];
			$y = (int)$m[3];
			if (!checkdate($mo, $d, $y)) {
				return '';
			}
			return sprintf('%04d-%02d-%02d', $y, $mo, $d);
		}
		// Y-m-d
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
				return '';
			}
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}
		$ts = strtotime($raw);
		if ($ts === false) {
			return '';
		}
		return date('Y-m-d', $ts);
	}

	/**
	 * Gán nhãn theo entry_kind — riêng từng lớp (mqbb / pcth).
	 *
	 * @param array $rawLogs rows with id, registered_on, entry_kind, created_at
	 * @param string $classCode
	 * @return array
	 */
	public static function classifyClassRegLogsForClass(array $rawLogs, $classCode) {
		$classCode = self::normalizeClassRegCode($classCode, false);
		$classLabel = self::getClassRegCodeLabel($classCode);
		$rows = array();
		$cycle = 0;
		$anchor = '';
		$until = '';
		$seq = 0;
		$retakeN = 0;

		foreach ($rawLogs as $raw) {
			$seq++;
			$on = (string)$raw['registered_on'];
			$dateLabel = self::formatClassRegDate($on);
			$kindRaw = isset($raw['entry_kind']) ? strtolower(trim((string)$raw['entry_kind'])) : 'register';
			$isRetake = ($kindRaw === 'retake');

			if ($isRetake) {
				if ($cycle <= 0) {
					$cycle = 1;
					$anchor = $on;
					$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
					$kind = 'register';
					$isRetake = false;
					$badge = 'Lần 1';
					$label = 'Đăng ký lớp ' . $classLabel . ' lần 1: ' . $dateLabel;
				} else {
					$retakeN++;
					$kind = 'retake';
					$badge = 'Học lại';
					$label = 'Học lại lớp ' . $classLabel . ' lần ' . $retakeN . ': ' . $dateLabel;
				}
			} else {
				$cycle++;
				$anchor = $on;
				$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
				$kind = 'register';
				$badge = 'Lần ' . $cycle;
				$label = 'Đăng ký lớp ' . $classLabel . ' lần ' . $cycle . ': ' . $dateLabel;
			}

			$rows[] = array(
				'id' => isset($raw['id']) ? (int)$raw['id'] : 0,
				'class_code' => $classCode,
				'class_label' => $classLabel,
				'n' => $seq,
				'cycle' => $cycle,
				'kind' => $kind,
				'is_retake' => $isRetake,
				'retake_n' => $isRetake ? $retakeN : 0,
				'badge' => $badge,
				'registered_on' => $on,
				'registered_on_label' => $dateLabel,
				'label' => $label,
				'created_at' => isset($raw['created_at']) && $raw['created_at'] !== null
					? (string)$raw['created_at'] : '',
				'anchor_on' => $anchor,
				'until_on' => $until,
				'show_retake_btn' => false,
			);
		}

		return $rows;
	}

	/**
	 * @deprecated use classifyClassRegLogsForClass
	 */
	public static function classifyClassRegLogs(array $rawLogs) {
		return self::classifyClassRegLogsForClass($rawLogs, 'mqbb');
	}

	public static function getClassRegLogs($contactId) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return array();
		}
		$raw = self::fetchClassRegRawRows($contactId);
		$byClass = array();
		foreach ($raw as $row) {
			$code = $row['class_code'];
			if (!isset($byClass[$code])) {
				$byClass[$code] = array();
			}
			$byClass[$code][] = $row;
		}
		$merged = array();
		foreach (array_keys(self::CLASS_REG_CODES) as $code) {
			$rows = isset($byClass[$code]) ? $byClass[$code] : array();
			if ($rows) {
				$merged = array_merge($merged, self::classifyClassRegLogsForClass($rows, $code));
			}
		}
		usort($merged, function ($a, $b) {
			$c = strcmp($a['registered_on'], $b['registered_on']);
			if ($c !== 0) {
				return $c;
			}
			return $a['id'] - $b['id'];
		});
		return $merged;
	}

	/**
	 * Tính quyền Học lại / date_min cho một lớp (logs đã classify).
	 */
	protected static function computeClassRegSummaryState(array $logs) {
		$first = !empty($logs) ? $logs[0]['registered_on'] : '';
		$last = !empty($logs) ? $logs[count($logs) - 1]['registered_on'] : '';
		$anchor = '';
		$until = '';
		$cycle = 0;
		$retakeUsed = false;
		$retakeAvailable = false;
		$nextKind = 'register';
		$canAdd = true;
		$canAddRegister = true;
		$dateMin = '';
		$dateMax = '';
		$retakeDateMin = '';
		$retakeDateMax = '';

		$rightsLabel = 'Chưa mở quyền học lại — thêm Đăng ký lần 1, rồi bấm nút Học lại khi cần (1 lần / 1 năm).';
		$hint = 'Chọn ngày Đăng ký lần 1. Sau mỗi lần Học lại phải chờ 1 năm thì nút Học lại mới hiện lại.';

		$lastRetakeOn = '';
		$retakeCount = 0;
		$nextRetakeOpenOn = '';
		$retakeCooldown = false;

		if (!empty($logs)) {
			$registerCount = 0;
			$lan1Index = -1;
			$retakeUsed = false;
			$cycle = 0;
			foreach ($logs as $idx => $log) {
				if (empty($log['is_retake'])) {
					$registerCount++;
					if ((int)$log['cycle'] === 1 && $lan1Index < 0) {
						$lan1Index = $idx;
						$anchor = (string)$log['registered_on'];
						$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
					}
				} else {
					$retakeUsed = true;
					$retakeCount++;
					$lastRetakeOn = (string)$log['registered_on'];
				}
				$cycle = max($cycle, (int)$log['cycle']);
			}
			if ($lan1Index < 0) {
				foreach ($logs as $idx => $log) {
					if (empty($log['is_retake'])) {
						$lan1Index = $idx;
						$anchor = (string)$log['registered_on'];
						$until = date('Y-m-d', strtotime($anchor . ' +1 year'));
						break;
					}
				}
			}

			$today = date('Y-m-d');
			$dateMin = date('Y-m-d', strtotime($last . ' +1 day'));
			$dateMax = '';
			$retakeAvailable = false;
			$retakeDateMin = $dateMin;
			$retakeDateMax = '';

			if ($lastRetakeOn !== '') {
				$nextRetakeOpenOn = date('Y-m-d', strtotime($lastRetakeOn . ' +1 year'));
				$renewUntil = date('Y-m-d', strtotime($nextRetakeOpenOn . ' +1 year'));
				if ($today < $nextRetakeOpenOn) {
					$retakeCooldown = true;
					$retakeAvailable = false;
				} else {
					$retakeAvailable = ($today <= $renewUntil);
					$retakeDateMin = $dateMin;
					if ($nextRetakeOpenOn > $retakeDateMin) {
						$retakeDateMin = $nextRetakeOpenOn;
					}
					$retakeDateMax = $renewUntil;
					$until = $renewUntil;
				}
			} else {
				$windowOpen = ($until !== '' && $today <= $until);
				$retakeAvailable = (
					$lan1Index >= 0
					&& $registerCount === 1
					&& $windowOpen
				);
				$retakeDateMax = $until;
			}

			$btnIndex = $lan1Index;
			if ($retakeAvailable && $retakeCount > 0 && !empty($logs)) {
				$btnIndex = count($logs) - 1;
			}

			if ($retakeAvailable && $btnIndex >= 0) {
				$logs[$btnIndex]['show_retake_btn'] = true;
				$nextKind = 'retake';
				$nextRetakeLabel = 'Học lại lần ' . ($retakeCount + 1);
				$rightsLabel = 'Còn quyền ' . $nextRetakeLabel . ' đến ' . self::formatClassRegDate($retakeDateMax)
					. ' — bấm nút Học lại.';
				$hint = 'Nút Học lại: chọn ngày từ ' . self::formatClassRegDate($retakeDateMin)
					. ' đến ' . self::formatClassRegDate($retakeDateMax) . '.'
					. ' Form bên dưới dùng để Đăng ký lần mới (không dùng quyền học lại).';
			} elseif ($retakeCooldown && $nextRetakeOpenOn !== '') {
				$nextKind = 'register';
				$rightsLabel = 'Đã Học lại ngày ' . self::formatClassRegDate($lastRetakeOn)
					. '. Nút Học lại sẽ hiện lại từ ' . self::formatClassRegDate($nextRetakeOpenOn)
					. ' (sau 1 năm).';
				$hint = 'Trong lúc chờ có thể Đăng ký lần ' . ($cycle + 1)
					. ' bằng form bên dưới. Ngày mới phải sau lần gần nhất ('
					. self::formatClassRegDate($last) . ').';
			} elseif ($registerCount >= 2 && !$retakeUsed) {
				$nextKind = 'register';
				$rightsLabel = 'Đã có Đăng ký lần ' . $cycle . '.'
					. ' Không còn quyền Học lại của lần 1 — nếu có vấn đề hãy Đăng ký lần '
					. ($cycle + 1) . '.';
				$hint = 'Ngày đăng ký mới phải sau lần gần nhất (' . self::formatClassRegDate($last) . ').';
			} elseif ($lan1Index >= 0 && !$retakeUsed && $until !== '' && $today > $until) {
				$nextKind = 'register';
				$rightsLabel = 'Đã hết hạn học lại lần đầu (đến ' . self::formatClassRegDate($until) . ').'
					. ' Đăng ký tiếp sẽ là lần 2.';
				$hint = 'Ngày đăng ký mới phải sau lần gần nhất (' . self::formatClassRegDate($last) . ').';
			} else {
				$nextKind = 'register';
				$hint = 'Ngày đăng ký mới phải sau lần gần nhất (' . self::formatClassRegDate($last) . ').';
			}
		} else {
			$dateMin = '';
			$hint = 'Chọn lớp và ngày Đăng ký lần 1.';
		}

		// Cảnh báo trước 1 tháng: sắp mở Học lại (sau cooldown 1 năm) hoặc sắp hết hạn cửa sổ Học lại.
		$warning = '';
		$warningLevel = '';
		$warningDays = null;
		$todayTs = strtotime(date('Y-m-d'));
		if ($retakeCooldown && $nextRetakeOpenOn !== '') {
			$openTs = strtotime($nextRetakeOpenOn);
			if ($openTs !== false) {
				$days = (int) floor(($openTs - $todayTs) / 86400);
				if ($days >= 0 && $days <= 30) {
					$warningLevel = 'retake_soon';
					$warningDays = $days;
					$warning = $days === 0
						? 'Lớp này mở Học lại từ hôm nay (' . self::formatClassRegDate($nextRetakeOpenOn) . ').'
						: ('Lớp này còn ' . $days . ' ngày nữa sẽ được Học lại (từ ' . self::formatClassRegDate($nextRetakeOpenOn) . ').');
				}
			}
		} elseif ($retakeAvailable && $retakeDateMax !== '') {
			$maxTs = strtotime($retakeDateMax);
			if ($maxTs !== false) {
				$days = (int) floor(($maxTs - $todayTs) / 86400);
				if ($days >= 0 && $days <= 30) {
					$warningLevel = 'retake_expiring';
					$warningDays = $days;
					$warning = $days === 0
						? 'Hôm nay là hạn cuối Học lại (đến ' . self::formatClassRegDate($retakeDateMax) . ').'
						: ('Còn ' . $days . ' ngày nữa hết hạn Học lại (đến ' . self::formatClassRegDate($retakeDateMax) . ').');
				}
			}
		}

		return array(
			'logs' => $logs,
			'count' => count($logs),
			'first_on' => $first,
			'first_on_label' => self::formatClassRegDate($first),
			'last_on' => $last,
			'last_on_label' => self::formatClassRegDate($last),
			'anchor_on' => $anchor,
			'anchor_on_label' => self::formatClassRegDate($anchor),
			'until_on' => $until,
			'until_on_label' => self::formatClassRegDate($until),
			'cycle' => $cycle,
			'retake_used' => $retakeUsed,
			'retake_count' => $retakeCount,
			'last_retake_on' => $lastRetakeOn,
			'last_retake_on_label' => self::formatClassRegDate($lastRetakeOn),
			'next_retake_open_on' => $nextRetakeOpenOn,
			'next_retake_open_on_label' => self::formatClassRegDate($nextRetakeOpenOn),
			'retake_cooldown' => $retakeCooldown,
			'retake_available' => $retakeAvailable,
			'next_kind' => $nextKind,
			'can_add' => $canAdd,
			'can_add_register' => $canAddRegister,
			'date_min' => $dateMin,
			'date_max' => $dateMax,
			'retake_date_min' => $retakeDateMin,
			'retake_date_max' => $retakeDateMax,
			'rights_label' => $rightsLabel,
			'hint' => $hint,
			'warning' => $warning,
			'warning_level' => $warningLevel,
			'warning_days' => $warningDays,
		);
	}

	public static function getClassRegSummaryForClass($contactId, $classCode) {
		$classCode = self::normalizeClassRegCode($classCode, true);
		$raw = self::fetchClassRegRawRows($contactId, $classCode);
		$logs = self::classifyClassRegLogsForClass($raw, $classCode);
		$state = self::computeClassRegSummaryState($logs);
		$state['class_code'] = $classCode;
		$state['class_label'] = self::getClassRegCodeLabel($classCode);
		return $state;
	}

	public static function getClassRegSummary($contactId) {
		$contactId = (int)$contactId;
		$allLogs = array();
		$byClass = array();
		$rightsParts = array();
		$warningParts = array();
		$anyRetakeAvailable = false;

		foreach (array_keys(self::CLASS_REG_CODES) as $code) {
			$state = self::getClassRegSummaryForClass($contactId, $code);
			$byClass[$code] = array(
				'class_code' => $code,
				'class_label' => self::getClassRegCodeLabel($code),
				'cycle' => $state['cycle'],
				'count' => $state['count'],
				'date_min' => $state['date_min'],
				'date_max' => $state['date_max'],
				'retake_available' => $state['retake_available'],
				'retake_date_min' => $state['retake_date_min'],
				'retake_date_max' => $state['retake_date_max'],
				'rights_label' => $state['rights_label'],
				'hint' => $state['hint'],
				'first_on' => $state['first_on'],
				'last_on' => $state['last_on'],
				'warning' => isset($state['warning']) ? $state['warning'] : '',
				'warning_level' => isset($state['warning_level']) ? $state['warning_level'] : '',
				'warning_days' => isset($state['warning_days']) ? $state['warning_days'] : null,
			);
			foreach ($state['logs'] as $log) {
				$allLogs[] = $log;
			}
			if (!empty($state['retake_available'])) {
				$anyRetakeAvailable = true;
				$rightsParts[] = self::getClassRegCodeLabel($code) . ': ' . $state['rights_label'];
			}
			if (!empty($state['warning'])) {
				$warningParts[] = self::getClassRegCodeLabel($code) . ': ' . $state['warning'];
			}
		}

		usort($allLogs, function ($a, $b) {
			$c = strcmp($a['registered_on'], $b['registered_on']);
			if ($c !== 0) {
				return $c;
			}
			return $a['id'] - $b['id'];
		});

		$first = !empty($allLogs) ? $allLogs[0]['registered_on'] : '';
		$last = !empty($allLogs) ? $allLogs[count($allLogs) - 1]['registered_on'] : '';

		$rightsLabel = 'Mỗi lớp (PCTH / MQBB) đếm số lần đăng ký riêng. Chọn lớp khi thêm đăng ký.';
		if (!empty($rightsParts)) {
			$rightsLabel = implode(' ', $rightsParts);
		}

		$hint = 'Chọn lớp và ngày đăng ký. Ngày mới phải sau lần gần nhất của cùng lớp đó.';
		if (!empty($allLogs)) {
			$hint = 'Chọn lớp trước khi đăng ký. Mỗi lớp có Lần 1, Lần 2… riêng; Học lại chỉ áp dụng cho Lần 1 của từng lớp.';
		}

		return array(
			'logs' => $allLogs,
			'count' => count($allLogs),
			'first_on' => $first,
			'first_on_label' => self::formatClassRegDate($first),
			'last_on' => $last,
			'last_on_label' => self::formatClassRegDate($last),
			'class_options' => self::getClassRegOptions(),
			'by_class' => $byClass,
			'retake_available' => $anyRetakeAvailable,
			'can_add' => true,
			'can_add_register' => true,
			'date_min' => '',
			'date_max' => '',
			'retake_date_min' => '',
			'retake_date_max' => '',
			'rights_label' => $rightsLabel,
			'hint' => $hint,
			'warning' => !empty($warningParts) ? implode(' ', $warningParts) : '',
			'warning_level' => !empty($warningParts) ? 'retake_soon' : '',
		);
	}

	/**
	 * @param string $kind register|retake
	 * @param string $classCode mqbb|pcth
	 */
	public static function addClassRegLog($contactId, $registeredOn, $userId = null, $kind = 'register', $classCode = 'mqbb') {
		global $current_user;
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact không hợp lệ.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contactId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'Save', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$classCode = self::normalizeClassRegCode($classCode, true);
		$ymd = self::normalizeClassRegDate($registeredOn);
		if ($ymd === '') {
			throw new Exception('Ngày đăng ký không hợp lệ. Vui lòng chọn ngày trên lịch.');
		}
		$kind = strtolower(trim((string)$kind));
		if ($kind !== 'retake') {
			$kind = 'register';
		}
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		self::ensureClassRegSchema($adb);
		self::ensureEventTimeColumns($adb);

		$summary = self::getClassRegSummaryForClass($contactId, $classCode);
		$classLabel = self::getClassRegCodeLabel($classCode);
		if (!empty($summary['logs'])) {
			$last = $summary['last_on'];
			if ($ymd <= $last) {
				throw new Exception(
					'Ngày phải sau lần gần nhất của lớp ' . $classLabel
					. ' (' . self::formatClassRegDate($last) . ').'
				);
			}
		}

		if ($kind === 'retake') {
			if (empty($summary['logs'])) {
				throw new Exception('Chưa có Đăng ký lớp ' . $classLabel . ' lần 1 — không thể ghi Học lại.');
			}
			if (empty($summary['retake_available'])) {
				if (!empty($summary['retake_cooldown']) && !empty($summary['next_retake_open_on'])) {
					throw new Exception(
						'Chưa đến hạn Học lại lớp ' . $classLabel . '. Nút Học lại sẽ hiện từ '
						. self::formatClassRegDate($summary['next_retake_open_on']) . '.'
					);
				}
				throw new Exception('Hiện không còn quyền Học lại lớp ' . $classLabel . ' (đã hết hạn hoặc chưa đủ điều kiện).');
			}
			$retakeMin = !empty($summary['retake_date_min']) ? $summary['retake_date_min'] : '';
			$retakeMax = !empty($summary['retake_date_max']) ? $summary['retake_date_max'] : '';
			if ($retakeMin !== '' && $ymd < $retakeMin) {
				throw new Exception('Ngày Học lại phải từ ' . self::formatClassRegDate($retakeMin) . ' trở đi.');
			}
			if ($retakeMax !== '' && $ymd > $retakeMax) {
				throw new Exception('Ngày Học lại không được quá hạn ' . self::formatClassRegDate($retakeMax) . '.');
			}
		}

		$adb->pquery(
			'INSERT INTO bace_contact_class_reg_log (contactid, class_code, registered_on, entry_kind, created_at, created_by) VALUES (?,?,?,?,NOW(),?)',
			array($contactId, $classCode, $ymd, $kind, (int)$userId)
		);

		$summaryAfter = self::getClassRegSummary($contactId);
		$classStateAfter = self::getClassRegSummaryForClass($contactId, $classCode);

		// Sync Thời gian Đăng Ký = lần đăng ký đầu tiên (mọi lớp).
		$firstOn = $summaryAfter['first_on'];
		if ($firstOn !== '') {
			$existsCf = $adb->pquery('SELECT contactid FROM vtiger_contactscf WHERE contactid = ?', array($contactId));
			if ($existsCf && $adb->num_rows($existsCf) > 0) {
				$adb->pquery(
					'UPDATE vtiger_contactscf SET thoigian_dangky = ? WHERE contactid = ?',
					array($firstOn . ' 00:00:00', $contactId)
				);
			} else {
				$adb->pquery(
					'INSERT INTO vtiger_contactscf (contactid, thoigian_dangky) VALUES (?,?)',
					array($contactId, $firstOn . ' 00:00:00')
				);
			}
		}

		// Sync thời gian tham gia theo lớp = lần đăng ký đầu tiên của lớp đó.
		if ($kind === 'register' && (int)$classStateAfter['cycle'] === 1) {
			$cfCol = ($classCode === 'pcth') ? 'thoigian_pcth' : 'thoigian_mqbb';
			$existsCf = $adb->pquery('SELECT contactid FROM vtiger_contactscf WHERE contactid = ?', array($contactId));
			if ($existsCf && $adb->num_rows($existsCf) > 0) {
				$adb->pquery(
					'UPDATE vtiger_contactscf SET ' . $cfCol . ' = ? WHERE contactid = ?',
					array($ymd . ' 00:00:00', $contactId)
				);
			} else {
				$adb->pquery(
					'INSERT INTO vtiger_contactscf (contactid, ' . $cfCol . ') VALUES (?,?)',
					array($contactId, $ymd . ' 00:00:00')
				);
			}
		}

		return $summaryAfter;
	}

	protected static function getTagsForContactIds(array $contactIds, $userId = null) {
		if (empty($contactIds)) {
			return array();
		}
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT fo.object_id, t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($contactIds) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $contactIds)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$contactId = (int)$adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$contactId])) {
				$map[$contactId] = array();
			}
			$map[$contactId][] = $tag;
		}
		return $map;
	}

	protected static function getOwnerLabel($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '';
		}
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			$label = trim((string)$user->get('first_name') . ' ' . (string)$user->get('last_name'));
			if ($label === '') {
				$label = (string)$user->get('userlabel');
			}
			return decode_html($label);
		} catch (Exception $e) {
			return '';
		}
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
				'id' => (string)$id,
				'label' => decode_html((string)$label),
			);
		}
		return $userOptions;
	}

	/**
	 * Inline list save for phone, mailing address, and business model.
	 * @return array{success:bool,phone:string,address:string,business_model:string}
	 */
	public static function saveInlineFields($contactId, $phone = null, $address = null, $businessModel = null) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$outPhone = '';
		$outAddress = '';
		$needRecordSave = ($phone !== null || $address !== null);
		if ($needRecordSave) {
			$recordModel = Vtiger_Record_Model::getInstanceById($contactId, self::MODULE);
			$recordModel->set('id', $contactId);
			$recordModel->set('mode', 'edit');
			$outPhone = decode_html((string) $recordModel->get('phone'));
			if ($outPhone === '' || $outPhone === '--') {
				$outPhone = decode_html((string) $recordModel->get('mobile'));
			}
			$outAddress = decode_html((string) $recordModel->get('mailingstreet'));
			if ($phone !== null) {
				$digits = preg_replace('/\D+/', '', (string) $phone);
				$digits = substr((string) $digits, 0, 10);
				if ($digits !== '' && strlen($digits) !== 10) {
					throw new Exception('Số điện thoại phải đủ 10 số.');
				}
				$recordModel->set('phone', $digits);
				$outPhone = $digits;
			}
			if ($address !== null) {
				$addr = trim(decode_html((string) $address));
				$recordModel->set('mailingstreet', $addr);
				$outAddress = $addr;
			}
			$recordModel->save();
		}
		$savedBiz = '';
		if ($businessModel !== null) {
			$savedBiz = self::upsertBusinessModel($contactId, $businessModel);
		} else {
			$adb = PearDatabase::getInstance();
			self::ensureBusinessModelSchema($adb);
			$bizRes = $adb->pquery('SELECT business_model FROM bace_contact_profile WHERE contactid = ?', array($contactId));
			if ($bizRes && $adb->num_rows($bizRes) > 0) {
				require_once 'modules/Vtiger/helpers/BusinessModelHelper.php';
				$savedBiz = Vtiger_BusinessModel_Helper::normalize($adb->query_result($bizRes, 0, 'business_model'));
			}
		}
		return array(
			'success' => true,
			'phone' => $outPhone === '--' ? '' : $outPhone,
			'address' => $outAddress === '--' ? '' : $outAddress,
			'business_model' => $savedBiz,
		);
	}

	/**
	 * Replace Contact tags (whitelist via ContactTagCatalog).
	 * @param array $tagNames
	 * @return array{success:bool,tags:string[]}
	 */
	public static function saveTags($contactId, array $tagNames, $userId = null) {
		global $current_user;
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $contactId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';

		$clean = array();
		foreach ($tagNames as $name) {
			$name = trim(decode_html((string) $name));
			if ($name === '' || !Contacts_ContactTagCatalog::isAllowed($name)) {
				continue;
			}
			$key = Contacts_ContactTagCatalog::normalizeKey($name);
			if ($key === '') {
				continue;
			}
			$clean[] = $key;
		}
		$clean = array_values(array_unique($clean));

		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $contactId);
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
			Vtiger_Tag_Model::saveForRecord($contactId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($contactId, $toRemove, $userId, self::MODULE);
		}
		$tagsMap = self::getTagsForContactIds(array($contactId), $userId);
		$raw = isset($tagsMap[$contactId]) ? array_values($tagsMap[$contactId]) : array();
		return array(
			'success' => true,
			'tags' => Contacts_ContactTagCatalog::filterTagNames($raw),
		);
	}

	public static function deleteContact($contactId) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'Delete', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($contactId, self::MODULE);
		$recordModel->delete();
		return true;
	}
}
