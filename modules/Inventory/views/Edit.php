<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Inventory_Edit_View extends Vtiger_Edit_View {

	protected function isDuplicateRequest(Vtiger_Request $request) {
		$flag = $request->get('isDuplicate');
		if ($flag === true || $flag === 1 || $flag === '1') {
			return true;
		}
		if (is_string($flag)) {
			$normalized = strtolower(trim($flag));
			return $normalized === 'true' || $normalized === 'yes' || $normalized === 'on';
		}
		return false;
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$sourceRecord = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		if(empty($sourceRecord) && empty($sourceModule)) {
			$sourceRecord = $request->get('returnrecord');
			$sourceModule = $request->get('returnmodule');
		}
		$relatedProducts = null;
		$currencyInfo = null;
		$isDuplicate = $this->isDuplicateRequest($request);

		$viewer->assign('MODE', '');
		$viewer->assign('IS_DUPLICATE', false);
		if ($request->has('totalProductCount')) {
			if($record) {
				$recordModel = Vtiger_Record_Model::getInstanceById($record);
			} else {
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			}
			$relatedProducts = $recordModel->convertRequestToProducts($request);
			$taxes = $relatedProducts[1]['final_details']['taxes'];
		} else if(!empty($record)  && $isDuplicate) {
			$recordModel = Inventory_Record_Model::getInstanceById($record, $moduleName);
			$currencyInfo = $recordModel->getCurrencyInfo();
			$taxes = $recordModel->getProductTaxes();
			$relatedProducts = $recordModel->getProducts();

			//While Duplicating record, If the related record is deleted then we are removing related record info in record model
			$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
			foreach ($mandatoryFieldModels as $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$fieldName = $fieldModel->get('name');
					if (Vtiger_Util_Helper::checkRecordExistance($recordModel->get($fieldName))) {
						$recordModel->set($fieldName, '');
					}
				}
			}
			$viewer->assign('IS_DUPLICATE', true);
		} elseif (!empty($record)) {
			$recordModel = Inventory_Record_Model::getInstanceById($record, $moduleName);
			$currencyInfo = $recordModel->getCurrencyInfo();
			$taxes = $recordModel->getProductTaxes();
			$relatedProducts = $recordModel->getProducts();
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} elseif (($request->get('salesorder_id') || $request->get('quote_id') || $request->get('invoice_id')) && ($moduleName == 'PurchaseOrder')) {
			if ($request->get('salesorder_id')) {
				$referenceId = $request->get('salesorder_id');
			} elseif ($request->get('invoice_id')) {
				$referenceId = $request->get('invoice_id');
			} else{
				$referenceId = $request->get('quote_id');
			}

			$parentRecordModel = Inventory_Record_Model::getInstanceById($referenceId);
			$currencyInfo = $parentRecordModel->getCurrencyInfo();

			$relatedProducts = $parentRecordModel->getProductsForPurchaseOrder();
			$taxes = $parentRecordModel->getProductTaxes();

			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->setRecordFieldValues($parentRecordModel);
		} elseif ($request->get('salesorder_id') || $request->get('quote_id')) {
			if ($request->get('salesorder_id')) {
				$referenceId = $request->get('salesorder_id');
			} else {
				$referenceId = $request->get('quote_id');
			}

			$parentRecordModel = Inventory_Record_Model::getInstanceById($referenceId);
			$currencyInfo = $parentRecordModel->getCurrencyInfo();
			$taxes = $parentRecordModel->getProductTaxes();
			$relatedProducts = $parentRecordModel->getProducts();
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->setRecordFieldValues($parentRecordModel);
			if ($moduleName === 'SalesOrder' && $request->get('quote_id')) {
				$recordModel->set('quote_id', $referenceId);
			}
			// Create Quote from Sales Order: draft stage, drop SO-only refs
			if ($moduleName === 'Quotes' && $request->get('salesorder_id')) {
				if ($recordModel->getModule()->getField('quotestage')) {
					$recordModel->set('quotestage', 'Created');
				}
				$recordModel->set('subject', trim((string) $parentRecordModel->get('subject')));
			}
		} else {
			$taxes = Inventory_Module_Model::getAllProductTaxes();
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);

			//The creation of Inventory record from action and Related list of product/service detailview the product/service details will calculated by following code
			if ($request->get('product_id') || $sourceModule === 'Products' || $request->get('productid')) {
				if($sourceRecord) {
					$productRecordModel = Products_Record_Model::getInstanceById($sourceRecord);
				} else if($request->get('product_id')) {
					$productRecordModel = Products_Record_Model::getInstanceById($request->get('product_id'));
				} else if($request->get('productid')) {
					$productRecordModel = Products_Record_Model::getInstanceById($request->get('productid'));
				}
				$relatedProducts = $productRecordModel->getDetailsForInventoryModule($recordModel);
			} elseif ($request->get('service_id') || $sourceModule === 'Services') {
				if($sourceRecord) {
					$serviceRecordModel = Services_Record_Model::getInstanceById($sourceRecord);
				} else {
					$serviceRecordModel = Services_Record_Model::getInstanceById($request->get('service_id'));
				}
				$relatedProducts = $serviceRecordModel->getDetailsForInventoryModule($recordModel);
			} elseif ($sourceRecord && in_array($sourceModule, array('Accounts', 'Contacts', 'Potentials', 'Vendors', 'PurchaseOrder'))) {
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
				$recordModel->setParentRecordData($parentRecordModel);
				if ($sourceModule !== 'PurchaseOrder') {
					$relatedProducts = $recordModel->getParentRecordRelatedLineItems($parentRecordModel);
				}
			} elseif ($sourceRecord && in_array($sourceModule, array('HelpDesk', 'Leads'))) {
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
				$relatedProducts = $recordModel->getParentRecordRelatedLineItems($parentRecordModel);
			}

			// Prefill Quote from ServiceContracts (Tuibao — Khách hàng nhượng quyền)
			$scId = (int) $request->get('servicecontract_id');
			if ($scId > 0 && $moduleName === 'Quotes' && empty($record)) {
				$this->applyServiceContractPrefillToQuote($recordModel, $scId, $viewer);
			}
		}

		$deductTaxes = $relatedProducts && isset($relatedProducts[1]['final_details']['deductTaxes']) ? $relatedProducts[1]['final_details']['deductTaxes'] : null;
		if (!$deductTaxes) {
			$deductTaxes = Inventory_TaxRecord_Model::getDeductTaxesList();
		}

		$taxType = $relatedProducts ? $relatedProducts[1]['final_details']['taxtype'] : null;
		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		//get the inventory terms and conditions
		$inventoryRecordModel = Inventory_Record_Model::getCleanInstance($moduleName);
		$termsAndConditions = $inventoryRecordModel->getInventoryTermsAndConditions();

		foreach($requestFieldList as $fieldName=>$fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);

		$viewer->assign('VIEW_MODE', "fullForm");

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $sourceModule);
			$viewer->assign('SOURCE_RECORD', $sourceRecord);
		}
		if(!empty($record)  && $isDuplicate) {
			$viewer->assign('IS_DUPLICATE',true);
		} else {
			$viewer->assign('IS_DUPLICATE',false);
		}
		$currencies = Inventory_Module_Model::getAllCurrencies();
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$recordStructure = $recordStructureInstance->getStructure();

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD',$recordModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$taxRegions = $recordModel->getRegionsList();
		$defaultRegionInfo = $taxRegions[0];
		unset($taxRegions[0]);

		$viewer->assign('TAX_REGIONS', $taxRegions);
		$viewer->assign('DEFAULT_TAX_REGION_INFO', $defaultRegionInfo);
		$viewer->assign('INVENTORY_CHARGES', Inventory_Charges_Model::getInventoryCharges());
		$viewer->assign('RELATED_PRODUCTS', $relatedProducts);
		$viewer->assign('DEDUCTED_TAXES', $deductTaxes);
		$viewer->assign('TAXES', $taxes);
		$viewer->assign('TAX_TYPE', $taxType);
		$viewer->assign('CURRENCINFO', $currencyInfo);
		$viewer->assign('CURRENCIES', $currencies);
		$viewer->assign('TERMSANDCONDITIONS', $termsAndConditions);

		// Price channel: Tuibao franchise vs retail invoice tiers
		$priceChannel = 'retail';
		$scPrefillId = (int) $request->get('servicecontract_id');
		if ($scPrefillId > 0) {
			$priceChannel = 'tuibao';
		} else {
			$accountId = 0;
			if ($recordModel) {
				$accountId = (int) $recordModel->get('account_id');
			}
			if ($accountId > 0 && is_file('modules/ProductsServices/models/PricingEngine.php')) {
				require_once 'modules/ProductsServices/models/PricingEngine.php';
				if (ProductsServices_PricingEngine_Model::isTuibaoAccount($accountId)) {
					$priceChannel = 'tuibao';
				}
			}
		}
		$viewer->assign('MK_PRICE_CHANNEL', $priceChannel);

		$productModuleModel = Vtiger_Module_Model::getInstance('Products');
		$viewer->assign('PRODUCT_ACTIVE', $productModuleModel->isActive());

		$serviceModuleModel = Vtiger_Module_Model::getInstance('Services');
		$viewer->assign('SERVICE_ACTIVE', $serviceModuleModel->isActive());

		// added to set the return values
		if ($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}

		if ($request->get('displayMode') == 'overlay') {
			$viewer->assign('SCRIPTS', $this->getOverlayHeaderScripts($request));
			echo @$viewer->view('OverlayEditView.tpl', $moduleName);
		} else {
			@$viewer->view('EditView.tpl', 'Inventory');
		}
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$moduleName = $request->getModule();
		$modulePopUpFile = 'modules.'.$moduleName.'.resources.Popup';
		$moduleEditFile = 'modules.'.$moduleName.'.resources.Edit';
		unset($headerScriptInstances[$modulePopUpFile]);
		unset($headerScriptInstances[$moduleEditFile]);

		$jsFileNames = array(
				'modules.Inventory.resources.Edit',
				'modules.Inventory.resources.Popup',
				'modules.PriceBooks.resources.Popup',
		);
		$jsFileNames[] = $moduleEditFile;
		$jsFileNames[] = $modulePopUpFile;
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$modulePopUpFile = 'modules.'.$moduleName.'.resources.Popup';
		$moduleEditFile = 'modules.'.$moduleName.'.resources.Edit';

		$jsFileNames = array(
			'modules.Inventory.resources.Popup',
			'modules.PriceBooks.resources.Popup',
		);
		$jsFileNames[] = $moduleEditFile;
		$jsFileNames[] = $modulePopUpFile;
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}

	/**
	 * Prefill Quote create form from ServiceContracts (Tuibao franchise customer).
	 *
	 * @param Vtiger_Record_Model $recordModel
	 * @param int $serviceContractId
	 * @param Vtiger_Viewer $viewer
	 */
	protected function applyServiceContractPrefillToQuote($recordModel, $serviceContractId, $viewer) {
		$serviceContractId = (int) $serviceContractId;
		if ($serviceContractId <= 0 || !$recordModel) {
			return;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			"SELECT sc.servicecontractsid, sc.subject, sc.sc_related_to,
			        p.phone, p.email, p.business_note, p.address_line
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid
			 WHERE sc.servicecontractsid = ?
			 LIMIT 1",
			array($serviceContractId)
		);
		if (!$rs || $db->num_rows($rs) === 0) {
			return;
		}
		$name = decode_html((string) $db->query_result($rs, 0, 'subject'));
		$accountId = (int) $db->query_result($rs, 0, 'sc_related_to');
		$phone = decode_html((string) $db->query_result($rs, 0, 'phone'));
		$email = decode_html((string) $db->query_result($rs, 0, 'email'));
		$businessNote = trim(decode_html((string) $db->query_result($rs, 0, 'business_note')));
		$addressLine = trim(decode_html((string) $db->query_result($rs, 0, 'address_line')));
		// Địa chỉ kinh doanh (business_note) — fallback address_line.
		$address = $businessNote !== '' ? $businessNote : $addressLine;

		if ($name !== '') {
			$recordModel->set('subject', $name);
		}
		if ($accountId > 0 && $recordModel->getModule()->getField('account_id')) {
			$recordModel->set('account_id', $accountId);
		}
		if ($phone !== '' && $recordModel->getModule()->getField('mk_customer_phone')) {
			$recordModel->set('mk_customer_phone', preg_replace('/\D+/', '', $phone));
		}
		if ($email !== '' && $recordModel->getModule()->getField('mk_customer_email')) {
			$recordModel->set('mk_customer_email', $email);
		}
		if ($address !== '') {
			if ($recordModel->getModule()->getField('bill_street')) {
				$recordModel->set('bill_street', $address);
			}
			if ($recordModel->getModule()->getField('ship_street')) {
				$recordModel->set('ship_street', $address);
			}
		}
		if ($viewer) {
			$prefill = array(
				'id' => $serviceContractId,
				'name' => $name,
				'phone' => $phone,
				'email' => $email,
				'account_id' => $accountId,
				'address' => $address,
				'business_note' => $businessNote,
			);
			$viewer->assign('MK_SC_PREFILL', $prefill);
			$viewer->assign('MK_SC_PREFILL_JSON', Zend_Json::encode($prefill));
			$viewer->assign('MK_SERVICECONTRACT_ID', $serviceContractId);
		}
	}

}
