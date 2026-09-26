<?php
/**
 * Resolve customer display name for SALES Quotes / SalesOrder — contact only (not organization).
 *
 * Important: list-view reference fields are already display HTML ("--", links), so IDs must
 * be read from rawData or loaded from DB by record id — never cast get('potential_id') to int.
 */
class Vtiger_MkSalesCustomerName_Helper {

	/**
	 * @param Vtiger_Record_Model $recordModel
	 * @return string Plain-text contact name
	 */
	public static function resolveDisplayName(Vtiger_Record_Model $recordModel) {
		return self::resolveListStyleName($recordModel);
	}

	/**
	 * Same name as the SALES list customer column: contact, then Account / subject.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @return string
	 */
	public static function resolveListStyleName(Vtiger_Record_Model $recordModel) {
		$name = self::resolveContactDisplayName($recordModel);
		if ($name === '') {
			$name = self::resolveAlternateCustomerName($recordModel);
		}
		return $name;
	}

	/**
	 * Contact person only — never fall back to account / organization name.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @return string
	 */
	public static function resolveContactDisplayName(Vtiger_Record_Model $recordModel) {
		$refs = self::resolveRecordRefs($recordModel);
		$contactId = (int) $refs['contact_id'];
		if ($contactId > 0) {
			$name = self::readContactNameById($contactId);
			if ($name !== '') {
				$recordModel->set('contact_id', $contactId);
				return $name;
			}
		}
		return '';
	}

	/**
	 * List view uses account_id column slot for customer.
	 * Prefer contact name; fall back to quote subject / Account / SC (franchise).
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @return Vtiger_Record_Model
	 */
	public static function applyListCustomerColumn(Vtiger_Record_Model $recordModel) {
		$beforeContactId = self::extractRawId($recordModel, array('contact_id', 'contactid'));
		$name = self::resolveContactDisplayName($recordModel);
		if ($name === '') {
			$name = self::resolveAlternateCustomerName($recordModel);
		}
		$display = $name !== '' ? $name : '--';
		// Plain text for list cell (strip any previous reference HTML).
		$recordModel->set('account_id', $display);

		$resolvedContactId = self::extractRawId($recordModel, array('contact_id', 'contactid'));
		if ($resolvedContactId <= 0) {
			$resolvedContactId = (int) $recordModel->get('contact_id');
		}
		if ($beforeContactId <= 0 && $resolvedContactId > 0) {
			self::persistContactIdOnRecord($recordModel, $resolvedContactId);
		}

		return $recordModel;
	}

	/**
	 * Franchise / empty-contact quotes: subject, Account, or linked ServiceContract name.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @return string
	 */
	public static function resolveAlternateCustomerName(Vtiger_Record_Model $recordModel) {
		// Prefer real customer entities (SC / Account) over free-text subject.
		$scCandidates = array();
		$accountCandidates = array();
		$subjectCandidates = array();

		$subject = self::cleanDisplayLabel($recordModel->get('subject'));
		if ($subject !== '') {
			$subjectCandidates[] = $subject;
		}

		// Raw fields from DB when list layer already transformed values.
		$recordId = (int) $recordModel->getId();
		$moduleName = method_exists($recordModel, 'getModuleName') ? (string) $recordModel->getModuleName() : '';
		if ($recordId > 0 && ($moduleName === 'Quotes' || $moduleName === 'SalesOrder')) {
			try {
				$db = PearDatabase::getInstance();
				if ($moduleName === 'Quotes') {
					$scIdEarly = self::loadQuoteServiceContractId($recordId);
					if ($scIdEarly > 0) {
						$scName = self::readServiceContractCustomerLabel($scIdEarly);
						if ($scName !== '') {
							$scCandidates[] = $scName;
						}
					}
					$rs = $db->pquery(
						'SELECT q.subject, q.accountid FROM vtiger_quotes q WHERE q.quoteid = ?',
						array($recordId)
					);
				} else {
					$rs = $db->pquery(
						'SELECT so.subject, so.accountid FROM vtiger_salesorder so WHERE so.salesorderid = ?',
						array($recordId)
					);
				}
				if ($rs && $db->num_rows($rs) > 0) {
					$dbSubject = self::cleanDisplayLabel($db->query_result($rs, 0, 'subject'));
					if ($dbSubject !== '') {
						$subjectCandidates[] = $dbSubject;
					}
					$accountId = (int) $db->query_result($rs, 0, 'accountid');
					if ($accountId > 0) {
						$accName = self::readAccountNameById($accountId);
						if ($accName !== '') {
							$accountCandidates[] = $accName;
						}
					}
				}
			} catch (Exception $e) {
				// ignore
			}
		}

		$refs = self::resolveRecordRefs($recordModel);
		if (!empty($refs['account_id'])) {
			$accName = self::readAccountNameById((int) $refs['account_id']);
			if ($accName !== '') {
				$accountCandidates[] = $accName;
			}
		}

		foreach (array_merge($scCandidates, $accountCandidates, $subjectCandidates) as $c) {
			if ($c !== '' && $c !== '--' && $c !== '—') {
				return $c;
			}
		}
		return '';
	}

	/**
	 * @param int $accountId
	 * @return string
	 */
	public static function readAccountNameById($accountId) {
		$accountId = (int) $accountId;
		if ($accountId <= 0) {
			return '';
		}
		try {
			$db = PearDatabase::getInstance();
			$rs = $db->pquery(
				'SELECT a.accountname FROM vtiger_account a
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
				 WHERE a.accountid = ?',
				array($accountId)
			);
			if ($rs && $db->num_rows($rs) > 0) {
				return self::cleanDisplayLabel($db->query_result($rs, 0, 'accountname'));
			}
		} catch (Exception $e) {
			return '';
		}
		return '';
	}

	/**
	 * @param int $scId
	 * @return string
	 */
	public static function readServiceContractCustomerLabel($scId) {
		$scId = (int) $scId;
		if ($scId <= 0) {
			return '';
		}
		try {
			$db = PearDatabase::getInstance();
			$rs = $db->pquery(
				'SELECT sc.subject, acc.accountname
				 FROM vtiger_servicecontracts sc
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
				 LEFT JOIN vtiger_account acc ON acc.accountid = sc.sc_related_to
				 WHERE sc.servicecontractsid = ?',
				array($scId)
			);
			if ($rs && $db->num_rows($rs) > 0) {
				$subject = self::cleanDisplayLabel($db->query_result($rs, 0, 'subject'));
				if ($subject !== '') {
					return $subject;
				}
				return self::cleanDisplayLabel($db->query_result($rs, 0, 'accountname'));
			}
		} catch (Exception $e) {
			return '';
		}
		return '';
	}

	/**
	 * @param int $quoteId
	 * @return int
	 */
	protected static function loadQuoteServiceContractId($quoteId) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return 0;
		}
		try {
			$db = PearDatabase::getInstance();
			$chk = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array('mk_servicecontract_id'));
			if (!$chk || !$db->num_rows($chk)) {
				return 0;
			}
			$rs = $db->pquery(
				'SELECT mk_servicecontract_id FROM vtiger_quotes WHERE quoteid = ?',
				array($quoteId)
			);
			if ($rs && $db->num_rows($rs) > 0) {
				return (int) $db->query_result($rs, 0, 'mk_servicecontract_id');
			}
		} catch (Exception $e) {
			return 0;
		}
		return 0;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected static function cleanDisplayLabel($value) {
		$text = self::normalizePersonName($value);
		if ($text === '' || $text === '--' || $text === '—') {
			return '';
		}
		// Reject pure placeholder noise
		if (preg_match('/^\.+$/', $text)) {
			return '';
		}
		return $text;
	}

	/**
	 * @param Vtiger_Record_Model $recordModel
	 * @return array{contact_id:int,potential_id:int,account_id:int}
	 */
	protected static function resolveRecordRefs(Vtiger_Record_Model $recordModel) {
		$contactId = self::extractRawId($recordModel, array('contact_id', 'contactid'));
		$potentialId = self::extractRawId($recordModel, array('potential_id', 'potentialid'));
		$accountId = self::extractRawId($recordModel, array('account_id', 'accountid'));

		$recordId = (int) $recordModel->getId();
		$moduleName = '';
		if (method_exists($recordModel, 'getModuleName')) {
			$moduleName = (string) $recordModel->getModuleName();
		}
		if ($moduleName === '' && method_exists($recordModel, 'getModule')) {
			$module = $recordModel->getModule();
			if ($module && method_exists($module, 'getName')) {
				$moduleName = (string) $module->getName();
			}
		}
		if ($recordId > 0 && ($contactId <= 0 || $potentialId <= 0)) {
			$dbRefs = self::loadRefsFromDb($moduleName, $recordId);
			if ($contactId <= 0) {
				$contactId = (int) $dbRefs['contact_id'];
			}
			if ($potentialId <= 0) {
				$potentialId = (int) $dbRefs['potential_id'];
			}
			if ($accountId <= 0) {
				$accountId = (int) $dbRefs['account_id'];
			}
		}

		if ($contactId <= 0 && $potentialId > 0) {
			$contactId = self::resolveContactIdFromPotentialId($potentialId);
		}

		return array(
			'contact_id' => $contactId,
			'potential_id' => $potentialId,
			'account_id' => $accountId,
		);
	}

	/**
	 * Public wrapper for modules that need raw reference ids (SO→Quote duplicate).
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @param array $keys
	 * @return int
	 */
	public static function extractRawIdPublic(Vtiger_Record_Model $recordModel, array $keys) {
		return self::extractRawId($recordModel, $keys);
	}

	/**
	 * Read numeric id from raw list-row data or valueMap (ignore display HTML).
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @param array $keys
	 * @return int
	 */
	protected static function extractRawId(Vtiger_Record_Model $recordModel, array $keys) {
		$rawData = method_exists($recordModel, 'getRawData') ? $recordModel->getRawData() : null;
		if (is_array($rawData) || is_object($rawData)) {
			foreach ($keys as $key) {
				if (is_array($rawData) && isset($rawData[$key])) {
					$id = self::toPositiveInt($rawData[$key]);
					if ($id > 0) {
						return $id;
					}
				}
				if (is_object($rawData) && isset($rawData->$key)) {
					$id = self::toPositiveInt($rawData->$key);
					if ($id > 0) {
						return $id;
					}
				}
			}
		}

		foreach ($keys as $key) {
			if (method_exists($recordModel, 'getRaw')) {
				$id = self::toPositiveInt($recordModel->getRaw($key));
				if ($id > 0) {
					return $id;
				}
			}
			$id = self::toPositiveInt($recordModel->get($key));
			if ($id > 0) {
				return $id;
			}
		}
		return 0;
	}

	/**
	 * @param mixed $value
	 * @return int
	 */
	protected static function toPositiveInt($value) {
		if (is_int($value) || is_float($value)) {
			return ((int) $value) > 0 ? (int) $value : 0;
		}
		$text = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'));
		if ($text === '' || $text === '--' || $text === '—') {
			return 0;
		}
		// Pure numeric id only — reject display labels like "Hưng Tăng Chấn".
		if (preg_match('/^\d+$/', $text)) {
			return (int) $text;
		}
		return 0;
	}

	/**
	 * @param string $moduleName
	 * @param int $recordId
	 * @return array{contact_id:int,potential_id:int,account_id:int}
	 */
	protected static function loadRefsFromDb($moduleName, $recordId) {
		$empty = array('contact_id' => 0, 'potential_id' => 0, 'account_id' => 0);
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return $empty;
		}
		$tableMap = array(
			'Quotes' => array('table' => 'vtiger_quotes', 'id' => 'quoteid'),
			'SalesOrder' => array('table' => 'vtiger_salesorder', 'id' => 'salesorderid'),
		);
		if (!isset($tableMap[$moduleName])) {
			return $empty;
		}
		try {
			$db = PearDatabase::getInstance();
			$meta = $tableMap[$moduleName];
			$result = $db->pquery(
				'SELECT contactid, accountid, potentialid FROM ' . $meta['table'] . ' WHERE ' . $meta['id'] . ' = ?',
				array($recordId)
			);
			if (!$result || $db->num_rows($result) <= 0) {
				return $empty;
			}
			return array(
				'contact_id' => (int) $db->query_result($result, 0, 'contactid'),
				'account_id' => (int) $db->query_result($result, 0, 'accountid'),
				'potential_id' => (int) $db->query_result($result, 0, 'potentialid'),
			);
		} catch (Exception $e) {
			return $empty;
		}
	}

	/**
	 * Backfill missing contactid on quote / sales order when resolved from opportunity.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @param int $contactId
	 */
	protected static function persistContactIdOnRecord(Vtiger_Record_Model $recordModel, $contactId) {
		$recordId = (int) $recordModel->getId();
		$contactId = (int) $contactId;
		if ($recordId <= 0 || $contactId <= 0) {
			return;
		}
		$moduleName = $recordModel->getModuleName();
		$tableMap = array(
			'Quotes' => array('table' => 'vtiger_quotes', 'id' => 'quoteid'),
			'SalesOrder' => array('table' => 'vtiger_salesorder', 'id' => 'salesorderid'),
		);
		if (!isset($tableMap[$moduleName])) {
			return;
		}
		try {
			$db = PearDatabase::getInstance();
			$meta = $tableMap[$moduleName];
			$db->pquery(
				'UPDATE ' . $meta['table'] . ' SET contactid = ? WHERE ' . $meta['id'] . ' = ? AND (contactid IS NULL OR contactid = 0)',
				array($contactId, $recordId)
			);
			$recordModel->set('contact_id', $contactId);
		} catch (Exception $e) {
			// display-only fallback
		}
	}

	/**
	 * @param int $potentialId
	 * @return int
	 */
	public static function resolveContactIdFromPotentialId($potentialId) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return 0;
		}
		try {
			$db = PearDatabase::getInstance();
			$result = $db->pquery(
				'SELECT contact_id, potentialname, related_to FROM vtiger_potential WHERE potentialid = ?',
				array($potentialId)
			);
			if (!$result || $db->num_rows($result) <= 0) {
				return 0;
			}
			$contactId = (int) $db->query_result($result, 0, 'contact_id');
			if ($contactId > 0) {
				return $contactId;
			}

			$rel = $db->pquery(
				'SELECT contactid FROM vtiger_contpotentialrel WHERE potentialid = ? ORDER BY contactid DESC LIMIT 1',
				array($potentialId)
			);
			if ($rel && $db->num_rows($rel) > 0) {
				$contactId = (int) $db->query_result($rel, 0, 'contactid');
				if ($contactId > 0) {
					// Keep potential.contact_id in sync for next time.
					$db->pquery(
						'UPDATE vtiger_potential SET contact_id = ? WHERE potentialid = ? AND (contact_id IS NULL OR contact_id = 0)',
						array($contactId, $potentialId)
					);
					return $contactId;
				}
			}

			$potentialName = trim((string) $db->query_result($result, 0, 'potentialname'));
			$accountId = (int) $db->query_result($result, 0, 'related_to');
			$contactId = self::findContactIdByFullName($potentialName, $accountId);
			if ($contactId > 0) {
				$db->pquery(
					'UPDATE vtiger_potential SET contact_id = ? WHERE potentialid = ? AND (contact_id IS NULL OR contact_id = 0)',
					array($contactId, $potentialId)
				);
				$db->pquery(
					'INSERT IGNORE INTO vtiger_contpotentialrel (contactid, potentialid) VALUES (?, ?)',
					array($contactId, $potentialId)
				);
			}
			return $contactId;
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * @param string $fullName
	 * @param int $accountId
	 * @return int
	 */
	public static function findContactIdByFullName($fullName, $accountId = 0) {
		$fullName = self::normalizePersonName($fullName);
		if ($fullName === '') {
			return 0;
		}
		try {
			$db = PearDatabase::getInstance();
			$params = array($fullName, $fullName, $fullName, $fullName);
			$sql = 'SELECT cd.contactid
				FROM vtiger_contactdetails cd
				INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
				WHERE LOWER(TRIM(CONCAT(IFNULL(cd.firstname, \'\'), \' \', IFNULL(cd.lastname, \'\')))) = LOWER(?)
				   OR LOWER(TRIM(CONCAT(IFNULL(cd.lastname, \'\'), \' \', IFNULL(cd.firstname, \'\')))) = LOWER(?)
				   OR LOWER(TRIM(IFNULL(cd.firstname, \'\'))) = LOWER(?)
				   OR LOWER(TRIM(IFNULL(cd.lastname, \'\'))) = LOWER(?)';
			if ($accountId > 0) {
				$sql .= ' AND (cd.accountid = ? OR cd.accountid = 0 OR cd.accountid IS NULL)';
				$params[] = $accountId;
			}
			$sql .= ' ORDER BY cd.contactid DESC LIMIT 1';
			$result = $db->pquery($sql, $params);
			if ($result && $db->num_rows($result) > 0) {
				return (int) $db->query_result($result, 0, 'contactid');
			}

			// Soft match: opportunity name often equals contact full name with extra spaces/case.
			$like = '%' . str_replace(' ', '%', $fullName) . '%';
			$soft = $db->pquery(
				'SELECT cd.contactid
				 FROM vtiger_contactdetails cd
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
				 WHERE LOWER(TRIM(CONCAT(IFNULL(cd.firstname, \'\'), \' \', IFNULL(cd.lastname, \'\')))) LIKE LOWER(?)
				 ORDER BY cd.contactid DESC LIMIT 1',
				array($like)
			);
			if ($soft && $db->num_rows($soft) > 0) {
				return (int) $db->query_result($soft, 0, 'contactid');
			}
		} catch (Exception $e) {
			return 0;
		}
		return 0;
	}

	/**
	 * @param int $contactId
	 * @return string
	 */
	public static function readContactNameById($contactId) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			return '';
		}
		try {
			$db = PearDatabase::getInstance();
			$result = $db->pquery(
				'SELECT cd.firstname, cd.lastname
				 FROM vtiger_contactdetails cd
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
				 WHERE cd.contactid = ?',
				array($contactId)
			);
			if ($result && $db->num_rows($result) > 0) {
				// Vietnamese display: Họ + đệm + tên (lastname then firstname).
				$parts = array();
				foreach (array('lastname', 'firstname') as $field) {
					$part = trim((string) $db->query_result($result, 0, $field));
					if ($part !== '') {
						$parts[] = $part;
					}
				}
				return self::normalizePersonName(implode(' ', $parts));
			}
		} catch (Exception $e) {
			return '';
		}
		return '';
	}

	protected static function normalizePersonName($name) {
		$name = html_entity_decode(strip_tags((string) $name), ENT_QUOTES, 'UTF-8');
		$name = preg_replace('/\s+/u', ' ', trim($name));
		return $name;
	}
}
