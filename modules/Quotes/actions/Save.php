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
		return $recordModel;
	}

	public function saveRecord($request) {
		$potentialId = (int) $request->get('potential_id');
		if ($potentialId <= 0) {
			throw new AppException('Vui lòng chọn Tên Cơ hội trước khi lưu báo giá.');
		}

		// Auto-fill subject (Tên khách hàng / quote title) from Opportunity customer.
		try {
			$subject = trim((string) $request->get('subject'));
			if ($subject === '') {
				$customerName = $this->resolveCustomerNameFromPotentialId($potentialId);
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
