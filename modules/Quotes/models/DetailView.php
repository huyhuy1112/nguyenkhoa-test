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
	 * Allow loading soft-deleted quotes when opened from Sales Order reference.
	 */
	public static function getInstance($moduleName, $recordId) {
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'DetailView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$allowDeleted = false;
		$mkSoRef = isset($_REQUEST['mk_so_ref']) ? trim((string) $_REQUEST['mk_so_ref']) : '';
		if ($mkSoRef === '1') {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			if (Quotes_QuoteBaService_Helper::hasActiveSalesOrderForQuote((int) $recordId)) {
				$allowDeleted = true;
			}
		}

		if ($allowDeleted) {
			$recordModel = Quotes_Record_Model::getInstanceByIdIncludingDeleted((int) $recordId, $moduleName);
			$recordModel->set('mk_so_ref_view', 1);
			$recordModel->set('mk_quote_converted', 1);
		} else {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			if (Quotes_QuoteBaService_Helper::hasActiveSalesOrderForQuote((int) $recordId)) {
				$recordModel->set('mk_quote_converted', 1);
			}
		}

		return $instance->setModule($moduleModel)->setRecord($recordModel);
	}

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
		if (!($recordModel instanceof Quotes_Record_Model) && $recordModel->getId()) {
			try {
				$recordModel = Quotes_Record_Model::getInstanceById($recordModel->getId(), 'Quotes');
			} catch (Exception $e) {
				if ((int) $this->getRecord()->get('mk_so_ref_view') === 1) {
					$recordModel = Quotes_Record_Model::getInstanceByIdIncludingDeleted((int) $this->getRecord()->getId(), 'Quotes');
					$recordModel->set('mk_so_ref_view', 1);
					$recordModel->set('mk_quote_converted', 1);
				} else {
					throw $e;
				}
			}
		}

		$isConvertedRef = (int) $recordModel->get('mk_so_ref_view') === 1
			|| (int) $recordModel->get('mk_quote_converted') === 1;

		if ($isConvertedRef && !empty($linkModelList['DETAILVIEWBASIC'])) {
			$basicFiltered = array();
			foreach ($linkModelList['DETAILVIEWBASIC'] as $basicLink) {
				$basicLabel = is_object($basicLink) && method_exists($basicLink, 'getLabel')
					? (string) $basicLink->getLabel()
					: '';
				if ($basicLabel === 'LBL_EDIT' || stripos($basicLabel, 'EDIT') !== false) {
					continue;
				}
				$basicFiltered[] = $basicLink;
			}
			$linkModelList['DETAILVIEWBASIC'] = $basicFiltered;
		}

		// Filter Quote exports based on saved line-item types (Product vs Service).
		$quoteId = (int) $recordModel->getId();
		$itemTypeClassification = Quotes_QuoteItemTypeHelper::classifyQuoteByLineItems($quoteId);

		$invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');
		if (!$isConvertedRef && $currentUserModel->hasModuleActionPermission($invoiceModuleModel->getId(), 'CreateView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => vtranslate('LBL_GENERATE').' '.vtranslate($invoiceModuleModel->getSingularLabelKey(), 'Invoice'),
				'linkurl' => $recordModel->getCreateInvoiceUrl(),
				'linkicon' => ''
			);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		}
		
		$salesOrderModuleModel = Vtiger_Module_Model::getInstance('SalesOrder');
		if ($recordModel->hasLinkedSalesOrder()) {
			if ($currentUserModel->hasModuleActionPermission($salesOrderModuleModel->getId(), 'DetailView')) {
				$viewSalesOrderLink = Vtiger_Link_Model::getInstanceFromValues(array(
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_VIEW_SALES_ORDER',
					'linkurl' => $recordModel->getLinkedSalesOrderDetailViewUrl(),
					'linkicon' => '',
				));
				$linkModelList['DETAILVIEWBASIC'] = $this->insertQuoteSalesOrderBasicLink(
					isset($linkModelList['DETAILVIEWBASIC']) ? $linkModelList['DETAILVIEWBASIC'] : array(),
					$viewSalesOrderLink
				);
			}
		} elseif (!$isConvertedRef && $currentUserModel->hasModuleActionPermission($salesOrderModuleModel->getId(), 'CreateView')) {
			$createSalesOrderLink = Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => 'LBL_CREATE_SALES_ORDER',
				'linkurl' => $recordModel->getCreateSalesOrderUrl(),
				'linkicon' => '',
			));
			$linkModelList['DETAILVIEWBASIC'] = $this->insertQuoteSalesOrderBasicLink(
				isset($linkModelList['DETAILVIEWBASIC']) ? $linkModelList['DETAILVIEWBASIC'] : array(),
				$createSalesOrderLink
			);
		}

		$purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
		if (!$isConvertedRef && $currentUserModel->hasModuleActionPermission($purchaseOrderModuleModel->getId(), 'CreateView')) {
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
				$isCreateSalesOrderFromQuote = (
					strpos($linkUrl, 'module=SalesOrder') !== false
					&& strpos($linkUrl, 'quote_id=') !== false
				);

				if ($isCreateSalesOrderFromQuote) {
					continue;
				}

				// Business rule:
				// - If ANY Product exists in the quote (product_only or mixed) => keep only Sale export.
				// - If ALL items are Service (service_only) => keep only Project export.
				// - If there are no classified items (empty) => hide both Excel exports.
				if ($itemTypeClassification === 'product_only' || $itemTypeClassification === 'mixed') {
					if ($isProjectExport) continue;
				} elseif ($itemTypeClassification === 'service_only') {
					if ($isSaleExport) continue;
				} elseif ($itemTypeClassification === 'empty') {
					if ($isSaleExport || $isProjectExport) continue;
				}

				$filteredLinks[] = $linkModel;
			}
			$linkModelList['DETAILVIEW'] = $filteredLinks;
		}

		return $linkModelList;
	}

	/**
	 * Quotes SALES: show ProductsServices tab with clear label (match Order detail).
	 */
	public function getDetailViewRelatedLinks() {
		$links = parent::getDetailViewRelatedLinks();
		$result = array();
		foreach ($links as $link) {
			$relatedModule = isset($link['relatedModuleName']) ? $link['relatedModuleName'] : null;
			if ($relatedModule === 'Products') {
				continue;
			}
			if ($relatedModule === 'Services') {
				continue;
			}
			if ($relatedModule === 'ProductsServices') {
				$link['linklabel'] = 'Product And Service';
			}
			$result[] = $link;
		}
		return $result;
	}

	/**
	 * Insert Sales Order CTA before Edit on the Quote detail topbar.
	 */
	protected function insertQuoteSalesOrderBasicLink(array $basicLinks, Vtiger_Link_Model $salesOrderLink) {
		$reorderedBasicLinks = array();
		$inserted = false;
		foreach ($basicLinks as $basicLink) {
			$basicLabel = is_object($basicLink) && method_exists($basicLink, 'getLabel')
				? $basicLink->getLabel() : '';
			if (!$inserted && $basicLabel === 'LBL_EDIT') {
				$reorderedBasicLinks[] = $salesOrderLink;
				$inserted = true;
			}
			$reorderedBasicLinks[] = $basicLink;
		}
		if (!$inserted) {
			$reorderedBasicLinks[] = $salesOrderLink;
		}
		return $reorderedBasicLinks;
	}
		
}
