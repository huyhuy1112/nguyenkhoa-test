<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class SalesOrder_SaveAjax_Action extends Inventory_SaveAjax_Action {

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$recordModel = parent::getRecordModelFromRequest($request);
		$this->syncReceivedBalance($recordModel, $request);
		return $recordModel;
	}

	/**
	 * Keep balance = total - received when customer paid amount is updated inline.
	 */
	protected function syncReceivedBalance(Vtiger_Record_Model $recordModel, Vtiger_Request $request) {
		$paidField = '';
		foreach (array('received', 'paid_amount', 'amount_paid', 'paid', 'mk_customer_paid') as $candidate) {
			if ($request->has($candidate)) {
				$paidField = $candidate;
				break;
			}
		}
		if ($paidField === '') {
			return;
		}

		$paidRaw = $request->get($paidField);
		if (class_exists('CurrencyField')) {
			$paidAmount = (float) CurrencyField::convertToDBFormat($paidRaw, null, true);
		} else {
			$paidAmount = (float) preg_replace('/[^\d.-]/', '', str_replace(',', '.', (string) $paidRaw));
		}
		if ($paidAmount < 0) {
			$paidAmount = 0;
		}
		$recordModel->set($paidField, $paidAmount);

		$total = (float) $recordModel->get('total');
		if ($total <= 0 && $recordModel->get('hdnGrandTotal') !== null && $recordModel->get('hdnGrandTotal') !== '') {
			$total = (float) $recordModel->get('hdnGrandTotal');
		}
		$balance = $total - $paidAmount;
		if ($balance < 0) {
			$balance = 0;
		}
		$balanceField = $recordModel->getModule()->getField('balance');
		if ($balanceField) {
			$recordModel->set('balance', $balance);
		}
	}
}
