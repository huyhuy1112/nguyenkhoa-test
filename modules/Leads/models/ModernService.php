<?php
/*+***********************************************************************************
 * Modern Leads SALES UI — DB service (Phase 1).
 * Maps cache-shaped lead rows ↔ vtiger Leads + side tables + Tags module.
 *************************************************************************************/

require_once 'modules/Leads/data/ModernSeedData.php';
require_once 'modules/Leads/models/CommerceService.php';
require_once 'modules/Leads/models/ConvertService.php';

class Leads_ModernService {

	const MODULE = 'Leads';

	const MAX_CALLS_PER_DAY = 10;

	protected static $sourceTags = array('facebook', 'tiktok', 'website', 'zalo', 'other', 'other_source');

	protected static $purchaseMap = array(
		'mua_lan_dau' => 'New',
		'mua_lai' => 'Contact in Future',
		'khong_mua' => 'Lost Lead',
		'ngung_mua' => 'Junk',
	);

	public static function installSchema(PearDatabase $adb) {
		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_lead_profile (
			leadid INT(19) NOT NULL,
			mk_cache_id VARCHAR(32) DEFAULT NULL,
			cccd VARCHAR(32) DEFAULT NULL,
			segment VARCHAR(64) DEFAULT NULL,
			district VARCHAR(128) DEFAULT NULL,
			address_line VARCHAR(255) DEFAULT NULL,
			area VARCHAR(255) DEFAULT NULL,
			lead_value DECIMAL(18,2) DEFAULT 0,
			last_touch DATETIME DEFAULT NULL,
			next_action VARCHAR(255) DEFAULT NULL,
			open_tickets INT(11) DEFAULT 0,
			customer_type VARCHAR(32) DEFAULT NULL,
			purchase_reason TEXT DEFAULT NULL,
			is_modern TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT NULL,
			modified_at DATETIME DEFAULT NULL,
			PRIMARY KEY (leadid),
			UNIQUE KEY uniq_mk_cache_id (mk_cache_id),
			KEY idx_last_touch (last_touch)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_lead_purchases (
			id INT(11) NOT NULL AUTO_INCREMENT,
			leadid INT(19) NOT NULL,
			order_id VARCHAR(64) DEFAULT NULL,
			order_name VARCHAR(255) DEFAULT NULL,
			product VARCHAR(255) DEFAULT NULL,
			qty INT(11) DEFAULT 0,
			value DECIMAL(18,2) DEFAULT 0,
			purchase_date VARCHAR(32) DEFAULT NULL,
			sort_order INT(11) DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_lead (leadid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_lead_calendar_tasks (
			id INT(11) NOT NULL AUTO_INCREMENT,
			leadid INT(19) NOT NULL,
			task_type VARCHAR(32) DEFAULT 'task',
			subject VARCHAR(255) DEFAULT NULL,
			status VARCHAR(32) DEFAULT 'open',
			due_at DATETIME DEFAULT NULL,
			due_label VARCHAR(64) DEFAULT NULL,
			sort_order INT(11) DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_lead (leadid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		$adb->pquery("CREATE TABLE IF NOT EXISTS bace_lead_segments (
			id INT(11) NOT NULL AUTO_INCREMENT,
			userid INT(11) NOT NULL,
			name VARCHAR(128) NOT NULL,
			filters_json TEXT NOT NULL,
			created_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_user (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		$colRes = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE 'potential_id'", array());
		if (!$colRes || $adb->num_rows($colRes) < 1) {
			$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN potential_id INT(19) DEFAULT NULL AFTER mk_cache_id", array());
		}
	}

	public static function isInstalled(PearDatabase $adb) {
		$res = $adb->pquery("SHOW TABLES LIKE ?", array('bace_lead_profile'));
		return ($res && $adb->num_rows($res) > 0);
	}

	public static function listLeads($userId = null) {
		$adb = PearDatabase::getInstance();
		if (!self::isInstalled($adb)) {
			return array();
		}
		$sql = "SELECT p.leadid, p.mk_cache_id, p.lead_value, p.last_touch, p.next_action, p.open_tickets,
				p.segment, p.district, p.address_line, p.area, p.cccd, p.customer_type, p.purchase_reason,
				ld.firstname, ld.lastname, ld.email, ld.company, ld.leadsource, ld.leadstatus,
				la.phone, ce.smownerid
			FROM bace_lead_profile p
			INNER JOIN vtiger_leaddetails ld ON ld.leadid = p.leadid
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			WHERE p.is_modern = 1
			ORDER BY p.last_touch DESC, p.leadid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$leadIds = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$leadIds[] = (int)$row['leadid'];
			$rows[] = $row;
		}
		$tagsByLead = self::getTagsForLeadIds($leadIds, $userId);
		$purchasesByLead = self::getPurchasesForLeadIds($leadIds);
		$tasksByLead = self::getCalendarTasksForLeadIds($leadIds);
		$out = array();
		foreach ($rows as $row) {
			$leadId = (int)$row['leadid'];
			$out[] = self::composeCacheRow($row, $tagsByLead[$leadId] ?? array(), $purchasesByLead[$leadId] ?? array(), $tasksByLead[$leadId] ?? array());
		}
		return $out;
	}

	public static function getLead($idOrCacheId, $userId = null) {
		$leadId = self::resolveLeadId($idOrCacheId);
		if (!$leadId && is_numeric($idOrCacheId) && self::vtigerLeadExists((int)$idOrCacheId)) {
			$leadId = (int)$idOrCacheId;
			self::ensureModernProfile($leadId);
		}
		if (!$leadId) {
			return null;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT p.leadid, p.mk_cache_id, p.lead_value, p.last_touch, p.next_action, p.open_tickets,
				p.segment, p.district, p.address_line, p.area, p.cccd, p.customer_type, p.purchase_reason,
				ld.firstname, ld.lastname, ld.email, ld.company, ld.leadsource, ld.leadstatus,
				la.phone, ce.smownerid
			FROM bace_lead_profile p
			INNER JOIN vtiger_leaddetails ld ON ld.leadid = p.leadid
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			WHERE p.leadid = ? AND p.is_modern = 1",
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			if (self::vtigerLeadExists($leadId)) {
				self::ensureModernProfile($leadId);
				$res = $adb->pquery(
					"SELECT p.leadid, p.mk_cache_id, p.lead_value, p.last_touch, p.next_action, p.open_tickets,
						p.segment, p.district, p.address_line, p.area, p.cccd, p.customer_type, p.purchase_reason,
						ld.firstname, ld.lastname, ld.email, ld.company, ld.leadsource, ld.leadstatus,
						la.phone, ce.smownerid
					FROM bace_lead_profile p
					INNER JOIN vtiger_leaddetails ld ON ld.leadid = p.leadid
					INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
					LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
					WHERE p.leadid = ? AND p.is_modern = 1",
					array($leadId)
				);
			}
		}
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		$row = $adb->query_result_rowdata($res, 0);
		$tags = self::getTagsForLeadIds(array($leadId), $userId);
		$tagList = $tags[$leadId] ?? array();
		$tagList = self::syncCallAttemptTagsIfNeeded($leadId, $tagList, $userId);
		$purchases = self::getPurchasesForLeadIds(array($leadId));
		$tasks = self::getCalendarTasksForLeadIds(array($leadId));
		return self::composeCacheRow(
			$row,
			$tagList,
			$purchases[$leadId] ?? array(),
			$tasks[$leadId] ?? array()
		);
	}

	public static function syncCallAttemptTagsForLead($leadId, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$resolved = self::resolveLeadId($leadId);
		if (!$resolved) {
			return;
		}
		$tagsByLead = self::getTagsForLeadIds(array($resolved), $userId);
		self::syncCallAttemptTagsIfNeeded($resolved, $tagsByLead[$resolved] ?? array(), $userId);
	}

	public static function applyCallAttemptTags(array $tags, $todayCallCount) {
		$out = array();
		foreach ($tags as $tag) {
			if (strpos((string)$tag, 'goi_lan_') !== 0) {
				$out[] = $tag;
			}
		}
		$todayCallCount = (int)$todayCallCount;
		if ($todayCallCount > 0) {
			$n = min($todayCallCount, self::MAX_CALLS_PER_DAY);
			$out[] = 'goi_lan_' . $n;
		}
		return array_values(array_unique($out));
	}

	protected static function syncCallAttemptTagsIfNeeded($leadId, array $tags, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$todayCalls = Leads_CommerceService::countTodayCallsForLead($leadId);
		$expected = self::applyCallAttemptTags($tags, $todayCalls);
		$current = array_values(array_unique(array_map('strval', $tags)));
		sort($current);
		$next = array_values(array_unique(array_map('strval', $expected)));
		sort($next);
		if ($current !== $next) {
			self::syncTags($leadId, $expected, $userId);
		}
		return $expected;
	}

	protected static function vtigerLeadExists($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT 1 FROM vtiger_crmentity ce
			 INNER JOIN vtiger_leaddetails ld ON ld.leadid = ce.crmid
			 WHERE ce.crmid = ? AND ce.deleted = 0 AND ce.setype = ?",
			array((int)$leadId, self::MODULE)
		);
		return ($res && $adb->num_rows($res) > 0);
	}

	protected static function ensureModernProfile($leadId) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$exists = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE leadid = ?", array((int)$leadId));
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}
		if (!self::vtigerLeadExists($leadId)) {
			return;
		}
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			"INSERT INTO bace_lead_profile(leadid, is_modern, created_at, modified_at) VALUES(?,?,?,?)",
			array((int)$leadId, 1, $now, $now)
		);
	}

	public static function saveLead(array $payload, $recordId = null) {
		global $current_user;
		$adb = PearDatabase::getInstance();
		$userId = (int)$current_user->id;
		$leadId = self::resolveLeadId($recordId);
		if (!$leadId && isset($payload['id'])) {
			$leadId = self::resolveLeadId($payload['id']);
		}
		if (!$leadId) {
			$existingId = self::findExistingLeadIdByPhoneOrEmail(
				isset($payload['phone']) ? $payload['phone'] : '',
				isset($payload['email']) ? $payload['email'] : ''
			);
			if ($existingId) {
				$leadId = $existingId;
			}
		}
		$requestedId = ($recordId !== null && $recordId !== '') ? $recordId : null;
		if ($requestedId === null && isset($payload['id']) && $payload['id'] !== '') {
			$requestedId = $payload['id'];
		}
		if ($requestedId !== null && $requestedId !== '' && !$leadId) {
			throw new Exception('Lead not found.');
		}
		if ($leadId) {
			$payload = self::hydratePayloadForExistingLead($leadId, $payload, $userId);
		}

		$ownerId = self::resolveUserId(isset($payload['owner']) ? $payload['owner'] : '', $userId);
		$name = trim((string)(isset($payload['name']) ? $payload['name'] : ''));
		$phone = trim((string)(isset($payload['phone']) ? $payload['phone'] : ''));
		if ($name === '' || $phone === '') {
			throw new Exception('Name and phone are required.');
		}

		list($firstname, $lastname) = self::splitName($name);
		$tags = isset($payload['tags']) && is_array($payload['tags']) ? $payload['tags'] : array();
		$company = trim((string)(isset($payload['companyName']) ? $payload['companyName'] : ''));
		if ($company === '') {
			$company = '-';
		}

		$isNew = !$leadId;
		if ($leadId) {
			$recordModel = Vtiger_Record_Model::getInstanceById($leadId, self::MODULE);
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance(self::MODULE);
		}

		$recordModel->set('firstname', $firstname);
		$recordModel->set('lastname', $lastname);
		$recordModel->set('phone', $phone);
		$recordModel->set('email', isset($payload['email']) ? $payload['email'] : '');
		$recordModel->set('company', $company);
		$recordModel->set('leadsource', self::mapLeadsource($tags));
		$recordModel->set('leadstatus', self::mapLeadstatus($tags));
		$recordModel->set('assigned_user_id', $ownerId);
		$recordModel->set('lane', isset($payload['address']) ? $payload['address'] : '');
		$recordModel->set('code', isset($payload['district']) ? $payload['district'] : '');
		$recordModel->save();
		$leadId = (int)$recordModel->getId();

		$now = date('Y-m-d H:i:s');
		$lastTouch = isset($payload['last_touch']) ? self::normalizeDateTime($payload['last_touch']) : $now;
		$mkCacheId = isset($payload['id']) && !is_numeric($payload['id']) ? $payload['id'] : null;
		if (!$mkCacheId && $leadId) {
			$existing = $adb->pquery("SELECT mk_cache_id FROM bace_lead_profile WHERE leadid = ?", array($leadId));
			if ($existing && $adb->num_rows($existing) > 0) {
				$mkCacheId = $adb->query_result($existing, 0, 'mk_cache_id');
			}
		}

		$profile = array(
			'mk_cache_id' => $mkCacheId,
			'cccd' => isset($payload['cccd']) ? $payload['cccd'] : '',
			'segment' => isset($payload['segment']) ? $payload['segment'] : '',
			'district' => isset($payload['district']) ? $payload['district'] : '',
			'address_line' => isset($payload['address']) ? $payload['address'] : '',
			'area' => isset($payload['area']) ? $payload['area'] : '',
			'lead_value' => isset($payload['value']) ? (float)$payload['value'] : 0,
			'last_touch' => $lastTouch,
			'next_action' => isset($payload['next_action']) ? $payload['next_action'] : '',
			'open_tickets' => isset($payload['openTickets']) ? (int)$payload['openTickets'] : 0,
			'customer_type' => self::findTag($tags, array('individual', 'company')),
			'purchase_reason' => isset($payload['purchaseReason']) ? $payload['purchaseReason'] : '',
		);
		self::upsertProfile($leadId, $profile);
		$todayCalls = Leads_CommerceService::countTodayCallsForLead($leadId);
		$tags = self::applyCallAttemptTags($tags, $todayCalls);
		$tags = self::applyRegionTags($tags, isset($payload['district']) ? $payload['district'] : '');
		self::syncTags($leadId, $tags, $userId);

		if ($isNew && empty($mkCacheId)) {
			Leads_ConvertService::ensurePotentialForLead($leadId, $payload, $ownerId);
		}

		return self::getLead((string)$leadId, $userId);
	}

	/**
	 * Update assigned_user_id only — never creates a new Lead record.
	 */
	public static function assignOwnerToLeads(array $idsOrCacheIds, $ownerUserId) {
		global $current_user;
		$userId = (int)$current_user->id;
		$ownerId = self::resolveUserId($ownerUserId, $userId);
		if ($ownerId <= 0) {
			throw new Exception('Invalid owner.');
		}
		$updated = array();
		$failed = array();
		foreach ($idsOrCacheIds as $idOrCacheId) {
			if ($idOrCacheId === null || $idOrCacheId === '') {
				continue;
			}
			$leadId = self::resolveLeadId($idOrCacheId);
			if (!$leadId) {
				$failed[] = $idOrCacheId;
				continue;
			}
			try {
				$recordModel = Vtiger_Record_Model::getInstanceById($leadId, self::MODULE);
				$recordModel->set('id', $leadId);
				$recordModel->set('mode', 'edit');
				$recordModel->set('assigned_user_id', $ownerId);
				$recordModel->save();
				$lead = self::getLead((string)$leadId, $userId);
				if ($lead) {
					$updated[] = $lead;
				}
			} catch (Exception $e) {
				$failed[] = $idOrCacheId;
			}
		}
		if (empty($updated) && !empty($failed)) {
			throw new Exception('Could not update owner for selected lead(s).');
		}
		return $updated;
	}

	public static function deleteLead($idOrCacheId) {
		$leadId = self::resolveLeadId($idOrCacheId);
		if (!$leadId) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery("DELETE FROM bace_lead_purchases WHERE leadid = ?", array($leadId));
		$adb->pquery("DELETE FROM bace_lead_calendar_tasks WHERE leadid = ?", array($leadId));
		$adb->pquery("DELETE FROM bace_lead_profile WHERE leadid = ?", array($leadId));
		$recordModel = Vtiger_Record_Model::getInstanceById($leadId, self::MODULE);
		$recordModel->delete();
		return true;
	}

	public static function getSegments($userId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT id, name, filters_json FROM bace_lead_segments WHERE userid = ? ORDER BY id ASC",
			array((int)$userId)
		);
		$rows = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$filters = json_decode($row['filters_json'], true);
			$rows[] = array(
				'id' => (string)$row['id'],
				'name' => $row['name'],
				'filters' => is_array($filters) ? $filters : array(),
			);
		}
		return $rows;
	}

	public static function saveSegments($userId, array $segments) {
		$adb = PearDatabase::getInstance();
		$adb->pquery("DELETE FROM bace_lead_segments WHERE userid = ?", array((int)$userId));
		foreach ($segments as $seg) {
			if (empty($seg['name'])) {
				continue;
			}
			$filters = isset($seg['filters']) ? $seg['filters'] : array();
			$adb->pquery(
				"INSERT INTO bace_lead_segments(userid, name, filters_json, created_at) VALUES(?,?,?,?)",
				array((int)$userId, $seg['name'], json_encode($filters), date('Y-m-d H:i:s'))
			);
		}
		return self::getSegments($userId);
	}

	public static function isAutoSeedEnabled() {
		global $MK_LEADS_AUTO_SEED_DEMO;
		return isset($MK_LEADS_AUTO_SEED_DEMO) ? (bool)$MK_LEADS_AUTO_SEED_DEMO : false;
	}

	public static function ensureDemoSeeded() {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		if (!self::isAutoSeedEnabled()) {
			return array('seeded' => false, 'disabled' => true, 'created' => 0, 'skipped' => 0);
		}
		$res = $adb->pquery(
			"SELECT COUNT(*) AS c FROM bace_lead_profile WHERE mk_cache_id LIKE ?",
			array('L-10%')
		);
		$demoCount = ($res && $adb->num_rows($res) > 0) ? (int)$adb->query_result($res, 0, 'c') : 0;
		if ($demoCount >= 21) {
			return array('seeded' => false, 'demo_count' => $demoCount, 'created' => 0, 'skipped' => 21);
		}
		$result = self::seedDemoLeads(false);
		$result['seeded'] = true;
		return $result;
	}

	public static function seedDemoLeads($force = false) {
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		$created = 0;
		$skipped = 0;
		foreach (Leads_ModernSeedData::allEnriched() as $lead) {
			$cacheId = $lead['id'];
			$exists = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE mk_cache_id = ?", array($cacheId));
			if (!$force && $exists && $adb->num_rows($exists) > 0) {
				$skipped++;
				continue;
			}
			if ($force && $exists && $adb->num_rows($exists) > 0) {
				self::deleteLead($adb->query_result($exists, 0, 'leadid'));
			}
			$saved = self::saveLead($lead);
			$leadId = isset($saved['crmid']) ? (int)$saved['crmid'] : self::resolveLeadId($cacheId);
			if ($leadId && isset($lead['purchases']) && is_array($lead['purchases'])) {
				self::replacePurchases($leadId, $lead['purchases']);
			}
			if ($leadId && isset($lead['calendarTasks']) && is_array($lead['calendarTasks'])) {
				self::replaceCalendarTasks($leadId, $lead['calendarTasks']);
			}
			$created++;
		}
		$totalRes = $adb->pquery("SELECT COUNT(*) AS c FROM bace_lead_profile WHERE is_modern = 1", array());
		$total = ($totalRes && $adb->num_rows($totalRes) > 0) ? (int)$adb->query_result($totalRes, 0, 'c') : 0;
		return array('created' => $created, 'skipped' => $skipped, 'total' => $total);
	}

	protected static function decodeText($value) {
		if ($value === null || $value === '') {
			return '';
		}
		if (!is_string($value)) {
			return $value;
		}
		return decode_html($value);
	}

	protected static function composeDisplayName($firstname, $lastname) {
		$firstname = self::decodeText(trim((string)$firstname));
		$lastname = self::decodeText(trim((string)$lastname));
		if ($firstname === '' && $lastname === '') {
			return '';
		}
		if ($firstname === '' || $firstname === '.') {
			return $lastname;
		}
		if ($lastname === '' || $lastname === '.') {
			return $firstname;
		}
		return trim($lastname . ' ' . $firstname);
	}

	protected static function composeCacheRow(array $row, array $tags, array $purchases, array $calendarTasks) {
		$leadId = (int)$row['leadid'];
		$name = self::composeDisplayName(
			isset($row['firstname']) ? $row['firstname'] : '',
			isset($row['lastname']) ? $row['lastname'] : ''
		);
		$ownerName = self::getOwnerLabel((int)$row['smownerid']);
		if ($ownerName === '') {
			$ownerName = self::getUserDisplayName((int)$row['smownerid']);
		}
		$id = !empty($row['mk_cache_id']) ? $row['mk_cache_id'] : (string)$leadId;
		$lastTouch = !empty($row['last_touch']) ? date('c', strtotime($row['last_touch'])) : date('c');
		$company = self::decodeText(isset($row['company']) ? $row['company'] : '');
		$storedNext = self::decodeText(isset($row['next_action']) ? $row['next_action'] : '');
		$nextAction = Leads_CommerceService::deriveNextActionLabel($calendarTasks, $storedNext);

		$conversion = Leads_ConvertService::getConversionStatus($leadId);

		return array(
			'id' => $id,
			'crmid' => $leadId,
			'potentialId' => $conversion['potentialId'],
			'converted' => $conversion['converted'],
			'canConvert' => $conversion['canConvert'],
			'potentialUrl' => $conversion['potentialUrl'],
			'name' => $name,
			'phone' => self::decodeText(isset($row['phone']) ? $row['phone'] : ''),
			'email' => self::decodeText(isset($row['email']) ? $row['email'] : ''),
			'cccd' => self::decodeText(isset($row['cccd']) ? $row['cccd'] : ''),
			'companyName' => ($company === '-' || $company === '') ? '' : $company,
			'tags' => array_values($tags),
			'owner' => self::decodeText($ownerName),
			'owner_username' => self::getUsername((int)$row['smownerid']),
			'value' => (float)$row['lead_value'],
			'last_touch' => $lastTouch,
			'next_action' => $nextAction,
			'segment' => self::decodeText(isset($row['segment']) ? $row['segment'] : ''),
			'district' => self::decodeText(isset($row['district']) ? $row['district'] : ''),
			'address' => self::decodeText(isset($row['address_line']) ? $row['address_line'] : ''),
			'area' => self::decodeText(isset($row['area']) ? $row['area'] : ''),
			'openTickets' => (int)$row['open_tickets'],
			'purchases' => $purchases,
			'calendarTasks' => $calendarTasks,
			'activities' => array(),
			'notes' => '',
		);
	}

	protected static function upsertProfile($leadId, array $profile) {
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$exists = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE leadid = ?", array($leadId));
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				"UPDATE bace_lead_profile SET mk_cache_id=?, cccd=?, segment=?, district=?, address_line=?, area=?,
				 lead_value=?, last_touch=?, next_action=?, open_tickets=?, customer_type=?, purchase_reason=?, modified_at=?
				 WHERE leadid=?",
				array(
					$profile['mk_cache_id'], $profile['cccd'], $profile['segment'], $profile['district'],
					$profile['address_line'], $profile['area'], $profile['lead_value'], $profile['last_touch'],
					$profile['next_action'], $profile['open_tickets'], $profile['customer_type'],
					$profile['purchase_reason'], $now, $leadId,
				)
			);
			return;
		}
		$adb->pquery(
			"INSERT INTO bace_lead_profile(leadid, mk_cache_id, cccd, segment, district, address_line, area, lead_value,
			 last_touch, next_action, open_tickets, customer_type, purchase_reason, is_modern, created_at, modified_at)
			 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)",
			array(
				$leadId, $profile['mk_cache_id'], $profile['cccd'], $profile['segment'], $profile['district'],
				$profile['address_line'], $profile['area'], $profile['lead_value'], $profile['last_touch'],
				$profile['next_action'], $profile['open_tickets'], $profile['customer_type'],
				$profile['purchase_reason'], $now, $now,
			)
		);
	}

	protected static function replacePurchases($leadId, array $purchases) {
		$adb = PearDatabase::getInstance();
		$adb->pquery("DELETE FROM bace_lead_purchases WHERE leadid = ?", array($leadId));
		$sort = 0;
		foreach ($purchases as $p) {
			$adb->pquery(
				"INSERT INTO bace_lead_purchases(leadid, order_id, order_name, product, qty, value, purchase_date, sort_order)
				 VALUES(?,?,?,?,?,?,?,?)",
				array(
					$leadId,
					isset($p['orderId']) ? $p['orderId'] : '',
					isset($p['orderName']) ? $p['orderName'] : '',
					isset($p['product']) ? $p['product'] : '',
					isset($p['qty']) ? (int)$p['qty'] : 0,
					isset($p['value']) ? (float)$p['value'] : 0,
					isset($p['date']) ? $p['date'] : '',
					$sort++,
				)
			);
		}
	}

	public static function syncCalendarTasks($idOrCacheId, array $tasks, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$leadId = self::resolveLeadId($idOrCacheId);
		if (!$leadId) {
			throw new Exception('Lead not found.');
		}
		self::replaceCalendarTasks($leadId, $tasks);
		Leads_CommerceService::syncNextActionForLead($leadId);
		return self::getLead((string)$idOrCacheId, $userId);
	}

	public static function persistCalendarTasksOnly($leadId, array $tasks) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return;
		}
		self::replaceCalendarTasks($leadId, $tasks);
	}

	protected static function hydratePayloadForExistingLead($leadId, array $payload, $userId = null) {
		$existing = self::getLead((string)$leadId, $userId);
		if ($existing) {
			foreach (array('name', 'phone', 'email', 'owner', 'value', 'last_touch', 'next_action', 'segment', 'district', 'address', 'area', 'cccd', 'companyName', 'purchaseReason', 'openTickets') as $field) {
				if ((!isset($payload[$field]) || $payload[$field] === '' || $payload[$field] === null) && isset($existing[$field]) && $existing[$field] !== '' && $existing[$field] !== null) {
					$payload[$field] = $existing[$field];
				}
			}
			if ((!isset($payload['tags']) || !is_array($payload['tags']) || empty($payload['tags'])) && !empty($existing['tags'])) {
				$payload['tags'] = $existing['tags'];
			}
			if (!isset($payload['id']) || $payload['id'] === '') {
				$payload['id'] = $existing['id'];
			}
			return $payload;
		}
		try {
			$recordModel = Vtiger_Record_Model::getInstanceById((int)$leadId, self::MODULE);
		} catch (Exception $e) {
			return $payload;
		}
		if (empty($payload['name'])) {
			$payload['name'] = self::composeDisplayName($recordModel->get('firstname'), $recordModel->get('lastname'));
		}
		if (empty($payload['phone'])) {
			$payload['phone'] = self::decodeText($recordModel->get('phone'));
		}
		if (empty($payload['email'])) {
			$payload['email'] = self::decodeText($recordModel->get('email'));
		}
		if (empty($payload['companyName'])) {
			$company = self::decodeText($recordModel->get('company'));
			if ($company !== '' && $company !== '-') {
				$payload['companyName'] = $company;
			}
		}
		return $payload;
	}

	protected static function normalizePhone($phone) {
		return preg_replace('/\D+/', '', (string)$phone);
	}

	public static function findExistingLeadIdByPhoneOrEmail($phone, $email = '', $excludeLeadId = null) {
		$adb = PearDatabase::getInstance();
		$phoneNorm = self::normalizePhone($phone);
		$email = strtolower(trim((string)$email));
		if ($phoneNorm === '' && $email === '') {
			return null;
		}
		$conds = array();
		$params = array();
		if ($phoneNorm !== '') {
			$conds[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(la.phone,''),' ',''),'-',''),'.',''),'+',''),' ','') = ?";
			$params[] = $phoneNorm;
		}
		if ($email !== '') {
			$conds[] = "LOWER(TRIM(IFNULL(ld.email,''))) = ?";
			$params[] = $email;
		}
		$sql = "SELECT p.leadid
			FROM bace_lead_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			INNER JOIN vtiger_leaddetails ld ON ld.leadid = p.leadid
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			WHERE p.is_modern = 1 AND (" . implode(' OR ', $conds) . ")";
		if ($excludeLeadId) {
			$sql .= " AND p.leadid != ?";
			$params[] = (int)$excludeLeadId;
		}
		$sql .= " ORDER BY p.last_touch DESC, p.leadid DESC LIMIT 1";
		$res = $adb->pquery($sql, $params);
		if ($res && $adb->num_rows($res) > 0) {
			return (int)$adb->query_result($res, 0, 'leadid');
		}
		return null;
	}

	public static function dedupeModernLeadsByPhone($dryRun = true) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT p.leadid, la.phone, ld.email, p.last_touch
			 FROM bace_lead_profile p
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			 INNER JOIN vtiger_leaddetails ld ON ld.leadid = p.leadid
			 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
			 WHERE p.is_modern = 1
			 ORDER BY p.leadid ASC",
			array()
		);
		$groups = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$leadId = (int)$row['leadid'];
			$phoneNorm = self::normalizePhone($row['phone'] ?? '');
			$email = strtolower(trim((string)($row['email'] ?? '')));
			$name = strtolower(trim(preg_replace('/\s+/', ' ', self::composeDisplayName(
				isset($row['firstname']) ? $row['firstname'] : '',
				isset($row['lastname']) ? $row['lastname'] : ''
			))));
			if ($phoneNorm !== '') {
				$key = 'p:' . $phoneNorm;
			} elseif ($email !== '') {
				$key = 'e:' . $email;
			} elseif ($name !== '') {
				$key = 'n:' . $name;
			} else {
				$key = 'id:' . $leadId;
			}
			if (!isset($groups[$key])) {
				$groups[$key] = array();
			}
			$groups[$key][] = array(
				'leadid' => $leadId,
				'last_touch' => $row['last_touch'] ?? null,
			);
		}

		$deleted = 0;
		$kept = 0;
		$report = array();
		foreach ($groups as $key => $items) {
			if (count($items) < 2) {
				continue;
			}
			usort($items, function ($a, $b) use ($adb) {
				$actA = self::countLeadActivities((int)$a['leadid'], $adb);
				$actB = self::countLeadActivities((int)$b['leadid'], $adb);
				if ($actA !== $actB) {
					return $actB - $actA;
				}
				$ta = !empty($a['last_touch']) ? strtotime($a['last_touch']) : 0;
				$tb = !empty($b['last_touch']) ? strtotime($b['last_touch']) : 0;
				if ($ta !== $tb) {
					return $tb - $ta;
				}
				return (int)$b['leadid'] - (int)$a['leadid'];
			});
			$keeper = (int)$items[0]['leadid'];
			$kept++;
			for ($j = 1; $j < count($items); $j++) {
				$dupId = (int)$items[$j]['leadid'];
				$report[] = array('keep' => $keeper, 'delete' => $dupId, 'group' => $key);
				if (!$dryRun) {
					self::deleteLead($dupId);
				}
				$deleted++;
			}
		}
		return array('groups' => $kept, 'deleted' => $deleted, 'dry_run' => $dryRun, 'report' => $report);
	}

	protected static function countLeadActivities($leadId, PearDatabase $adb = null) {
		if ($adb === null) {
			$adb = PearDatabase::getInstance();
		}
		$res = $adb->pquery(
			"SELECT COUNT(*) AS c FROM vtiger_seactivityrel WHERE crmid = ?",
			array((int)$leadId)
		);
		return ($res && $adb->num_rows($res) > 0) ? (int)$adb->query_result($res, 0, 'c') : 0;
	}

	protected static function replaceCalendarTasks($leadId, array $tasks) {
		$adb = PearDatabase::getInstance();
		$adb->pquery("DELETE FROM bace_lead_calendar_tasks WHERE leadid = ?", array($leadId));
		$sort = 0;
		foreach ($tasks as $t) {
			$dueAt = isset($t['dueAt']) ? self::normalizeDateTime($t['dueAt']) : null;
			$adb->pquery(
				"INSERT INTO bace_lead_calendar_tasks(leadid, task_type, subject, status, due_at, due_label, sort_order)
				 VALUES(?,?,?,?,?,?,?)",
				array(
					$leadId,
					isset($t['type']) ? $t['type'] : 'task',
					isset($t['subject']) ? $t['subject'] : '',
					isset($t['status']) ? $t['status'] : 'open',
					$dueAt,
					isset($t['dueLabel']) ? $t['dueLabel'] : '',
					$sort++,
				)
			);
		}
	}

	protected static function getPurchasesForLeadIds(array $leadIds) {
		return Leads_CommerceService::getPurchasesForLeadIds($leadIds);
	}

	protected static function getCalendarTasksForLeadIds(array $leadIds) {
		return Leads_CommerceService::getCalendarTasksForLeadIds($leadIds);
	}

	protected static function getTagsForLeadIds(array $leadIds, $userId = null) {
		if (empty($leadIds)) {
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
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($leadIds) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $leadIds)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int)$adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$leadId])) {
				$map[$leadId] = array();
			}
			$map[$leadId][] = $tag;
		}
		return $map;
	}

	protected static function syncTags($leadId, array $tagNames, $userId) {
		$tagNames = array_values(array_unique(array_filter(array_map('strval', $tagNames))));
		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $leadId);
		$existingByName = array();
		$existingIds = array();
		foreach ($existing as $tagModel) {
			$name = $tagModel->getName();
			$existingByName[$name] = $tagModel->getId();
			$existingIds[] = $tagModel->getId();
		}
		$targetIds = array();
		foreach ($tagNames as $name) {
			if (isset($existingByName[$name])) {
				$targetIds[] = $existingByName[$name];
				continue;
			}
			$tagModel = Vtiger_Tag_Model::getInstanceByName($name, $userId);
			if ($tagModel) {
				$targetIds[] = $tagModel->getId();
				continue;
			}
			$newTag = new Vtiger_Tag_Model();
			$newTag->setName($name)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$targetIds[] = $newTag->create();
		}
		$targetIds = array_values(array_unique($targetIds));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($leadId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($leadId, $toRemove, $userId, self::MODULE);
		}
	}

	protected static function resolveLeadId($idOrCacheId) {
		if ($idOrCacheId === null || $idOrCacheId === '') {
			return null;
		}
		if (is_numeric($idOrCacheId)) {
			$leadId = (int)$idOrCacheId;
			return self::vtigerLeadExists($leadId) ? $leadId : null;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE mk_cache_id = ?", array($idOrCacheId));
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'leadid');
			return self::vtigerLeadExists($leadId) ? $leadId : null;
		}
		return null;
	}

	/**
	 * Active CRM users the current user may assign Leads to (SALES list / edit).
	 */
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

	public static function resolveUserId($owner, $fallbackUserId) {
		$owner = trim((string)$owner);
		if ($owner === '') {
			return (int)$fallbackUserId;
		}
		if (is_numeric($owner)) {
			$userId = (int)$owner;
			if ($userId > 0) {
				return $userId;
			}
		}
		$adb = PearDatabase::getInstance();
		$queries = array(
			array("SELECT id FROM vtiger_users WHERE user_name = ? AND status = 'Active'", array($owner)),
			array("SELECT id FROM vtiger_users WHERE userlabel = ? AND status = 'Active'", array($owner)),
			array("SELECT id FROM vtiger_users WHERE first_name = ? AND status = 'Active'", array($owner)),
			array("SELECT id FROM vtiger_users WHERE last_name = ? AND status = 'Active'", array($owner)),
			array("SELECT id FROM vtiger_users WHERE CONCAT(first_name,' ',last_name) = ? AND status = 'Active'", array($owner)),
			array("SELECT id FROM vtiger_users WHERE userlabel LIKE ? AND status = 'Active'", array('%' . $owner . '%')),
		);
		foreach ($queries as $q) {
			$res = $adb->pquery($q[0], $q[1]);
			if ($res && $adb->num_rows($res) > 0) {
				return (int)$adb->query_result($res, 0, 'id');
			}
		}
		return (int)$fallbackUserId;
	}

	protected static function getOwnerLabel($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '';
		}
		try {
			$userRecord = Users_Record_Model::getInstanceById($userId, 'Users');
			if (method_exists($userRecord, 'getName')) {
				$name = trim(decode_html($userRecord->getName()));
				if ($name !== '') {
					return $name;
				}
			}
			$label = trim(decode_html((string)$userRecord->get('userlabel')));
			if ($label !== '') {
				return $label;
			}
		} catch (Exception $e) {
			// fall through to SQL
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT userlabel, first_name, last_name, user_name FROM vtiger_users WHERE id = ?",
			array($userId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$userlabel = self::decodeText($adb->query_result($res, 0, 'userlabel'));
			if ($userlabel !== '') {
				return $userlabel;
			}
			$first = self::decodeText($adb->query_result($res, 0, 'first_name'));
			$last = self::decodeText($adb->query_result($res, 0, 'last_name'));
			$full = trim($first . ' ' . $last);
			if ($full !== '') {
				return $full;
			}
			return self::decodeText($adb->query_result($res, 0, 'user_name'));
		}
		return '';
	}

	protected static function getUserDisplayName($userId) {
		$username = self::getUsername($userId);
		if ($username) {
			return $username;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT userlabel, first_name FROM vtiger_users WHERE id = ?", array($userId));
		if ($res && $adb->num_rows($res) > 0) {
			$label = $adb->query_result($res, 0, 'userlabel');
			if ($label) {
				return self::decodeText($label);
			}
			return self::decodeText($adb->query_result($res, 0, 'first_name'));
		}
		return 'User';
	}

	protected static function getUsername($userId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT user_name FROM vtiger_users WHERE id = ?", array($userId));
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result($res, 0, 'user_name');
		}
		return '';
	}

	protected static function splitName($fullName) {
		$fullName = trim(preg_replace('/\s+/u', ' ', $fullName));
		if ($fullName === '') {
			return array('', 'Lead');
		}
		$parts = preg_split('/\s+/u', $fullName);
		if (count($parts) === 1) {
			return array($parts[0], '.');
		}
		$firstname = array_pop($parts);
		$lastname = trim(implode(' ', $parts));
		if ($lastname === '') {
			$lastname = '.';
		}
		return array($firstname, $lastname);
	}

	protected static function findTag(array $tags, array $pool) {
		foreach ($tags as $tag) {
			if (in_array($tag, $pool, true)) {
				return $tag;
			}
		}
		return null;
	}

	/**
	 * Khu vực 1/2/3 → tag KV1/KV2/KV3 on saved lead profile.
	 */
	protected static function mapDistrictToRegionTag($district) {
		$district = trim((string)$district);
		if ($district === '') {
			return null;
		}
		if (preg_match('/^khu\s*vực\s*([123])$/iu', $district, $m)) {
			return 'KV' . $m[1];
		}
		if (preg_match('/^kv([123])$/i', $district, $m)) {
			return 'KV' . $m[1];
		}
		return null;
	}

	protected static function applyRegionTags(array $tags, $district) {
		$regionTag = self::mapDistrictToRegionTag($district);
		if (!$regionTag) {
			$regionTag = self::findTag($tags, array('KV1', 'KV2', 'KV3'));
		}
		$tags = array_values(array_filter($tags, function ($tag) {
			return !preg_match('/^KV[123]$/i', (string)$tag);
		}));
		if ($regionTag) {
			$tags[] = strtoupper($regionTag);
		}
		return $tags;
	}

	protected static function mapLeadsource(array $tags) {
		return self::mapLeadsourcePublic($tags);
	}

	public static function mapLeadsourcePublic(array $tags) {
		$source = self::findTag($tags, self::$sourceTags);
		if (!$source) {
			return 'Other';
		}
		$map = array(
			'facebook' => 'Facebook',
			'tiktok' => 'Other',
			'website' => 'Web Site',
			'zalo' => 'Other',
			'other' => 'Other',
			'other_source' => 'Other',
		);
		return isset($map[$source]) ? $map[$source] : 'Other';
	}

	protected static function mapLeadstatus(array $tags) {
		$purchase = self::findTag($tags, array_keys(self::$purchaseMap));
		if ($purchase && isset(self::$purchaseMap[$purchase])) {
			return self::$purchaseMap[$purchase];
		}
		return 'New';
	}

	protected static function normalizeDateTime($value) {
		if (empty($value)) {
			return date('Y-m-d H:i:s');
		}
		$ts = strtotime($value);
		if ($ts === false) {
			return date('Y-m-d H:i:s');
		}
		return date('Y-m-d H:i:s', $ts);
	}
}
