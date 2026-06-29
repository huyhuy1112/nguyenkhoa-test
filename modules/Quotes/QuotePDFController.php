<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
	
include_once 'include/InventoryPDFController.php';
require_once 'modules/Inventory/helpers/TermsDisplayHelper.php';

class Vtiger_QuotePDFController extends Vtiger_InventoryPDFController{
	function buildHeaderModelTitle() {
		$singularModuleNameKey = 'SINGLE_'.$this->moduleName;
		$translatedSingularModuleLabel = getTranslatedString($singularModuleNameKey, $this->moduleName);
		if($translatedSingularModuleLabel == $singularModuleNameKey) {
			$translatedSingularModuleLabel = getTranslatedString($this->moduleName, $this->moduleName);
		}
		return sprintf("%s: %s", $translatedSingularModuleLabel, $this->focusColumnValue('quote_no'));
	}

	function getWatermarkContent() {
		return $this->focusColumnValue('quotestatus');
	}

	function buildHeaderModelColumnLeft() {
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$company = Quotes_QuoteBaService_Helper::getCompanyProfile();
		$contentLines = array();
		if (!empty($company['tax_code'])) {
			$contentLines[] = 'MST: ' . $company['tax_code'];
		}
		if (!empty($company['address'])) {
			$contentLines[] = $company['address'];
		}
		if (!empty($company['phone'])) {
			$contentLines[] = getTranslatedString('Phone: ', $this->moduleName) . $company['phone'];
		}

		$logoPath = $company['logo_path'];
		if ($logoPath === '' || !is_readable($logoPath)) {
			global $adb;
			$result = $adb->pquery('SELECT logoname FROM vtiger_organizationdetails LIMIT 1', array());
			if ($result && $adb->num_rows($result)) {
				$logoname = $adb->query_result($result, 0, 'logoname');
				if ($logoname) {
					$logoPath = 'test/logo/' . $logoname;
				}
			}
		}

		return array(
			'logo' => $logoPath,
			'summary' => $company['company_name'],
			'content' => implode("\n", $contentLines),
		);
	}

	function buildFooterModel() {
		$footerModel = new Vtiger_PDF_Model();
		$description = Inventory_TermsDisplayHelper::htmlToPlainText($this->focusColumnValue('description'));
		$terms = Inventory_TermsDisplayHelper::htmlToPlainText($this->focusColumnValue('terms_conditions'));
		$footerModel->set(Vtiger_PDF_InventoryFooterViewer::$DESCRIPTION_DATA_KEY, $description);
		$footerModel->set(Vtiger_PDF_InventoryFooterViewer::$TERMSANDCONDITION_DATA_KEY, $terms);
		return $footerModel;
	}

	function buildHeaderModelColumnRight() {
		$issueDateLabel = getTranslatedString('Issued Date', $this->moduleName);
		$validDateLabel = getTranslatedString('Valid Date', $this->moduleName);
		$billingAddressLabel = getTranslatedString('Billing Address', $this->moduleName);
		$shippingAddressLabel = getTranslatedString('Shipping Address', $this->moduleName);

		$modelColumn2 = array(
				'dates' => array(
					$issueDateLabel  => $this->formatDate(date("Y-m-d")),
					$validDateLabel => $this->formatDate($this->focusColumnValue('validtill')),
				),
				$billingAddressLabel  => $this->buildHeaderBillingAddress(),
				$shippingAddressLabel => $this->buildHeaderShippingAddress()
			);
		return $modelColumn2;
	}
}

?>
