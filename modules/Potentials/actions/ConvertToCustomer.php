<?php
/*+***********************************************************************************
 * Potentials → Customer (Contacts) conversion helper.
 * Workflow: when consulting finishes, jump to the Customer (Contact) record.
 * Tier (Vàng/Bạc/Đồng) is chosen at convert time (no longer set on Lead).
 *************************************************************************************/

class Potentials_ConvertToCustomer_Action extends Vtiger_Action_Controller {
	/** Canonical Contact tier tags. */
	private static $TIER_KEYS = array('vang', 'bac', 'dong');

	public function checkPermission(Vtiger_Request $request) {
		$recordId = $request->get('record');
		if (!$recordId) {
			throw new AppException('LBL_RECORD_NOT_FOUND');
		}
		if (!Users_Privileges_Model::isPermitted('Potentials', 'DetailView', $recordId)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$recordId = (int) $request->get('record');
		try {
			// Tier optional — staff sets Vàng/Bạc/Đồng later via Contact dropdown.
			$tier = $this->normalizeTier($request->get('tier'));

			$opp = Vtiger_Record_Model::getInstanceById($recordId, 'Potentials');
			$contactId = (int) $opp->get('contact_id');
			if ($contactId <= 0) {
				// Fallback: some installs relate via vtiger_contpotentialrel.
				$db = PearDatabase::getInstance();
				$res = $db->pquery(
					'SELECT contactid FROM vtiger_contpotentialrel WHERE potentialid=? ORDER BY contactid DESC LIMIT 1',
					array($recordId)
				);
				if ($db->num_rows($res) > 0) {
					$contactId = (int) $db->query_result($res, 0, 'contactid');
				}
			}

			if ($contactId <= 0) {
				$contactId = $this->createContactFromOpportunity($opp);
				if ($contactId > 0) {
					$this->linkOpportunityToContact($recordId, $contactId);
				}
			}

			if ($contactId <= 0) {
				$response->setResult(array(
					'success' => false,
					'message' => 'Không thể tạo Khách hàng từ Opportunity. Vui lòng kiểm tra dữ liệu (ít nhất cần Tên khách hàng).'
				));
				$response->emit();
				return;
			}

			// Sync Opp tags first; apply tier only when explicitly chosen.
			$this->syncAllowedTagsFromOpportunity($recordId, $contactId);
			if ($tier !== '') {
				$this->applyCustomerTierTag($contactId, $tier);
			}

			$response->setResult(array(
				'success' => true,
				'contact_id' => $contactId,
				'tier' => $tier,
				'list_url' => 'index.php?module=Contacts&view=List&app=SALES',
				'detail_url' => 'index.php?module=Contacts&view=Detail&record=' . $contactId . '&app=SALES',
			));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	/**
	 * @param mixed $raw
	 * @return string vang|bac|dong|''
	 */
	private function normalizeTier($raw) {
		$s = strtolower(trim(decode_html((string) $raw)));
		if ($s === '') {
			return '';
		}
		$map = array(
			'vang' => 'vang',
			'gold' => 'vang',
			'vàng' => 'vang',
			'bac' => 'bac',
			'silver' => 'bac',
			'bạc' => 'bac',
			'dong' => 'dong',
			'bronze' => 'dong',
			'đồng' => 'dong',
		);
		if (isset($map[$s])) {
			return $map[$s];
		}
		// Strip accents fallback
		$ascii = $s;
		if (function_exists('transliterator_transliterate')) {
			$ascii = strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $s));
		}
		$ascii = str_replace(array('đ', 'Đ'), array('d', 'd'), $ascii);
		if (isset($map[$ascii])) {
			return $map[$ascii];
		}
		return in_array($s, self::$TIER_KEYS, true) ? $s : '';
	}

	/**
	 * Set exclusive Hạng KH tag (vang|bac|dong) on Contact.
	 */
	private function applyCustomerTierTag($contactId, $tier) {
		global $current_user;
		$contactId = (int) $contactId;
		$tier = $this->normalizeTier($tier);
		if ($contactId <= 0 || $tier === '') {
			return;
		}
		require_once 'modules/Vtiger/models/Tag.php';
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		if (!Contacts_ContactTagCatalog::isAllowed($tier)) {
			return;
		}

		$userId = (int) $current_user->id;
		$existing = Vtiger_Tag_Model::getAllAccessible($userId, 'Contacts', $contactId);
		$existingIds = array();
		$keepIds = array();
		foreach ($existing as $tagModel) {
			$tid = (int) $tagModel->getId();
			$existingIds[] = $tid;
			$name = decode_html((string) $tagModel->getName());
			$key = Contacts_ContactTagCatalog::normalizeKey($name);
			if (in_array($key, self::$TIER_KEYS, true)) {
				continue; // drop old tiers
			}
			$keepIds[] = $tid;
		}

		$tagModel = Vtiger_Tag_Model::getInstanceByName($tier, $userId);
		if ($tagModel) {
			$tierId = (int) $tagModel->getId();
		} else {
			$newTag = new Vtiger_Tag_Model();
			$newTag->setName($tier)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$tierId = (int) $newTag->create();
		}
		if ($tierId <= 0) {
			return;
		}

		$targetIds = array_values(array_unique(array_filter(array_merge($keepIds, array($tierId)))));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($contactId, $toAdd, $userId, 'Contacts');
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($contactId, $toRemove, $userId, 'Contacts');
		}
	}

	/**
	 * Best-effort: copy BA-allowed Opp tags onto Contact (tier already applied exclusively).
	 */
	private function syncAllowedTagsFromOpportunity($potentialId, $contactId) {
		try {
			require_once 'modules/Leads/models/ConvertService.php';
			Leads_ConvertService::syncContactTagsFromPotentials((int) $contactId);
		} catch (Exception $e) {
			// ignore — convert still succeeds with tier
		}
	}

	private function createContactFromOpportunity(Vtiger_Record_Model $opp) {
		$moduleModel = Vtiger_Module_Model::getInstance('Contacts');
		if (!$moduleModel) {
			return 0;
		}
		if (!Users_Privileges_Model::isPermitted('Contacts', 'CreateView')) {
			return 0;
		}

		$oppName = trim((string) $opp->get('potentialname'));
		$accountId = (int) $opp->get('related_to');

		// Contacts requires lastname at minimum.
		$lastname = $oppName !== '' ? $oppName : 'Khách hàng';

		$contact = Vtiger_Record_Model::getCleanInstance('Contacts');
		$contact->set('mode', '');
		$contact->set('lastname', $lastname);

		$assignedUser = $opp->get('assigned_user_id');
		if ($assignedUser) {
			$contact->set('assigned_user_id', $assignedUser);
		}

		if ($accountId > 0) {
			$contact->set('account_id', $accountId);
		}

		// Best-effort copy common fields if exist on Opportunity.
		$maybe = array(
			'firstname' => array('firstname'),
			'email' => array('email', 'email1'),
			'phone' => array('phone'),
			'mobile' => array('mobile'),
			'mailingcity' => array('mailingcity'),
			'mailingstreet' => array('mailingstreet'),
			'mailingstate' => array('mailingstate'),
			'mailingzip' => array('mailingzip'),
			'mailingcountry' => array('mailingcountry'),
		);
		foreach ($maybe as $contactField => $oppFields) {
			foreach ($oppFields as $oppField) {
				$val = $opp->get($oppField);
				if ($val !== null && $val !== '') {
					$contact->set($contactField, $val);
					break;
				}
			}
		}

		$contact->save();
		return (int) $contact->getId();
	}

	private function linkOpportunityToContact($potentialId, $contactId) {
		$db = PearDatabase::getInstance();
		// Set main contact_id on opportunity.
		$db->pquery('UPDATE vtiger_potential SET contact_id=? WHERE potentialid=?', array($contactId, $potentialId));
		// Ensure relation row exists (ignore failures if table differs).
		try {
			$check = $db->pquery(
				'SELECT 1 FROM vtiger_contpotentialrel WHERE potentialid=? AND contactid=? LIMIT 1',
				array($potentialId, $contactId)
			);
			if ($db->num_rows($check) == 0) {
				$db->pquery(
					'INSERT INTO vtiger_contpotentialrel (contactid, potentialid) VALUES (?, ?)',
					array($contactId, $potentialId)
				);
			}
		} catch (Exception $e) {
			// ignore
		}
	}
}
