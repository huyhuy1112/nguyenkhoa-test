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
		return self::resolveContactDisplayName($recordModel);
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
	 * List view uses account_id column slot for customer — show contact name only.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @return Vtiger_Record_Model
	 */
	public static function applyListCustomerColumn(Vtiger_Record_Model $recordModel) {
		$beforeContactId = self::extractRawId($recordModel, array('contact_id', 'contactid'));
		$name = self::resolveContactDisplayName($recordModel);
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
