<?php
/*+***********************************************************************************
 * Invoice ListView — SALES customer column from Invoice / linked SalesOrder.
 *************************************************************************************/

class Invoice_ListView_Model extends Inventory_ListView_Model {

	public function getListViewEntries($pagingModel) {
		$listViewRecordModels = parent::getListViewEntries($pagingModel);
		if (empty($listViewRecordModels)) {
			return $listViewRecordModels;
		}
		if (strtoupper((string) (isset($_REQUEST['app']) ? $_REQUEST['app'] : '')) === 'SALES') {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			foreach ($listViewRecordModels as $recordId => $recordModel) {
				$listViewRecordModels[$recordId] = Vtiger_MkSalesCustomerName_Helper::applyInvoiceListCustomerColumn($recordModel);
			}
		}
		return $listViewRecordModels;
	}
}
