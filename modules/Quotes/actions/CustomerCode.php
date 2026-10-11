<?php
/*+***********************************************************************************
 * Mã khách trên báo giá: KL_ / MIUTEA_ (Contacts), TUIBAO (nhượng quyền / account).
 *************************************************************************************/

class Quotes_CustomerCode_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('Quotes', 'EditView')
			&& !Users_Privileges_Model::isPermitted('Quotes', 'CreateView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$contactId = (int) $request->get('contact_id');
		$accountId = (int) $request->get('account_id');
		$scId = (int) $request->get('servicecontract_id');
		$code = '';
		$segment = '';

		if ($scId > 0) {
			$segment = 'tuibao';
		}

		if ($contactId > 0) {
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery(
				'SELECT contact_no FROM vtiger_contactdetails WHERE contactid = ? LIMIT 1',
				array($contactId)
			);
			if ($res && $adb->num_rows($res) > 0) {
				$code = trim(decode_html((string) $adb->query_result($res, 0, 'contact_no')));
			}
			if (preg_match('/^MIUTEA_/i', $code)) {
				$segment = 'miutea';
			} elseif (preg_match('/^KL_/i', $code) || preg_match('/^KL/i', $code)) {
				$segment = 'kl';
			}
		}

		if ($segment === '' && $accountId > 0) {
			$adb = isset($adb) ? $adb : PearDatabase::getInstance();
			$accRs = $adb->pquery(
				'SELECT account_no FROM vtiger_account WHERE accountid = ? LIMIT 1',
				array($accountId)
			);
			$accountNo = '';
			if ($accRs && $adb->num_rows($accRs) > 0) {
				$accountNo = trim(decode_html((string) $adb->query_result($accRs, 0, 'account_no')));
			}
			$isTuibaoCode = stripos($accountNo, 'TUIBAO') === 0;
			$isTuibaoAccount = class_exists('ProductsServices_PricingEngine_Model')
				&& ProductsServices_PricingEngine_Model::isTuibaoAccount($accountId);
			if ($isTuibaoCode || $isTuibaoAccount) {
				$segment = 'tuibao';
				if ($code === '') {
					$code = $accountNo !== '' ? $accountNo : 'TUIBAO';
				}
			}
		}

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'customer_code' => $code,
			'segment' => $segment,
		));
		$response->emit();
	}
}
