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
 * Quotes Record Model Class
 */
class Quotes_Record_Model extends Inventory_Record_Model {

	/**
	 * Load quote even when soft-deleted (converted quote referenced from Sales Order).
	 */
	public static function getInstanceByIdIncludingDeleted($recordId, $module = 'Quotes') {
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			throw new Exception(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (is_object($module) && is_a($module, 'Vtiger_Module_Model')) {
			$moduleName = $module->get('name');
			$moduleModel = $module;
		} else {
			$moduleName = $module ? (string) $module : 'Quotes';
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		}
		$focus = CRMEntity::getInstance($moduleName);
		$focus->id = $recordId;
		$focus->retrieve_entity_info($recordId, $moduleName, true);
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
		$instance = new $modelClassName();
		return $instance->setData($focus->column_fields)->set('id', $recordId)->setModuleFromInstance($moduleModel)->setEntity($focus);
	}

	public function getCreateInvoiceUrl() {
		$invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');

		return "index.php?module=".$invoiceModuleModel->getName()."&view=".$invoiceModuleModel->getEditViewName()."&quote_id=".$this->getId();
	}

	public function getCreateSalesOrderUrl() {
		$salesOrderModuleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		$quoteId = $this->getId();

		return 'index.php?module=' . $salesOrderModuleModel->getName()
			. '&view=' . $salesOrderModuleModel->getEditViewName()
			. '&quote_id=' . $quoteId
			. '&app=SALES';
	}

	/**
	 * First non-deleted Sales Order linked to this quote (1 quote : 1 SO rule).
	 */
	public function getLinkedSalesOrderId() {
		$quoteId = (int) $this->getId();
		if ($quoteId <= 0) {
			return 0;
		}

		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT vtiger_salesorder.salesorderid
			 FROM vtiger_salesorder
			 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_salesorder.salesorderid
			 WHERE vtiger_crmentity.deleted = 0 AND vtiger_salesorder.quoteid = ?
			 ORDER BY vtiger_crmentity.modifiedtime DESC
			 LIMIT 1',
			array($quoteId)
		);

		if ($db->num_rows($result)) {
			return (int) $db->query_result($result, 0, 'salesorderid');
		}

		return 0;
	}

	public function hasLinkedSalesOrder() {
		return $this->getLinkedSalesOrderId() > 0;
	}

	public function getLinkedSalesOrderDetailViewUrl() {
		$salesOrderId = $this->getLinkedSalesOrderId();
		if ($salesOrderId <= 0) {
			return '';
		}

		$salesOrderModel = Vtiger_Record_Model::getInstanceById($salesOrderId, 'SalesOrder');
		return $salesOrderModel->getDetailViewUrl() . '&app=SALES';
	}

	public function getCreatePurchaseOrderUrl() {
		$purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
		return "index.php?module=".$purchaseOrderModuleModel->getName()."&view=".$purchaseOrderModuleModel->getEditViewName()."&quote_id=".$this->getId();
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