<?php
/*+***********************************************************************************
 * Invoice Detail — premium workspace + SALES list inline panel.
 *************************************************************************************/

class Invoice_Detail_View extends Inventory_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	protected function isMkModernInvoiceDetail(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		return $app === 'TOOLS' || $app === 'SUPPORT' || $app === 'SALES';
	}

	protected function isSalesListInlineContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'SALES';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernInvoiceDetail($request)) {
			$viewer = $this->getViewer($request);
			$app = strtoupper((string) $request->get('app'));
			$viewer->assign('SELECTED_MENU_CATEGORY', $app);
			$viewer->assign('MK_INV_MK_DETAIL', true);
		}
		parent::preProcess($request, $display);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernInvoiceDetail($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Invoice/resources/InvoiceToolsDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	/**
	 * Inline panel for SALES Invoice list (order → invoice snapshot).
	 */
	public function showListInlineDetail(Vtiger_Request $request) {
		if (!$this->isSalesListInlineContext($request)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		$moduleName = 'Invoice';
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
		}
		$relatedProducts = $this->enrichLineUsageUnits($relatedProducts);
		$viewer->assign('RELATED_PRODUCTS', $relatedProducts);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');

		$paidField = $this->resolveInlinePaidFieldName($moduleModel);
		$paidRaw = (float) $recordModel->get($paidField);
		if ($paidRaw < 0) {
			$paidRaw = 0;
		}
		$grandRaw = 0.0;
		if (!empty($relatedProducts[1]['final_details']['grandTotal'])) {
			$grandRaw = (float) preg_replace('/[^\d.-]/', '', (string) $relatedProducts[1]['final_details']['grandTotal']);
		}
		if ($grandRaw <= 0) {
			$grandRaw = (float) $recordModel->get('hdnGrandTotal');
		}
		if ($grandRaw <= 0) {
			$grandRaw = (float) $recordModel->get('total');
		}
		if ($grandRaw < 0) {
			$grandRaw = 0;
		}
		$remainingRaw = max(0.0, $grandRaw - $paidRaw);
		$formatMoney = function ($value) {
			return Vtiger_Currency_UIType::transformDisplayValue($value, null, true);
		};

		$soMeta = $this->resolveLinkedSalesOrderMeta($recordModel);
		$viewer->assign('INLINE_SO_ID', $soMeta['id']);
		$viewer->assign('INLINE_SO_NO', $soMeta['no']);
		$viewer->assign('INLINE_SO_URL', $soMeta['url']);
		$viewer->assign('INLINE_SO_STATUS', $soMeta['status']);
		$viewer->assign('INLINE_CUSTOMER_NAME', $this->resolveInlineCustomerName($recordModel, $soMeta));
		$viewer->assign('INLINE_INFO_FIELDS', $this->getInlineInfoFields($moduleModel, $recordModel, $soMeta));
		$viewer->assign('INLINE_PAID_FIELD', $paidField);
		$viewer->assign('INLINE_PAID_RAW', $paidRaw);
		$viewer->assign('INLINE_PAID_DISPLAY', $formatMoney($paidRaw));
		$viewer->assign('INLINE_GRAND_RAW', $grandRaw);
		$viewer->assign('INLINE_REMAINING_DISPLAY', $formatMoney($remainingRaw));
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_PRINT_URL', 'index.php?module=Invoice&action=ExportPDF&record=' . (int) $recordId . '&app=SALES');
		$viewer->assign('INLINE_CREATED_DATE', $this->formatInlineCreatedDateDmY($recordModel));
		$statusField = $this->resolveInvoiceStatusFieldName($moduleModel);
		$statusRaw = $statusField ? trim((string) $recordModel->get($statusField)) : '';
		$statusLabel = $statusField ? trim((string) $recordModel->getDisplayValue($statusField)) : '';
		$statusLabel = $this->resolveInvoiceStatusLabel($statusRaw, $statusLabel);
		$viewer->assign('INLINE_INVOICE_STATUS', $statusRaw);
		$viewer->assign('INLINE_INVOICE_STATUS_LABEL', $statusLabel);

		return $viewer->view('partials/ListInlineDetail.tpl', $moduleName, true);
	}

	protected function resolveInvoiceStatusFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('invoicestatus', 'status') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel && $fieldModel->isViewable()) {
				return $fieldName;
			}
		}
		return '';
	}

	protected function resolveInlinePaidFieldName(Vtiger_Module_Model $moduleModel) {
		foreach (array('received', 'paid', 'mk_customer_paid', 'paid_amount') as $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
			if ($fieldModel) {
				return $fieldName;
			}
		}
		return 'received';
	}

	protected function formatInlineCreatedDateDmY(Vtiger_Record_Model $recordModel) {
		$raw = trim((string) $recordModel->get('createdtime'));
		if ($raw === '') {
			return date('d/m/Y');
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			return $m[3] . '/' . $m[2] . '/' . $m[1];
		}
		$ts = strtotime($raw);
		return $ts ? date('d/m/Y', $ts) : date('d/m/Y');
	}

	protected function resolveLinkedSalesOrderMeta(Vtiger_Record_Model $recordModel) {
		$meta = array('id' => 0, 'no' => '', 'url' => '', 'status' => '', 'model' => null);
		$soId = (int) $recordModel->get('salesorder_id');
		if ($soId <= 0) {
			$db = PearDatabase::getInstance();
			$rs = $db->pquery('SELECT salesorderid FROM vtiger_invoice WHERE invoiceid = ?', array((int) $recordModel->getId()));
			if ($rs && $db->num_rows($rs) > 0) {
				$soId = (int) $db->query_result($rs, 0, 'salesorderid');
			}
		}
		if ($soId <= 0) {
			return $meta;
		}
		try {
			$soModel = Vtiger_Record_Model::getInstanceById($soId, 'SalesOrder');
		} catch (Exception $e) {
			return $meta;
		}
		if (!$soModel) {
			return $meta;
		}
		$meta['id'] = $soId;
		$meta['model'] = $soModel;
		$meta['no'] = trim((string) $soModel->getDisplayValue('salesorder_no'));
		if ($meta['no'] === '') {
			$meta['no'] = trim((string) $soModel->get('salesorder_no'));
		}
		$meta['status'] = trim((string) $soModel->getDisplayValue('sostatus'));
		if ($meta['status'] === '' || $meta['status'] === trim((string) $soModel->get('sostatus'))) {
			$soRaw = trim((string) $soModel->get('sostatus'));
			$soTranslated = $soRaw !== '' ? trim((string) vtranslate($soRaw, 'SalesOrder')) : '';
			if ($soTranslated !== '' && $soTranslated !== $soRaw) {
				$meta['status'] = $soTranslated;
			} elseif ($soRaw !== '') {
				$meta['status'] = $soRaw;
			}
		}
		$meta['url'] = $soModel->getDetailViewUrl() . '&app=SALES';
		return $meta;
	}

	protected function resolveInlineCustomerName(Vtiger_Record_Model $recordModel, array $soMeta) {
		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		$name = Vtiger_MkSalesCustomerName_Helper::resolveListStyleName($recordModel);
		if ($name === '' && !empty($soMeta['model'])) {
			$name = Vtiger_MkSalesCustomerName_Helper::resolveListStyleName($soMeta['model']);
		}
		return $name !== '' ? $name : '—';
	}

	protected function resolveInvoiceStatusLabel($raw, $display = '') {
		$raw = trim((string) $raw);
		$display = trim((string) $display);
		$map = array(
			'Created' => 'Hóa đơn mới',
			'AutoCreated' => 'Tạo tự động',
			'Sent' => 'Đã gửi',
			'Paid' => 'Đã thanh toán',
			'Credit Invoice' => 'Phải thu',
			'Cancel' => 'Hủy bỏ',
			'Cancelled' => 'Đã hủy',
			'Approved' => 'Đã xác nhận',
			'Pending' => 'Đang chờ',
			'Rejected' => 'Từ chối',
			'Delivered' => 'Đã giao',
		);
		if ($display !== '' && $display !== $raw && !isset($map[$display])) {
			return $display;
		}
		if ($raw !== '' && isset($map[$raw])) {
			return $map[$raw];
		}
		if ($raw !== '') {
			$translated = trim((string) vtranslate($raw, 'Invoice'));
			if ($translated !== '' && $translated !== $raw) {
				return $translated;
			}
		}
		return $display !== '' ? $display : $raw;
	}

	protected function enrichLineUsageUnits(array $products) {
		$db = PearDatabase::getInstance();
		$formatMoney = function ($value) {
			$raw = is_numeric($value) ? (float) $value : (float) preg_replace('/[^\d.-]/', '', (string) $value);
			return Vtiger_Currency_UIType::transformDisplayValue($raw, null, true);
		};
		$formatQty = function ($value) {
			$q = (float) preg_replace('/[^\d.-]/', '', (string) $value);
			if (abs($q - round($q)) < 0.0001) {
				return (string) (int) round($q);
			}
			return rtrim(rtrim(number_format($q, 3, '.', ''), '0'), '.');
		};

		$count = php7_count($products);
		for ($i = 1; $i <= $count; $i++) {
			if (!isset($products[$i])) {
				continue;
			}
			$productId = (int) (isset($products[$i]['hdnProductId' . $i]) ? $products[$i]['hdnProductId' . $i] : 0);
			if ($productId <= 0) {
				$products[$i]['usageunit' . $i] = '';
				$products[$i]['lineSku' . $i] = '';
				continue;
			}

			$unit = '';
			$sku = '';
			$catalogName = '';
			$rs = $db->pquery(
				'SELECT unit, sku, productsservicesname FROM vtiger_productsservices WHERE productsservicesid = ?',
				array($productId)
			);
			if ($rs && $db->num_rows($rs) > 0) {
				$unit = (string) $db->query_result($rs, 0, 'unit');
				$sku = (string) $db->query_result($rs, 0, 'sku');
				$catalogName = (string) $db->query_result($rs, 0, 'productsservicesname');
			}
			if ($catalogName === '') {
				$prod = $db->pquery('SELECT productname, productcode FROM vtiger_products WHERE productid = ?', array($productId));
				if ($prod && $db->num_rows($prod) > 0) {
					$catalogName = (string) $db->query_result($prod, 0, 'productname');
					if ($sku === '') {
						$sku = (string) $db->query_result($prod, 0, 'productcode');
					}
				} else {
					$svc = $db->pquery('SELECT servicename, service_no FROM vtiger_service WHERE serviceid = ?', array($productId));
					if ($svc && $db->num_rows($svc) > 0) {
						$catalogName = (string) $db->query_result($svc, 0, 'servicename');
						if ($sku === '') {
							$sku = (string) $db->query_result($svc, 0, 'service_no');
						}
					}
				}
			}

			$sku = trim(decode_html($sku));
			$catalogName = trim(decode_html($catalogName));
			$existingName = trim(decode_html(isset($products[$i]['productName' . $i]) ? (string) $products[$i]['productName' . $i] : ''));
			$existingCode = trim(decode_html(isset($products[$i]['hdnProductcode' . $i]) ? (string) $products[$i]['hdnProductcode' . $i] : ''));

			$products[$i]['usageunit' . $i] = trim(decode_html($unit));
			$products[$i]['lineSku' . $i] = $sku !== '' ? $sku : ($existingCode !== '' ? $existingCode : '—');

			// Prefer catalog name when stored productName is empty or is just the SKU/code.
			$nameLooksLikeCode = ($existingName !== '' && (
				strcasecmp($existingName, $sku) === 0
				|| strcasecmp($existingName, $existingCode) === 0
			));
			if ($catalogName !== '' && ($existingName === '' || $nameLooksLikeCode)) {
				$products[$i]['productName' . $i] = $catalogName;
			} elseif ($existingName === '' && $sku !== '') {
				$products[$i]['productName' . $i] = $sku;
			}

			if (isset($products[$i]['qty' . $i])) {
				$products[$i]['qty' . $i] = $formatQty($products[$i]['qty' . $i]);
			}
			foreach (array('listPrice', 'unitPrice', 'productTotal', 'netPrice', 'totalAfterDiscount') as $moneyKey) {
				$key = $moneyKey . $i;
				if (isset($products[$i][$key]) && $products[$i][$key] !== '') {
					$products[$i][$key] = $formatMoney($products[$i][$key]);
				}
			}
		}

		if (isset($products[1]['final_details']) && is_array($products[1]['final_details'])) {
			foreach (array('hdnSubTotal', 'grandTotal', 'discountTotal_final', 'tax_totalamount', 'shipping_handling_charge', 'adjustment') as $finalKey) {
				if (isset($products[1]['final_details'][$finalKey]) && $products[1]['final_details'][$finalKey] !== '') {
					$products[1]['final_details'][$finalKey] = $formatMoney($products[1]['final_details'][$finalKey]);
				}
			}
		}

		return $products;
	}

	protected function getInlineInfoFields(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel, array $soMeta) {
		$fields = array();
		if (!empty($soMeta['id'])) {
			$soLabel = $soMeta['no'] !== '' ? $soMeta['no'] : ('#' . $soMeta['id']);
			$soHtml = '<a href="' . htmlspecialchars($soMeta['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
				. htmlspecialchars($soLabel, ENT_QUOTES, 'UTF-8') . '</a>';
			if ($soMeta['status'] !== '') {
				$soHtml .= ' <span class="mk-inv-inline-so-status">' . htmlspecialchars($soMeta['status'], ENT_QUOTES, 'UTF-8') . '</span>';
			}
			$fields[] = array(
				'name' => 'salesorder_id',
				'label' => 'Đơn hàng nguồn',
				'value' => $soHtml,
				'raw_value' => (string) $soMeta['id'],
				'data_type' => 'reference',
				'editable' => false,
				'is_html' => true,
				'picklist_values' => array(),
			);
		}

		$statusField = $this->resolveInvoiceStatusFieldName($moduleModel);
		$candidates = array(
			array('names' => array($statusField), 'label' => 'Trạng thái hóa đơn'),
			array('names' => array('createdtime'), 'label' => 'Thời gian'),
			array('names' => array('received', 'paid', 'mk_customer_paid'), 'label' => 'Khách đã trả'),
			array('names' => array('assigned_user_id'), 'label' => 'Người phụ trách'),
			array('names' => array('invoicedate', 'duedate'), 'label' => 'Ngày hóa đơn'),
		);
		$seen = array('salesorder_id' => true);
		foreach ($candidates as $candidate) {
			foreach ($candidate['names'] as $fieldName) {
				if ($fieldName === '' || isset($seen[$fieldName])) {
					continue;
				}
				$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
				if (!$fieldModel || !$fieldModel->isViewable()) {
					continue;
				}
				$seen[$fieldName] = true;
				$rawValue = (string) $recordModel->get($fieldName);
				$value = trim((string) $recordModel->getDisplayValue($fieldName));
				if ($fieldName === $statusField) {
					$value = $this->resolveInvoiceStatusLabel($rawValue, $value);
				} elseif (in_array($fieldName, array('received', 'paid', 'mk_customer_paid'), true)) {
					$value = Vtiger_Currency_UIType::transformDisplayValue((float) preg_replace('/[^\d.-]/', '', $rawValue), null, true);
				}
				if ($value === '') {
					$value = '—';
				}
				$fields[] = array(
					'name' => $fieldName,
					'label' => $candidate['label'],
					'value' => $value,
					'raw_value' => $rawValue,
					'data_type' => $fieldModel->getFieldDataType(),
					'editable' => false,
					'is_html' => false,
					'picklist_values' => array(),
				);
				break;
			}
		}
		return $fields;
	}
}
