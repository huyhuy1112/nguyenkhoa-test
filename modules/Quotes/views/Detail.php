<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';

class Quotes_Detail_View extends Inventory_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	protected function isSalesListInlineContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

	public function showListInlineDetail(Vtiger_Request $request) {
		if (!$this->isSalesListInlineContext($request)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		$moduleName = 'Quotes';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Inventory_Record_Model::getInstanceById($recordId, $moduleName);
		$rawProducts = $recordModel->getProducts();

		$this->showLineItemDetails($request);

		$viewer = $this->getViewer($request);
		$relatedProducts = $viewer->getTemplateVars('RELATED_PRODUCTS');
		if (!is_array($relatedProducts) || empty($relatedProducts)) {
			$relatedProducts = $rawProducts;
		}
		$relatedProducts = $this->normalizeInlineMoneyTotals($relatedProducts, $rawProducts, $recordModel);
		$relatedProducts = $this->enrichLineUsageUnits($relatedProducts);
		$viewer->assign('RELATED_PRODUCTS', $relatedProducts);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('INLINE_INFO_FIELDS', $this->getInlineInfoFields($moduleModel, $recordModel));
		$viewer->assign('INLINE_CUSTOMER_NAME', $this->resolveInlineCustomerName($recordModel));
		$viewer->assign('INLINE_NOTES', $this->resolveInlineNotes($recordModel));
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_CONFIRM_URL', 'index.php?module=Quotes&action=ConfirmSalesOrder&record=' . (int) $recordId);
		$viewer->assign('INLINE_PRINT_URL', 'index.php?module=Quotes&action=ExportPDF&record=' . (int) $recordId . '&preview=1');
		$viewer->assign('INLINE_PRINT_DOWNLOAD_URL', 'index.php?module=Quotes&action=ExportPDF&record=' . (int) $recordId);

		return $viewer->view('partials/ListInlineDetail.tpl', $moduleName, true);
	}

	/**
	 * Header totals can be out of scale vs line items (e.g. 7.35 vs 7,000,000).
	 * Rebuild display totals from line amounts so separators/zeros match the product table.
	 */
	protected function normalizeInlineMoneyTotals(array $displayProducts, array $rawProducts, Vtiger_Record_Model $recordModel) {
		if (empty($rawProducts[1]['final_details']) || empty($displayProducts[1]['final_details'])) {
			return $displayProducts;
		}

		$lineSubTotal = 0.0;
		$productsCount = php7_count($rawProducts);
		for ($i = 1; $i <= $productsCount; $i++) {
			if (!isset($rawProducts[$i])) {
				continue;
			}
			$lineSubTotal += (float) $rawProducts[$i]['productTotal' . $i];
		}

		$rawFinal = $rawProducts[1]['final_details'];
		$headerSubTotal = (float) $rawFinal['hdnSubTotal'];
		$discount = (float) $rawFinal['discountTotal_final'];
		$tax = (float) $rawFinal['tax_totalamount'];
		$shipping = (float) $rawFinal['shipping_handling_charge'];
		$adjustment = (float) $rawFinal['adjustment'];
		$grand = (float) $rawFinal['grandTotal'];

		$scale = 1.0;
		if ($lineSubTotal > 0 && $headerSubTotal > 0 && $lineSubTotal > ($headerSubTotal * 50)) {
			$scale = $lineSubTotal / $headerSubTotal;
		}

		$subTotal = $lineSubTotal > 0 ? $lineSubTotal : ($headerSubTotal * $scale);
		$discount *= $scale;
		$tax *= $scale;
		$shipping *= $scale;
		$adjustment *= $scale;
		$discountAmountFinal = ((float) $rawFinal['discount_amount_final']) * $scale;

		$mkVatAmount = (float) $recordModel->get('mk_vat_amount');
		$mkVatPercent = (float) $recordModel->get('mk_vat_percent');
		$headerPreTax = (float) $recordModel->get('pre_tax_total');
		$headerTotal = (float) $recordModel->get('total');

		if ($mkVatPercent <= 0 || $mkVatPercent > 100) {
			$mkVatPercent = 8.0;
		}

		if ($mkVatAmount > 0) {
			if ($scale > 1 && $mkVatAmount < ($subTotal * 0.001)) {
				$tax = $mkVatAmount * $scale;
			} else {
				$tax = $mkVatAmount;
			}
		}

		if ($tax <= 0 && $headerPreTax > 0 && $headerTotal > $headerPreTax) {
			$derived = ($headerTotal - $headerPreTax) * $scale;
			// Ignore absurd gaps (bad saved totals); prefer % calculation.
			if ($subTotal <= 0 || $derived <= ($subTotal * 0.5)) {
				$tax = $derived;
			}
		}

		if ($tax <= 0 && $subTotal > 0) {
			$tax = round(($subTotal - $discount) * $mkVatPercent / 100);
		}

		// Reject absurd tax relative to goods (e.g. 693M tax on 7M goods).
		if ($subTotal > 0 && $tax > ($subTotal * 0.5)) {
			$tax = round(($subTotal - $discount) * $mkVatPercent / 100);
		}

		$base = $subTotal - $discount + $shipping + $adjustment;
		if ($grand > 0) {
			$grand *= $scale;
		}
		if ($grand <= 0 || ($subTotal > 0 && $grand > ($subTotal * 2))) {
			$grand = $base + $tax;
		}

		if ($tax <= 0 && $grand > $base && ($grand - $base) <= ($subTotal * 0.5)) {
			$tax = $grand - $base;
		}
		if ($tax < 0) {
			$tax = 0;
		}
		$grand = $base + $tax;

		$formatMoney = function ($value) {
			return Vtiger_Currency_UIType::transformDisplayValue($value, null, true);
		};

		$displayProducts[1]['final_details']['hdnSubTotal'] = $formatMoney($subTotal);
		$displayProducts[1]['final_details']['discountTotal_final'] = $formatMoney($discount);
		$displayProducts[1]['final_details']['discount_amount_final'] = $formatMoney($discountAmountFinal);
		$displayProducts[1]['final_details']['tax_totalamount'] = $formatMoney($tax);
		$displayProducts[1]['final_details']['shipping_handling_charge'] = $formatMoney($shipping);
		$displayProducts[1]['final_details']['adjustment'] = $formatMoney($adjustment);
		$displayProducts[1]['final_details']['grandTotal'] = $formatMoney($grand);
		$displayProducts[1]['final_details']['amount_in_words'] = Quotes_QuoteBaService_Helper::amountInWordsVi($grand);

		return $displayProducts;
	}

	/**
	 * Attach product usage unit (đơn vị tính) for Excel preview / export labels.
	 */
	protected function enrichLineUsageUnits(array $products) {
		$db = PearDatabase::getInstance();
		$count = php7_count($products);
		for ($i = 1; $i <= $count; $i++) {
			if (!isset($products[$i])) {
				continue;
			}
			$productId = (int) ($products[$i]['hdnProductId' . $i] ?? 0);
			if ($productId <= 0) {
				$products[$i]['usageunit' . $i] = '';
				continue;
			}
			$entityType = (string) ($products[$i]['entityType' . $i] ?? 'Products');
			$unit = '';
			if (strcasecmp($entityType, 'Services') === 0) {
				$rs = $db->pquery('SELECT service_usageunit FROM vtiger_service WHERE serviceid = ?', array($productId));
				if ($rs && $db->num_rows($rs) > 0) {
					$unit = (string) $db->query_result($rs, 0, 'service_usageunit');
				}
			} else {
				$rs = $db->pquery('SELECT usageunit FROM vtiger_products WHERE productid = ?', array($productId));
				if ($rs && $db->num_rows($rs) > 0) {
					$unit = (string) $db->query_result($rs, 0, 'usageunit');
				}
			}
			$products[$i]['usageunit' . $i] = trim(decode_html($unit));
		}
		return $products;
	}

	protected function resolveInlineCustomerName(Vtiger_Record_Model $recordModel) {
		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		$name = Vtiger_MkSalesCustomerName_Helper::resolveDisplayName($recordModel);
		return $name !== '' ? $name : '—';
	}

	protected function resolveInlineNotes(Vtiger_Record_Model $recordModel) {
		$description = trim(strip_tags(decode_html((string) $recordModel->get('description'))));
		if ($description !== '') {
			return $description;
		}
		return trim(strip_tags(decode_html((string) $recordModel->get('terms_conditions'))));
	}

	protected function getQuoteStageLabelMap() {
		return array(
			'Created' => 'Nháp',
			'Nháp' => 'Nháp',
			'Draft' => 'Nháp',
			'Đã tạo' => 'Nháp',
		);
	}

	protected function resolveQuoteStageLabel($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		$map = $this->getQuoteStageLabelMap();
		if (isset($map[$value])) {
			return $map[$value];
		}
		foreach ($map as $key => $label) {
			if (strcasecmp($key, $value) === 0 || strcasecmp($label, $value) === 0) {
				return $label;
			}
		}
		return $value;
	}

	protected function getQuoteStageOptions(Vtiger_Module_Model $moduleModel, $fieldName) {
		$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
		if (!$fieldModel || $fieldModel->getFieldDataType() !== 'picklist') {
			return array();
		}
		$values = $fieldModel->getPicklistValues();
		if (!is_array($values)) {
			return array();
		}
		$options = array();
		foreach ($values as $key => $label) {
			$options[$key] = $this->resolveQuoteStageLabel($key);
			if ($options[$key] === $key) {
				$options[$key] = $this->resolveQuoteStageLabel($label);
			}
			if ($options[$key] === $key) {
				$options[$key] = $label;
			}
		}
		return $options;
	}

	protected function isQuoteStageFieldName($fieldName) {
		return $fieldName === 'quotestage';
	}

	protected function resolveInlineFieldModel(Vtiger_Module_Model $moduleModel, array $fieldNames, array $labelHints = array()) {
		foreach ($fieldNames as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldModel;
			}
		}
		if (empty($labelHints)) {
			return null;
		}
		foreach ($moduleModel->getFields() as $fieldModel) {
			if (!$fieldModel || !$fieldModel->isViewable()) {
				continue;
			}
			$label = trim((string) $fieldModel->get('label'));
			$translated = vtranslate($label, $moduleModel->getName());
			foreach ($labelHints as $hint) {
				if (strcasecmp($label, $hint) === 0 || strcasecmp($translated, $hint) === 0) {
					return $fieldModel;
				}
			}
		}
		return null;
	}

	protected function resolveInlineCreatorField(Vtiger_Record_Model $recordModel) {
		foreach (array('smcreatorid', 'created_user_id') as $fieldName) {
			$rawValue = $recordModel->get($fieldName);
			if ($rawValue !== '' && $rawValue !== null) {
				$displayValue = trim((string) $recordModel->getDisplayValue($fieldName));
				if ($displayValue !== '') {
					return array(
						'name' => $fieldName,
						'display' => $displayValue,
						'raw' => $rawValue,
					);
				}
			}
		}
		global $adb;
		$result = $adb->pquery('SELECT smcreatorid FROM vtiger_crmentity WHERE crmid = ?', array($recordModel->getId()));
		if ($result && $adb->num_rows($result)) {
			$creatorId = $adb->query_result($result, 0, 'smcreatorid');
			if (!empty($creatorId)) {
				return array(
					'name' => 'smcreatorid',
					'display' => getUserFullName($creatorId),
					'raw' => $creatorId,
				);
			}
		}
		return array(
			'name' => 'smcreatorid',
			'display' => '',
			'raw' => '',
		);
	}

	protected function buildInlineInfoFieldEntry(
		Vtiger_Module_Model $moduleModel,
		Vtiger_Record_Model $recordModel,
		Vtiger_Field_Model $fieldModel,
		$label
	) {
		$fieldName = $fieldModel->getName();
		$value = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue($fieldName)), ENT_QUOTES, 'UTF-8'));
		if ($this->isQuoteStageFieldName($fieldName)) {
			$value = $this->resolveQuoteStageLabel($value);
		}
		if ($value === '') {
			$value = '—';
		}

		return array(
			'name' => $fieldName,
			'label' => $label,
			'value' => $value,
		);
	}

	protected function getInlineInfoFields(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel) {
		$candidates = array(
			array(
				'names' => array('smcreatorid', 'created_user_id'),
				'label' => 'Người tạo',
				'label_hints' => array('Người tạo', 'Creator', 'Created By'),
				'virtual' => 'creator',
			),
			array(
				'names' => array('leadsource'),
				'label' => 'Kênh bán',
				'label_hints' => array('Kênh bán', 'Lead Source', 'Source', 'Funnel'),
			),
			array(
				'names' => array('quotestage'),
				'label' => 'Trạng thái',
				'label_hints' => array('Trạng thái', 'Quote Stage', 'Status'),
			),
			array(
				'names' => array('assigned_user_id'),
				'label' => 'Phụ trách',
				'label_hints' => array('Phụ trách', 'Assigned To'),
			),
			array(
				'names' => array('createdtime'),
				'label' => 'Ngày tạo',
				'label_hints' => array('Ngày tạo', 'Created Time'),
			),
			array(
				'names' => array('validtill'),
				'label' => 'Hiệu lực đến',
				'label_hints' => array('Hiệu lực đến', 'Valid Till', 'Valid Until'),
			),
		);

		$fields = array();
		$seenLabels = array();
		foreach ($candidates as $candidate) {
			$label = $candidate['label'];
			if (isset($seenLabels[$label])) {
				continue;
			}

			if (!empty($candidate['virtual']) && $candidate['virtual'] === 'creator') {
				$creator = $this->resolveInlineCreatorField($recordModel);
				$displayValue = $creator['display'] !== '' ? $creator['display'] : '—';
				$fields[] = array(
					'name' => $creator['name'],
					'label' => $label,
					'value' => $displayValue,
				);
				$seenLabels[$label] = true;
				continue;
			}

			$fieldModel = $this->resolveInlineFieldModel(
				$moduleModel,
				(array) $candidate['names'],
				(array) $candidate['label_hints']
			);
			if (!$fieldModel) {
				continue;
			}
			$fields[] = $this->buildInlineInfoFieldEntry($moduleModel, $recordModel, $fieldModel, $label);
			$seenLabels[$label] = true;
		}
		return $fields;
	}

}
