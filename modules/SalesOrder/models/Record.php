<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Inventory Record Model Class
 */
class SalesOrder_Record_Model extends Inventory_Record_Model {

	function getCreateInvoiceUrl() {
		$invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');

		return "index.php?module=".$invoiceModuleModel->getName()."&view=".$invoiceModuleModel->getEditViewName()."&salesorder_id=".$this->getId();
	}

	function getCreatePurchaseOrderUrl() {
		$purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
		return "index.php?module=".$purchaseOrderModuleModel->getName()."&view=".$purchaseOrderModuleModel->getEditViewName()."&salesorder_id=".$this->getId();
	}

	/**
	 * Print / download PDF — same "HÓA ĐƠN ĐẶT HÀNG" layout as Excel export.
	 */
	public function getPDF() {
		require_once 'modules/SalesOrder/helpers/SaleInvoicePdf.php';
		$recordId = $this->getId();
		$moduleName = $this->getModuleName();
		$focus = CRMEntity::getInstance($moduleName);
		$focus->retrieve_entity_info($recordId, $moduleName);
		$focus->id = $recordId;
		$fileName = $moduleName . '_' . getModuleSequenceNumber($moduleName, $recordId) . '.pdf';
		SalesOrder_SaleInvoicePdf_Helper::output($focus, $moduleName, $fileName, 'D');
	}

	public function getPDFFileName() {
		require_once 'modules/SalesOrder/helpers/SaleInvoicePdf.php';
		$recordId = $this->getId();
		$moduleName = $this->getModuleName();
		$focus = CRMEntity::getInstance($moduleName);
		$focus->retrieve_entity_info($recordId, $moduleName);
		$focus->id = $recordId;
		$sequenceNo = getModuleSequenceNumber($moduleName, $recordId);
		$translatedName = vtranslate($moduleName, $moduleName);
		$filePath = 'storage/' . $translatedName . '_' . $sequenceNo . '.pdf';
		SalesOrder_SaleInvoicePdf_Helper::output($focus, $moduleName, $filePath, 'F');
		return $filePath;
	}

}