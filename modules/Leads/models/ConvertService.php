<?php
/*+***********************************************************************************
 * Modern Leads — auto Opportunity + Convert Lead (Contact / Account / Potential).
 *************************************************************************************/

vimport('~~/include/Webservices/ConvertLead.php');

class Leads_ConvertService {

	const MODULE = 'Leads';

	public static function isAutoOpportunityEnabled() {
		global $MK_LEADS_AUTO_OPPORTUNITY;
		return isset($MK_LEADS_AUTO_OPPORTUNITY) && (bool)$MK_LEADS_AUTO_OPPORTUNITY;
	}

	public static function ensurePotentialForLead($leadId, array $payload, $ownerId) {
		$leadId = (int)$leadId;
		if ($leadId <= 0 || !self::isAutoOpportunityEnabled()) {
			return null;
		}
		$existing = self::getLinkedPotentialId($leadId);
		if ($existing) {
			return $existing;
		}

		$name = trim((string)(isset($payload['name']) ? $payload['name'] : ''));
		if ($name === '') {
			$name = 'Lead #' . $leadId;
		}
		$amount = isset($payload['value']) ? (float)$payload['value'] : 0;
		$ownerId = (int)$ownerId;
		if ($ownerId <= 0) {
			global $current_user;
			$ownerId = (int)$current_user->id;
		}

		$potential = Vtiger_Record_Model::getCleanInstance('Potentials');
		$potential->set('potentialname', $name);
		$potential->set('amount', $amount);
		$potential->set('assigned_user_id', $ownerId);
		$potential->set('sales_stage', 'Prospecting');
		$potential->set('closingdate', date('Y-m-d', strtotime('+30 days')));
		$potential->set('order_category', self::resolveOrderCategory(isset($payload['order_category']) ? $payload['order_category'] : ''));
		if (!empty($payload['tags']) && is_array($payload['tags'])) {
			$potential->set('leadsource', Leads_ModernService::mapLeadsourcePublic($payload['tags']));
		}
		$potential->save();
		$potentialId = (int)$potential->getId();
		if ($potentialId > 0) {
			self::transferLeadTags($leadId, array('Potentials' => $potentialId), $ownerId);
		}
		if ($potentialId <= 0) {
			return null;
		}

		self::relateRecords($leadId, self::MODULE, $potentialId, 'Potentials');
		self::storePotentialId($leadId, $potentialId);
		return $potentialId;
	}

	public static function convertLead($leadId, array $options = array()) {
		global $current_user;
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			throw new Exception('Invalid lead id.');
		}

		$status = self::getConversionStatus($leadId);
		if (!$status['canConvert']) {
			return array(
				'already_converted' => true,
				'potentialId' => $status['potentialId'],
				'redirect' => $status['potentialUrl'],
			);
		}

		$recordModel = Vtiger_Record_Model::getInstanceById($leadId, self::MODULE);
		if (method_exists($recordModel, 'isLeadConverted') && $recordModel->isLeadConverted()) {
			self::resetConvertedFlagIfNeeded($leadId);
		}

		// BA workflow: Lead is input data only.
		// Convert Lead -> Opportunity MUST create Contact (BA confirmed).
		$modules = isset($options['modules']) && is_array($options['modules'])
			? $options['modules']
			: array('Contacts', 'Potentials');
		if (!in_array('Potentials', $modules, true)) {
			$modules[] = 'Potentials';
		}
		if (!in_array('Contacts', $modules, true)) {
			$modules[] = 'Contacts';
		}
		$createAccount = !empty($options['create_account']);
		$orderCategory = self::resolveOrderCategory(isset($options['order_category']) ? $options['order_category'] : '');

		$assignId = isset($options['assigned_user_id']) ? (int)$options['assigned_user_id'] : (int)$current_user->id;
		// Transfer related records to Contact by default (Contact is always created).
		$entityValues = array(
			'transferRelatedRecordsTo' => 'Contacts',
			'assignedTo' => vtws_getWebserviceEntityId(vtws_getOwnerType($assignId), $assignId),
			'leadId' => vtws_getWebserviceEntityId(self::MODULE, $leadId),
			'imageAttachmentId' => '',
			'entities' => array(),
		);

		$convertLeadFields = $recordModel->getConvertLeadFields();
		// Create modules explicitly requested (default: Contacts + Potentials; Accounts optional).
		foreach (array('Accounts', 'Contacts', 'Potentials') as $module) {
			if (!vtlib_isModuleActive($module) || !in_array($module, $modules, true)) {
				continue;
			}
			if ($module === 'Accounts' && !$createAccount) {
				continue;
			}
			$entityValues['entities'][$module] = array(
				'create' => true,
				'name' => $module,
				'source' => 'CRM',
			);
			if ($module === 'Accounts') {
				$company = trim((string)$recordModel->get('company'));
				$entityValues['entities'][$module]['accountname'] =
					($company === '' || $company === '-')
						? trim($recordModel->get('firstname') . ' ' . $recordModel->get('lastname'))
						: $company;
			}
			if (empty($convertLeadFields[$module])) {
				if ($module === 'Potentials') {
					$entityValues['entities'][$module]['order_category'] = $orderCategory;
				}
				continue;
			}
			foreach ($convertLeadFields[$module] as $fieldModel) {
				$fieldName = $fieldModel->getName();
				if ($module === 'Potentials' && $fieldName === 'order_category') {
					continue;
				}
				$fieldValue = self::defaultConvertValue($recordModel, $fieldModel, $module, $orderCategory);
				if ($fieldValue === null || $fieldValue === '') {
					continue;
				}
				if ($fieldModel->getFieldDataType() === 'currency') {
					if ($fieldModel->get('uitype') == 72) {
						$fieldValue = Vtiger_Currency_UIType::convertToDBFormat($fieldValue, null, true);
					} else {
						$fieldValue = Vtiger_Currency_UIType::convertToDBFormat($fieldValue);
					}
				} elseif ($fieldModel->getFieldDataType() === 'date') {
					$fieldValue = DateTimeField::convertToDBFormat($fieldValue);
				} elseif ($fieldModel->getFieldDataType() === 'owner' && $fieldValue) {
					$ids = vtws_getIdComponents($fieldValue);
					if (php7_count($ids) === 1) {
						$fieldValue = vtws_getWebserviceEntityId(vtws_getOwnerType($fieldValue), $fieldValue);
					}
				} elseif ($fieldModel->getFieldDataType() === 'reference' && $fieldValue) {
					$ids = vtws_getIdComponents($fieldValue);
					if (php7_count($ids) === 1) {
						$fieldValue = vtws_getWebserviceEntityId(getSalesEntityType($fieldValue), $fieldValue);
					}
				}
				$entityValues['entities'][$module][$fieldName] = $fieldValue;
			}
			if ($module === 'Potentials') {
				$entityValues['entities'][$module]['order_category'] = $orderCategory;
			}
		}

		// Ensure Potentials has sane defaults for mandatory fields even if mapping is incomplete.
		if (!empty($entityValues['entities']['Potentials']) && is_array($entityValues['entities']['Potentials'])) {
			self::fillPotentialDefaults($entityValues['entities']['Potentials'], $recordModel, $orderCategory, $assignId);
		}

		try {
			$result = vtws_convertlead($entityValues, $current_user);
		} catch (Exception $e) {
			error_log('[MK_LEAD_CONVERT] vtws_convertlead exception: ' . $e->getMessage());
			error_log('[MK_LEAD_CONVERT] entities keys: ' . implode(',', array_keys($entityValues['entities'])));
			throw new Exception($e->getMessage());
		}
		if (empty($result) || !is_array($result)) {
			$missing = self::validatePotentialMandatory(isset($entityValues['entities']['Potentials']) ? $entityValues['entities']['Potentials'] : array());
			if (!empty($missing)) {
				throw new Exception('Thiếu field bắt buộc của Cơ hội: ' . implode(', ', $missing));
			}
			throw new Exception('Convert lead failed (empty result). Kiểm tra field bắt buộc Contact/Opportunity.');
		}
		if (empty($result['Contacts'])) {
			throw new Exception('Convert lead failed: Contact không được tạo.');
		}
		if (empty($result['Potentials'])) {
			$missing = self::validatePotentialMandatory(isset($entityValues['entities']['Potentials']) ? $entityValues['entities']['Potentials'] : array());
			if (!empty($missing)) {
				throw new Exception('Thiếu field bắt buộc của Cơ hội: ' . implode(', ', $missing));
			}
			throw new Exception('Convert lead failed: Opportunity không được tạo.');
		}
		$potentialId = null;
		$contactId = null;
		$accountId = null;
		if (!empty($result['Potentials'])) {
			$parts = vtws_getIdComponents($result['Potentials']);
			$potentialId = (int)$parts[1];
		}
		if (!empty($result['Contacts'])) {
			$parts = vtws_getIdComponents($result['Contacts']);
			$contactId = (int)$parts[1];
		}
		if (!empty($result['Accounts'])) {
			$parts = vtws_getIdComponents($result['Accounts']);
			$accountId = (int)$parts[1];
		}
		if ($potentialId) {
			self::storePotentialId($leadId, $potentialId);
		}
		if ($contactId) {
			self::relateRecords($leadId, self::MODULE, $contactId, 'Contacts');
			self::storeContactId($leadId, $contactId);
			self::syncLeadSegmentTagToContact($leadId, $contactId, (int)$current_user->id);
		}
		self::transferLeadTags($leadId, array(
			'Potentials' => $potentialId,
			'Contacts' => $contactId,
			'Accounts' => $accountId,
		), (int)$current_user->id);

		return array(
			'success' => true,
			'potentialId' => $potentialId,
			'contactId' => $contactId,
			'accountId' => $accountId,
			'redirect' => self::potentialDetailUrl($potentialId),
		);
	}

	protected static function decodeLeadField($value) {
		if ($value === null || $value === '') {
			return '';
		}
		$text = is_string($value) ? $value : (string)$value;
		$decoded = decode_html($text);
		if ($decoded !== $text && strpos($decoded, '&') !== false) {
			$decoded = decode_html($decoded);
		}
		return $decoded;
	}

	protected static function resolveOrderCategory($value) {
		$value = trim((string)$value);
		if (in_array($value, array('Internal', 'Project'), true)) {
			return $value;
		}
		return 'Internal';
	}

	protected static function defaultOrderCategory() {
		return 'Internal';
	}

	protected static function defaultConvertValue(Vtiger_Record_Model $lead, Vtiger_Field_Model $fieldModel, $targetModule, $orderCategory = null) {
		$fieldName = $fieldModel->getName();
		if ($targetModule === 'Potentials') {
			if ($fieldName === 'potentialname') {
				return self::decodeLeadField(trim($lead->get('firstname') . ' ' . $lead->get('lastname')));
			}
			if ($fieldName === 'order_category') {
				return self::resolveOrderCategory($orderCategory);
			}
			if ($fieldName === 'amount') {
				$adb = PearDatabase::getInstance();
				$res = $adb->pquery("SELECT lead_value FROM bace_lead_profile WHERE leadid = ?", array($lead->getId()));
				if ($res && $adb->num_rows($res) > 0) {
					return (float)$adb->query_result($res, 0, 'lead_value');
				}
			}
			if ($fieldName === 'closingdate') {
				return date('Y-m-d', strtotime('+30 days'));
			}
			if ($fieldName === 'sales_stage') {
				return 'Prospecting';
			}
			if ($fieldName === 'leadsource') {
				return $lead->get('leadsource');
			}
		}
		if ($targetModule === 'Contacts') {
			if ($fieldName === 'firstname') {
				return self::decodeLeadField($lead->get('firstname'));
			}
			if ($fieldName === 'lastname') {
				return self::decodeLeadField($lead->get('lastname'));
			}
			if ($fieldName === 'email') {
				return $lead->get('email');
			}
			if ($fieldName === 'phone' || $fieldName === 'mobile') {
				return $lead->get('phone');
			}
		}
		if ($targetModule === 'Accounts' && $fieldName === 'accountname') {
			$company = trim((string)$lead->get('company'));
			return ($company === '' || $company === '-') ? trim($lead->get('firstname') . ' ' . $lead->get('lastname')) : $company;
		}
		return $lead->get($fieldName);
	}

	public static function getLinkedLeadIdByPotential($potentialId) {
		$potentialId = (int)$potentialId;
		if ($potentialId <= 0) {
			return null;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT leadid FROM bace_lead_profile WHERE potential_id = ? LIMIT 1",
			array($potentialId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'leadid');
			if ($leadId > 0) {
				return $leadId;
			}
		}
		$res = $adb->pquery(
			"SELECT crmid FROM vtiger_crmentityrel
			 WHERE relcrmid = ? AND relmodule = ? AND module = ?
			 ORDER BY crmid DESC LIMIT 1",
			array($potentialId, 'Potentials', 'Leads')
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'crmid');
			if ($leadId > 0) {
				return $leadId;
			}
		}
		$res = $adb->pquery(
			"SELECT relcrmid FROM vtiger_crmentityrel
			 WHERE crmid = ? AND module = ? AND relmodule = ?
			 ORDER BY relcrmid DESC LIMIT 1",
			array($potentialId, 'Potentials', 'Leads')
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'relcrmid');
			return $leadId > 0 ? $leadId : null;
		}
		return null;
	}

	public static function getLinkedPotentialId($leadId, $verifyExists = true) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT potential_id FROM bace_lead_profile WHERE leadid = ?", array((int)$leadId));
		if ($res && $adb->num_rows($res) > 0) {
			$potentialId = (int)$adb->query_result($res, 0, 'potential_id');
			if ($potentialId > 0) {
				if ($verifyExists && !self::potentialRecordExists($potentialId)) {
					self::clearPotentialId($leadId);
					self::resetConvertedFlagIfNeeded($leadId);
					return null;
				}
				return $potentialId;
			}
		}
		return null;
	}

	public static function getConversionStatus($leadId) {
		$leadId = (int)$leadId;
		$potentialId = self::getLinkedPotentialId($leadId, true);
		$canConvert = ($potentialId === null);
		return array(
			'converted' => !$canConvert,
			'canConvert' => $canConvert,
			'potentialId' => $potentialId,
			'potentialUrl' => self::potentialDetailUrl($potentialId),
		);
	}

	public static function potentialRecordExists($potentialId) {
		$potentialId = (int)$potentialId;
		if ($potentialId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT 1 FROM vtiger_crmentity WHERE crmid = ? AND setype = 'Potentials' AND deleted = 0",
			array($potentialId)
		);
		return $res && $adb->num_rows($res) > 0;
	}

	public static function clearPotentialId($leadId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE bace_lead_profile SET potential_id = NULL WHERE leadid = ?",
			array((int)$leadId)
		);
	}

	public static function resetConvertedFlagIfNeeded($leadId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE vtiger_leaddetails SET converted = 0 WHERE leadid = ? AND converted = 1",
			array((int)$leadId)
		);
	}

	public static function storePotentialId($leadId, $potentialId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE bace_lead_profile SET potential_id = ? WHERE leadid = ?",
			array((int)$potentialId, (int)$leadId)
		);
	}

	public static function storeContactId($leadId, $contactId) {
		$leadId = (int)$leadId;
		$contactId = (int)$contactId;
		if ($leadId <= 0 || $contactId <= 0) {
			return;
		}
		$adb = PearDatabase::getInstance();
		require_once 'modules/Leads/models/ModernService.php';
		Leads_ModernService::installSchema($adb);
		$adb->pquery(
			"UPDATE bace_lead_profile SET contact_id = ? WHERE leadid = ?",
			array($contactId, $leadId)
		);
	}

	public static function getLinkedPotentialIdsByContact($contactId) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$ids = array();

		$res = $adb->pquery(
			"SELECT p.potentialid FROM vtiger_potential p
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			 WHERE p.contact_id = ?",
			array($contactId)
		);
		if ($res) {
			$count = $adb->num_rows($res);
			for ($i = 0; $i < $count; $i++) {
				$ids[] = (int)$adb->query_result($res, $i, 'potentialid');
			}
		}

		$res = $adb->pquery(
			"SELECT potentialid FROM vtiger_contpotentialrel WHERE contactid = ?",
			array($contactId)
		);
		if ($res) {
			$count = $adb->num_rows($res);
			for ($i = 0; $i < $count; $i++) {
				$ids[] = (int)$adb->query_result($res, $i, 'potentialid');
			}
		}

		return array_values(array_unique(array_filter($ids)));
	}

	public static function potentialDetailUrl($potentialId) {
		if (!$potentialId) {
			return '';
		}
		return 'index.php?module=Potentials&view=Detail&record=' . (int)$potentialId . '&app=SALES';
	}

	protected static function relateRecords($crmid, $module, $relcrmid, $relmodule) {
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			"SELECT 1 FROM vtiger_crmentityrel WHERE crmid = ? AND module = ? AND relcrmid = ? AND relmodule = ?",
			array((int)$crmid, $module, (int)$relcrmid, $relmodule)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}
		$adb->pquery(
			"INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?,?,?,?)",
			array((int)$crmid, $module, (int)$relcrmid, $relmodule)
		);
	}

	protected static function fillPotentialDefaults(array &$potentialEntity, Vtiger_Record_Model $lead, $orderCategory, $assignId) {
		// Hard defaults for common mandatory fields
		if (empty($potentialEntity['potentialname'])) {
			$name = trim((string)self::decodeLeadField(trim($lead->get('firstname') . ' ' . $lead->get('lastname'))));
			if ($name === '') {
				$name = 'Lead #' . (int)$lead->getId();
			}
			$potentialEntity['potentialname'] = $name;
		}
		if (empty($potentialEntity['closingdate'])) {
			$potentialEntity['closingdate'] = date('Y-m-d', strtotime('+30 days'));
		}
		if (empty($potentialEntity['sales_stage'])) {
			$potentialEntity['sales_stage'] = 'Prospecting';
		}
		if (empty($potentialEntity['order_category'])) {
			$potentialEntity['order_category'] = self::resolveOrderCategory($orderCategory);
		}

		// Best-effort: if Opportunity requires Organization (related_to) but we don't want to create a new Account,
		// try to match an existing Account by Lead company name.
		if (empty($potentialEntity['related_to'])) {
			$company = trim((string)$lead->get('company'));
			if ($company !== '' && $company !== '-') {
				$accountId = self::lookupAccountIdByName($company);
				if ($accountId > 0) {
					$potentialEntity['related_to'] = vtws_getWebserviceEntityId('Accounts', $accountId);
				}
			}
		}

		// Fill any other mandatory fields with safe defaults if possible.
		try {
			$potentialModule = Vtiger_Module_Model::getInstance('Potentials');
			if ($potentialModule) {
				$fields = $potentialModule->getFields();
				foreach ($fields as $fname => $fmodel) {
					if (!$fmodel || !$fmodel->isMandatory()) {
						continue;
					}
					if (isset($potentialEntity[$fname]) && $potentialEntity[$fname] !== '' && $potentialEntity[$fname] !== null) {
						continue;
					}
					$type = $fmodel->getFieldDataType();
					if ($type === 'date') {
						$potentialEntity[$fname] = date('Y-m-d', strtotime('+30 days'));
						continue;
					}
					if ($type === 'owner') {
						$potentialEntity[$fname] = (int)$assignId;
						continue;
					}
					if ($type === 'picklist' || $type === 'multipicklist') {
						$vals = $fmodel->getPicklistValues();
						if (!empty($vals) && is_array($vals)) {
							foreach ($vals as $pv) {
								if ($pv !== '' && $pv !== null) {
									$potentialEntity[$fname] = $pv;
									break;
								}
							}
						}
						continue;
					}
					if ($type === 'currency' || $type === 'integer' || $type === 'double') {
						$potentialEntity[$fname] = 0;
						continue;
					}
					if ($type === 'string' || $type === 'text') {
						$potentialEntity[$fname] = $potentialEntity['potentialname'];
						continue;
					}
				}
			}
		} catch (Exception $e) {
			// ignore
		}
	}

	protected static function lookupAccountIdByName($accountName) {
		$accountName = trim((string)$accountName);
		if ($accountName === '') {
			return 0;
		}
		$db = PearDatabase::getInstance();
		$res = $db->pquery(
			'SELECT vtiger_account.accountid
			 FROM vtiger_account
			 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid
			 WHERE vtiger_crmentity.deleted = 0 AND vtiger_account.accountname = ?
			 LIMIT 1',
			array($accountName)
		);
		if ($res && $db->num_rows($res) > 0) {
			return (int)$db->query_result($res, 0, 'accountid');
		}
		return 0;
	}

	protected static function validatePotentialMandatory(array $potentialEntity) {
		$labels = array();
		try {
			$potentialModule = Vtiger_Module_Model::getInstance('Potentials');
			if (!$potentialModule) {
				return $labels;
			}
			$fields = $potentialModule->getFields();
			foreach ($fields as $fname => $fmodel) {
				if (!$fmodel || !$fmodel->isMandatory()) {
					continue;
				}
				if (!isset($potentialEntity[$fname]) || $potentialEntity[$fname] === null || $potentialEntity[$fname] === '') {
					$labels[] = vtranslate($fmodel->get('label'), 'Potentials');
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		return $labels;
	}

	/**
	 * Copy freetags from Lead to converted entities (Opportunity, Contact, Account).
	 * Opportunity & Contact receive BA-filtered tags only (Excel categories).
	 */
	public static function transferLeadTags($leadId, array $targetsByModule, $userId = null) {
		$leadId = (int)$leadId;
		if ($leadId <= 0) {
			return;
		}
		global $current_user;
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		$tagModels = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $leadId);
		if (empty($tagModels)) {
			return;
		}
		require_once 'modules/Potentials/helpers/OppTagCatalog.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		$allTagIds = array_keys($tagModels);
		$oppTagIds = Potentials_OppTagCatalog::filterTagModelIds($tagModels);
		$contactTagIds = Contacts_ContactTagCatalog::filterTagModelIds($tagModels);

		foreach ($targetsByModule as $module => $recordId) {
			$recordId = (int)$recordId;
			if ($recordId <= 0 || empty($module)) {
				continue;
			}
			if ($module === 'Potentials') {
				$tagIds = $oppTagIds;
			} elseif ($module === 'Contacts') {
				$tagIds = $contactTagIds;
			} else {
				$tagIds = $allTagIds;
			}
			if (empty($tagIds)) {
				continue;
			}
			Vtiger_Tag_Model::saveForRecord($recordId, $tagIds, $userId, $module);
		}
	}

	/**
	 * Backfill BA-filtered tags from linked Lead onto Opportunity (detail view / repair).
	 */
	public static function syncFilteredTagsToPotential($leadId, $potentialId, $userId = null) {
		$leadId = (int)$leadId;
		$potentialId = (int)$potentialId;
		if ($leadId <= 0 || $potentialId <= 0) {
			return 0;
		}
		global $current_user;
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Potentials/helpers/OppTagCatalog.php';

		$leadTags = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $leadId);
		if (empty($leadTags)) {
			return 0;
		}
		$filteredIds = Potentials_OppTagCatalog::filterTagModelIds($leadTags);
		if (empty($filteredIds)) {
			return 0;
		}

		$oppTags = Vtiger_Tag_Model::getAllAccessible($userId, 'Potentials', $potentialId);
		$existingOppIds = array_map('intval', array_keys($oppTags));
		$toAdd = array_values(array_diff($filteredIds, $existingOppIds));
		if (empty($toAdd)) {
			return 0;
		}
		Vtiger_Tag_Model::saveForRecord($potentialId, $toAdd, $userId, 'Potentials');
		return count($toAdd);
	}

	/**
	 * When viewing Opportunity detail, ensure tags from linked Lead are present.
	 */
	public static function ensurePotentialTagsFromLead($potentialId, $userId = null) {
		$potentialId = (int)$potentialId;
		if ($potentialId <= 0) {
			return 0;
		}
		$leadId = self::getLinkedLeadIdByPotential($potentialId);
		if (!$leadId) {
			return 0;
		}
		return self::syncFilteredTagsToPotential($leadId, $potentialId, $userId);
	}

	public static function getLinkedLeadIdByContact($contactId) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return null;
		}
		$adb = PearDatabase::getInstance();

		$res = $adb->pquery(
			"SELECT leadid FROM bace_lead_profile WHERE contact_id = ? AND leadid > 0 ORDER BY leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'leadid');
			if ($leadId > 0) {
				return $leadId;
			}
		}

		$res = $adb->pquery(
			"SELECT crmid FROM vtiger_crmentityrel
			 WHERE relcrmid = ? AND relmodule = ? AND module = ?
			 ORDER BY crmid DESC LIMIT 1",
			array($contactId, 'Contacts', 'Leads')
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'crmid');
			if ($leadId > 0) {
				return $leadId;
			}
		}
		$res = $adb->pquery(
			"SELECT relcrmid FROM vtiger_crmentityrel
			 WHERE crmid = ? AND module = ? AND relmodule = ?
			 ORDER BY relcrmid DESC LIMIT 1",
			array($contactId, 'Contacts', 'Leads')
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'relcrmid');
			if ($leadId > 0) {
				return $leadId;
			}
		}

		$res = $adb->pquery(
			"SELECT lp.leadid FROM bace_lead_profile lp
			 INNER JOIN vtiger_potential p ON p.potentialid = lp.potential_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			 WHERE p.contact_id = ? AND lp.leadid > 0
			 ORDER BY lp.leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'leadid');
			if ($leadId > 0) {
				return $leadId;
			}
		}

		$res = $adb->pquery(
			"SELECT lp.leadid FROM bace_lead_profile lp
			 INNER JOIN vtiger_contpotentialrel cpr ON cpr.potentialid = lp.potential_id
			 WHERE cpr.contactid = ? AND lp.leadid > 0
			 ORDER BY lp.leadid DESC LIMIT 1",
			array($contactId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$leadId = (int)$adb->query_result($res, 0, 'leadid');
			return $leadId > 0 ? $leadId : null;
		}

		return null;
	}

	public static function syncContactTagsFromPotentials($contactId, $userId = null) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return 0;
		}
		global $current_user;
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';

		$potentialIds = self::getLinkedPotentialIdsByContact($contactId);
		if (empty($potentialIds)) {
			return 0;
		}

		$contactTags = Vtiger_Tag_Model::getAllAccessible($userId, 'Contacts', $contactId);
		$existingIds = array_map('intval', array_keys($contactTags));
		$toAdd = array();

		foreach ($potentialIds as $potentialId) {
			$oppTags = Vtiger_Tag_Model::getAllAccessible($userId, 'Potentials', (int)$potentialId);
			if (empty($oppTags)) {
				continue;
			}
			$allowedIds = Contacts_ContactTagCatalog::filterTagModelIds($oppTags);
			foreach ($allowedIds as $tagId) {
				if (!in_array((int)$tagId, $existingIds, true) && !in_array((int)$tagId, $toAdd, true)) {
					$toAdd[] = (int)$tagId;
				}
			}
		}

		if (empty($toAdd)) {
			return 0;
		}
		Vtiger_Tag_Model::saveForRecord($contactId, $toAdd, $userId, 'Contacts');
		return count($toAdd);
	}

	/**
	 * Backfill BA-filtered Contact tags from linked Lead.
	 */
	public static function syncFilteredTagsToContact($leadId, $contactId, $userId = null) {
		$leadId = (int)$leadId;
		$contactId = (int)$contactId;
		if ($leadId <= 0 || $contactId <= 0) {
			return 0;
		}
		global $current_user;
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';

		$leadTags = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $leadId);
		if (empty($leadTags)) {
			return 0;
		}
		$filteredIds = Contacts_ContactTagCatalog::filterTagModelIds($leadTags);
		if (empty($filteredIds)) {
			return 0;
		}

		$contactTags = Vtiger_Tag_Model::getAllAccessible($userId, 'Contacts', $contactId);
		$existingIds = array_map('intval', array_keys($contactTags));
		$toAdd = array_values(array_diff($filteredIds, $existingIds));
		if (empty($toAdd)) {
			return 0;
		}
		Vtiger_Tag_Model::saveForRecord($contactId, $toAdd, $userId, 'Contacts');
		return count($toAdd);
	}

	public static function ensureContactTagsFromLead($contactId, $userId = null) {
		$contactId = (int)$contactId;
		if ($contactId <= 0) {
			return 0;
		}
		require_once 'modules/Leads/models/ModernService.php';
		Leads_ModernService::installSchema(PearDatabase::getInstance());

		$added = 0;
		$leadId = self::getLinkedLeadIdByContact($contactId);
		if ($leadId) {
			self::storeContactId($leadId, $contactId);
			$added += self::syncFilteredTagsToContact($leadId, $contactId, $userId);
			$added += self::syncLeadSegmentTagToContact($leadId, $contactId, $userId);
		}
		$added += self::syncContactTagsFromPotentials($contactId, $userId);
		return $added;
	}

	/**
	 * Copy Lead Trạng thái khách (segment) onto Contact as a Loại khách tag.
	 */
	public static function syncLeadSegmentTagToContact($leadId, $contactId, $userId = null) {
		$leadId = (int)$leadId;
		$contactId = (int)$contactId;
		if ($leadId <= 0 || $contactId <= 0) {
			return 0;
		}
		global $current_user;
		if ($userId === null || (int)$userId <= 0) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		require_once 'modules/Leads/models/ModernService.php';
		Leads_ModernService::installSchema($adb);
		$res = $adb->pquery(
			"SELECT segment FROM bace_lead_profile WHERE leadid = ? LIMIT 1",
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return 0;
		}
		$segment = strtolower(trim((string)$adb->query_result($res, 0, 'segment')));
		if (!in_array($segment, array('co_quan', 'chuan_bi_mo', 'gia_dinh'), true)) {
			return 0;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		if (!Contacts_ContactTagCatalog::isAllowed($segment)) {
			return 0;
		}

		$tagModel = Vtiger_Tag_Model::getInstanceByName($segment, $userId);
		if (!$tagModel) {
			$tagModel = new Vtiger_Tag_Model();
			$tagModel->setName($segment)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$tagModel->create();
		}
		$tagId = (int)$tagModel->getId();
		if ($tagId <= 0) {
			return 0;
		}
		$contactTags = Vtiger_Tag_Model::getAllAccessible($userId, 'Contacts', $contactId);
		$existingIds = array_map('intval', array_keys($contactTags));
		if (in_array($tagId, $existingIds, true)) {
			return 0;
		}
		Vtiger_Tag_Model::saveForRecord($contactId, array($tagId), $userId, 'Contacts');
		return 1;
	}

}
