<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Quotes_Save_Action extends Inventory_Save_Action {
	/**
	 * Resolve customer display name for quote subject from Opportunity.
	 * Prefer Account (related_to), then Contact.
	 */
	protected function resolveCustomerNameFromPotentialId($potentialId) {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			return '';
		}
		try {
			$potential = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
			if (!$potential) {
				return '';
			}
			$accountId = (int) $potential->get('related_to');
			if ($accountId > 0) {
				$account = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
				if ($account) {
					$name = trim((string) $account->get('accountname'));
					if ($name === '') {
						$name = trim((string) $account->getName());
					}
					if ($name !== '') {
						return $name;
					}
				}
			}
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$contactId = (int) $potential->get('contact_id');
			if ($contactId <= 0) {
				$contactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
			}
			if ($contactId > 0) {
				return trim((string) Vtiger_MkSalesCustomerName_Helper::readContactNameById($contactId));
			}
		} catch (Exception $e) {
			return '';
		}
		return '';
	}

	protected function getRecordModelFromRequest(Vtiger_Request $request) {
		$recordModel = parent::getRecordModelFromRequest($request);
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			$recordModel->set('quotestage', Quotes_QuoteBaService_Helper::resolveDraftQuoteStage());
		}
		// Always assign to the user who is saving (no UI to pick another owner).
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser) {
			$recordModel->set('assigned_user_id', $currentUser->getId());
		}
		return $recordModel;
	}

	public function saveRecord($request) {
		$potentialId = (int) $request->get('potential_id');
		$contactId = (int) $request->get('contact_id');
		$subject = trim((string) $request->get('subject'));

		if ($potentialId <= 0 && $contactId <= 0 && $subject === '') {
			throw new AppException('Vui lòng chọn Khách hàng trước khi lưu báo giá.');
		}

		// Auto-fill subject from Opportunity / Contact when empty.
		try {
			if ($subject === '' && $potentialId > 0) {
				$customerName = $this->resolveCustomerNameFromPotentialId($potentialId);
				if ($customerName !== '') {
					$request->set('subject', $customerName);
					$subject = $customerName;
				}
			}
			if ($subject === '' && $contactId > 0) {
				require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
				$customerName = trim((string) Vtiger_MkSalesCustomerName_Helper::readContactNameById($contactId));
				if ($customerName !== '') {
					$request->set('subject', $customerName);
				}
			}
		} catch (Exception $e) {
			// If anything goes wrong, fall back to default save behavior.
		}

		try {
			$contactId = (int) $request->get('contact_id');
			if ($contactId <= 0 && $potentialId > 0) {
				require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
				$potContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
				if ($potContactId > 0) {
					$request->set('contact_id', $potContactId);
					$request->set('contact_id_display', Vtiger_MkSalesCustomerName_Helper::readContactNameById($potContactId));
				}
			}
		} catch (Exception $e) {
			// keep default save behavior
		}

		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		Quotes_QuoteBaService_Helper::applySaveDefaults($request);

		return parent::saveRecord($request);
	}
}
