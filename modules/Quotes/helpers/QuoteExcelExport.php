<?php
/*+***********************************************************************************
 * Quotes Excel export — BÁO GIÁ layout (Sale export).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';

class Quotes_QuoteExcelExport_Helper {

	const FONT = 'Arial';
	const COL_FIRST = 'B';
	const COL_LAST = 'H';

	// Nguyên Khoa defaults (used when org profile still has TDB placeholders)
	const NK_COMPANY_NAME = 'nguyenlieuphachemt';
	const NK_ADDRESS = '6/24 Đường số 3, Cư Xá Lữ Gia, Phú Thọ, Hồ Chí Minh';
	const NK_PHONE = '0973969498';

	protected static function resolveNguyenKhoaLogoPath() {
		global $root_directory;
		$candidates = array(
			'layouts/v7/modules/Quotes/resources/images/nguyenkhoa-excel-logo.png',
			'layouts/v7/resources/Images/nguyenkhoa-logo.png',
			'layouts/v7/skins/images/nguyenkhoa-logo.png',
		);
		$roots = array();
		if (!empty($root_directory)) {
			$roots[] = rtrim((string) $root_directory, "/\\");
		}
		$repoRoot = realpath(dirname(__FILE__) . '/../../..');
		if ($repoRoot) {
			$roots[] = $repoRoot;
		}
		$cwd = getcwd();
		if ($cwd) {
			$roots[] = rtrim((string) $cwd, "/\\");
		}
		foreach ($candidates as $rel) {
			foreach (array_values(array_unique($roots)) as $root) {
				$abs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
				if (Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($abs)) {
					return $abs;
				}
			}
		}
		return '';
	}

	protected static function applyNguyenKhoaCompanyBranding(array $company) {
		$logo = self::resolveNguyenKhoaLogoPath();
		if ($logo !== '') {
			$company['logo_path'] = $logo;
		}

		// Preview always uses NK identity — keep Excel download identical.
		$company['company_name'] = self::NK_COMPANY_NAME;
		$company['address'] = self::NK_ADDRESS;
		$company['phone'] = self::NK_PHONE;
		return $company;
	}

	protected static function formatMoneyVn($amount) {
		return number_format((float) $amount, 0, ',', '.');
	}

	protected static function formatDateViLong($dateDmY) {
		$raw = trim((string) $dateDmY);
		if ($raw === '') {
			return 'Ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y');
		}
		// Prefer explicit d/m/Y (or d-m-Y) — never rely on strtotime MM/DD ambiguity.
		if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
			$day = (int) $m[1];
			$month = (int) $m[2];
			$year = (int) $m[3];
			if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
				return 'Ngày ' . sprintf('%02d', $day) . ' tháng ' . sprintf('%02d', $month) . ' năm ' . $year;
			}
		}
		// DB datetime: 2026-07-11 12:34:56
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			return 'Ngày ' . $m[3] . ' tháng ' . $m[2] . ' năm ' . $m[1];
		}
		$ts = strtotime($raw);
		if ($ts) {
			return 'Ngày ' . date('d', $ts) . ' tháng ' . date('m', $ts) . ' năm ' . date('Y', $ts);
		}
		return $raw;
	}

	/**
	 * Build d/m/Y from CRM datetime / date fields without MM/DD swap.
	 */
	protected static function toDayMonthYear($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return date('d/m/Y');
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			return $m[3] . '/' . $m[2] . '/' . $m[1];
		}
		if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
			$a = (int) $m[1];
			$b = (int) $m[2];
			$year = $m[3];
			// If first part > 12 it must be day (d/m/Y).
			if ($a > 12 && $b >= 1 && $b <= 12) {
				return sprintf('%02d/%02d/%s', $a, $b, $year);
			}
			// If second part > 12 it must be m/d/Y → swap to d/m/Y.
			if ($b > 12 && $a >= 1 && $a <= 12) {
				return sprintf('%02d/%02d/%s', $b, $a, $year);
			}
			// Ambiguous: treat as d/m/Y (VN).
			return sprintf('%02d/%02d/%s', $a, $b, $year);
		}
		$ts = strtotime($raw);
		if ($ts) {
			return date('d/m/Y', $ts);
		}
		return date('d/m/Y');
	}

	protected static function setTextCell(PHPExcel_Worksheet $sheet, $cell, $value) {
		$sheet->setCellValueExplicit($cell, (string) $value, PHPExcel_Cell_DataType::TYPE_STRING);
	}

	/**
	 * Prefer SO/Quote description (Ghi chú). Ignore default English terms boilerplate.
	 */
	protected static function resolveExportNotes(CRMEntity $focus, $termsHtml = '') {
		$notes = trim(self::decode($focus->column_fields['description'] ?? ''));
		if ($notes === '') {
			$notes = trim(self::stripTermsHtml($termsHtml));
			$notes = preg_replace('/^\s*1\.\s*Thông tin sản phẩm:\s*/iu', '', (string) $notes);
			$notes = preg_replace('/^\s*2\.\s*Điều khoản.*$/ium', '', (string) $notes);
			$notes = trim((string) $notes);
		}
		if ($notes !== '' && (
			stripos($notes, 'Unless otherwise agreed') !== false
			|| stripos($notes, 'all invoices are payable') !== false
		)) {
			$notes = '';
		}
		return $notes;
	}

	protected static function colRange($row, $from = self::COL_FIRST, $to = self::COL_LAST) {
		return $from . $row . ':' . $to . $row;
	}

	protected static function decode($value) {
		$s = (string) $value;
		if ($s === '') {
			return '';
		}
		if (function_exists('decode_html')) {
			$s = decode_html($s);
		}
		$prev = '';
		while ($prev !== $s) {
			$prev = $s;
			$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return trim($s);
	}

	protected static function normalizeAccountName($name) {
		$name = self::decode($name);
		if ($name === '') {
			return '';
		}
		// Tránh "Công ty Công ty ..." khi dữ liệu lưu thêm tiền tố trùng nhãn.
		$name = preg_replace('/^(Công ty\s+)+/iu', 'Công ty ', $name);
		return trim($name);
	}

	protected static function stripTermsHtml($html) {
		$html = Quotes_QuoteBaService_Helper::stripSignatureFromTermsHtml((string) $html);
		$html = decode_html($html);
		$html = preg_replace('/<li[^>]*>/i', "\n", $html);
		$html = preg_replace('/<\/li>/i', "\n", $html);
		$html = preg_replace('/<\/(p|div|h[1-6])>/i', "\n", $html);
		$html = preg_replace('/<br\s*\/?>/i', "\n", $html);
		$html = strip_tags($html);
		$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$html = preg_replace("/\r\n|\r/", "\n", $html);
		$html = preg_replace("/[ \t]+/", ' ', $html);
		$html = preg_replace("/\n{3,}/", "\n\n", $html);
		return trim($html);
	}

	protected static function applyBankPlaceholders($text, array $company) {
		$map = array(
			'[Ngân hàng]:' => '[Ngân hàng]: ' . ($company['bank_name'] ?? ''),
			'[Người thụ hưởng]:' => '[Người thụ hưởng]: ' . ($company['account_holder'] ?? ''),
			'[Số tài khoản]:' => '[Số tài khoản]: ' . ($company['bank_account'] ?? ''),
		);
		foreach ($map as $needle => $replacement) {
			if (strpos($text, $needle) !== false && trim(substr($replacement, strlen($needle))) !== '') {
				$text = str_replace($needle, $replacement, $text);
			}
		}
		return $text;
	}

	/**
	 * @return array<int, array{type:string,text:string}>
	 */
	protected static function parseTermsLines($termsHtml, $productInfo, array $company) {
		$lines = array();
		$termsText = self::applyBankPlaceholders(self::stripTermsHtml($termsHtml), $company);
		$productText = self::stripTermsHtml($productInfo);

		$hasProductSection = (bool) preg_match('/^\s*1\.\s*Thông tin sản phẩm/ium', $termsText);
		if ($productText !== '' && !$hasProductSection) {
			$lines[] = array('type' => 'section', 'text' => '1. Thông tin sản phẩm:');
			foreach (preg_split("/\n+/", $productText) as $chunk) {
				$chunk = trim($chunk);
				if ($chunk !== '') {
					$lines[] = array('type' => 'body', 'text' => $chunk);
				}
			}
		}

		if ($termsText !== '') {
			foreach (preg_split("/\n+/", $termsText) as $chunk) {
				$chunk = trim($chunk);
				if ($chunk === '') {
					continue;
				}
				if (preg_match('/^\d+\.\d+\./', $chunk)) {
					$lines[] = array('type' => 'sub', 'text' => $chunk);
				} elseif (preg_match('/^\d+\.\s*.+/', $chunk)) {
					$lines[] = array('type' => 'section', 'text' => $chunk);
				} else {
					$lines[] = array('type' => 'body', 'text' => $chunk);
				}
			}
		}

		if (empty($lines)) {
			$lines[] = array('type' => 'section', 'text' => '1. Thông tin sản phẩm:');
			$lines[] = array('type' => 'section', 'text' => '2. Điều khoản và phương thức thanh toán:');
			$lines[] = array('type' => 'section', 'text' => '3. Thời gian thực hiện:');
			$lines[] = array('type' => 'section', 'text' => '4. Chú ý:');
		}

		return $lines;
	}

	protected static function styleSectionBar(PHPExcel_Worksheet $sheet, $row) {
		$range = self::colRange($row);
		$sheet->getStyle($range)->applyFromArray(array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb' => 'E7E6E6'),
			),
			'font' => array('bold' => true, 'name' => self::FONT, 'size' => 10),
			'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
		));
	}

	protected static function styleTableHeader(PHPExcel_Worksheet $sheet, $row) {
		$range = self::colRange($row);
		$sheet->getStyle($range)->applyFromArray(array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_NONE,
			),
			'font' => array('bold' => true, 'name' => self::FONT, 'size' => 10, 'color' => array('rgb' => '111111')),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
				'wrap' => true,
			),
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '111111')),
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '111111')),
			),
		));
	}

	protected static function styleBodyRow(PHPExcel_Worksheet $sheet, $row, $wrap = true) {
		$range = self::colRange($row);
		$sheet->getStyle($range)->applyFromArray(array(
			'font' => array('name' => self::FONT, 'size' => 10),
			'alignment' => array(
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
				'wrap' => $wrap,
			),
		));
	}

	protected static function addCompanyLogo(PHPExcel_Worksheet $sheet, $logoPath) {
		if (!Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($logoPath)) {
			$logoPath = Quotes_QuoteBaService_Helper::resolveQuoteLogoPath('');
		}
		if ($logoPath === '') {
			return;
		}
		try {
			$drawing = new PHPExcel_Worksheet_Drawing();
			$drawing->setName('TDB Logo');
			$drawing->setDescription('TDB Solution');
			$drawing->setPath($logoPath);
			$drawing->setHeight(44);
			$drawing->setCoordinates('F1');
			$drawing->setOffsetX(8);
			$drawing->setOffsetY(2);
			$drawing->setWorksheet($sheet);
			$sheet->getRowDimension(1)->setRowHeight(52);
		} catch (Exception $e) {
			// Skip logo if PHPExcel cannot embed the image on this server.
		}
	}

	protected static function writeCompanyHeader(PHPExcel_Worksheet $sheet, array $company) {
		$infoColFrom = 'B';
		$infoColTo = 'E';
		$row = 1;

		$sheet->mergeCells($infoColFrom . $row . ':' . $infoColTo . $row);
		$sheet->setCellValue($infoColFrom . $row, mb_strtoupper($company['company_name'], 'UTF-8'));
		$sheet->getStyle($infoColFrom . $row)->getFont()->setBold(true)->setSize(11)->setName(self::FONT);
		$sheet->getStyle($infoColFrom . $row)->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		$row++;

		$sheet->mergeCells($infoColFrom . $row . ':' . $infoColTo . $row);
		$sheet->setCellValue($infoColFrom . $row, 'Mã số thuế: ' . $company['tax_code']);
		$row++;

		if (!empty($company['website'])) {
			$sheet->mergeCells($infoColFrom . $row . ':' . $infoColTo . $row);
			$sheet->setCellValue($infoColFrom . $row, '[Website]: ' . $company['website']);
			$row++;
		}

		$sheet->mergeCells($infoColFrom . $row . ':' . $infoColTo . $row);
		$sheet->setCellValue($infoColFrom . $row, 'Địa chỉ: ' . $company['address']);
		$sheet->getStyle($infoColFrom . '2:' . $infoColTo . $row)->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

		for ($headerRow = 1; $headerRow <= $row; $headerRow++) {
			$sheet->getRowDimension($headerRow)->setRowHeight(18);
		}

		self::addCompanyLogo($sheet, $company['logo_path'] ?? '');

		return $row + 2;
	}

	protected static function gatherQuoteContext(CRMEntity $focus) {
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$company = self::applyNguyenKhoaCompanyBranding(Quotes_QuoteBaService_Helper::getCompanyProfile());

		$accountName = '';
		$phone = '';
		$email = '';
		$address = '';
		$accountId = $focus->column_fields['account_id'] ?? '';
		if (!empty($accountId)) {
			$focusAccount = CRMEntity::getInstance('Accounts');
			$focusAccount->retrieve_entity_info($accountId, 'Accounts');
			$accountName = self::normalizeAccountName($focusAccount->column_fields['accountname'] ?? '');
			$phone = $focusAccount->column_fields['phone'] ?? '';
			$email = !empty($focusAccount->column_fields['email1'])
				? $focusAccount->column_fields['email1']
				: ($focusAccount->column_fields['email2'] ?? '');
			$address = self::decode($focusAccount->column_fields['bill_street'] ?? '');
			if ($address === '') {
				$address = self::decode($focusAccount->column_fields['ship_street'] ?? '');
			}
		}

		$receiver = '';
		$contactId = $focus->column_fields['contact_id'] ?? '';
		if (!empty($contactId)) {
			$focusContact = CRMEntity::getInstance('Contacts');
			$focusContact->retrieve_entity_info($contactId, 'Contacts');
			$receiver = trim(
				self::decode($focusContact->column_fields['firstname'] ?? '') . ' '
				. self::decode($focusContact->column_fields['lastname'] ?? '')
			);
			if ($phone === '') {
				$phone = $focusContact->column_fields['mobile'] ?? ($focusContact->column_fields['phone'] ?? '');
			}
			if ($email === '') {
				$email = $focusContact->column_fields['email'] ?? '';
			}
		}

		if (!empty($focus->column_fields['mk_client_company'])) {
			$accountName = self::normalizeAccountName($focus->column_fields['mk_client_company']);
		}
		if (!empty($focus->column_fields['mk_customer_phone'])) {
			$phone = $focus->column_fields['mk_customer_phone'];
		}
		if (!empty($focus->column_fields['mk_customer_email'])) {
			$email = $focus->column_fields['mk_customer_email'];
		}

		$quoteNo = $focus->column_fields['quote_no'] ?? '';
		if ($quoteNo === '' && !empty($focus->column_fields['salesorder_no'])) {
			$quoteNo = $focus->column_fields['salesorder_no'];
		}
		$quoteDate = '';
		if (!empty($focus->column_fields['createdtime'])) {
			$quoteDate = self::toDayMonthYear($focus->column_fields['createdtime']);
		} elseif (!empty($focus->column_fields['mk_quote_date'])) {
			$quoteDate = self::toDayMonthYear($focus->column_fields['mk_quote_date']);
		} elseif (!empty($focus->column_fields['duedate'])) {
			$quoteDate = self::toDayMonthYear($focus->column_fields['duedate']);
		} else {
			$quoteDate = date('d/m/Y');
		}

		$vatPercent = (float) ($focus->column_fields['mk_vat_percent'] ?? Quotes_QuoteBaService_Helper::DEFAULT_VAT_PERCENT);
		if ($vatPercent <= 0 || $vatPercent > 100) {
			$vatPercent = Quotes_QuoteBaService_Helper::DEFAULT_VAT_PERCENT;
		}

		$headerSubTotal = (float) ($focus->column_fields['hdnSubTotal'] ?? $focus->column_fields['pre_tax_total'] ?? $focus->column_fields['subtotal'] ?? 0);
		$headerGrand = (float) ($focus->column_fields['hdnGrandTotal'] ?? $focus->column_fields['total'] ?? 0);
		$headerDiscount = (float) ($focus->column_fields['hdnDiscountAmount'] ?? $focus->column_fields['discount_amount'] ?? 0);
		if (!empty($focus->column_fields['hdnDiscountPercent'])) {
			$headerDiscount = $headerSubTotal * ((float) $focus->column_fields['hdnDiscountPercent']) / 100;
		}
		$mkVatAmount = (float) ($focus->column_fields['mk_vat_amount'] ?? 0);
		$taxAmount = $mkVatAmount;
		if ($taxAmount <= 0 && $headerGrand > ($headerSubTotal - $headerDiscount)) {
			$derived = $headerGrand - ($headerSubTotal - $headerDiscount);
			if ($headerSubTotal <= 0 || $derived <= ($headerSubTotal * 0.5)) {
				$taxAmount = $derived;
			}
		}
		if ($taxAmount <= 0 && $headerSubTotal > 0) {
			$taxAmount = round(($headerSubTotal - $headerDiscount) * $vatPercent / 100);
		}
		if ($headerSubTotal > 0 && $taxAmount > ($headerSubTotal * 0.5)) {
			$taxAmount = round(($headerSubTotal - $headerDiscount) * $vatPercent / 100);
		}
		if ($taxAmount < 0) {
			$taxAmount = 0;
		}
		if ($headerGrand <= 0 || ($headerSubTotal > 0 && $headerGrand > ($headerSubTotal * 2))) {
			$headerGrand = $headerSubTotal - $headerDiscount + $taxAmount;
		}

		$amountWords = self::decode($focus->column_fields['mk_amount_in_words'] ?? '');
		if ($amountWords === '' && $headerGrand > 0) {
			$amountWords = Quotes_QuoteBaService_Helper::amountInWordsVi($headerGrand);
		}

		return array(
			'company' => $company,
			'account_name' => $accountName,
			'receiver' => $receiver,
			'phone' => $phone,
			'email' => $email,
			'address' => $address,
			'quote_no' => $quoteNo,
			'quote_date' => $quoteDate,
			'vat_percent' => $vatPercent,
			'tax_amount' => $taxAmount,
			'discount_amount' => $headerDiscount,
			'sub_total' => $headerSubTotal,
			'grand_total' => $headerGrand,
			'amount_words' => $amountWords,
			'terms_html' => $focus->column_fields['terms_conditions'] ?? '',
			'notes' => self::resolveExportNotes($focus, $focus->column_fields['terms_conditions'] ?? ''),
			'product_info' => $focus->column_fields['mk_product_info'] ?? '',
		);
	}

	/**
	 * Shared context for Excel + PDF invoice layouts.
	 */
	public static function getSaleExportContext(CRMEntity $focus) {
		return self::gatherQuoteContext($focus);
	}

	public static function formatDateViLongPublic($dateDmY) {
		return self::formatDateViLong($dateDmY);
	}

	public static function formatMoneyVnPublic($amount) {
		return self::formatMoneyVn($amount);
	}

	public static function resolveExportNotesPublic(CRMEntity $focus, $termsHtml = '') {
		return self::resolveExportNotes($focus, $termsHtml);
	}

	public static function nkCompanyName() {
		return self::NK_COMPANY_NAME;
	}

	public static function nkAddress() {
		return self::NK_ADDRESS;
	}

	public static function nkPhone() {
		return self::NK_PHONE;
	}

	public static function resolveLogoPathPublic() {
		return self::resolveNguyenKhoaLogoPath();
	}

	public static function buildSaleWorkbook(CRMEntity $focus, $moduleName = 'Quotes') {
		require_once 'libraries/PHPExcel/PHPExcel.php';

		$ctx = self::gatherQuoteContext($focus);
		$company = $ctx['company'];
		$isSalesOrder = ($moduleName === 'SalesOrder');
		$sheetTitle = $isSalesOrder ? 'Don hang' : 'Bao gia';
		$docTitle = $isSalesOrder ? 'HÓA ĐƠN ĐẶT HÀNG' : 'HÓA ĐƠN ĐẶT HÀNG';
		$docNoLabel = $isSalesOrder ? 'Mã đơn hàng: ' : 'Mã đơn hàng: ';

		$book = new PHPExcel();
		$book->getDefaultStyle()->getFont()->setName(self::FONT)->setSize(10);
		$sheet = $book->setActiveSheetIndex(0);
		$sheet->setTitle($sheetTitle);

		foreach (array('A' => 2, 'B' => 6, 'C' => 14, 'D' => 18, 'E' => 12, 'F' => 14, 'G' => 10, 'H' => 16) as $col => $width) {
			$sheet->getColumnDimension($col)->setWidth($width);
		}

		// ===== NK invoice-style header (like provided form) =====
		$row = 1;
		// Center logo at top (keep clear of header text)
		try {
			$logoPath = $company['logo_path'] ?? '';
			if (Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($logoPath)) {
				$drawing = new PHPExcel_Worksheet_Drawing();
				$drawing->setName('NK Logo');
				$drawing->setDescription('Nguyên Khoa');
				$drawing->setPath($logoPath);
				$drawing->setHeight(150);
				// Center logo across the sheet (B..H).
				$drawing->setCoordinates('D1');
				$drawing->setOffsetX(0);
				$drawing->setOffsetY(2);
				$drawing->setWorksheet($sheet);
			}
		} catch (Exception $e) { /* ignore */ }

		$sheet->getRowDimension(1)->setRowHeight(128);
		$sheet->getRowDimension(2)->setRowHeight(6);

		$sheet->mergeCells('B3:H3');
		$sheet->setCellValue('B3', (string) ($company['company_name'] ?? self::NK_COMPANY_NAME));
		$sheet->getStyle('B3')->getFont()->setBold(true)->setSize(12)->setName(self::FONT);
		$sheet->getStyle('B3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B4:H4');
		$sheet->setCellValue('B4', 'Địa chỉ: ' . (string) ($company['address'] ?? self::NK_ADDRESS));
		$sheet->getStyle('B4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B5:H5');
		self::setTextCell($sheet, 'B5', 'Điện thoại: ' . (string) ($company['phone'] ?? self::NK_PHONE));
		$sheet->getStyle('B5')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B7:H7');
		$sheet->setCellValue('B7', $docTitle);
		$sheet->getStyle('B7')->applyFromArray(array(
			'font' => array('bold' => true, 'size' => 13, 'name' => self::FONT),
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
		));

		$docNo = $ctx['quote_no'] !== '' ? $ctx['quote_no'] : (($isSalesOrder ? 'SO' : 'DH') . $focus->id);
		$sheet->mergeCells('B8:H8');
		$sheet->setCellValue('B8', $docNoLabel . $docNo);
		$sheet->getStyle('B8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B9:H9');
		$sheet->setCellValue('B9', self::formatDateViLong($ctx['quote_date']));
		$sheet->getStyle('B9')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$row = 11;
		$sheet->setCellValue('B' . $row, 'Khách hàng:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $ctx['account_name'] !== '' ? $ctx['account_name'] : $ctx['receiver']);
		$row++;
		$sheet->setCellValue('B' . $row, 'SĐT:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		self::setTextCell($sheet, 'C' . $row, (string) ($ctx['phone'] ?? ''));
		$row++;
		$sheet->setCellValue('B' . $row, 'Địa chỉ:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $ctx['address']);
		$row++;
		// Ghi chú = description (create / list inline), not default terms boilerplate.
		$notes = isset($ctx['notes']) ? trim((string) $ctx['notes']) : self::resolveExportNotes($focus, $ctx['terms_html'] ?? '');
		$sheet->setCellValue('B' . $row, 'Ghi chú:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $notes !== '' ? $notes : '');
		$sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
		$sheet->getRowDimension($row)->setRowHeight(-1);
		$row++;
		$row++;

		$headerRow = $row;
		// Preview layout: Đơn giá (name + unit + price stacked) | SL | T.Tiền
		$sheet->setCellValue('B' . $row, 'Đơn giá');
		$sheet->setCellValue('G' . $row, 'SL');
		$sheet->setCellValue('H' . $row, 'T.Tiền');
		$sheet->mergeCells('B' . $row . ':F' . $row);
		self::styleTableHeader($sheet, $row);
		$sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$row++;

		$firstProductRow = $row;
		$associated_products = getAssociatedProducts($moduleName, $focus);
		$productLineItemIndex = 0;
		$focusProduct = CRMEntity::getInstance('Products');
		$focusService = CRMEntity::getInstance('Services');

		foreach ($associated_products as $productLineItem) {
			++$productLineItemIndex;
			$productId = $productLineItem['hdnProductId' . $productLineItemIndex] ?? '';
			$usageUnit = '';
			$entityType = (string) ($productLineItem['entityType' . $productLineItemIndex] ?? 'Products');
			if (!empty($productId)) {
				if (strcasecmp($entityType, 'Services') === 0) {
					try {
						$focusService->retrieve_entity_info($productId, 'Services');
						$usageUnit = self::decode($focusService->column_fields['service_usageunit'] ?? '');
					} catch (Exception $e) {
						$usageUnit = '';
					}
				} else {
					try {
						$focusProduct->retrieve_entity_info($productId, 'Products');
						$usageUnit = self::decode($focusProduct->column_fields['usageunit'] ?? '');
					} catch (Exception $e) {
						$usageUnit = '';
					}
				}
			}

			$quantity = (float) ($productLineItem['qty' . $productLineItemIndex] ?? 0);
			$listPrice = (float) ($productLineItem['listPrice' . $productLineItemIndex] ?? 0);
			$discount = (float) ($productLineItem['discountTotal' . $productLineItemIndex] ?? 0);
			$productName = self::decode($productLineItem['productName' . $productLineItemIndex] ?? '');
			$productTotal = (float) ($productLineItem['productTotal' . $productLineItemIndex] ?? 0);
			$totalAfterDiscount = (float) ($productLineItem['totalAfterDiscount' . $productLineItemIndex] ?? 0);
			if ($quantity <= 0) {
				$quantity = 1.0;
			}
			if ($listPrice <= 0 && $productTotal > 0) {
				$listPrice = $productTotal / $quantity;
			}
			if ($listPrice <= 0 && $totalAfterDiscount > 0) {
				$listPrice = ($totalAfterDiscount + $discount) / $quantity;
			}
			$total = ($quantity * $listPrice) - $discount;
			if ($total <= 0 && $productTotal > 0) {
				$total = $productTotal - $discount;
			}
			if ($total <= 0 && $totalAfterDiscount > 0) {
				$total = $totalAfterDiscount;
			}

			$label = $productName;
			$usageUnit = trim((string) $usageUnit);
			if ($usageUnit !== '' && stripos($label, '(' . $usageUnit . ')') === false) {
				$label .= ' (' . $usageUnit . ')';
			}
			$cellText = $label . "\n" . self::formatMoneyVn($listPrice);

			$sheet->mergeCells('B' . $row . ':F' . $row);
			$sheet->setCellValue('B' . $row, $cellText);
			$sheet->setCellValue('G' . $row, $quantity);
			$sheet->setCellValue('H' . $row, $total);
			// Name on top; SL + T.Tiền sit on the price line (bottom of wrapped cell)
			$sheet->getStyle('B' . $row)->getFont()->setBold(false);
			$sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_BOTTOM);
			$sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_BOTTOM);
			$sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
			$sheet->getStyle('B' . $row . ':H' . $row)->applyFromArray(array(
				'borders' => array(
					'bottom' => array('style' => PHPExcel_Style_Border::BORDER_DASHED, 'color' => array('rgb' => 'B0B0B0')),
				),
			));
			$sheet->getRowDimension($row)->setRowHeight(32);
			$row++;
		}

		if ($productLineItemIndex === 0) {
			$sheet->mergeCells('C' . $row . ':H' . $row);
			$sheet->setCellValue('B' . $row, '1');
			$sheet->setCellValue('C' . $row, '—');
			$row++;
		}

		$lastProductRow = $row - 1;
		// If line prices were saved as 0, backfill from header subtotal.
		$headerSubForLines = (float) ($focus->column_fields['hdnSubTotal'] ?? $focus->column_fields['pre_tax_total'] ?? 0);
		if ($headerSubForLines <= 0) {
			$headerSubForLines = (float) ($ctx['sub_total'] ?? 0);
		}
		$lineMoneySum = 0.0;
		if ($productLineItemIndex > 0 && $firstProductRow <= $lastProductRow) {
			for ($i = $firstProductRow; $i <= $lastProductRow; $i++) {
				$lineMoneySum += (float) $sheet->getCell('H' . $i)->getValue();
			}
			if ($lineMoneySum <= 0 && $headerSubForLines > 0) {
				$share = $headerSubForLines / max(1, $productLineItemIndex);
				for ($i = $firstProductRow; $i <= $lastProductRow; $i++) {
					$qtyCell = (float) $sheet->getCell('G' . $i)->getValue();
					if ($qtyCell <= 0) {
						$qtyCell = 1;
					}
					$existing = (string) $sheet->getCell('B' . $i)->getValue();
					$parts = preg_split("/\r\n|\n|\r/", $existing);
					$namePart = trim((string) ($parts[0] ?? ''));
					$sheet->setCellValue('B' . $i, $namePart . "\n" . self::formatMoneyVn($share / $qtyCell));
					$sheet->setCellValue('H' . $i, $share);
				}
			}
		}

		$totalRow = $row;
		// Totals on the right (match preview): label in E:F, money in H.
		$sheet->mergeCells('E' . $totalRow . ':F' . $totalRow);
		$sheet->setCellValue('E' . $totalRow, 'Tổng tiền hàng:');
		$sheet->setCellValue('H' . $totalRow, '=SUM(H' . $firstProductRow . ':H' . $lastProductRow . ')');
		$sheet->getStyle('E' . $totalRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$row++;

		// Prefer actual quote tax/discount amounts so Excel matches UI (not a default VAT %).
		$lineSubTotal = 0.0;
		for ($i = $firstProductRow; $i <= $lastProductRow; $i++) {
			$lineSubTotal += (float) $sheet->getCell('H' . $i)->getValue();
		}
		$taxAmount = (float) ($ctx['tax_amount'] ?? 0);
		$discountAmount = (float) ($ctx['discount_amount'] ?? 0);
		$grandTotal = (float) ($ctx['grand_total'] ?? 0);
		$vatPercent = (float) ($ctx['vat_percent'] ?? 0);
		if ($vatPercent <= 0 || $vatPercent > 100) {
			$vatPercent = 8.0;
		}
		if ($lineSubTotal > 0) {
			$headerSub = (float) ($focus->column_fields['hdnSubTotal'] ?? $focus->column_fields['pre_tax_total'] ?? 0);
			if ($headerSub > 0 && $lineSubTotal > ($headerSub * 50)) {
				$scale = $lineSubTotal / $headerSub;
				$taxAmount *= $scale;
				$discountAmount *= $scale;
				$grandTotal *= $scale;
			}
			if ($taxAmount <= 0 && $grandTotal > ($lineSubTotal - $discountAmount)) {
				$derived = $grandTotal - ($lineSubTotal - $discountAmount);
				if ($derived <= ($lineSubTotal * 0.5)) {
					$taxAmount = $derived;
				}
			}
			if ($taxAmount <= 0) {
				$taxAmount = round(($lineSubTotal - $discountAmount) * $vatPercent / 100);
			}
			// Reject absurd tax (bad saved totals leaking into export).
			if ($taxAmount > ($lineSubTotal * 0.5)) {
				$taxAmount = round(($lineSubTotal - $discountAmount) * $vatPercent / 100);
			}
			$grandTotal = $lineSubTotal - $discountAmount + $taxAmount;
		}

		$taxRow = $row;
		$sheet->mergeCells('E' . $taxRow . ':F' . $taxRow);
		$sheet->setCellValue('E' . $taxRow, 'Thuế:');
		$sheet->setCellValue('H' . $taxRow, $taxAmount);
		$sheet->getStyle('E' . $taxRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$row++;

		$discountRow = $row;
		$sheet->mergeCells('E' . $discountRow . ':F' . $discountRow);
		$sheet->setCellValue('E' . $discountRow, 'Chiết khấu:');
		$sheet->setCellValue('H' . $discountRow, $discountAmount);
		$sheet->getStyle('E' . $discountRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$row++;

		$grandRow = $row;
		$sheet->mergeCells('E' . $grandRow . ':F' . $grandRow);
		$sheet->setCellValue('E' . $grandRow, 'Tổng thanh toán:');
		$sheet->setCellValue('H' . $grandRow, $grandTotal > 0 ? $grandTotal : ('=H' . $totalRow . '+H' . $taxRow . '-H' . $discountRow));
		$sheet->getStyle('E' . $grandRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$sheet->getStyle('H' . $totalRow . ':H' . $grandRow)->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle('H' . $totalRow . ':H' . $grandRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$sheet->getStyle('E' . $totalRow . ':E' . $grandRow)->getFont()->setBold(true)->setName(self::FONT);
		$sheet->getStyle('H' . $totalRow . ':H' . $grandRow)->getFont()->setBold(true)->setName(self::FONT);
		$row++;

		// Amount in words: left side, below tổng thanh toán (match preview).
		$amountWords = trim((string) ($ctx['amount_words'] ?? ''));
		if ($amountWords === '' && $grandTotal > 0) {
			$amountWords = Quotes_QuoteBaService_Helper::amountInWordsVi($grandTotal);
		}
		if ($amountWords !== '') {
			$sheet->mergeCells('B' . $row . ':D' . $row);
			$sheet->setCellValue('B' . $row, '(' . $amountWords . ')');
			$sheet->getStyle('B' . $row)->getFont()->setItalic(true)->setName(self::FONT)->setSize(11);
			$sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$row += 2;
		} else {
			$row += 1;
		}

		$row += 1;
		$sheet->mergeCells('B' . $row . ':H' . $row);
		$sheet->setCellValue('B' . $row, 'Cảm ơn và hẹn gặp lại!');
		$sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle('B' . $row)->getFont()->setItalic(true)->setName(self::FONT);

		$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
		$sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$sheet->getPageMargins()->setTop(0.5)->setRight(0.4)->setLeft(0.4)->setBottom(0.5);

		return $book;
	}

	public static function buildSaleFilename(CRMEntity $focus, $recordId, $moduleName = 'Quotes') {
		$isSalesOrder = ($moduleName === 'SalesOrder');
		$fileBase = '';
		$potentialId = isset($focus->column_fields['potential_id']) ? (int) $focus->column_fields['potential_id'] : 0;
		if ($potentialId > 0) {
			try {
				$pot = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
				if ($pot) {
					$fileBase = (string) $pot->get('potentialname');
					if ($fileBase === '') {
						$fileBase = (string) $pot->getName();
					}
				}
			} catch (Exception $e) {
				// ignore
			}
		}
		$fileBase = trim(self::decode($fileBase));
		if ($fileBase === '') {
			$docNo = '';
			if ($isSalesOrder && !empty($focus->column_fields['salesorder_no'])) {
				$docNo = self::decode($focus->column_fields['salesorder_no']);
			} elseif (!empty($focus->column_fields['quote_no'])) {
				$docNo = self::decode($focus->column_fields['quote_no']);
			}
			$fileBase = $docNo !== '' ? $docNo : (($isSalesOrder ? 'SalesOrder_' : 'Quote_') . $recordId);
		}
		$fileBase = preg_replace('/^\d{6}-/', '', $fileBase);
		$fileBase = trim($fileBase);
		$prefix = $isSalesOrder ? 'NK SO-' : 'NK Quo-';
		$prefixPattern = $isSalesOrder ? '/^NK SO-/i' : '/^NK Quo-/i';
		if ($fileBase !== '' && !preg_match($prefixPattern, $fileBase)) {
			$fileBase = $prefix . $fileBase;
		}
		$fileBase = preg_replace('/[^\p{L}\p{N}\s\-\_\.]/u', '_', $fileBase);
		$fileBase = preg_replace('/\s+/', ' ', $fileBase);
		$fileBase = trim($fileBase);
		if (mb_strlen($fileBase, 'UTF-8') > 80) {
			$fileBase = mb_substr($fileBase, 0, 80, 'UTF-8');
		}
		return $fileBase . '.xlsx';
	}
}
