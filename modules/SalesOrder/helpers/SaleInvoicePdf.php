<?php
/*+***********************************************************************************
 * SalesOrder / Quotes invoice PDF — PHIẾU ĐẶT HÀNG layout (NK branding).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
require_once 'modules/Quotes/helpers/QuoteBaService.php';
include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';

class SalesOrder_SaleInvoicePdf_Helper {

	const FONT = 'times';
	const GREEN = array(8, 160, 69);
	const ACCENT = array(196, 74, 28);

	/**
	 * @param CRMEntity $focus
	 * @param string $moduleName SalesOrder|Quotes
	 * @param string $fileName
	 * @param string $dest I=inline, D=download, F=file, S=string
	 * @return string|void
	 */
	public static function output(CRMEntity $focus, $moduleName, $fileName, $dest = 'I') {
		$pdf = self::build($focus, $moduleName);
		return $pdf->Output($fileName, $dest);
	}

	/**
	 * Browser HTML print (PHIẾU ĐẶT HÀNG). Prefer this for window.print().
	 *
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @param bool $autoPrint
	 */
	public static function outputHtml(CRMEntity $focus, $moduleName = 'SalesOrder', $autoPrint = false) {
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		if (isset($GLOBALS['csrf']) && is_array($GLOBALS['csrf'])) {
			$GLOBALS['csrf']['rewrite'] = false;
			$GLOBALS['csrf']['frame-breaker'] = false;
		}
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: private, no-store');
		echo self::buildHtml(self::prepareDocumentData($focus, $moduleName), $autoPrint);
		exit;
	}

	/**
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @return array
	 */
	public static function prepareDocumentData(CRMEntity $focus, $moduleName = 'SalesOrder') {
		$ctx = Quotes_QuoteExcelExport_Helper::getSaleExportContext($focus);
		$companyName = Quotes_QuoteExcelExport_Helper::nkCompanyName();
		$address = Quotes_QuoteExcelExport_Helper::nkAddress();
		$phone = Quotes_QuoteExcelExport_Helper::nkPhone();
		$logoPath = Quotes_QuoteExcelExport_Helper::resolveLogoPathPublic();
		if ($logoPath === '' && !empty($ctx['company']['logo_path'])) {
			$logoPath = $ctx['company']['logo_path'];
		}

		$isQuote = ($moduleName === 'Quotes');
		$docTitle = $isQuote ? 'BÁO GIÁ' : 'PHIẾU ĐẶT HÀNG';
		$docNoLabel = $isQuote ? 'Mã báo giá: ' : 'Hóa Đơn Số: ';
		$docNo = $ctx['quote_no'] !== '' ? $ctx['quote_no'] : (($isQuote ? 'BG' : 'DH') . $focus->id);
		$dateLabel = Quotes_QuoteExcelExport_Helper::formatDateViLongPublic($ctx['quote_date']);
		// Prefer receiver (Contacts firstname+lastname order) to avoid normalizeAccountName reformatting.
		$customer = trim((string) ($ctx['receiver'] ?? ''));
		if ($customer === '') {
			$customer = trim((string) ($ctx['account_name'] ?? ''));
		}
		$customerPhone = (string) ($ctx['phone'] ?? '');
		$customerAddress = (string) ($ctx['address'] ?? '');
		$notes = isset($ctx['notes']) ? trim((string) $ctx['notes']) : Quotes_QuoteExcelExport_Helper::resolveExportNotesPublic($focus, $ctx['terms_html'] ?? '');

		$ownerId = (int) ($focus->column_fields['assigned_user_id'] ?? 0);
		$salesName = '';
		$salesPhone = '';
		if ($ownerId > 0) {
			$salesName = trim((string) getOwnerName($ownerId));
			try {
				$db = PearDatabase::getInstance();
				$urs = $db->pquery(
					'SELECT phone_mobile, phone_work, first_name, last_name FROM vtiger_users WHERE id = ?',
					array($ownerId)
				);
				if ($urs && $db->num_rows($urs) > 0) {
					$salesPhone = trim((string) ($db->query_result($urs, 0, 'phone_mobile') ?: $db->query_result($urs, 0, 'phone_work')));
					if ($salesName === '') {
						$salesName = trim((string) ($db->query_result($urs, 0, 'first_name') . ' ' . $db->query_result($urs, 0, 'last_name')));
					}
				}
			} catch (Exception $e) {
				// keep owner name only
			}
		}
		if ($salesName === '') {
			$salesName = '—';
		}

		$lines = self::buildLines($focus, $moduleName, $ctx);
		$subTotal = (float) ($ctx['sub_total'] ?? 0);
		$taxAmount = (float) ($ctx['tax_amount'] ?? 0);
		$discountAmount = (float) ($ctx['discount_amount'] ?? 0);
		$grandTotal = (float) ($ctx['grand_total'] ?? 0);
		$vatPercent = (float) ($ctx['vat_percent'] ?? 8);
		if ($vatPercent <= 0 || $vatPercent > 100) {
			$vatPercent = 8.0;
		}

		$lineSum = 0.0;
		$lineDiscountSum = 0.0;
		foreach ($lines as $line) {
			$lineSum += (float) $line['total'];
			$lineDiscountSum += (float) ($line['discount'] ?? 0);
		}
		if ($lineSum > 0) {
			$subTotal = $lineSum;
		}
		if ($discountAmount <= 0 && $lineDiscountSum > 0) {
			$discountAmount = $lineDiscountSum;
		}

		$payable = $subTotal - $discountAmount;
		if ($payable < 0) {
			$payable = 0.0;
		}

		$amountWords = '';
		if ($payable > 0) {
			$amountWords = Quotes_QuoteBaService_Helper::amountInWordsVi($payable);
		}
		if ($amountWords !== '' && !preg_match('/\.\/\.?\s*$/u', $amountWords)) {
			$amountWords = rtrim($amountWords, '.') . './.';
		}

		return array(
			'company_name' => $companyName,
			'company_address' => $address,
			'company_phone' => $phone,
			'logo_path' => $logoPath,
			'logo_url' => self::resolveLogoWebUrl($logoPath),
			'doc_title' => $docTitle,
			'doc_no_label' => $docNoLabel,
			'doc_no' => $docNo,
			'date_label' => $dateLabel,
			'customer' => $customer !== '' ? $customer : '—',
			'customer_phone' => $customerPhone !== '' ? $customerPhone : '—',
			'customer_address' => $customerAddress !== '' ? $customerAddress : '—',
			'notes' => $notes,
			'sales_name' => $salesName,
			'sales_phone' => $salesPhone !== '' ? $salesPhone : '—',
			'branch' => 'Hồ Chí Minh',
			'lines' => $lines,
			'sub_total' => $subTotal,
			'discount' => $discountAmount,
			'payable' => $payable,
			'amount_words' => $amountWords,
			'printed_at' => date('d/m/y, g:i A'),
		);
	}

	protected static function resolveLogoWebUrl($logoPath) {
		$relCandidates = array(
			'layouts/v7/modules/Quotes/resources/images/nguyenkhoa-excel-logo.png',
			'layouts/v7/resources/Images/nguyenkhoa-logo.png',
			'layouts/v7/skins/images/nguyenkhoa-logo.png',
		);
		$rel = '';
		foreach ($relCandidates as $candidate) {
			if (is_readable($candidate)) {
				$rel = $candidate;
				break;
			}
		}
		if ($rel === '') {
			$logoPath = (string) $logoPath;
			if ($logoPath !== '' && is_readable($logoPath)) {
				$cwd = rtrim((string) getcwd(), "/\\") . DIRECTORY_SEPARATOR;
				$norm = str_replace('\\', '/', $logoPath);
				$cwdNorm = str_replace('\\', '/', $cwd);
				if (strpos($norm, $cwdNorm) === 0) {
					$rel = ltrim(substr($norm, strlen($cwdNorm)), '/');
				}
			}
		}
		if ($rel === '') {
			return '';
		}
		return self::absoluteSiteUrl($rel);
	}

	protected static function absoluteSiteUrl($relPath) {
		global $site_URL;
		$base = rtrim((string) $site_URL, '/');
		if ($base === '') {
			$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
			$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
			$base = ($https ? 'https' : 'http') . '://' . $host;
		}
		return $base . '/' . ltrim(str_replace('\\', '/', (string) $relPath), '/');
	}

	/**
	 * @param array $data
	 * @param bool $autoPrint
	 * @return string
	 */
	public static function buildHtml(array $data, $autoPrint = false) {
		$h = function ($v) {
			return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
		};
		$money = function ($v) {
			return Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($v);
		};

		$rows = '';
		$stt = 0;
		if (empty($data['lines'])) {
			$rows = '<tr><td colspan="6" style="text-align:center;padding:12px;">Chưa có hàng hóa trong đơn hàng.</td></tr>';
		} else {
			foreach ($data['lines'] as $line) {
				++$stt;
				$qty = ((float) $line['qty'] == (int) $line['qty'])
					? (string) (int) $line['qty']
					: rtrim(rtrim(number_format((float) $line['qty'], 3, ',', ''), '0'), ',');
				$note = trim((string) ($line['note'] ?? $line['comment'] ?? ''));
				$rows .= '<tr>'
					. '<td class="c">' . $stt . '</td>'
					. '<td>' . $h($line['name']) . '</td>'
					. '<td class="c">' . $h($qty) . '</td>'
					. '<td class="r">' . $h($money($line['price'])) . '</td>'
					. '<td class="r">' . $h($money($line['total'])) . '</td>'
					. '<td>' . $h($note) . '</td>'
					. '</tr>';
			}
		}

		$logo = '';
		if (!empty($data['logo_url'])) {
			$logo = '<img class="mk-ph-logo" src="' . $h($data['logo_url']) . '" alt="NK" />';
		}

		$baseHref = self::absoluteSiteUrl('');
		$autoScript = '';
		if ($autoPrint) {
			$autoScript = '<script>window.addEventListener("load",function(){setTimeout(function(){try{document.title=" ";window.focus();window.print();}catch(e){}},180);});</script>';
		}

		return '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8" />'
			. '<meta name="viewport" content="width=device-width, initial-scale=1" />'
			. '<base href="' . $h(rtrim($baseHref, '/') . '/') . '" />'
			. '<title> </title>'
			. '<style>
@page{size:A4;margin:0}
@page :left{margin:0}
@page :right{margin:0}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:#fff}
body{padding:8mm;font:12.5px/1.4 "Times New Roman",Times,serif;color:#111}
.sheet{max-width:900px;margin:0 auto;display:flex;flex-direction:column;min-height:calc(297mm - 56mm);box-sizing:border-box}
.head{display:flex;gap:12px;align-items:flex-start;margin-bottom:10px}
.mk-ph-logo{width:170px;height:auto;object-fit:contain;flex:0 0 auto}
.head-main{flex:1;min-width:0}
.company{font-size:16px;font-weight:700;margin:0 0 4px}
.meta{color:#111;font-size:12px;margin:0}
.meta-label{color:#c44a1c;font-weight:700}
.title{text-align:center;margin:14px 0 4px;font-size:22px;font-weight:800;letter-spacing:.02em}
.docno{text-align:center;font-size:14px;font-weight:400;margin:0 0 14px}
.info{display:grid;grid-template-columns:1fr 1fr;column-gap:24px;row-gap:4px;margin-bottom:12px;align-items:start}
.info-col{display:flex;flex-direction:column;gap:4px;min-width:0}
.info-row{margin:0;display:grid;grid-template-columns:88px 1fr;align-items:start;min-height:1.45em}
.info-row b{display:inline;min-width:0;font-weight:700}
table{width:100%;border-collapse:collapse;margin:8px 0 2px}
th,td{border:1px solid #222;padding:6px 8px;vertical-align:top}
th{background:#f3f3f3;font-size:12px;font-weight:700}
td.c,th.c{text-align:center}
td.r,th.r{text-align:right}
.summary{display:block;margin-top:2px;width:100%}
.vat-between{margin:2px 0 4px;font-size:12px;font-style:normal;font-weight:700;color:#111;text-align:left}
.totals{width:100%;margin:0}
.totals-row{display:grid;grid-template-columns:7% 44% 8% 14% 15% 12%;align-items:baseline;margin:3px 0;font-size:13px;width:100%}
.totals-row .t-label{grid-column:1 / 5;font-weight:700;text-align:left}
.totals-row .t-value{grid-column:5;font-weight:400;text-align:right}
.totals-row .t-value.is-bold{font-weight:700}
.words{font-style:italic;font-weight:700;margin:8px 0 8px}
.signs{display:flex;gap:8px;margin:8px 0 0;align-items:flex-start;flex:0 0 auto;page-break-after:avoid;break-after:avoid}
.sign{flex:1;text-align:center}
.sign .date-above{margin:0 0 4px;font-weight:400;font-size:12px;line-height:1.3;min-height:1.3em}
.sign b{display:block;margin-bottom:2px;font-weight:700}
.sign span{font-size:10px;font-style:italic;color:#444}
.note-list{margin:0;padding-left:16px;font-size:10.5px;line-height:1.3}
.note-list li{margin:0 0 3px;page-break-inside:avoid;break-inside:avoid}
.mk-note{margin-top:auto;padding-top:6px;flex:0 0 auto;page-break-inside:avoid;break-inside:avoid;-webkit-column-break-inside:avoid}
.mk-note .note-title{font-weight:700;margin:0 0 3px;font-size:11px;page-break-after:avoid;break-after:avoid}
@media print{
html,body{margin:0!important;padding:8mm!important;height:auto!important}
.sheet{min-height:calc(297mm - 56mm)!important;height:auto!important}
.mk-note{margin-top:auto!important;padding-top:6px!important;page-break-inside:avoid!important;break-inside:avoid!important;page-break-before:avoid!important}
.mk-note .note-list,.mk-note .note-list li{page-break-inside:avoid!important;break-inside:avoid!important}
.signs{page-break-after:avoid!important}
.no-print{display:none!important}
}
</style></head><body><div class="sheet">'
			. '<div class="head">' . $logo
			. '<div class="head-main"><p class="company">' . $h($data['company_name']) . '</p>'
			. '<p class="meta"><span class="meta-label">Địa chỉ:</span> ' . $h($data['company_address']) . '</p>'
			. '<p class="meta"><span class="meta-label">Điện thoại:</span> ' . $h($data['company_phone']) . '</p></div></div>'
			. '<h1 class="title">' . $h($data['doc_title']) . '</h1>'
			. '<p class="docno">' . $h($data['doc_no_label'] . $data['doc_no']) . '</p>'
			. '<div class="info">'
			. '<div class="info-col">'
			. '<p class="info-row"><b>Khách Hàng:</b> <span>' . $h($data['customer']) . '</span></p>'
			. '<p class="info-row"><b>SĐT:</b> <span>' . $h($data['sales_phone'] !== '' ? $data['sales_phone'] : '—') . '</span></p>'
			. '<p class="info-row"><b>Địa chỉ:</b> <span>' . $h($data['customer_address'] !== '' ? $data['customer_address'] : '—') . '</span></p>'
			. '</div><div class="info-col">'
			. '<p class="info-row"><b>Chi nhánh:</b> <span>' . $h($data['branch']) . '</span></p>'
			. '<p class="info-row"><b>NVBH:</b> <span>' . $h($data['sales_name']) . '</span></p>'
			. '<p class="info-row"><b>Ghi chú:</b> <span>' . $h($data['notes'] !== '' ? $data['notes'] : '—') . '</span></p>'
			. '</div></div>'
			. '<table><thead><tr>'
			. '<th class="c" style="width:7%">STT</th><th style="width:44%">Tên Hàng</th>'
			. '<th class="c" style="width:8%">SL</th><th class="r" style="width:14%">Đơn Giá</th>'
			. '<th class="r" style="width:15%">Thành Tiền</th><th style="width:12%">Ghi Chú</th>'
			. '</tr></thead><tbody>' . $rows . '</tbody></table>'
			. '<p class="vat-between"><strong>Đơn giá này đã bao gồm VAT</strong></p>'
			. '<div class="summary"><div class="totals">'
			. '<div class="totals-row"><span class="t-label">Tổng Cộng:</span><span class="t-value is-bold">' . $h($money($data['sub_total'])) . '</span></div>'
			. '<div class="totals-row"><span class="t-label">Chiết Khấu:</span><span class="t-value">' . $h($money($data['discount'])) . '</span></div>'
			. '<div class="totals-row"><span class="t-label">Tổng Thanh Toán:</span><span class="t-value">' . $h($money($data['payable'])) . '</span></div>'
			. '</div>'
			. (!empty($data['amount_words']) ? '<p class="words">Tổng thanh toán bằng chữ: ' . $h($data['amount_words']) . '</p>' : '')
			. '</div>'
			. '<div class="signs">'
			. '<div class="sign"><div class="date-above">&nbsp;</div><b>Thủ Kho</b><span>(Ký và ghi rõ họ tên)</span></div>'
			. '<div class="sign"><div class="date-above">&nbsp;</div><b>Vận chuyển</b><span>(Ký và ghi rõ họ tên)</span></div>'
			. '<div class="sign"><div class="date-above">' . $h($data['date_label']) . '</div><b>Khách hàng</b><span>(Ký và ghi rõ họ tên)</span></div>'
			. '</div>'
			. '<div class="mk-note"><p class="note-title">Lưu ý:</p><ul class="note-list">'
			. '<li>Khi nhận hàng: Nếu có sai lệch về số lượng kiện hàng thực tế so với PGH / phiếu giao nhận của dịch vụ vận chuyển, hãy liên hệ ngay với NVKD để được giải quyết (chúng tôi chỉ giải quyết khiếu nại về giao nhận kiện hàng trong ngày Quý khách nhận được hàng).</li>'
			. '<li>Về đơn hàng: Chúng tôi chỉ giải quyết khiếu nại trong vòng 3 ngày kể từ ngày Quý khách nhận được hàng (Bao gồm tất cả các trường hợp về số lượng sản phẩm, tình trạng hàng hóa như: vỡ hỏng, móp méo, lỗi). Quý khách hãy cung cấp hình ảnh, video hàng hóa thực nhận cho NVKD để khiếu nại.</li>'
			. '</ul></div>'
			. '</div>' . $autoScript . '</body></html>';
	}

	/**
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @return Vtiger_PDF_TCPDF
	 */
	public static function build(CRMEntity $focus, $moduleName = 'SalesOrder') {
		return self::buildPhieuDatHangPdf($focus, $moduleName);
	}

	/**
	 * Legacy centered BÁO GIÁ layout (Quotes).
	 *
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @return Vtiger_PDF_TCPDF
	 */
	protected static function buildLegacyInvoice(CRMEntity $focus, $moduleName = 'Quotes') {
		$ctx = Quotes_QuoteExcelExport_Helper::getSaleExportContext($focus);
		$companyName = Quotes_QuoteExcelExport_Helper::nkCompanyName();
		$address = Quotes_QuoteExcelExport_Helper::nkAddress();
		$phone = Quotes_QuoteExcelExport_Helper::nkPhone();
		$logoPath = Quotes_QuoteExcelExport_Helper::resolveLogoPathPublic();
		if ($logoPath === '' && !empty($ctx['company']['logo_path'])) {
			$logoPath = $ctx['company']['logo_path'];
		}

		$docNo = $ctx['quote_no'] !== '' ? $ctx['quote_no'] : (($moduleName === 'SalesOrder' ? 'DH' : 'DH') . $focus->id);
		$dateLabel = Quotes_QuoteExcelExport_Helper::formatDateViLongPublic($ctx['quote_date']);
		$customer = trim((string) ($ctx['receiver'] ?? ''));
		if ($customer === '') {
			$customer = trim((string) ($ctx['account_name'] ?? ''));
		}
		$customerPhone = (string) ($ctx['phone'] ?? '');
		$customerAddress = (string) ($ctx['address'] ?? '');
		$notes = isset($ctx['notes']) ? trim((string) $ctx['notes']) : Quotes_QuoteExcelExport_Helper::resolveExportNotesPublic($focus, $ctx['terms_html'] ?? '');
		$salesPhone = !empty($ctx['sales_phone']) ? trim((string) $ctx['sales_phone']) : '';

		$lines = self::buildLines($focus, $moduleName, $ctx);
		$subTotal = (float) ($ctx['sub_total'] ?? 0);
		$taxAmount = (float) ($ctx['tax_amount'] ?? 0);
		$discountAmount = (float) ($ctx['discount_amount'] ?? 0);
		$grandTotal = (float) ($ctx['grand_total'] ?? 0);
		$vatPercent = (float) ($ctx['vat_percent'] ?? 8);
		if ($vatPercent <= 0 || $vatPercent > 100) {
			$vatPercent = 8.0;
		}

		$lineSum = 0.0;
		foreach ($lines as $line) {
			$lineSum += (float) $line['total'];
		}
		if ($lineSum > 0) {
			$subTotal = $lineSum;
			if ($taxAmount <= 0 && $grandTotal > ($subTotal - $discountAmount)) {
				$derived = $grandTotal - ($subTotal - $discountAmount);
				if ($derived <= ($subTotal * 0.5)) {
					$taxAmount = $derived;
				}
			}
			if ($taxAmount <= 0) {
				$taxAmount = round(($subTotal - $discountAmount) * $vatPercent / 100);
			}
			if ($taxAmount > ($subTotal * 0.5)) {
				$taxAmount = round(($subTotal - $discountAmount) * $vatPercent / 100);
			}
			$grandTotal = $subTotal - $discountAmount + $taxAmount;
		} elseif ($grandTotal <= 0) {
			$grandTotal = $subTotal - $discountAmount + $taxAmount;
		}

		$amountWords = trim((string) ($ctx['amount_words'] ?? ''));
		if ($amountWords === '' && $grandTotal > 0) {
			$amountWords = Quotes_QuoteBaService_Helper::amountInWordsVi($grandTotal);
		}

		$pdf = new Vtiger_PDF_TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetCreator('Nguyên Khoa');
		$pdf->SetAuthor($companyName);
		$isQuote = ($moduleName === 'Quotes');
		$docTitle = $isQuote ? 'BÁO GIÁ' : 'HÓA ĐƠN ĐẶT HÀNG';
		$docNoLabel = $isQuote ? 'Mã báo giá: ' : 'Mã đơn hàng: ';
		$pdf->SetTitle($docTitle . ' ' . $docNo);
		$pdf->SetMargins(14, 12, 14);
		$pdf->SetAutoPageBreak(true, 14);
		$pdf->AddPage();
		$pdf->SetFont(self::FONT, '', 10);

		$margins = $pdf->getMargins();
		$pageW = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
		$x = $margins['left'];
		$y = $pdf->GetY();

		if ($logoPath !== '' && is_readable($logoPath) && Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($logoPath)) {
			$logoW = 58;
			$pdf->Image($logoPath, ($pdf->getPageWidth() - $logoW) / 2, $y, $logoW, 0, '', '', '', false, 300);
			$pdf->SetY($y + 42);
		}

		$pdf->SetFont(self::FONT, 'B', 12);
		$pdf->Cell($pageW, 6, self::utf($companyName), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 9);
		$pdf->MultiCell($pageW, 5, self::utf('Địa chỉ: ' . $address), 0, 'C', false, 1);
		$pdf->Cell($pageW, 5, self::utf('Điện thoại: ' . $phone), 0, 1, 'C');
		$pdf->Ln(4);

		$pdf->SetFont(self::FONT, 'B', 13);
		$pdf->Cell($pageW, 7, self::utf($docTitle), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 10);
		$pdf->Cell($pageW, 5, self::utf($docNoLabel . $docNo), 0, 1, 'C');
		$pdf->Cell($pageW, 5, self::utf($dateLabel), 0, 1, 'C');
		$pdf->Ln(4);

		$pdf->SetFont(self::FONT, '', 10);
		self::writeLabelValue($pdf, $pageW, 'Khách hàng:', $customer !== '' ? $customer : '—');
		self::writeLabelValue($pdf, $pageW, 'SĐT:', $salesPhone !== '' ? $salesPhone : '—');
		self::writeLabelValue($pdf, $pageW, 'Địa chỉ:', $customerAddress !== '' ? $customerAddress : '—');
		$pdf->Ln(3);

		$colItem = $pageW * 0.62;
		$colQty = $pageW * 0.14;
		$colMoney = $pageW * 0.24;
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont(self::FONT, 'B', 10);
		self::drawSolidLine($pdf, $x, $pdf->GetY(), $x + $pageW);
		$pdf->Cell($colItem, 8, self::utf('Đơn giá'), 0, 0, 'L', false);
		$pdf->Cell($colQty, 8, self::utf('SL'), 0, 0, 'C', false);
		$pdf->Cell($colMoney, 8, self::utf('T.Tiền'), 0, 1, 'R', false);
		self::drawSolidLine($pdf, $x, $pdf->GetY(), $x + $pageW);

		$pdf->SetFont(self::FONT, '', 10);
		if (empty($lines)) {
			$pdf->Cell($pageW, 10, self::utf('Chưa có hàng hóa trong đơn hàng.'), 0, 1, 'C');
		} else {
			foreach ($lines as $line) {
				$name = $line['name'];
				if ($line['unit'] !== '' && stripos($name, '(' . $line['unit'] . ')') === false) {
					$name .= ' (' . $line['unit'] . ')';
				}
				$priceText = Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($line['price']);
				$totalText = Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($line['total']);
				$qtyText = ((float) $line['qty'] == (int) $line['qty'])
					? (string) (int) $line['qty']
					: rtrim(rtrim(number_format((float) $line['qty'], 3, ',', ''), '0'), ',');

				$startY = $pdf->GetY();
				if ($startY > 260) {
					$pdf->AddPage();
					$startY = $pdf->GetY();
				}

				$pdf->SetFont(self::FONT, '', 10);
				$pdf->MultiCell($pageW, 5, self::utf($name), 0, 'L', false, 1, $x, $startY, true, 0, false, true, 0, 'T', false);
				$valuesY = $pdf->GetY();
				$pdf->SetXY($x, $valuesY);
				$pdf->Cell($colItem, 5, self::utf($priceText), 0, 0, 'L');
				$pdf->Cell($colQty, 5, self::utf($qtyText), 0, 0, 'C');
				$pdf->Cell($colMoney, 5, self::utf($totalText), 0, 1, 'R');
				self::drawDashedLine($pdf, $x, $pdf->GetY(), $x + $pageW, array(80, 80, 80));
			}
		}

		$pdf->Ln(4);

		$totals = array(
			array('Tổng tiền hàng', $subTotal, false),
			array('Thuế', $taxAmount, false),
			array('Chiết khấu', $discountAmount, false),
			array('Tổng thanh toán', $grandTotal, true),
		);

		$leftW = $pageW * 0.48;
		$rightW = $pageW * 0.52;
		$totalsStartY = $pdf->GetY();

		$ty = $totalsStartY;
		foreach ($totals as $item) {
			$pdf->SetXY($x + $leftW, $ty);
			$pdf->SetFont(self::FONT, 'B', $item[2] ? 11 : 10);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->Cell($rightW * 0.55, 6, self::utf($item[0] . ':'), 0, 0, 'L');
			$pdf->Cell($rightW * 0.45, 6, self::utf(Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($item[1])), 0, 1, 'R');
			$ty += 6;
		}
		$pdf->SetTextColor(0, 0, 0);

		$pdf->SetY(max($ty, $totalsStartY) + 2);
		if ($amountWords !== '') {
			$pdf->SetFont(self::FONT, 'I', 10);
			$pdf->MultiCell($leftW + 10, 5, self::utf('(' . $amountWords . ')'), 0, 'L', false, 1);
		}
		$pdf->Ln(6);

		$pdf->SetFont(self::FONT, 'I', 10);
		$pdf->Cell($pageW, 6, self::utf('Cảm ơn và hẹn gặp lại!'), 0, 1, 'C');

		return $pdf;
	}

	/**
	 * PHIẾU ĐẶT HÀNG layout (Sales Order).
	 *
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @return Vtiger_PDF_TCPDF
	 */
	protected static function buildPhieuDatHangPdf(CRMEntity $focus, $moduleName = 'SalesOrder') {
		$data = self::prepareDocumentData($focus, $moduleName);
		$companyName = $data['company_name'];
		$address = $data['company_address'];
		$phone = $data['company_phone'];
		$logoPath = $data['logo_path'];
		$docTitle = $data['doc_title'];
		$docNoLabel = $data['doc_no_label'];
		$docNo = $data['doc_no'];
		$dateLabel = $data['date_label'];
		$customer = $data['customer'];
		$customerPhone = $data['customer_phone'];
		$customerAddress = $data['customer_address'];
		$notes = $data['notes'];
		$salesName = $data['sales_name'];
		$salesPhone = $data['sales_phone'];
		$branch = $data['branch'];
		$lines = $data['lines'];
		$subTotal = $data['sub_total'];
		$discountAmount = $data['discount'];
		$payable = $data['payable'];
		$amountWords = $data['amount_words'];
		$printedAt = $data['printed_at'];

		$pdf = new Vtiger_PDF_TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetCreator('Nguyên Khoa');
		$pdf->SetAuthor($companyName);
		$pdf->SetTitle($docTitle . ' ' . $docNo);
		$pdf->SetMargins(12, 10, 12);
		$pdf->SetAutoPageBreak(true, 12);
		$pdf->AddPage();
		$pdf->SetFont(self::FONT, '', 10);

		$margins = $pdf->getMargins();
		$pageW = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
		$x = $margins['left'];
		$y = $pdf->GetY();

		// —— Header: logo trái + thông tin công ty ——
		$logoW = 44;
		$logoH = 0;
		$headerRightX = $x;
		if ($logoPath !== '' && is_readable($logoPath) && Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($logoPath)) {
			$pdf->Image($logoPath, $x, $y, $logoW, 0, '', '', '', false, 300);
			$headerRightX = $x + $logoW + 4;
			$logoH = 22;
		}

		$pdf->SetXY($headerRightX, $y);
		$pdf->SetFont(self::FONT, 'B', 12);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell($pageW - ($headerRightX - $x), 6, self::utf($companyName), 0, 'L', false, 1);

		$pdf->SetX($headerRightX);
		$pdf->SetFont(self::FONT, '', 8);
		$metaW = $pageW - ($headerRightX - $x);
		$labelW = 24;

		// Company: only the labels in red, values in black.
		$pdf->SetFont(self::FONT, 'B', 8);
		$pdf->SetTextColor(self::ACCENT[0], self::ACCENT[1], self::ACCENT[2]);
		$pdf->SetX($headerRightX);
		$pdf->Cell($labelW, 4, self::utf('Địa chỉ:'), 0, 0, 'L');
		$pdf->SetFont(self::FONT, '', 8);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell($metaW - $labelW, 4, self::utf($address), 0, 'L', false, 1);

		$pdf->SetFont(self::FONT, 'B', 8);
		$pdf->SetTextColor(self::ACCENT[0], self::ACCENT[1], self::ACCENT[2]);
		$pdf->SetX($headerRightX);
		$pdf->Cell($labelW, 4, self::utf('Điện thoại:'), 0, 0, 'L');
		$pdf->SetFont(self::FONT, '', 8);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell($metaW - $labelW, 4, self::utf($phone), 0, 'L', false, 1);

		$afterHeaderY = max($pdf->GetY(), $y + max($logoH, 18)) + 4;
		$pdf->SetY($afterHeaderY);

		// —— Title ——
		$pdf->SetFont(self::FONT, 'B', 16);
		$pdf->Cell($pageW, 8, self::utf($docTitle), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 11);
		$pdf->Cell($pageW, 6, self::utf($docNoLabel . $docNo), 0, 1, 'C');
		$pdf->Ln(3);

		// —— Customer / staff two columns ——
		$colW = $pageW / 2;
		$infoStartY = $pdf->GetY();
		/* Trái: KH / SĐT / Địa chỉ — Phải: Chi nhánh / NVBH / Ghi chú (không dòng trắng) */
		$leftLines = array(
			array('Khách Hàng:', $customer !== '' ? $customer : '—'),
			array('SĐT:', $salesPhone !== '' ? $salesPhone : '—'),
			array('Địa chỉ:', $customerAddress !== '' ? $customerAddress : '—'),
		);
		$rightLines = array(
			array('Chi nhánh:', $branch),
			array('NVBH:', $salesName),
			array('Ghi chú:', $notes !== '' ? $notes : '—'),
		);

		$leftY = $infoStartY;
		foreach ($leftLines as $row) {
			$pdf->SetXY($x, $leftY);
			self::writeInlineLabelValue($pdf, $colW - 4, $row[0], $row[1]);
			$leftY = $pdf->GetY() + 1;
		}

		$rightY = $infoStartY;
		foreach ($rightLines as $row) {
			$pdf->SetXY($x + $colW, $rightY);
			self::writeInlineLabelValue($pdf, $colW - 2, $row[0], $row[1]);
			$rightY = $pdf->GetY() + 1;
		}
		$pdf->SetY(max($leftY, $rightY) + 3);

		// —— Items table ——
		$cols = array(
			array('w' => $pageW * 0.07, 'label' => 'STT', 'align' => 'C'),
			array('w' => $pageW * 0.44, 'label' => 'Tên Hàng', 'align' => 'L'),
			array('w' => $pageW * 0.08, 'label' => 'SL', 'align' => 'C'),
			array('w' => $pageW * 0.14, 'label' => 'Đơn Giá', 'align' => 'R'),
			array('w' => $pageW * 0.15, 'label' => 'Thành Tiền', 'align' => 'R'),
			array('w' => $pageW * 0.12, 'label' => 'Ghi Chú', 'align' => 'L'),
		);
		$sumW = 0;
		foreach ($cols as $c) {
			$sumW += $c['w'];
		}
		if (abs($sumW - $pageW) > 0.2) {
			$cols[1]['w'] += ($pageW - $sumW);
		}

		$pdf->SetFont(self::FONT, 'B', 9);
		$pdf->SetFillColor(245, 245, 245);
		foreach ($cols as $i => $c) {
			$pdf->Cell($c['w'], 7, self::utf($c['label']), 1, $i === count($cols) - 1 ? 1 : 0, $c['align'], true);
		}

		$pdf->SetFont(self::FONT, '', 9);
		if (empty($lines)) {
			$pdf->Cell($pageW, 8, self::utf('Chưa có hàng hóa trong đơn hàng.'), 1, 1, 'C');
		} else {
			$stt = 0;
			foreach ($lines as $line) {
				++$stt;
				if ($pdf->GetY() > 250) {
					$pdf->AddPage();
					$pdf->SetFont(self::FONT, 'B', 9);
					foreach ($cols as $i => $c) {
						$pdf->Cell($c['w'], 7, self::utf($c['label']), 1, $i === count($cols) - 1 ? 1 : 0, $c['align'], true);
					}
					$pdf->SetFont(self::FONT, '', 9);
				}

				$name = $line['name'];
				$qtyText = ((float) $line['qty'] == (int) $line['qty'])
					? (string) (int) $line['qty']
					: rtrim(rtrim(number_format((float) $line['qty'], 3, ',', ''), '0'), ',');
				$priceText = Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($line['price']);
				$totalText = Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($line['total']);
				$noteText = trim((string) ($line['note'] ?? $line['comment'] ?? ''));

				$rowH = max(7, self::estimateWrappedHeight($pdf, $cols[1]['w'], $name, 4.2));
				$cells = array(
					(string) $stt,
					$name,
					$qtyText,
					$priceText,
					$totalText,
					$noteText,
				);
				$startY = $pdf->GetY();
				$cx = $x;
				foreach ($cols as $i => $c) {
					$pdf->MultiCell($c['w'], $rowH, self::utf($cells[$i]), 1, $c['align'], false, 0, $cx, $startY, true, 0, false, true, $rowH, 'M', false);
					$cx += $c['w'];
				}
				$pdf->SetXY($x, $startY + $rowH);
			}
		}

		$pdf->Ln(0.5);
		$pdf->SetFont(self::FONT, 'B', 9);
		$pdf->MultiCell($pageW, 4.5, self::utf('Đơn giá này đã bao gồm VAT'), 0, 'L', false, 1);
		$pdf->Ln(0.5);

		// —— Totals: chữ trái, số căn cột Thành Tiền ——
		// boldValue: true = số Tổng Cộng đậm; false = số thường (Tổng Thanh Toán không đậm)
		$thanhTienX = $x;
		for ($ci = 0; $ci < 4; $ci++) {
			$thanhTienX += $cols[$ci]['w'];
		}
		$thanhTienW = $cols[4]['w'];
		$labelW = $thanhTienX - $x;
		$totals = array(
			array('Tổng Cộng:', $subTotal, true),
			array('Chiết Khấu:', $discountAmount, false),
			array('Tổng Thanh Toán:', $payable, false),
		);
		foreach ($totals as $item) {
			$pdf->SetX($x);
			$pdf->SetFont(self::FONT, 'B', 10);
			$pdf->Cell($labelW, 6, self::utf($item[0]), 0, 0, 'L');
			$pdf->SetFont(self::FONT, $item[2] ? 'B' : '', 10);
			$pdf->Cell(
				$thanhTienW,
				6,
				self::utf(Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($item[1])),
				0,
				1,
				'R'
			);
		}

		if ($amountWords !== '') {
			$pdf->Ln(1);
			$pdf->SetFont(self::FONT, 'BI', 10);
			$pdf->MultiCell($pageW, 5, self::utf('Tổng thanh toán bằng chữ: ' . $amountWords), 0, 'L', false, 1);
		}

		$pdf->Ln(6);

		// —— Signatures: ngày nằm trên cột Khách hàng (như mẫu) ——
		$sigW = $pageW / 3;
		$sigY = $pdf->GetY();
		$sigs = array('Thủ Kho', 'Vận chuyển', 'Khách hàng');
		foreach ($sigs as $i => $label) {
			$cx = $x + $i * $sigW;
			$pdf->SetXY($cx, $sigY);
			if ($i === 2) {
				$pdf->SetFont(self::FONT, '', 10);
				$pdf->Cell($sigW, 5, self::utf($dateLabel), 0, 2, 'C');
				$pdf->SetX($cx);
			} else {
				$pdf->Cell($sigW, 5, '', 0, 2, 'C');
				$pdf->SetX($cx);
			}
			$pdf->SetFont(self::FONT, 'B', 10);
			$pdf->Cell($sigW, 5, self::utf($label), 0, 2, 'C');
			$pdf->SetX($cx);
			$pdf->SetFont(self::FONT, 'I', 8);
			$pdf->Cell($sigW, 4, self::utf('(Ký và ghi rõ họ tên)'), 0, 0, 'C');
		}
		$pdf->SetY($sigY + 16);

		/* Lưu ý: neo gần cuối tờ nhưng chừa đủ chỗ cho cả 2 bullet — không AddPage */
		$note1 = '- Khi nhận hàng: Nếu có sai lệch về số lượng kiện hàng thực tế so với PGH / phiếu giao nhận của dịch vụ vận chuyển, hãy liên hệ ngay với NVKD để được giải quyết (chúng tôi chỉ giải quyết khiếu nại về giao nhận kiện hàng trong ngày Quý khách nhận được hàng).';
		$note2 = '- Về đơn hàng: Chúng tôi chỉ giải quyết khiếu nại trong vòng 3 ngày kể từ ngày Quý khách nhận được hàng (Bao gồm tất cả các trường hợp về số lượng sản phẩm, tình trạng hàng hóa như: vỡ hỏng, móp méo, lỗi). Quý khách hãy cung cấp hình ảnh, video hàng hóa thực nhận cho NVKD để khiếu nại.';
		$noteLineH = 3.6;
		$noteTitleH = 4.2;
		$margins = $pdf->getMargins();
		$pageH = $pdf->getPageHeight();
		$noteBlockH = $noteTitleH
			+ self::estimateWrappedHeight($pdf, $pageW, $note1, $noteLineH)
			+ self::estimateWrappedHeight($pdf, $pageW, $note2, $noteLineH)
			+ 3;
		/* Chừa lề đáy + buffer để 2 bullet không tràn trang */
		$bottomY = $pageH - $margins['bottom'] - $noteBlockH - 14;
		$curY = $pdf->GetY();
		if ($curY < $bottomY) {
			$pdf->SetY($bottomY);
		} elseif ($curY + $noteBlockH > $pageH - $margins['bottom'] - 4) {
			$pullUp = ($curY + $noteBlockH) - ($pageH - $margins['bottom'] - 4) + 2;
			$pdf->SetY(max($margins['top'] + 4, $curY - $pullUp));
		}

		$pdf->SetFont(self::FONT, 'B', 9);
		$pdf->Cell($pageW, $noteTitleH, self::utf('Lưu ý:'), 0, 1, 'L');
		$pdf->SetFont(self::FONT, '', 8);
		$pdf->MultiCell($pageW, $noteLineH, self::utf($note1), 0, 'L', false, 1);
		$pdf->MultiCell($pageW, $noteLineH, self::utf($note2), 0, 'L', false, 1);

		return $pdf;
	}

	protected static function writeInlineLabelValue(Vtiger_PDF_TCPDF $pdf, $width, $label, $value) {
		if ((string) $label === '' && (string) $value === '') {
			$pdf->Cell(max(20, (float) $width), 5, '', 0, 1, 'L');
			return;
		}
		$labelW = 24;
		$pdf->SetFont(self::FONT, 'B', 9);
		$pdf->Cell($labelW, 5, self::utf($label), 0, 0, 'L');
		$pdf->SetFont(self::FONT, '', 9);
		$pdf->MultiCell(max(20, $width - $labelW), 5, self::utf($value), 0, 'L', false, 1);
	}

	protected static function estimateWrappedHeight(Vtiger_PDF_TCPDF $pdf, $width, $text, $lineH) {
		$text = trim((string) $text);
		if ($text === '') {
			return $lineH;
		}
		$nb = method_exists($pdf, 'getNumLines') ? (int) $pdf->getNumLines(self::utf($text), $width) : 1;
		if ($nb < 1) {
			$nb = 1;
		}
		return max($lineH, $nb * $lineH);
	}

	/**
	 * Draw a solid horizontal rule (header separators).
	 *
	 * @param Vtiger_PDF_TCPDF $pdf
	 * @param float $x1
	 * @param float $y
	 * @param float $x2
	 * @param int[] $rgb
	 */
	protected static function drawSolidLine(Vtiger_PDF_TCPDF $pdf, $x1, $y, $x2, $rgb = array(17, 17, 17)) {
		$pdf->SetDrawColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
		if (method_exists($pdf, 'SetLineStyle')) {
			$pdf->SetLineStyle(array(
				'width' => 0.35,
				'cap' => 'butt',
				'join' => 'miter',
				'dash' => 0,
				'color' => array((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]),
			));
		} else {
			$pdf->SetLineWidth(0.35);
		}
		$pdf->Line($x1, $y, $x2, $y);
	}

	/**
	 * Draw a dashed horizontal rule (invoice separators).
	 *
	 * @param Vtiger_PDF_TCPDF $pdf
	 * @param float $x1
	 * @param float $y
	 * @param float $x2
	 * @param int[] $rgb
	 */
	protected static function drawDashedLine(Vtiger_PDF_TCPDF $pdf, $x1, $y, $x2, $rgb = array(17, 17, 17)) {
		$pdf->SetDrawColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
		if (method_exists($pdf, 'SetLineStyle')) {
			$pdf->SetLineStyle(array(
				'width' => 0.55,
				'cap' => 'butt',
				'join' => 'miter',
				'dash' => '1.8,1.1',
				'color' => array((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]),
			));
			$pdf->Line($x1, $y, $x2, $y);
			$pdf->SetLineStyle(array(
				'width' => 0.2,
				'cap' => 'butt',
				'join' => 'miter',
				'dash' => 0,
				'color' => array(0, 0, 0),
			));
			return;
		}
		$pdf->SetLineWidth(0.55);
		$dash = 1.8;
		$gap = 1.1;
		$x = $x1;
		while ($x < $x2) {
			$xEnd = min($x + $dash, $x2);
			$pdf->Line($x, $y, $xEnd, $y);
			$x = $xEnd + $gap;
		}
		$pdf->SetLineWidth(0.2);
	}

	protected static function writeLabelValue(Vtiger_PDF_TCPDF $pdf, $pageW, $label, $value) {
		$labelW = 28;
		$pdf->SetFont(self::FONT, 'B', 10);
		$pdf->Cell($labelW, 5, self::utf($label), 0, 0, 'L');
		$pdf->SetFont(self::FONT, '', 10);
		$pdf->MultiCell($pageW - $labelW, 5, self::utf($value), 0, 'L', false, 1);
	}

	/** Centered note/terms block (Điều khoản hợp đồng / Ghi chú). */
	protected static function writeCenteredNote(Vtiger_PDF_TCPDF $pdf, $pageW, $label, $value) {
		$pdf->SetFont(self::FONT, 'B', 10);
		$pdf->Cell($pageW, 5, self::utf($label), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 10);
		$plain = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		if ($plain === '') {
			$plain = '—';
		}
		$pdf->MultiCell($pageW, 5, self::utf($plain), 0, 'C', false, 1);
	}

	protected static function utf($text) {
		return (string) $text;
	}

	protected static function buildLines(CRMEntity $focus, $moduleName, array $ctx) {
		$associated = getAssociatedProducts($moduleName, $focus);
		$lines = array();
		$index = 0;
		$focusProduct = CRMEntity::getInstance('Products');
		$focusService = CRMEntity::getInstance('Services');

		foreach ($associated as $productLineItem) {
			++$index;
			if (empty($productLineItem['hdnProductId' . $index])) {
				continue;
			}
			$productId = $productLineItem['hdnProductId' . $index];
			$entityType = (string) ($productLineItem['entityType' . $index] ?? 'Products');
			$usageUnit = '';
			$productCode = '';
			if (!empty($productId)) {
				try {
					if (strcasecmp($entityType, 'Services') === 0) {
						$focusService->retrieve_entity_info($productId, 'Services');
						$usageUnit = decode_html($focusService->column_fields['service_usageunit'] ?? '');
						$productCode = decode_html($focusService->column_fields['service_no'] ?? '');
					} else {
						$focusProduct->retrieve_entity_info($productId, 'Products');
						$usageUnit = decode_html($focusProduct->column_fields['usageunit'] ?? '');
						$productCode = decode_html(
							$focusProduct->column_fields['productcode']
							?? $focusProduct->column_fields['product_no']
							?? ''
						);
					}
				} catch (Exception $e) {
					$usageUnit = '';
					$productCode = '';
				}
			}
			if ($productCode === '' && !empty($productLineItem['hdnProductcode' . $index])) {
				$productCode = decode_html($productLineItem['hdnProductcode' . $index]);
			}
			if ($productCode === '' && !empty($productId)) {
				try {
					$db = PearDatabase::getInstance();
					$rs = $db->pquery(
						'SELECT sku FROM vtiger_productsservices WHERE productsservicesid = ? LIMIT 1',
						array($productId)
					);
					if ($rs && $db->num_rows($rs) > 0) {
						$productCode = trim((string) $db->query_result($rs, 0, 'sku'));
					}
				} catch (Exception $e) {
					$productCode = $productCode;
				}
			}

			$quantity = (float) ($productLineItem['qty' . $index] ?? 0);
			$listPrice = (float) ($productLineItem['listPrice' . $index] ?? 0);
			$discount = (float) ($productLineItem['discountTotal' . $index] ?? 0);
			$productName = decode_html($productLineItem['productName' . $index] ?? '');
			$productTotal = (float) ($productLineItem['productTotal' . $index] ?? 0);
			$totalAfterDiscount = (float) ($productLineItem['totalAfterDiscount' . $index] ?? 0);
			if ($quantity <= 0) {
				$quantity = 1.0;
			}
			if ($listPrice <= 0 && $productTotal > 0) {
				$listPrice = $productTotal / $quantity;
			}
			if ($listPrice <= 0 && $totalAfterDiscount > 0) {
				$listPrice = ($totalAfterDiscount + $discount) / $quantity;
			}
			$total = $quantity * $listPrice;
			if ($total <= 0 && $productTotal > 0) {
				$total = $productTotal;
			}
			if ($total <= 0 && $totalAfterDiscount > 0) {
				$total = $totalAfterDiscount + $discount;
			}
			if ($listPrice <= 0 && $quantity > 0 && $total > 0) {
				$listPrice = $total / $quantity;
			}

			$lineComment = '';
			if (!empty($productLineItem['comment' . $index])) {
				$lineComment = decode_html($productLineItem['comment' . $index]);
			}
			$lines[] = array(
				'name' => $productName,
				'code' => trim((string) $productCode),
				'unit' => trim((string) $usageUnit),
				'qty' => $quantity,
				'price' => $listPrice,
				'total' => $total,
				'discount' => $discount,
				'note' => trim((string) $lineComment),
			);
		}

		if (empty($lines) && !empty($ctx['lines']) && is_array($ctx['lines'])) {
			foreach ($ctx['lines'] as $line) {
				$lines[] = array(
					'name' => (string) ($line['name'] ?? ''),
					'code' => (string) ($line['code'] ?? ''),
					'unit' => (string) ($line['unit'] ?? ''),
					'qty' => (float) ($line['qty'] ?? 1),
					'price' => (float) ($line['price'] ?? 0),
					'total' => (float) ($line['total'] ?? 0),
					'discount' => (float) ($line['discount'] ?? 0),
					'note' => (string) ($line['note'] ?? $line['comment'] ?? ''),
				);
			}
		}

		$headerSub = (float) ($ctx['sub_total'] ?? 0);
		$lineMoneySum = 0.0;
		foreach ($lines as $line) {
			$lineMoneySum += (float) $line['total'];
		}
		if ($lineMoneySum <= 0 && $headerSub > 0 && count($lines) > 0) {
			$share = $headerSub / count($lines);
			foreach ($lines as &$line) {
				$qty = $line['qty'] > 0 ? $line['qty'] : 1;
				$line['total'] = $share;
				$line['price'] = $share / $qty;
			}
			unset($line);
		}

		return $lines;
	}
}
