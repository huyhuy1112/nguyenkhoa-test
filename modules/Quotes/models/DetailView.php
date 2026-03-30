<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Quotes_DetailView_Model extends Inventory_DetailView_Model {

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams) {
		require_once 'modules/Quotes/helpers/QuoteItemTypeHelper.php';

		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		$linkModelList = parent::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();

		// Filter Quote exports based on saved line-item types (Product vs Service).
		// This is evaluated at detail-time and does not touch totals/tax math.
		$quoteId = (int) $recordModel->getId();
		$itemTypeClassification = Quotes_QuoteItemTypeHelper::classifyQuoteByLineItems($quoteId);

		$invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');
		if($currentUserModel->hasModuleActionPermission($invoiceModuleModel->getId(), 'CreateView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => vtranslate('LBL_GENERATE').' '.vtranslate($invoiceModuleModel->getSingularLabelKey(), 'Invoice'),
				'linkurl' => $recordModel->getCreateInvoiceUrl(),
				'linkicon' => ''
			);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		}
		
		$salesOrderModuleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if($currentUserModel->hasModuleActionPermission($salesOrderModuleModel->getId(), 'CreateView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => vtranslate('LBL_GENERATE').' '.vtranslate($salesOrderModuleModel->getSingularLabelKey(), 'SalesOrder'),
				'linkurl' => $recordModel->getCreateSalesOrderUrl(),
				'linkicon' => ''
			);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		}

		$purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
		if($currentUserModel->hasModuleActionPermission($purchaseOrderModuleModel->getId(), 'CreateView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => vtranslate('LBL_GENERATE').' '.vtranslate($purchaseOrderModuleModel->getSingularLabelKey(), 'PurchaseOrder'),
				'linkurl' => $recordModel->getCreatePurchaseOrderUrl(),
				'linkicon' => ''
			);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		}

		// Remove / keep the two Excel export actions depending on classification.
		if (!empty($linkModelList['DETAILVIEW'])) {
			$filteredLinks = array();
			foreach ($linkModelList['DETAILVIEW'] as $linkModel) {
				$linkUrl = '';
				if (is_object($linkModel) && method_exists($linkModel, 'get')) {
					$linkUrl = (string) $linkModel->get('linkurl');
				} elseif (is_object($linkModel) && isset($linkModel->linkurl)) {
					$linkUrl = (string) $linkModel->linkurl;
				}

				$isSaleExport = (strpos($linkUrl, 'action=ExportExcelForSale') !== false);
				$isProjectExport = (strpos($linkUrl, 'action=ExportExcelForProject') !== false);

				if ($itemTypeClassification === 'product_only') {
					if ($isProjectExport) continue;
				} elseif ($itemTypeClassification === 'service_only') {
					if ($isSaleExport) continue;
				} elseif ($itemTypeClassification === 'empty') {
					if ($isSaleExport || $isProjectExport) continue;
				}

				// mixed => keep both export actions.
				$filteredLinks[] = $linkModel;
			}

			$linkModelList['DETAILVIEW'] = $filteredLinks;
		}

		return $linkModelList;
	}
		
}
