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

		$modules = isset($options['modules']) && is_array($options['modules'])
			? $options['modules']
			: array('Contacts', 'Potentials');
		if (!in_array('Potentials', $modules, true)) {
			$modules[] = 'Potentials';
		}
		$createAccount = !empty($options['create_account']);
		if (!in_array('Contacts', $modules, true)) {
			$modules[] = 'Contacts';
		}
		if (!in_array('Accounts', $modules, true)) {
			$modules[] = 'Accounts';
		}
		$createAccount = true;
		$orderCategory = self::resolveOrderCategory(isset($options['order_category']) ? $options['order_category'] : '');

		$assignId = isset($options['assigned_user_id']) ? (int)$options['assigned_user_id'] : (int)$current_user->id;
		$entityValues = array(
			'transferRelatedRecordsTo' => 'Potentials',
			'assignedTo' => vtws_getWebserviceEntityId(vtws_getOwnerType($assignId), $assignId),
			'leadId' => vtws_getWebserviceEntityId(self::MODULE, $leadId),
			'imageAttachmentId' => '',
			'entities' => array(),
		);

		$convertLeadFields = $recordModel->getConvertLeadFields();
		foreach (array('Accounts', 'Contacts', 'Potentials') as $module) {
			if (!vtlib_isModuleActive($module) || !in_array($module, $modules, true)) {
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
				}
				$entityValues['entities'][$module][$fieldName] = $fieldValue;
			}
			if ($module === 'Potentials') {
				$entityValues['entities'][$module]['order_category'] = $orderCategory;
			}
		}

		$result = vtws_convertlead($entityValues, $current_user);
		if (empty($result) || !is_array($result)) {
			throw new Exception('Convert lead failed. Please check required Opportunity fields.');
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
}
