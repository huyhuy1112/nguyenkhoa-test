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
include_once dirname(__FILE__). '/SalesOrderPDFContentViewer.php';
include_once dirname(__FILE__). '/SalesOrderPDFHeaderViewer.php';
include_once dirname(__FILE__). '/SalesOrderPDFFooterViewer.php';
require_once 'modules/Quotes/helpers/QuoteBaService.php';
require_once 'modules/Inventory/helpers/TermsDisplayHelper.php';

class Vtiger_SalesOrderPDFController extends Vtiger_InventoryPDFController{
	function buildHeaderModelTitle() {
		$singularModuleNameKey = 'SINGLE_'.$this->moduleName;
		$translatedSingularModuleLabel = getTranslatedString($singularModuleNameKey, $this->moduleName);
		if($translatedSingularModuleLabel == $singularModuleNameKey) {
			$translatedSingularModuleLabel = getTranslatedString($this->moduleName, $this->moduleName);
		}
		return sprintf("%s: %s", $translatedSingularModuleLabel, $this->focusColumnValue('salesorder_no'));
	}

	function getContentViewer() {
		if($this->focusColumnValue('hdnTaxType') == "individual") {
			$contentViewer = new SalesOrderPDFContentViewer();
			$contentViewer->cells = array(
				'Code' => 28,
				'Name' => 52,
				'Quantity' => 18,
				'Price' => 22,
				'Discount' => 16,
				'Tax' => 14,
				'Total' => 26,
			);
		} else {
			$contentViewer = new SalesOrderPDFContentViewer();
		}
		$contentViewer->setContentModels($this->buildContentModels());
		$contentViewer->setSummaryModel($this->buildSummaryModel());
		$contentViewer->setLabelModel($this->buildContentLabelModel());
		$contentViewer->setWatermarkModel($this->buildWatermarkModel());
		return $contentViewer;
	}

	function buildHeaderModel() {
		$headerModel = parent::buildHeaderModel();
		$headerModel->set('status', $this->focusColumnValue('sostatus'));
		return $headerModel;
	}

	function getHeaderViewer() {
		$headerViewer = new SalesOrderPDFHeaderViewer();
		$headerViewer->setModel($this->buildHeaderModel());
		return $headerViewer;
	}

	function getFooterViewer() {
		$footerViewer = new SalesOrderPDFFooterViewer();
		$footerViewer->setModel($this->buildFooterModel());
		$footerViewer->setLabelModel($this->buildFooterLabelModel());
		$footerViewer->setOnLastPage();
		return $footerViewer;
	}

	function buildHeaderModelColumnLeft() {
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
		if (!empty($company['website'])) {
			$contentLines[] = getTranslatedString('Website: ', $this->moduleName) . $company['website'];
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

	function buildHeaderModelColumnCenter() {
		$subject = $this->focusColumnValue('subject');
		$customerName = $this->resolveReferenceLabel($this->focusColumnValue('account_id'), 'Accounts');
		$contactName = $this->resolveReferenceLabel($this->focusColumnValue('contact_id'), 'Contacts');
		$purchaseOrder = $this->focusColumnValue('vtiger_purchaseorder');
		$quoteName = $this->resolveReferenceLabel($this->focusColumnValue('quote_id'), 'Quotes');
		
		$subjectLabel = getTranslatedString('Subject', $this->moduleName);
        $quoteNameLabel = getTranslatedString('Quote Name', $this->moduleName);
		$customerNameLabel = getTranslatedString('Customer Name', $this->moduleName);
		$contactNameLabel = getTranslatedString('Contact Name', $this->moduleName);
		$purchaseOrderLabel = getTranslatedString('Purchase Order', $this->moduleName);

		$modelColumn1 = array(
				$subjectLabel		=>	$subject,
				$customerNameLabel	=>	$customerName,
				$contactNameLabel	=>	$contactName,
				$purchaseOrderLabel =>  $purchaseOrder,
                $quoteNameLabel => $quoteName
			);
		return $modelColumn1;
	}

	function buildHeaderModelColumnRight() {
		$issueDateLabel = getTranslatedString('Issued Date', $this->moduleName);
		$validDateLabel = getTranslatedString('Due Date', $this->moduleName);
		$billingAddressLabel = getTranslatedString('Billing Address', $this->moduleName);
		$shippingAddressLabel = getTranslatedString('Shipping Address', $this->moduleName);


		$modelColumn2 = array(
				'dates' => array(
					$issueDateLabel  => $this->formatDate(date("Y-m-d")),
					$validDateLabel => $this->formatDate($this->focusColumnValue('duedate')),
				),
				$billingAddressLabel  => $this->buildHeaderBillingAddress(),
				$shippingAddressLabel => $this->buildHeaderShippingAddress()
			);
		return $modelColumn2;
	}

	function getWatermarkContent() {
		return $this->focusColumnValue('sostatus');
	}
}
?>