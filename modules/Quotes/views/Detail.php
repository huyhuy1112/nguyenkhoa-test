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

	protected function isSalesOrderQuoteRefRequest(Vtiger_Request $request) {
		return trim((string) $request->get('mk_so_ref')) === '1';
	}

	public function checkPermission(Vtiger_Request $request) {
		if ($this->isSalesOrderQuoteRefRequest($request)) {
			$recordId = (int) $request->get('record');
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			if ($recordId > 0 && Quotes_QuoteBaService_Helper::hasActiveSalesOrderForQuote($recordId)) {
				$recordEntityName = getSalesEntityType($recordId);
				if ($recordEntityName === 'Quotes') {
					return true;
				}
			}
		}
		return parent::checkPermission($request);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
		$viewer = $this->getViewer($request);
		$isSoRef = $this->isSalesOrderQuoteRefRequest($request);
		$viewer->assign('MK_QUOTE_SO_REF_VIEW', $isSoRef ? 1 : 0);
		$converted = 0;
		if ($isSoRef) {
			$converted = 1;
		} else {
			$recordId = (int) $request->get('record');
			if ($recordId > 0) {
				require_once 'modules/Quotes/helpers/QuoteBaService.php';
				$converted = Quotes_QuoteBaService_Helper::hasActiveSalesOrderForQuote($recordId) ? 1 : 0;
			}
		}
		$viewer->assign('MK_QUOTE_CONVERTED_BADGE', $converted);
	}

	/**
	 * Full detail + list inline: normalize totals (có thuế) + SKU line tax như list dropdown.
	 */
	public function showLineItemDetails(Vtiger_Request $request) {
		parent::showLineItemDetails($request);
		$viewer = $this->getViewer($request);
		$displayProducts = $viewer->getTemplateVars('RELATED_PRODUCTS');
		$recordModel = $viewer->getTemplateVars('RECORD');
		if (!is_array($displayProducts) || empty($displayProducts) || !$recordModel) {
			return;
		}
		$rawProducts = $viewer->getTemplateVars('MK_INLINE_RAW_PRODUCTS');
		if (!is_array($rawProducts) || empty($rawProducts)) {
			$rawProducts = $recordModel->getProducts();
			if (!is_array($rawProducts) || empty($rawProducts)) {
				$rawProducts = $displayProducts;
			}
		}
		$displayProducts = $this->normalizeInlineMoneyTotals($displayProducts, $rawProducts, $recordModel);
		$displayProducts = $this->enrichLineUsageUnits($displayProducts);
		$viewer->assign('RELATED_PRODUCTS', $displayProducts);
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

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MK_INLINE_RAW_PRODUCTS', unserialize(serialize($rawProducts)));
		$viewer->assign('MK_INLINE_RELATED_PRODUCTS', $rawProducts);

		$this->showLineItemDetails($request);

		$relatedProducts = $viewer->getTemplateVars('RELATED_PRODUCTS');
		if (!is_array($relatedProducts) || empty($relatedProducts)) {
			$relatedProducts = $rawProducts;
			$viewer->assign('RELATED_PRODUCTS', $relatedProducts);
		}

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
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$quoteStage = (string) $recordModel->get('quotestage');
		$viewer->assign('INLINE_QUOTE_STAGE', $quoteStage);
		$viewer->assign('INLINE_CAN_CONFIRM_ORDER', Quotes_QuoteBaService_Helper::isConfirmedQuoteStage($quoteStage));
		$viewer->assign('INLINE_PRINT_URL', 'index.php?module=Quotes&view=Print&record=' . (int) $recordId . '&app=SALES');
		$viewer->assign('INLINE_PRINT_DOWNLOAD_URL', 'index.php?module=Quotes&action=ExportPDF&record=' . (int) $recordId . '&app=SALES');
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('INLINE_ASSIGNED_USERS', $currentUser->getAccessibleUsersForModule($moduleName));

		return $viewer->view('partials/ListInlineDetail.tpl', $moduleName, true);
	}

	/**
	 * Rebuild display totals from line amounts so separators/zeros match the product table.
	 * BA quotes: unit prices include VAT — tổng theo thành tiền sau chiết khấu (không cộng VAT thêm).
	 */
	protected function normalizeInlineMoneyTotals(array $displayProducts, array $rawProducts, Vtiger_Record_Model $recordModel) {
		if (empty($rawProducts[1]['final_details']) || empty($displayProducts[1]['final_details'])) {
			return $displayProducts;
		}

		$lineGrossTotal = 0.0; // qty × đơn giá (trước CK)
		$lineNetTotal = 0.0;   // thành tiền sau chiết khấu
		$productsCount = php7_count($rawProducts);
		for ($i = 1; $i <= $productsCount; $i++) {
			if (!isset($rawProducts[$i]) || empty($rawProducts[$i]['hdnProductId' . $i])) {
				continue;
			}
			$productTotal = (float) ($rawProducts[$i]['productTotal' . $i] ?? 0);
			$discountTotal = (float) ($rawProducts[$i]['discountTotal' . $i] ?? 0);
			$afterDisc = (float) ($rawProducts[$i]['totalAfterDiscount' . $i] ?? 0);
			$netPrice = (float) ($rawProducts[$i]['netPrice' . $i] ?? 0);

			if ($productTotal <= 0) {
				$qty = (float) ($rawProducts[$i]['qty' . $i] ?? 0);
				$price = (float) ($rawProducts[$i]['listPrice' . $i] ?? 0);
				$productTotal = $qty * $price;
			}
			if ($afterDisc <= 0 && $productTotal > 0) {
				$afterDisc = max(0.0, $productTotal - $discountTotal);
			}
			// Prefer net after discount (and tax if any); for VAT-included BA tax is 0
			$lineNet = $afterDisc > 0 ? $afterDisc : ($netPrice > 0 ? $netPrice : $productTotal);
			// If netPrice still includes tax > afterDisc, prefer afterDisc
			if ($afterDisc > 0 && $netPrice > $afterDisc * 1.001) {
				$lineNet = $afterDisc;
			}

			$lineGrossTotal += $productTotal;
			$lineNetTotal += $lineNet;
		}

		$rawFinal = $rawProducts[1]['final_details'];
		$headerSubTotal = (float) ($rawFinal['hdnSubTotal'] ?? 0);
		$headerDiscountFinal = (float) ($rawFinal['discountTotal_final'] ?? 0);
		$shipping = (float) ($rawFinal['shipping_handling_charge'] ?? 0);
		$adjustment = (float) ($rawFinal['adjustment'] ?? 0);
		$headerGrand = (float) ($rawFinal['grandTotal'] ?? 0);
		$headerTotal = (float) $recordModel->get('total');
		if ($headerTotal <= 0) {
			$headerTotal = $headerGrand;
		}

		// Scale repair only when header is clearly wrong magnitude vs lines
		$scale = 1.0;
		$ref = $lineNetTotal > 0 ? $lineNetTotal : $lineGrossTotal;
		if ($ref > 0 && $headerSubTotal > 0 && $ref > ($headerSubTotal * 50)) {
			$scale = $ref / $headerSubTotal;
			$shipping *= $scale;
			$adjustment *= $scale;
			$headerTotal *= $scale;
			$headerGrand *= $scale;
			$headerDiscountFinal *= $scale;
		}

		// TỔNG TIỀN HÀNG = sum of line net (after CK), not productTotal
		$subTotal = $lineNetTotal > 0 ? $lineNetTotal : max(0.0, $headerSubTotal * $scale - $headerDiscountFinal);

		// VAT included in unit price → do not re-add tax for display
		$tax = 0.0;

		// Auto grand from lines
		$autoGrand = $subTotal + $shipping;

		// Keep explicit manual total only when it does not look like legacy mistakes:
		//  (a) productTotal sum * 1.08  (old +8% VAT on pre-discount)
		//  (b) stale header while lines already have line discounts
		$grand = $autoGrand;
		$tol = max(2.0, $subTotal * 0.001);
		$looksLikeVatOnGross = (
			$lineGrossTotal > 0
			&& abs($headerTotal - $lineGrossTotal * 1.08) <= max(2.0, $lineGrossTotal * 0.002)
		);
		$looksLikePreDiscountHeader = (
			$lineNetTotal > 0
			&& $lineGrossTotal > $lineNetTotal + $tol
			&& (
				abs($headerTotal - $lineGrossTotal) <= $tol
				|| abs($headerTotal - $lineGrossTotal * 1.08) <= max(2.0, $lineGrossTotal * 0.002)
				|| abs($headerSubTotal - $lineGrossTotal) <= $tol
			)
		);
		$manualDelta = abs($headerTotal - $autoGrand);
		$hasRealManual = (
			$headerTotal > 0
			&& $manualDelta > $tol
			&& !$looksLikeVatOnGross
			&& !$looksLikePreDiscountHeader
			// manual only if reasonably close to line net (user tweak), not 8%+ ghost
			&& $headerTotal <= ($subTotal * 1.15 + $shipping + abs($adjustment) + 1)
			&& $headerTotal >= ($subTotal * 0.5)
		);

		if ($hasRealManual) {
			$grand = $headerTotal;
		} else {
			$grand = $autoGrand;
			// Clear ghost adjustment for display consistency
			$adjustment = 0.0;
		}

		$discountDisplay = max(0.0, $lineGrossTotal - $lineNetTotal);
		if ($discountDisplay <= 0 && $headerDiscountFinal > 0) {
			$discountDisplay = $headerDiscountFinal * $scale;
		}

		$formatMoney = function ($value) {
			return Vtiger_Currency_UIType::transformDisplayValue($value, null, true);
		};

		$displayProducts[1]['final_details']['hdnSubTotal'] = $formatMoney($subTotal);
		$displayProducts[1]['final_details']['discountTotal_final'] = $formatMoney($discountDisplay);
		$displayProducts[1]['final_details']['discount_amount_final'] = $formatMoney($discountDisplay);
		$displayProducts[1]['final_details']['tax_totalamount'] = $formatMoney($tax);
		$displayProducts[1]['final_details']['shipping_handling_charge'] = $formatMoney($shipping);
		$displayProducts[1]['final_details']['adjustment'] = $formatMoney($adjustment);
		$displayProducts[1]['final_details']['grandTotal'] = $formatMoney($grand);
		$displayProducts[1]['final_details']['grandTotal_raw'] = $grand;
		$displayProducts[1]['final_details']['amount_in_words'] = Quotes_QuoteBaService_Helper::amountInWordsVi($grand);

		$displayProducts = $this->enrichInlineLineTax($displayProducts, $rawProducts, $subTotal, 0, $tax, $recordModel, $formatMoney);

		return $displayProducts;
	}

	/**
	 * Group-tax quotes do not populate per-line taxTotal in core inventory; distribute for inline list.
	 */
	protected function enrichInlineLineTax(array $displayProducts, array $rawProducts, $subTotal, $discount, $tax, Vtiger_Record_Model $recordModel, callable $formatMoney) {
		$taxableBase = max(0, (float) $subTotal - (float) $discount);
		$mkVatPercent = 8.0;
		$candidatePct = (float) $recordModel->get('mk_vat_percent');
		if ($candidatePct > 0 && $candidatePct <= 100) {
			$mkVatPercent = $candidatePct;
		}

		$productsCount = php7_count($rawProducts);
		for ($i = 1; $i <= $productsCount; $i++) {
			if (!isset($displayProducts[$i]) || empty($rawProducts[$i]['hdnProductId' . $i])) {
				continue;
			}
			$existingTax = (float) ($rawProducts[$i]['taxTotal' . $i] ?? 0);
			if ($existingTax > 0) {
				$displayProducts[$i]['taxTotal' . $i] = $formatMoney($existingTax);
				continue;
			}

			$lineTotal = (float) ($rawProducts[$i]['productTotal' . $i] ?? 0);
			if ($lineTotal <= 0) {
				$lineTotal = (float) ($rawProducts[$i]['totalAfterDiscount' . $i] ?? 0);
			}
			$lineTax = 0.0;
			if ($lineTotal > 0) {
				if ($taxableBase > 0 && $tax > 0) {
					$lineTax = round(((float) $tax) * $lineTotal / $taxableBase);
				} elseif ($mkVatPercent > 0) {
					$lineTax = round($lineTotal * $mkVatPercent / 100);
				}
			}
			$displayProducts[$i]['taxTotal' . $i] = $formatMoney($lineTax);
		}

		return $displayProducts;
	}

	/**
	 * Attach product usage unit + SKU for inline previews / exports.
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
				$products[$i]['lineSku' . $i] = '';
				continue;
			}
			$unit = '';
			$sku = '';
			$rs = $db->pquery('SELECT unit, sku FROM vtiger_productsservices WHERE productsservicesid = ?', array($productId));
			if ($rs && $db->num_rows($rs) > 0) {
				$unit = (string) $db->query_result($rs, 0, 'unit');
				$sku = (string) $db->query_result($rs, 0, 'sku');
			}
			$products[$i]['usageunit' . $i] = trim(decode_html($unit));
			$products[$i]['lineSku' . $i] = trim(decode_html($sku));
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
			'Báo giá' => 'Báo giá',
			'Accepted' => 'Báo giá',
			'Confirmed' => 'Báo giá',
			'Xác nhận' => 'Báo giá',
			'Chấp nhận' => 'Báo giá',
			'Delivered' => 'Báo giá',
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

		$dataType = $fieldModel->getFieldDataType();
		$rawValue = $recordModel->get($fieldName);
		$editValue = $rawValue;
		if ($dataType === 'date' || $dataType === 'datetime') {
			$editValue = $fieldModel->getUITypeModel()->getDisplayValue($rawValue);
		}
		$picklistValues = array();
		if ($dataType === 'picklist') {
			$picklistValues = $fieldModel->getPicklistValues();
			if ($this->isQuoteStageFieldName($fieldName) && is_array($picklistValues)) {
				$mapped = array();
				foreach ($picklistValues as $key => $label) {
					$mapped[$key] = $this->resolveQuoteStageLabel($key);
					if ($mapped[$key] === $key) {
						$mapped[$key] = $this->resolveQuoteStageLabel($label);
					}
					if ($mapped[$key] === $key) {
						$mapped[$key] = $label;
					}
				}
				$picklistValues = $mapped;
			}
		}
		$readOnlyFields = array('smcreatorid', 'created_user_id', 'createdtime', 'modifiedtime', 'modifiedby');
		return array(
			'name' => $fieldName,
			'label' => $label,
			'value' => $value,
			'raw_value' => $editValue,
			'data_type' => $dataType,
			'editable' => $fieldModel->isEditable() && !in_array($fieldName, $readOnlyFields, true),
			'picklist_values' => $picklistValues,
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
					'raw_value' => $creator['raw'],
					'data_type' => 'string',
					'editable' => false,
					'picklist_values' => array(),
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
