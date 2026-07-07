<?php
/*+***********************************************************************************
 * Quotes Excel export — BÁO GIÁ layout (Sale export).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';

class Quotes_QuoteExcelExport_Helper {

	const FONT = 'Arial Unicode MS';
	const COL_FIRST = 'B';
	const COL_LAST = 'H';

	// Nguyên Khoa defaults (used when org profile still has TDB placeholders)
	const NK_COMPANY_NAME = 'nguyenlieuphachemt';
	const NK_ADDRESS = '6/24 Đường số 3, Cư Xá Lữ Gia, Phú Thọ, Hồ Chí Minh';
	const NK_PHONE = '0973969498';

	protected static function resolveNguyenKhoaLogoPath() {
		global $root_directory;
		$candidates = array(
			'layouts/v7/resources/Images/nguyenkhoa-logo.png',
			'layouts/v7/skins/images/nguyenkhoa-logo.png',
		);
		$roots = array();
		if (!empty($root_directory)) {
			$roots[] = rtrim((string) $root_directory, "/\\");
		}
		$repoRoot = realpath(dirname(__FILE__) . '/../..');
		if ($repoRoot) {
			$roots[] = $repoRoot;
		}
		foreach ($candidates as $rel) {
			foreach ($roots as $root) {
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

		// If organization still uses stock/TDB defaults, force NK identity.
		$orgName = trim((string) ($company['company_name'] ?? ''));
		if ($orgName === '' || stripos($orgName, 'TDB') !== false) {
			$company['company_name'] = self::NK_COMPANY_NAME;
		}
		if (empty($company['address']) || stripos((string) $company['address'], 'bangalore') !== false) {
			$company['address'] = self::NK_ADDRESS;
		}
		if (empty($company['phone'])) {
			$company['phone'] = self::NK_PHONE;
		}
		return $company;
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
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb' => 'D9D9D9'),
			),
			'font' => array('bold' => true, 'name' => self::FONT, 'size' => 10),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
				'wrap' => true,
			),
			'borders' => array(
				'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
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
		$quoteDate = $focus->column_fields['mk_quote_date'] ?? '';
		if ($quoteDate === '' && !empty($focus->column_fields['createdtime'])) {
			$quoteDate = date('d/m/Y', strtotime($focus->column_fields['createdtime']));
		} elseif ($quoteDate !== '') {
			$quoteDate = date('d/m/Y', strtotime($quoteDate));
		} else {
			$quoteDate = date('d/m/Y');
		}

		$vatPercent = (float) ($focus->column_fields['mk_vat_percent'] ?? Quotes_QuoteBaService_Helper::DEFAULT_VAT_PERCENT);
		if ($vatPercent <= 0) {
			$vatPercent = Quotes_QuoteBaService_Helper::DEFAULT_VAT_PERCENT;
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
			'amount_words' => self::decode($focus->column_fields['mk_amount_in_words'] ?? ''),
			'terms_html' => $focus->column_fields['terms_conditions'] ?? '',
			'product_info' => $focus->column_fields['mk_product_info'] ?? '',
		);
	}

	public static function buildSaleWorkbook(CRMEntity $focus, $moduleName = 'Quotes') {
		require_once 'libraries/PHPExcel/PHPExcel.php';

		$ctx = self::gatherQuoteContext($focus);
		$company = $ctx['company'];

		$book = new PHPExcel();
		$book->getDefaultStyle()->getFont()->setName(self::FONT)->setSize(10);
		$sheet = $book->setActiveSheetIndex(0);
		$sheet->setTitle('Bao gia');

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
				$drawing->setHeight(88);
				$drawing->setCoordinates('D1');
				$drawing->setOffsetX(10);
				$drawing->setOffsetY(6);
				$drawing->setWorksheet($sheet);
			}
		} catch (Exception $e) { /* ignore */ }

		$sheet->getRowDimension(1)->setRowHeight(72);
		$sheet->getRowDimension(2)->setRowHeight(6);

		$sheet->mergeCells('B3:H3');
		$sheet->setCellValue('B3', (string) ($company['company_name'] ?? self::NK_COMPANY_NAME));
		$sheet->getStyle('B3')->getFont()->setBold(true)->setSize(12)->setName(self::FONT);
		$sheet->getStyle('B3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B4:H4');
		$sheet->setCellValue('B4', 'Địa chỉ: ' . (string) ($company['address'] ?? self::NK_ADDRESS));
		$sheet->getStyle('B4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B5:H5');
		$sheet->setCellValue('B5', 'Điện thoại: ' . (string) ($company['phone'] ?? self::NK_PHONE));
		$sheet->getStyle('B5')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B7:H7');
		$sheet->setCellValue('B7', 'HÓA ĐƠN ĐẶT HÀNG');
		$sheet->getStyle('B7')->applyFromArray(array(
			'font' => array('bold' => true, 'size' => 13, 'name' => self::FONT),
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
		));

		$docNo = $ctx['quote_no'] !== '' ? $ctx['quote_no'] : ('DH' . $focus->id);
		$sheet->mergeCells('B8:H8');
		$sheet->setCellValue('B8', 'Mã đơn hàng: ' . $docNo);
		$sheet->getStyle('B8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->mergeCells('B9:H9');
		$sheet->setCellValue('B9', 'Ngày ' . $ctx['quote_date']);
		$sheet->getStyle('B9')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$row = 11;
		$sheet->setCellValue('B' . $row, 'Khách hàng:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $ctx['account_name'] !== '' ? $ctx['account_name'] : $ctx['receiver']);
		$row++;
		$sheet->setCellValue('B' . $row, 'SĐT:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $ctx['phone']);
		$row++;
		$sheet->setCellValue('B' . $row, 'Địa chỉ:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $ctx['address']);
		$row++;
		// Notes: keep empty by default; only show if user actually filled something meaningful.
		$notes = self::stripTermsHtml($ctx['terms_html']);
		$notes = preg_replace('/^\s*1\.\s*Thông tin sản phẩm:\s*/iu', '', (string) $notes);
		$notes = preg_replace('/^\s*2\.\s*Điều khoản.*$/ium', '', (string) $notes);
		$notes = trim((string) $notes);
		$sheet->setCellValue('B' . $row, 'Ghi chú:');
		$sheet->mergeCells('C' . $row . ':H' . $row);
		$sheet->setCellValue('C' . $row, $notes !== '' ? $notes : '');
		$sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
		$sheet->getRowDimension($row)->setRowHeight(-1);
		$row++;
		$row++;

		$headerRow = $row;
		// Table matches print-like form
		$headers = array(
			'B' => 'Sản phẩm',
			'F' => 'Đơn giá',
			'G' => 'SL',
			'H' => 'T.Tiền',
		);
		foreach ($headers as $col => $label) {
			$sheet->setCellValue($col . $row, $label);
		}
		$sheet->mergeCells('B' . $row . ':E' . $row);
		self::styleTableHeader($sheet, $row);
		$row++;

		$firstProductRow = $row;
		$associated_products = getAssociatedProducts($moduleName, $focus);
		$productLineItemIndex = 0;
		$focusProduct = CRMEntity::getInstance('Products');

		foreach ($associated_products as $productLineItem) {
			++$productLineItemIndex;
			$productId = $productLineItem['hdnProductId' . $productLineItemIndex] ?? '';
			$productCode = '';
			$usageUnit = '';
			if (!empty($productId)) {
				$focusProduct->retrieve_entity_info($productId, 'Products');
				$productCode = self::decode($focusProduct->column_fields['productcode'] ?? '');
				$usageUnit = self::decode($focusProduct->column_fields['usageunit'] ?? '');
			}

			$quantity = (float) ($productLineItem['qty' . $productLineItemIndex] ?? 0);
			$listPrice = (float) ($productLineItem['listPrice' . $productLineItemIndex] ?? 0);
			$discount = (float) ($productLineItem['discountTotal' . $productLineItemIndex] ?? 0);
			$productName = self::decode($productLineItem['productName' . $productLineItemIndex] ?? '');
			$total = ($quantity * $listPrice) - $discount;

			$sheet->mergeCells('B' . $row . ':E' . $row);
			$sheet->setCellValue('B' . $row, $productName);
			$sheet->setCellValue('F' . $row, $listPrice);
			$sheet->setCellValue('G' . $row, $quantity);
			$sheet->setCellValue('H' . $row, $total);
			$sheet->getStyle('B' . $row . ':H' . $row)->applyFromArray(array(
				'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),
				'alignment' => array('wrap' => true, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP),
			));
			$sheet->getStyle('F' . $row . ':H' . $row)->getNumberFormat()->setFormatCode('#,##0');
			$row++;
		}

		if ($productLineItemIndex === 0) {
			$sheet->mergeCells('C' . $row . ':H' . $row);
			$sheet->setCellValue('B' . $row, '1');
			$sheet->setCellValue('C' . $row, '—');
			$row++;
		}

		$lastProductRow = $row - 1;
		$totalRow = $row;
		$sheet->mergeCells('B' . $totalRow . ':G' . $totalRow);
		$sheet->setCellValue('B' . $totalRow, 'Tổng tiền hàng:');
		$sheet->setCellValue('H' . $totalRow, '=SUM(H' . $firstProductRow . ':H' . $lastProductRow . ')');
		$row++;

		$vatRow = $row;
		$sheet->mergeCells('B' . $vatRow . ':G' . $vatRow);
		$sheet->setCellValue('B' . $vatRow, 'Chiết khấu:');
		$sheet->setCellValue('H' . $vatRow, '=H' . $totalRow . '*' . ($ctx['vat_percent'] / 100));
		$row++;

		$grandRow = $row;
		$sheet->mergeCells('B' . $grandRow . ':G' . $grandRow);
		$sheet->setCellValue('B' . $grandRow, 'Tổng thanh toán:');
		$sheet->setCellValue('H' . $grandRow, '=H' . $totalRow);
		$sheet->getStyle('H' . $totalRow . ':H' . $grandRow)->getNumberFormat()->setFormatCode('#,##0');
		$sheet->getStyle(self::colRange($totalRow) . ':' . self::colRange($grandRow))->applyFromArray(array(
			'font' => array('bold' => true, 'name' => self::FONT),
			'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),
		));
		$row += 2;

		if ($ctx['amount_words'] !== '') {
			$sheet->mergeCells(self::colRange($row));
			$sheet->setCellValue('B' . $row, '(' . $ctx['amount_words'] . ')');
			$sheet->getStyle(self::colRange($row))->getAlignment()->setWrapText(true);
			$row += 2;
		}

		$row += 2;
		$sheet->mergeCells('B' . $row . ':H' . $row);
		$sheet->setCellValue('B' . $row, 'Cảm ơn và hẹn gặp lại!');
		$sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
		$sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$sheet->getPageMargins()->setTop(0.5)->setRight(0.4)->setLeft(0.4)->setBottom(0.5);

		return $book;
	}

	public static function buildSaleFilename(CRMEntity $focus, $recordId) {
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
			$fileBase = 'Quote_' . $recordId;
		}
		$fileBase = preg_replace('/^\d{6}-/', '', $fileBase);
		$fileBase = trim($fileBase);
		if ($fileBase !== '' && !preg_match('/^NK Quo-/i', $fileBase)) {
			$fileBase = 'NK Quo-' . $fileBase;
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
