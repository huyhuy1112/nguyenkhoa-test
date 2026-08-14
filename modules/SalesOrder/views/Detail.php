<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SalesOrder_Detail_View extends Inventory_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	protected function isSalesListInlineContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isToolsOrdersContext($request)) {
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'TOOLS');
			$viewer->assign('MK_SO_TOOLS_DETAIL', true);
		}
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isToolsOrdersContext($request)) {
			echo $this->showModuleDetailView($request);
			return;
		}
		parent::process($request);
	}

	public function showListInlineDetail(Vtiger_Request $request) {
		if (!$this->isSalesListInlineContext($request)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		require_once 'modules/SalesOrder/helpers/ListNoteField.php';
		SalesOrder_ListNoteField_Helper::ensure();

		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		$moduleName = 'SalesOrder';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Inventory_Record_Model::getInstanceById($recordId, $moduleName);
		$this->repairMissingAccountFromQuote($recordModel);
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
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('INLINE_ASSIGNED_USERS', $currentUser->getAccessibleUsersForModule($moduleName));
		$viewer->assign('INLINE_BRANCH_LABEL', $this->resolveInlineBranchLabel($recordModel, $moduleModel));
		$paidField = $this->resolveInlinePaidFieldName($moduleModel);
		if ($paidField === '') {
			$paidField = 'received';
		}
		$paidRaw = (float) $recordModel->get($paidField);
		if ($paidRaw < 0) {
			$paidRaw = 0;
		}
		$grandRaw = 0.0;
		if (!empty($relatedProducts[1]['final_details']['grandTotal_raw'])) {
			$grandRaw = (float) $relatedProducts[1]['final_details']['grandTotal_raw'];
		} else {
			$grandRaw = (float) $recordModel->get('total');
		}
		if ($grandRaw < 0) {
			$grandRaw = 0;
		}
		$remainingRaw = $grandRaw - $paidRaw;
		if ($remainingRaw < 0) {
			$remainingRaw = 0;
		}
		$formatMoney = function ($value) {
			return Vtiger_Currency_UIType::transformDisplayValue($value, null, true);
		};
		$viewer->assign('INLINE_PAID_FIELD', $paidField);
		$viewer->assign('INLINE_PAID_RAW', $paidRaw);
		$viewer->assign('INLINE_PAID_DISPLAY', $formatMoney($paidRaw));
		$viewer->assign('INLINE_GRAND_RAW', $grandRaw);
		$viewer->assign('INLINE_REMAINING_DISPLAY', $formatMoney($remainingRaw));
		$viewer->assign('INLINE_CUSTOMER_NAME', $this->resolveInlineCustomerName($recordModel));
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_PRINT_URL', 'index.php?module=SalesOrder&view=Print&record=' . (int) $recordId . '&app=SALES');
		$viewer->assign('INLINE_PRINT_DOWNLOAD_URL', 'index.php?module=SalesOrder&action=ExportPDF&record=' . (int) $recordId);
		$viewer->assign('INLINE_CREATED_DATE', $this->formatInlineCreatedDateDmY($recordModel));
		$payableRaw = 0.0;
		if (!empty($relatedProducts[1]['final_details']['grandTotal_raw'])) {
			$payableRaw = (float) $relatedProducts[1]['final_details']['grandTotal_raw'];
		} else {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			$subRaw = Quotes_QuoteBaService_Helper::parseMoneyNumber(
				$relatedProducts[1]['final_details']['hdnSubTotal'] ?? $recordModel->get('hdnSubTotal')
			);
			$discRaw = Quotes_QuoteBaService_Helper::parseMoneyNumber(
				$relatedProducts[1]['final_details']['discountTotal_final'] ?? $recordModel->get('hdnDiscountAmount')
			);
			$payableRaw = max(0.0, $subRaw - $discRaw);
		}
		if ($payableRaw <= 0) {
			$payableRaw = $grandRaw;
		}
		$amountWords = '';
		if ($payableRaw > 0 && file_exists('modules/Quotes/helpers/QuoteBaService.php')) {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			if (class_exists('Quotes_QuoteBaService_Helper')) {
				$amountWords = Quotes_QuoteBaService_Helper::amountInWordsVi($payableRaw);
			}
		}
		$viewer->assign('INLINE_AMOUNT_WORDS', $amountWords);

		return $viewer->view('partials/ListInlineDetail.tpl', $moduleName, true);
	}

	/**
	 * Repair SO created from quote that missed accountid (failed/partial confirm).
	 */
	protected function repairMissingAccountFromQuote(Vtiger_Record_Model $recordModel) {
		$db = PearDatabase::getInstance();
		$accountId = (int) $recordModel->get('account_id');
		$contactId = (int) $recordModel->get('contact_id');
		if ($accountId > 0 && $contactId > 0) {
			return;
		}
		// Also try potential on the SO itself when quote is gone.
		if ($contactId <= 0) {
			$potentialId = (int) $recordModel->get('potential_id');
			if ($potentialId > 0) {
				require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
				$potContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
				if ($potContactId > 0) {
					$soId = (int) $recordModel->getId();
					$db->pquery('UPDATE vtiger_salesorder SET contactid = ? WHERE salesorderid = ?', array($potContactId, $soId));
					$recordModel->set('contact_id', $potContactId);
					$contactId = $potContactId;
				}
			}
		}
		if ($accountId > 0 && $contactId > 0) {
			return;
		}
		$quoteId = (int) $recordModel->get('quote_id');
		if ($quoteId <= 0) {
			return;
		}
		$rs = $db->pquery('SELECT accountid, contactid, potentialid FROM vtiger_quotes WHERE quoteid = ?', array($quoteId));
		if (!$rs || $db->num_rows($rs) <= 0) {
			return;
		}
		$quoteAccountId = (int) $db->query_result($rs, 0, 'accountid');
		$quoteContactId = (int) $db->query_result($rs, 0, 'contactid');
		$quotePotentialId = (int) $db->query_result($rs, 0, 'potentialid');
		if ($quoteContactId <= 0 && $quotePotentialId > 0) {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$quoteContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($quotePotentialId);
		}
		if ($quoteAccountId <= 0 && $quoteContactId <= 0) {
			return;
		}
		$soId = (int) $recordModel->getId();
		$db->pquery(
			'UPDATE vtiger_salesorder SET
				accountid = IF(? > 0, ?, accountid),
				contactid = IF(? > 0, ?, contactid)
			 WHERE salesorderid = ?',
			array($quoteAccountId, $quoteAccountId, $quoteContactId, $quoteContactId, $soId)
		);
		if ($quoteAccountId > 0) {
			$recordModel->set('account_id', $quoteAccountId);
		}
		if ($quoteContactId > 0) {
			$recordModel->set('contact_id', $quoteContactId);
		}
	}

	protected function resolveInlineCustomerName(Vtiger_Record_Model $recordModel) {
		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		$name = Vtiger_MkSalesCustomerName_Helper::resolveDisplayName($recordModel);
		return $name !== '' ? $name : '—';
	}

	/**
	 * Rebuild display totals from line amounts when header totals are out of scale.
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

		// BA unit prices already include VAT — never invent +8% tax on display/totals.
		// If header tax is ~8% of goods (and prices are VAT-included), treat tax as 0.
		$base = max(0.0, $subTotal - $discount + $shipping + $adjustment);
		if ($tax > 0 && $base > 0) {
			$taxRatio = $tax / max($base, 1);
			// Classic mis-calc: tax ≈ 8% of pre-tax goods.
			if ($taxRatio > 0.05 && $taxRatio < 0.12 && abs($tax - $base * 0.08) < max(100, $base * 0.02)) {
				$tax = 0;
			}
			// Or grand already inflated header
			if ($tax > ($subTotal * 0.5)) {
				$tax = 0;
			}
		}
		// Do NOT invent tax when tax fields are empty — prices are VAT-inclusive.
		if ($tax < 0) {
			$tax = 0;
		}

		$headerTotal = (float) $recordModel->get('total');
		if ($grand > 0) {
			$grand *= $scale;
		}

		// Base with discount fields as stored on SO.
		$base = max(0.0, $subTotal - $discount + $shipping + $adjustment);

		// Prefer header total (quote convert writes post-discount total, e.g. 850k with 15% off 1M).
		// Never re-inflate back to subTotal just because abs(diff) > 5% of gross.
		$headerScaled = $headerTotal > 0 ? ($headerTotal * $scale) : 0.0;
		if ($headerScaled > 0 && $subTotal > 0) {
			$hr = $headerScaled / max($subTotal, 1);
			// Strip accidental ~8% VAT on header only
			if ($hr > 1.05 && $hr < 1.12 && abs($headerScaled - $subTotal * 1.08) < max(100, $subTotal * 0.02)) {
				$headerScaled = $subTotal;
			}
		}
		// Infer discount from header when discount fields empty but total < goods total.
		if ($discount <= 0 && $headerScaled > 0 && $subTotal > 0 && $headerScaled + 0.5 < $subTotal) {
			$discount = max(0.0, $subTotal + $shipping + $adjustment - $headerScaled);
			$base = max(0.0, $subTotal - $discount + $shipping + $adjustment);
		}

		// Source of truth priority:
		// 1) header total after discount / strip tax
		// 2) recomputed base (goods − discount + charges)
		// 3) display grand stripped of 8% VAT
		if ($headerScaled > 0) {
			// Accept header when it is not wildly higher than goods (tax invent) and not zero junk.
			if ($subTotal <= 0 || $headerScaled <= ($subTotal * 1.05 + max(100, $subTotal * 0.02))) {
				$grand = $headerScaled;
			} else {
				$grand = $base;
			}
		} elseif ($grand > 0 && $base > 0) {
			$gr = $grand / max($base, 1);
			if ($gr > 1.05 && $gr < 1.12 && abs($grand - $base * 1.08) < max(100, $base * 0.02)) {
				$grand = $base;
			} elseif ($grand > ($subTotal * 1.05) && $subTotal > 0) {
				$grand = $base;
			}
			// Keep legitimate discounted grand below base without forcing back to full subTotal.
		} else {
			$grand = $base;
		}
		if ($grand <= 0 && $base > 0) {
			$grand = $base;
		}

		// Final: BA grand is VAT-included unit prices; do not add tax column.
		$tax = 0;

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
		$displayProducts[1]['final_details']['grandTotal_raw'] = $grand;

		// Repair line unit price / thành tiền; always map Giá bán = listPrice when unitPrice is catalog 0.
		$lineCount = 0;
		for ($i = 1; $i <= $productsCount; $i++) {
			if (!isset($rawProducts[$i]) || empty($rawProducts[$i]['hdnProductId' . $i])) {
				continue;
			}
			$lineCount++;
		}
		if ($subTotal > 0 && $lineCount > 0) {
			$share = $lineCount === 1 ? $subTotal : ($subTotal / $lineCount);
			for ($i = 1; $i <= $productsCount; $i++) {
				if (!isset($displayProducts[$i]) || empty($rawProducts[$i]['hdnProductId' . $i])) {
					continue;
				}
				$qty = (float) ($rawProducts[$i]['qty' . $i] ?? 0);
				if ($qty <= 0) {
					$qty = 1;
				}
				$lineTotal = (float) ($rawProducts[$i]['productTotal' . $i] ?? 0);
				$listPrice = (float) ($rawProducts[$i]['listPrice' . $i] ?? 0);
				$unitPriceCatalog = (float) ($rawProducts[$i]['unitPrice' . $i] ?? 0);
				if ($lineTotal <= 0) {
					$lineTotal = (float) ($rawProducts[$i]['totalAfterDiscount' . $i] ?? 0);
				}
				if ($lineTotal <= 0) {
					$lineTotal = $share;
				}
				if ($listPrice <= 0) {
					$listPrice = $lineTotal / $qty;
				}
				// Selling price for list view = listPrice (after discount unit); never show catalog 0.
				$sellPrice = $listPrice > 0 ? $listPrice : $unitPriceCatalog;
				$displayProducts[$i]['qty' . $i] = $qty;
				$displayProducts[$i]['listPrice' . $i] = $formatMoney($listPrice);
				$displayProducts[$i]['unitPrice' . $i] = $formatMoney($sellPrice);
				$displayProducts[$i]['productTotal' . $i] = $formatMoney($lineTotal);
			}
		}

		if (file_exists('modules/Quotes/helpers/QuoteBaService.php')) {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			if (class_exists('Quotes_QuoteBaService_Helper') && method_exists('Quotes_QuoteBaService_Helper', 'amountInWordsVi')) {
				$displayProducts[1]['final_details']['amount_in_words'] = Quotes_QuoteBaService_Helper::amountInWordsVi($grand);
			}
		}

		$displayProducts = $this->enrichInlineLineTax($displayProducts, $rawProducts, $subTotal, $discount, $tax, $recordModel, $formatMoney);

		return $displayProducts;
	}

	/**
	 * BA: unit prices already include VAT — force per-line tax display to 0.
	 * Do not invent 8% tax for the list inline view.
	 */
	protected function enrichInlineLineTax(array $displayProducts, array $rawProducts, $subTotal, $discount, $tax, Vtiger_Record_Model $recordModel, callable $formatMoney) {
		$productsCount = php7_count($rawProducts);
		$zero = $formatMoney(0);
		for ($i = 1; $i <= $productsCount; $i++) {
			if (!isset($displayProducts[$i]) || empty($rawProducts[$i]['hdnProductId' . $i])) {
				continue;
			}
			$displayProducts[$i]['taxTotal' . $i] = $zero;
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

	protected function resolveInlinePaidFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('received', 'paid_amount', 'amount_paid', 'paid', 'mk_customer_paid') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel) {
				return $fieldName;
			}
		}
		return 'received';
	}

	/**
	 * Always emit d/m/Y for Excel preview (avoid MM/DD user-format swap).
	 */
	protected function formatInlineCreatedDateDmY(Vtiger_Record_Model $recordModel) {
		$raw = trim((string) $recordModel->get('createdtime'));
		if ($raw === '') {
			return date('d/m/Y');
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			return $m[3] . '/' . $m[2] . '/' . $m[1];
		}
		$ts = strtotime($raw);
		if ($ts) {
			return date('d/m/Y', $ts);
		}
		return date('d/m/Y');
	}

	protected function resolveInlineBranchLabel(Vtiger_Record_Model $recordModel, Vtiger_Module_Model $moduleModel) {
		foreach (array('branch', 'mk_branch', 'store_location', 'location') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				$value = trim((string) $recordModel->getDisplayValue($fieldName));
				if ($value !== '') {
					return $value;
				}
			}
		}
		return '';
	}

	protected function getPosStatusLabelMap() {
		return array(
			'Created' => 'Phiếu tạm',
			'Approved' => 'Đã xác nhận',
			'Delivered' => 'Hoàn thành',
			'Cancelled' => 'Đã hủy',
			'Pending' => 'Đang chờ',
			'Paid' => 'Đã thanh toán',
			'Sent' => 'Đã gửi',
			'Rejected' => 'Từ chối',
			'waiting_print' => 'Chờ soạn',
			'picking' => 'Đang soạn',
			'packed' => 'Đã soạn',
			'shipped' => 'Đã giao',
			'rejected' => 'Từ chối',
			'Đã duyệt' => 'Đã xác nhận',
			'Đã tạo' => 'Phiếu tạm',
			'Đang chờ xử lý' => 'Đang chờ',
			'Đang giao hàng' => 'Đang giao hàng',
			'Hoàn thành' => 'Hoàn thành',
			'Đã gửi' => 'Đã gửi',
			'Đã thanh toán' => 'Đã thanh toán',
			'Đã hủy' => 'Đã hủy',
			'Từ chối' => 'Từ chối',
			'Chờ soạn' => 'Chờ soạn',
			'Đang soạn' => 'Đang soạn',
			'Đã soạn' => 'Đã soạn',
			'Đã giao' => 'Đã giao',
		);
	}

	protected function resolvePosStatusLabel($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		$map = $this->getPosStatusLabelMap();
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

	protected function getPosStatusOptions(Vtiger_Module_Model $moduleModel, $fieldName) {
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
			$options[$key] = $this->resolvePosStatusLabel($key);
			if ($options[$key] === $key) {
				$options[$key] = $this->resolvePosStatusLabel($label);
			}
			if ($options[$key] === $key) {
				$options[$key] = $label;
			}
		}
		return $options;
	}

	protected function isPosStatusFieldName($fieldName) {
		return in_array($fieldName, array('sostatus', 'salesorder_status', 'invoicestatus', 'status'), true);
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

	/**
	 * Warehouse used when confirming the order (linked goods issue).
	 */
	protected function resolveInlineWarehouseName(Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return '';
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT warehouse_id, storage_location
			 FROM vtiger_goodsissue
			 WHERE deleted = 0 AND salesorder_id = ?
			 ORDER BY issueid DESC
			 LIMIT 1',
			array($recordId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return '';
		}
		$whId = trim((string) $db->query_result($rs, 0, 'warehouse_id'));
		require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
		$name = $whId !== '' ? Warehouse_Registry::getName($whId) : '';
		if ($name === '') {
			$name = trim(decode_html((string) $db->query_result($rs, 0, 'storage_location')));
		}
		return $name;
	}

	protected function buildInlineInfoFieldEntry(
		Vtiger_Module_Model $moduleModel,
		Vtiger_Record_Model $recordModel,
		Vtiger_Field_Model $fieldModel,
		$label
	) {
		$fieldName = $fieldModel->getName();
		$value = trim((string) $recordModel->getDisplayValue($fieldName));
		if ($this->isPosStatusFieldName($fieldName)) {
			$value = $this->resolvePosStatusLabel($value);
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
			if ($this->isPosStatusFieldName($fieldName)) {
				$picklistValues = $this->getPosStatusOptions($moduleModel, $fieldName);
			} else {
				$picklistValues = $fieldModel->getPicklistValues();
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
				'names' => array(),
				'label' => 'Tham chiếu báo giá',
				'label_hints' => array('Tham chiếu báo giá', 'Quote', 'Quote Name'),
				'virtual' => 'quote_ref',
			),
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
				'names' => array('received', 'paid_amount', 'amount_paid', 'paid', 'mk_customer_paid'),
				'label' => 'Khách đã trả',
				'label_hints' => array('Khách đã trả', 'Received', 'Paid', 'Amount Paid'),
			),
			array(
				'names' => array('assigned_user_id'),
				'label' => 'Người nhận đặt',
				'label_hints' => array('Người nhận đặt', 'Assigned To', 'Ordered By'),
			),
			array(
				'names' => array('pricebook_id'),
				'label' => 'Bảng giá',
				'label_hints' => array('Bảng giá', 'Price Book', 'PriceBooks'),
			),
			array(
				'names' => array('createdtime'),
				'label' => 'Ngày đặt',
				'label_hints' => array('Ngày đặt', 'Created Time', 'Order Date'),
			),
			array(
				'names' => array('duedate'),
				'label' => 'Dự kiến giao',
				'label_hints' => array('Dự kiến giao', 'Due Date', 'Ngày bàn giao', 'Expected Delivery'),
			),
			array(
				'names' => array(),
				'label' => 'Kho xuất',
				'label_hints' => array('Kho xuất', 'Warehouse', 'Kho'),
				'virtual' => 'warehouse',
			),
		);

		$fields = array();
		$seenLabels = array();
		foreach ($candidates as $candidate) {
			$label = $candidate['label'];
			if (isset($seenLabels[$label])) {
				continue;
			}

			if (!empty($candidate['virtual']) && $candidate['virtual'] === 'quote_ref') {
				$quoteId = (int) $recordModel->get('quote_id');
				if ($quoteId <= 0) {
					$db = PearDatabase::getInstance();
					$rs = $db->pquery(
						'SELECT quoteid FROM vtiger_salesorder WHERE salesorderid = ?',
						array((int) $recordModel->getId())
					);
					if ($rs && $db->num_rows($rs) > 0) {
						$quoteId = (int) $db->query_result($rs, 0, 'quoteid');
					}
				}
				$valueHtml = '—';
				$rawValue = '';
				if ($quoteId > 0) {
					require_once 'modules/Quotes/helpers/QuoteBaService.php';
					$valueHtml = Quotes_QuoteBaService_Helper::buildQuoteRefInlineHtml($quoteId);
					$ref = Quotes_QuoteBaService_Helper::resolveQuoteReference($quoteId);
					$rawValue = $ref ? $ref['quote_no'] : '';
				}
				$fields[] = array(
					'name' => 'quote_id',
					'label' => $label,
					'value' => $valueHtml,
					'raw_value' => $rawValue,
					'data_type' => 'string',
					'editable' => false,
					'picklist_values' => array(),
					'is_html' => true,
				);
				$seenLabels[$label] = true;
				continue;
			}

			if (!empty($candidate['virtual']) && $candidate['virtual'] === 'creator') {
				$creator = $this->resolveInlineCreatorField($recordModel);
				$fieldModel = $this->resolveInlineFieldModel(
					$moduleModel,
					(array) $candidate['names'],
					(array) $candidate['label_hints']
				);
				$displayValue = $creator['display'] !== '' ? $creator['display'] : '—';
				$dataType = $fieldModel ? $fieldModel->getFieldDataType() : 'string';
				$fields[] = array(
					'name' => $creator['name'],
					'label' => $label,
					'value' => $displayValue,
					'raw_value' => $creator['raw'],
					'data_type' => $dataType,
					'editable' => false,
					'picklist_values' => array(),
				);
				$seenLabels[$label] = true;
				continue;
			}

			if (!empty($candidate['virtual']) && $candidate['virtual'] === 'warehouse') {
				$warehouseName = $this->resolveInlineWarehouseName($recordModel);
				$fields[] = array(
					'name' => 'mk_warehouse_name',
					'label' => $label,
					'value' => $warehouseName !== '' ? $warehouseName : '—',
					'raw_value' => $warehouseName,
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
			if ($label === 'Bảng giá') {
				if ($fieldModel->getName() !== 'pricebook_id' || (int) $recordModel->get('pricebook_id') <= 0) {
					continue;
				}
			}
			$fields[] = $this->buildInlineInfoFieldEntry($moduleModel, $recordModel, $fieldModel, $label);
			$seenLabels[$label] = true;
		}
		return $fields;
	}

	public function showModuleBasicView($request) {
		if ($this->isToolsOrdersContext($request)) {
			echo $this->showModuleDetailView($request);
			return;
		}
		parent::showModuleBasicView($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		if (!$this->isToolsOrdersContext($request)) {
			return parent::showModuleDetailView($request);
		}

		$recordId = $request->get('record');
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'SalesOrder');
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', 'SalesOrder');
		$viewer->assign('MODULE', 'SalesOrder');
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance('SalesOrder'));
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		return $viewer->view('ToolsOrdersDetailView.tpl', 'SalesOrder', true);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isToolsOrdersContext($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/SalesOrder/resources/SalesOrderToolsDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}
