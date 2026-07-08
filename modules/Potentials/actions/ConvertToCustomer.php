<?php
/*+***********************************************************************************
 * Potentials → Customer (Contacts) conversion helper.
 * Workflow: when consulting finishes, jump to the Customer (Contact) record.
 *************************************************************************************/

class Potentials_ConvertToCustomer_Action extends Vtiger_Action_Controller {
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

			$response->setResult(array(
				'success' => true,
				'contact_id' => $contactId
			));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
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

