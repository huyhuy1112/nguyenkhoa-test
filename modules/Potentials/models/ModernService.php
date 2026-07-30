<?php
/*+***********************************************************************************
 * Modern Potentials list — SALES UI (maps vtiger Potentials + freetags).
 *************************************************************************************/

class Potentials_ModernService {

	const MODULE = 'Potentials';

	public static function ensureProfileSchema($adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			"CREATE TABLE IF NOT EXISTS bace_potential_profile (
				potentialid INT UNSIGNED NOT NULL PRIMARY KEY,
				district VARCHAR(128) DEFAULT NULL,
				address_line VARCHAR(255) DEFAULT NULL,
				confirmed_at DATETIME NULL,
				converted_to_customer_at DATETIME NULL,
				contact_customer_id INT UNSIGNED DEFAULT NULL,
				modified_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			array()
		);
		self::ensureProfileColumn($adb, 'converted_to_customer_at', 'DATETIME NULL');
		self::ensureProfileColumn($adb, 'contact_customer_id', 'INT UNSIGNED DEFAULT NULL');
	}

	protected static function ensureProfileColumn(PearDatabase $adb, $column, $definition) {
		$res = $adb->pquery("SHOW COLUMNS FROM bace_potential_profile LIKE ?", array($column));
		if ($res && $adb->num_rows($res) > 0) {
			return;
		}
		$adb->pquery("ALTER TABLE bace_potential_profile ADD COLUMN {$column} {$definition}", array());
	}

	public static function listPotentials($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		self::ensureProfileSchema($adb);
		$sql = "SELECT p.potentialid, p.potentialname, p.sales_stage, p.closingdate, p.amount,
				p.leadsource, p.order_category, p.related_to, p.contact_id,
				ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description,
				acc.accountname,
				cd.firstname AS contact_firstname, cd.lastname AS contact_lastname,
				cd.phone AS contact_phone, cd.mobile AS contact_mobile,
				pp.district AS pot_district, pp.address_line AS pot_address, pp.confirmed_at,
				lp.district AS lead_district, lp.address_line AS lead_address, lp.area AS lead_area
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = p.related_to
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			LEFT JOIN bace_potential_profile pp ON pp.potentialid = p.potentialid
			LEFT JOIN bace_lead_profile lp ON lp.potential_id = p.potentialid
			WHERE pp.converted_to_customer_at IS NULL
			ORDER BY ce.modifiedtime DESC, p.potentialid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$potentialIds = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$potentialIds[] = (int)$row['potentialid'];
			$rows[] = $row;
		}
		$tagsByPotential = self::getTagsForPotentialIds($potentialIds, $userId);
		$confirmAtByTag = self::getConfirmTaggedOn($potentialIds);
		$out = array();
		foreach ($rows as $row) {
			$potentialId = (int)$row['potentialid'];
			$out[] = self::composeCacheRow(
				$row,
				$tagsByPotential[$potentialId] ?? array(),
				isset($confirmAtByTag[$potentialId]) ? $confirmAtByTag[$potentialId] : ''
			);
		}
		return $out;
	}

	protected static function composeCacheRow(array $row, array $tags, $taggedConfirmAt = '') {
		$potentialId = (int)$row['potentialid'];
		$ownerName = self::getOwnerLabel((int)$row['smownerid']);
		$contactName = trim(decode_html((string)$row['contact_firstname']) . ' ' . decode_html((string)$row['contact_lastname']));
		$accountName = decode_html((string)$row['accountname']);
		$modified = !empty($row['modifiedtime']) ? date('c', strtotime($row['modifiedtime'])) : date('c');
		$created = '';
		if (!empty($row['createdtime'])) {
			$ts = strtotime($row['createdtime']);
			if ($ts) {
				$created = date('c', $ts);
			}
		}
		$closing = !empty($row['closingdate']) ? $row['closingdate'] : '';
		$phone = decode_html((string)$row['contact_phone']);
		if ($phone === '' || $phone === '--') {
			$phone = decode_html((string)$row['contact_mobile']);
		}
		if ($phone === '--') {
			$phone = '';
		}
		$district = decode_html((string)(!empty($row['pot_district']) ? $row['pot_district'] : $row['lead_district']));
		$address = decode_html((string)(!empty($row['pot_address']) ? $row['pot_address'] : $row['lead_address']));
		$notes = decode_html(trim((string)$row['description']));

		$confirmedAt = '';
		if (!empty($row['confirmed_at'])) {
			$cts = strtotime($row['confirmed_at']);
			if ($cts) {
				$confirmedAt = date('c', $cts);
			}
		}
		if ($confirmedAt === '' && $taggedConfirmAt !== '') {
			$tts = strtotime($taggedConfirmAt);
			if ($tts) {
				$confirmedAt = date('c', $tts);
			}
		}
		$hasConfirm = false;
		foreach ($tags as $tg) {
			if (strtolower(trim((string)$tg)) === 'xac_nhan_tham_gia') {
				$hasConfirm = true;
				break;
			}
		}
		if (!$hasConfirm) {
			$confirmedAt = '';
		}

		$ruleMeta = array(
			'next_action' => '',
			'rule_id' => null,
			'rule_name' => null,
			'rule_alert_days' => null,
			'next_action_due_at' => null,
			'next_action_overdue' => false,
			'next_action_days_remaining' => null,
			'next_action_days_overdue' => null,
			'timeframe_label' => '',
		);
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$lastTouchRaw = !empty($row['modifiedtime']) ? $row['modifiedtime'] : null;
			$ruleMeta = HelpDesk_TagRuleEngineService::getInstance()->resolveNextActionMeta(
				$tags,
				$lastTouchRaw,
				''
			);
		} catch (Exception $e) {
			// ignore — rule metadata is best-effort
		}

		return array(
			'id' => (string)$potentialId,
			'crmid' => $potentialId,
			'name' => decode_html((string)$row['potentialname']),
			'sales_stage' => decode_html((string)$row['sales_stage']),
			'closingdate' => $closing,
			'amount' => (float)$row['amount'],
			'leadsource' => decode_html((string)$row['leadsource']),
			'order_category' => decode_html((string)$row['order_category']),
			'account' => ($accountName === '' || $accountName === '-') ? '' : $accountName,
			'contact' => ($contactName === '' || $contactName === '.') ? '' : $contactName,
			'contact_id' => (int)$row['contact_id'] > 0 ? (int)$row['contact_id'] : 0,
			'phone' => $phone,
			'district' => $district,
			'address' => $address,
			'notes' => $notes,
			'createdtime' => $created,
			'converted_at' => $created,
			'confirmed_at' => $confirmedAt,
			'owner' => $ownerName,
			'tags' => array_values($tags),
			'last_touch' => $modified,
			'next_action' => $ruleMeta['next_action'],
			'rule_id' => $ruleMeta['rule_id'],
			'rule_name' => $ruleMeta['rule_name'],
			'rule_alert_days' => $ruleMeta['rule_alert_days'],
			'next_action_due_at' => $ruleMeta['next_action_due_at'],
			'next_action_overdue' => $ruleMeta['next_action_overdue'],
			'next_action_days_remaining' => $ruleMeta['next_action_days_remaining'],
			'next_action_days_overdue' => $ruleMeta['next_action_days_overdue'],
			'next_action_timeframe' => $ruleMeta['timeframe_label'],
		);
	}

	protected static function getConfirmTaggedOn(array $potentialIds) {
		$map = array();
		if (empty($potentialIds)) {
			return $map;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT fo.object_id, fo.tagged_on
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND LOWER(t.tag) = ? AND fo.object_id IN (" . generateQuestionMarks($potentialIds) . ")",
			array_merge(array(self::MODULE, 'xac_nhan_tham_gia'), $potentialIds)
		);
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$pid = (int)$adb->query_result($res, $i, 'object_id');
				$map[$pid] = (string)$adb->query_result($res, $i, 'tagged_on');
			}
		}
		return $map;
	}

	protected static function getTagsForPotentialIds(array $potentialIds, $userId = null) {
		if (empty($potentialIds)) {
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
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($potentialIds) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $potentialIds)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$potentialId = (int)$adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$potentialId])) {
				$map[$potentialId] = array();
			}
			$map[$potentialId][] = $tag;
		}
		return $map;
	}

	protected static function sanitizeOwnerDisplayName($name) {
		$name = trim((string)$name);
		if ($name === '') {
			return '';
		}
		$cleaned = preg_replace('/\s+(Administrator|Admin|Manager|User)$/iu', '', $name);
		$cleaned = trim((string)$cleaned);
		return $cleaned !== '' ? $cleaned : $name;
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
			return self::sanitizeOwnerDisplayName(decode_html($label));
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
		foreach ($assignableUsers as $userId => $label) {
			$userId = (int)$userId;
			if ($userId <= 0) {
				continue;
			}
			try {
				$userRecord = Users_Record_Model::getInstanceById($userId, 'Users');
				$userName = (string)$userRecord->get('user_name');
				if ($userName === '') {
					continue;
				}
				$userOptions[] = array(
					'id' => $userId,
					'user_name' => $userName,
					'label' => decode_html($label),
				);
			} catch (Exception $e) {
				continue;
			}
		}
		return $userOptions;
	}

	/**
	 * Set / clear participation-confirm tag on an Opportunity (mutually exclusive).
	 * @return array{confirm:string,tags:string[],confirmed_at:string,confirmed_at_label:string}
	 */
	public static function setConfirmTag($potentialId, $confirmTag) {
		global $current_user;
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		$userId = (int) $current_user->id;
		$confirmTag = trim((string) $confirmTag);
		$allowed = array('xac_nhan_tham_gia', 'khong_xac_nhan_tham_gia');
		if ($confirmTag !== '' && !in_array($confirmTag, $allowed, true)) {
			throw new Exception('Invalid confirm tag.');
		}

		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $potentialId);
		$keepIds = array();
		$existingIds = array();
		foreach ($existing as $tagModel) {
			$tid = (int) $tagModel->getId();
			$existingIds[] = $tid;
			$name = decode_html((string) $tagModel->getName());
			$key = strtolower(trim($name));
			if (in_array($key, $allowed, true)) {
				continue;
			}
			$keepIds[] = $tid;
		}

		$targetIds = $keepIds;
		if ($confirmTag !== '') {
			$tagModel = Vtiger_Tag_Model::getInstanceByName($confirmTag, $userId);
			if ($tagModel) {
				$targetIds[] = (int) $tagModel->getId();
			} else {
				$newTag = new Vtiger_Tag_Model();
				$newTag->setName($confirmTag)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
				$targetIds[] = (int) $newTag->create();
			}
		}
		$targetIds = array_values(array_unique(array_filter($targetIds)));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($potentialId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($potentialId, $toRemove, $userId, self::MODULE);
		}

		$confirmedAt = '';
		$confirmedAtLabel = '';
		self::ensureProfileSchema();
		$adb = PearDatabase::getInstance();
		if ($confirmTag === 'xac_nhan_tham_gia') {
			$confirmedAt = date('Y-m-d H:i:s');
			$confirmedAtLabel = date('d/m/Y H:i:s', strtotime($confirmedAt));
			$exists = $adb->pquery('SELECT potentialid FROM bace_potential_profile WHERE potentialid = ?', array($potentialId));
			if ($exists && $adb->num_rows($exists) > 0) {
				$adb->pquery(
					'UPDATE bace_potential_profile SET confirmed_at = ?, modified_at = ? WHERE potentialid = ?',
					array($confirmedAt, $confirmedAt, $potentialId)
				);
			} else {
				$adb->pquery(
					'INSERT INTO bace_potential_profile (potentialid, confirmed_at, modified_at) VALUES (?,?,?)',
					array($potentialId, $confirmedAt, $confirmedAt)
				);
			}
		} else {
			$adb->pquery(
				'UPDATE bace_potential_profile SET confirmed_at = NULL, modified_at = ? WHERE potentialid = ?',
				array(date('Y-m-d H:i:s'), $potentialId)
			);
		}

		$tagsMap = self::getTagsForPotentialIds(array($potentialId), $userId);
		$tags = isset($tagsMap[$potentialId]) ? array_values($tagsMap[$potentialId]) : array();
		return array(
			'confirm' => $confirmTag,
			'tags' => $tags,
			'confirmed_at' => $confirmedAt !== '' ? date('c', strtotime($confirmedAt)) : '',
			'confirmed_at_label' => $confirmedAtLabel,
		);
	}

	/**
	 * Save khu vực / địa chỉ for Opp (and sync kv tags).
	 */
	public static function saveInlineLocation($potentialId, $regionKey, $address) {
		global $current_user;
		$potentialId = (int)$potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		$userId = (int)$current_user->id;
		$regionKey = strtolower(trim((string)$regionKey));
		if ($regionKey !== '' && !preg_match('/^kv[123]$/', $regionKey)) {
			$regionKey = '';
		}
		$address = trim(decode_html((string)$address));
		$district = $regionKey !== '' ? ('Khu vực ' . substr($regionKey, -1)) : '';

		self::ensureProfileSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT potentialid, confirmed_at FROM bace_potential_profile WHERE potentialid = ?', array($potentialId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_potential_profile SET district = ?, address_line = ?, modified_at = ? WHERE potentialid = ?',
				array($district !== '' ? $district : null, $address !== '' ? $address : null, $now, $potentialId)
			);
		} else {
			$adb->pquery(
				'INSERT INTO bace_potential_profile (potentialid, district, address_line, modified_at) VALUES (?,?,?,?)',
				array($potentialId, $district !== '' ? $district : null, $address !== '' ? $address : null, $now)
			);
		}

		// Sync region tags on Potential.
		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $potentialId);
		$keepIds = array();
		$existingIds = array();
		foreach ($existing as $tagModel) {
			$tid = (int)$tagModel->getId();
			$existingIds[] = $tid;
			$key = strtolower(trim(decode_html((string)$tagModel->getName())));
			if (preg_match('/^kv[123]$/', $key)) {
				continue;
			}
			$keepIds[] = $tid;
		}
		$targetIds = $keepIds;
		if ($regionKey !== '') {
			$tagModel = Vtiger_Tag_Model::getInstanceByName($regionKey, $userId);
			if ($tagModel) {
				$targetIds[] = (int)$tagModel->getId();
			} else {
				$newTag = new Vtiger_Tag_Model();
				$newTag->setName($regionKey)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
				$targetIds[] = (int)$newTag->create();
			}
		}
		$targetIds = array_values(array_unique(array_filter($targetIds)));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($potentialId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($potentialId, $toRemove, $userId, self::MODULE);
		}

		$tagsMap = self::getTagsForPotentialIds(array($potentialId), $userId);
		return array(
			'success' => true,
			'district' => $district,
			'address' => $address,
			'region' => $regionKey,
			'tags' => isset($tagsMap[$potentialId]) ? array_values($tagsMap[$potentialId]) : array(),
		);
	}

	/**
	 * Update phone on related Contact of an Opportunity.
	 * @return array{success:bool,phone:string,contact_id:int}
	 */
	public static function saveInlinePhone($potentialId, $phone) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $potentialId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $potentialId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$digits = preg_replace('/\D+/', '', (string) $phone);
		$digits = substr((string) $digits, 0, 10);
		if ($digits !== '' && strlen($digits) !== 10) {
			throw new Exception('Số điện thoại phải đủ 10 số.');
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT contact_id FROM vtiger_potential WHERE potentialid = ?', array($potentialId));
		$contactId = ($res && $adb->num_rows($res) > 0) ? (int) $adb->query_result($res, 0, 'contact_id') : 0;
		if ($contactId <= 0) {
			throw new Exception('Cơ hội chưa gắn khách hàng — không lưu được SĐT.');
		}
		if (!Users_Privileges_Model::isPermitted('Contacts', 'EditView', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
		$recordModel->set('id', $contactId);
		$recordModel->set('mode', 'edit');
		$recordModel->set('phone', $digits);
		$recordModel->save();
		return array(
			'success' => true,
			'phone' => $digits,
			'contact_id' => $contactId,
		);
	}

	/**
	 * Replace Opportunity tags (whitelist via OppTagCatalog when available).
	 * @param array $tagNames
	 * @return array{success:bool,tags:string[]}
	 */
	public static function saveTags($potentialId, array $tagNames, $userId = null) {
		global $current_user;
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $potentialId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $potentialId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Potentials/helpers/OppTagCatalog.php';

		$clean = array();
		foreach ($tagNames as $name) {
			$name = trim(decode_html((string) $name));
			if ($name === '') {
				continue;
			}
			if (method_exists('Potentials_OppTagCatalog', 'isAllowed') && !Potentials_OppTagCatalog::isAllowed($name)) {
				continue;
			}
			$key = method_exists('Potentials_OppTagCatalog', 'normalizeKey')
				? Potentials_OppTagCatalog::normalizeKey($name)
				: strtolower($name);
			if ($key === '') {
				continue;
			}
			$clean[] = $key;
		}
		$clean = array_values(array_unique($clean));

		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $potentialId);
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
			Vtiger_Tag_Model::saveForRecord($potentialId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($potentialId, $toRemove, $userId, self::MODULE);
		}
		$tagsMap = self::getTagsForPotentialIds(array($potentialId), $userId);
		return array(
			'success' => true,
			'tags' => isset($tagsMap[$potentialId]) ? array_values($tagsMap[$potentialId]) : array(),
		);
	}

	public static function markConvertedToCustomer($potentialId, $contactId = 0) {
		$potentialId = (int) $potentialId;
		$contactId = (int) $contactId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		$adb = PearDatabase::getInstance();
		self::ensureProfileSchema($adb);
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery('SELECT potentialid FROM bace_potential_profile WHERE potentialid = ?', array($potentialId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE bace_potential_profile SET converted_to_customer_at = ?, contact_customer_id = ?, modified_at = ? WHERE potentialid = ?',
				array($now, $contactId > 0 ? $contactId : null, $now, $potentialId)
			);
		} else {
			$adb->pquery(
				'INSERT INTO bace_potential_profile (potentialid, converted_to_customer_at, contact_customer_id, modified_at) VALUES (?,?,?,?)',
				array($potentialId, $now, $contactId > 0 ? $contactId : null, $now)
			);
		}
		return true;
	}

	public static function deletePotential($potentialId) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'Delete', $potentialId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($potentialId, self::MODULE);
		$recordModel->delete();
		return true;
	}
}
