<?php
/*+***********************************************************************************
 * PHIẾU NHẬP KHO — Mẫu số 01 - VT (Thông tư 99/2025/TT-BTC).
 * Preview HTML + PDF (TCPDF thuần). Header Số lượng đúng mẫu giấy (Hình 3).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';
require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class Warehouse_InboundReceiptPdf_Helper {

	public static function output(array $payload, $fileName, $dest = 'D') {
		self::flushOutputBuffers();
		if (isset($GLOBALS['csrf']) && is_array($GLOBALS['csrf'])) {
			$GLOBALS['csrf']['rewrite'] = false;
			$GLOBALS['csrf']['frame-breaker'] = false;
		}
		$pdf = self::buildPdf(self::normalizePayload($payload));
		return $pdf->Output($fileName, $dest);
	}

	public static function renderHtmlPreview(array $payload) {
		self::flushOutputBuffers();
		if (isset($GLOBALS['csrf']) && is_array($GLOBALS['csrf'])) {
			$GLOBALS['csrf']['rewrite'] = false;
			$GLOBALS['csrf']['frame-breaker'] = false;
		}
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: private, no-store');
		echo self::buildHtml(self::normalizePayload($payload));
		exit;
	}

	/** @deprecated */
	public static function buildFromHtml(array $payload) {
		return self::buildPdf(self::normalizePayload($payload));
	}

	protected static function flushOutputBuffers() {
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
	}

	/**
	 * Load TCPDF. Always use class_exists('TCPDF') WITH autoload.
	 * Prefer absolute paths from this file (không phụ thuộc cwd).
	 */
	protected static function ensureTcpdf() {
		if (class_exists('TCPDF')) {
			return;
		}

		$root = dirname(__DIR__, 3); // modules/Warehouse/helpers -> app root
		$paths = array(
			$root . '/vendor/tecnickcom/tcpdf/tcpdf.php',
			$root . '/vendor/autoload.php',
			$root . '/vtiger-prod/tecnickcom/tcpdf/tcpdf.php',
			$root . '/libraries/tcpdf/tcpdf.php',
			'vendor/tecnickcom/tcpdf/tcpdf.php',
			'vendor/autoload.php',
			'vtiger-prod/tecnickcom/tcpdf/tcpdf.php',
		);
		foreach ($paths as $path) {
			if (!is_readable($path)) {
				continue;
			}
			require_once $path;
			if (class_exists('TCPDF')) {
				return;
			}
		}

		if (is_readable($root . '/vtlib/Vtiger/PDF/PDFGenerator.php')) {
			include_once $root . '/vtlib/Vtiger/PDF/PDFGenerator.php';
		} elseif (is_readable('vtlib/Vtiger/PDF/PDFGenerator.php')) {
			include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';
		}
		if (class_exists('TCPDF')) {
			return;
		}

		throw new Exception('TCPDF không khả dụng — không tạo được PDF.');
	}

	protected static function normalizePayload(array $payload) {
		$receipt = isset($payload['receipt']) && is_array($payload['receipt']) ? $payload['receipt'] : array();
		$warehouse = isset($payload['warehouse']) && is_array($payload['warehouse']) ? $payload['warehouse'] : array();
		$company = trim(decode_html((string) (isset($payload['company']) ? $payload['company'] : 'Nguyên Khoa')));
		if ($company === '') {
			$company = 'Nguyên Khoa';
		}
		$lines = isset($receipt['lines']) && is_array($receipt['lines']) ? $receipt['lines'] : array();
		$createdTs = strtotime((string) (isset($receipt['createdAt']) ? $receipt['createdAt'] : ''));
		if (!$createdTs) {
			$createdTs = time();
		}
		$grandTotal = 0.0;
		foreach ($lines as $line) {
			$grandTotal += ((float) (isset($line['qty']) ? $line['qty'] : 0))
				* ((float) (isset($line['unit_price']) ? $line['unit_price'] : 0));
		}
		return array(
			'company' => $company,
			'lines' => $lines,
			'docNo' => trim(decode_html((string) (isset($receipt['id']) ? $receipt['id'] : ''))),
			'supplier' => trim(decode_html((string) (isset($receipt['supplier']) ? $receipt['supplier'] : ''))),
			'poRef' => trim(decode_html((string) (isset($receipt['poRef']) ? $receipt['poRef'] : ''))),
			'createdBy' => trim(decode_html((string) (isset($receipt['createdBy']) ? $receipt['createdBy'] : ''))),
			'whName' => trim(decode_html((string) (isset($warehouse['name']) ? $warehouse['name'] : ''))),
			'whAddress' => trim(decode_html((string) (isset($warehouse['address']) ? $warehouse['address'] : ''))),
			'manager' => trim(decode_html((string) (isset($warehouse['manager']) ? $warehouse['manager'] : ''))),
			'day' => date('d', $createdTs),
			'month' => date('m', $createdTs),
			'year' => date('Y', $createdTs),
			'grandTotal' => $grandTotal,
			'amountWords' => Quotes_QuoteBaService_Helper::amountInWordsVi($grandTotal),
		);
	}

	protected static function h($text) {
		return htmlspecialchars(decode_html((string) $text), ENT_QUOTES, 'UTF-8');
	}

	protected static function utf($text) {
		return decode_html((string) $text);
	}

	protected static function formatQty($qty) {
		$qty = (float) $qty;
		if ($qty == (int) $qty) {
			return (string) (int) $qty;
		}
		return rtrim(rtrim(number_format($qty, 3, ',', ''), '0'), ',');
	}

	protected static function money($amount) {
		$amount = (float) $amount;
		if ($amount <= 0) {
			return '';
		}
		return Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($amount);
	}

	protected static function dateSpacedHtml($day, $month, $year) {
		return 'Ngày&nbsp;&nbsp;&nbsp;' . self::h($day)
			. '&nbsp;&nbsp;&nbsp;tháng&nbsp;&nbsp;&nbsp;' . self::h($month)
			. '&nbsp;&nbsp;&nbsp;năm&nbsp;&nbsp;&nbsp;' . self::h($year);
	}

	protected static function dateSpacedText($day, $month, $year) {
		return 'Ngày   ' . $day . '   tháng   ' . $month . '   năm   ' . $year;
	}

	protected static function lineDescHtml(array $line) {
		$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
		$parts = array();
		if (!empty($line['lot'])) {
			$parts[] = 'Lô: ' . $line['lot'];
		}
		if (!empty($line['mfg'])) {
			$parts[] = 'NSX: ' . $line['mfg'];
		}
		if (!empty($line['expiry'])) {
			$parts[] = 'HSD: ' . $line['expiry'];
		}
		$html = self::h($name);
		if (!empty($parts)) {
			$html .= '<br/><span class="muted">' . self::h(implode(' · ', $parts)) . '</span>';
		}
		return $html;
	}

	protected static function lineDescText(array $line) {
		$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
		$parts = array($name);
		if (!empty($line['lot'])) {
			$parts[] = 'Lô: ' . $line['lot'];
		}
		if (!empty($line['mfg'])) {
			$parts[] = 'NSX: ' . $line['mfg'];
		}
		if (!empty($line['expiry'])) {
			$parts[] = 'HSD: ' . $line['expiry'];
		}
		return implode("\n", array_filter($parts));
	}

	protected static function skuText(array $line) {
		$sku = trim((string) (isset($line['sku']) ? $line['sku'] : ''));
		if ($sku === '' || preg_match('/^PS-\d+$/i', $sku)) {
			return '';
		}
		return $sku;
	}

	/**
	 * HTML preview — tờ giấy A4, khoảng cách dòng theo mẫu Hình 1 (01-VT).
	 */
	public static function buildHtml(array $d) {
		$rowsHtml = '';
		$i = 0;
		foreach ($d['lines'] as $line) {
			$i++;
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$price = (float) (isset($line['unit_price']) ? $line['unit_price'] : 0);
			$unit = trim((string) (isset($line['unit']) ? $line['unit'] : ''));
			$rowsHtml .= '<tr>'
				. '<td class="c">' . $i . '</td>'
				. '<td class="l">' . self::lineDescHtml($line) . '</td>'
				. '<td class="c">' . self::h(self::skuText($line)) . '</td>'
				. '<td class="c">' . self::h($unit) . '</td>'
				. '<td class="c">' . self::h(self::formatQty($qty)) . '</td>'
				. '<td class="c">' . self::h(self::formatQty($qty)) . '</td>'
				. '<td class="r">' . self::h(self::money($price)) . '</td>'
				. '<td class="r">' . self::h(self::money($qty * $price)) . '</td>'
				. '</tr>';
		}
		// Chỉ đánh STT cho dòng có hàng hoá — dòng trống để trắng như mẫu giấy
		while ($i < 3) {
			$i++;
			$rowsHtml .= '<tr class="blank"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
		}

		$deliverer = $d['supplier'] !== '' ? $d['supplier'] : ($d['createdBy'] !== '' ? $d['createdBy'] : '........................................');
		$poShow = $d['poRef'] !== '' ? self::h($d['poRef']) : '...............';
		$refOrg = $d['supplier'] !== '' ? self::h($d['supplier']) : '................................';
		$whName = $d['whName'] !== '' ? self::h($d['whName']) : '..................................................';
		$whAddr = $d['whAddress'] !== '' ? self::h($d['whAddress']) : '...............................................';
		$docNo = $d['docNo'] !== '' ? self::h($d['docNo']) : '....................';
		$totalMoney = self::money($d['grandTotal']);
		$amountWords = $d['amountWords'] !== '' ? $d['amountWords'] : '................................................................';
		$dateHtml = self::dateSpacedHtml($d['day'], $d['month'], $d['year']);
		$dots = '....................';

		return '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"/>'
			. '<title>PHIẾU NHẬP KHO ' . self::h($d['docNo']) . '</title>'
			. '<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  background:#e8eaed;
  color:#000;
  font-family:"Times New Roman",Times,serif;
  font-size:13px;
  line-height:1.35;
  -webkit-print-color-adjust:exact;
  print-color-adjust:exact;
}
.desk{padding:18px 12px 28px;min-height:100vh}
.sheet{
  width:210mm;
  min-height:297mm;
  margin:0 auto;
  background:#fff;
  padding:14mm 16mm 12mm;
  box-shadow:0 2px 12px rgba(0,0,0,.18);
}
.top{display:table;width:100%;margin:0 0 8mm}
.top-l,.top-r{display:table-cell;vertical-align:top}
.top-l{width:46%;font-size:13px;line-height:1.7}
.top-r{width:54%;text-align:right}
.top-r .mau{font-weight:700;font-size:13px}
.top-r .tt{font-style:italic;font-size:10px;line-height:1.35;margin-top:2px}
/* Tiêu đề giữa trang; Số/Nợ/Có bên phải — cả khối nằm dưới dòng ngày */
.title-wrap{position:relative;margin:2mm 0 8mm;min-height:30mm;padding-bottom:2mm}
.title-center{text-align:center;padding:0 52mm}
.title-center h1{margin:0;font-size:20pt;font-weight:700;letter-spacing:.5px;line-height:1.15}
.title-center .ngay{margin-top:2.5mm;font-size:12pt;font-style:italic}
.title-refs{position:absolute;right:0;top:16mm;width:48mm;text-align:left;font-size:12pt;line-height:1.7;white-space:nowrap}
.info{margin:0 0 5mm;font-size:12pt;line-height:2.05}
.info p{margin:0}
table.main{width:100%;border-collapse:collapse;table-layout:fixed;margin:0}
table.main td{border:1px solid #000;padding:2mm 1.2mm;vertical-align:middle}
.th{font-size:10pt;font-weight:700;text-align:center;line-height:1.25}
.th-empty{height:7mm;padding:0 !important}
.codes td{text-align:center;font-size:10pt;font-weight:700;padding:1.2mm !important}
td.c{text-align:center} td.r{text-align:right;padding-right:2mm !important} td.l{text-align:left;padding-left:2mm !important}
.muted{font-size:9pt}
tr.blank td{height:8mm}
.cong{font-weight:700;text-align:center}
.foot{margin:5mm 0 0;font-size:12pt;line-height:2.05}
.foot p{margin:0}
.sign-block{margin-top:6mm}
.sign-date{text-align:right;font-size:12pt;font-style:italic;margin:0 8mm 3mm 0}
.signs{width:100%;border-collapse:collapse;table-layout:fixed}
.signs td{width:25%;border:0;text-align:center;vertical-align:top;font-size:10.5pt;padding:0 1mm}
.signs .role{font-weight:700;line-height:1.35;min-height:14mm}
.signs .pad{height:18mm}
.signs .name{font-weight:400;margin-top:1mm}
.note{margin-top:8mm;font-size:9pt;font-style:italic;line-height:1.4}
@page{size:A4 portrait;margin:0}
@media print{
  body{background:#fff}
  .desk{padding:0}
  .sheet{box-shadow:none;margin:0;width:210mm;min-height:auto}
}
@media screen and (max-width:900px){
  .sheet{width:100%;min-height:0;padding:12px 14px}
  .title-center{padding:0 40mm 0 0}
}
</style></head><body><div class="desk"><div class="sheet">'
			. '<div class="top"><div class="top-l">Đơn vị: ' . self::h($d['company'] !== '' ? $d['company'] : $dots) . '<br/>Bộ phận: Kho</div>'
			. '<div class="top-r"><div class="mau">Mẫu số 01 - VT</div>'
			. '<div class="tt">(Kèm theo Thông tư số 99/2025/TT-BTC ngày 27 tháng 10 năm 2025<br/>của Bộ trưởng Bộ Tài chính)</div></div></div>'
			. '<div class="title-wrap">'
			. '<div class="title-center"><h1>PHIẾU NHẬP KHO</h1><div class="ngay">' . $dateHtml . '</div></div>'
			. '<div class="title-refs">Số: ' . $docNo . '<br/>Nợ: ' . $dots . '<br/>Có: ' . $dots . '</div>'
			. '</div>'
			. '<div class="info">'
			. '<p>- Họ và tên người giao: ' . self::h($deliverer) . '</p>'
			. '<p>- Theo PO số ' . $poShow
			. ' ngày&nbsp;&nbsp;&nbsp;' . self::h($d['day'])
			. '&nbsp;&nbsp;&nbsp;tháng&nbsp;&nbsp;&nbsp;' . self::h($d['month'])
			. '&nbsp;&nbsp;&nbsp;năm&nbsp;&nbsp;&nbsp;' . self::h($d['year'])
			. ' của ' . $refOrg . '</p>'
			. '<p>Nhập tại kho: ' . $whName . '&nbsp;&nbsp;địa điểm&nbsp;&nbsp;' . $whAddr . '</p>'
			. '</div>'
			. '<table class="main">'
			. '<colgroup>'
			. '<col style="width:6%"/><col style="width:28%"/><col style="width:9%"/><col style="width:8%"/>'
			. '<col style="width:10%"/><col style="width:10%"/><col style="width:12%"/><col style="width:17%"/>'
			. '</colgroup>'
			/* Hàng 1: Số lượng gộp 2 | ô trống | ô trống — Hàng 2: Theo CT | Thực nhập | Đơn giá | Thành tiền */
			. '<tr>'
			. '<td class="th" rowspan="2">STT</td>'
			. '<td class="th" rowspan="2">Tên, nhãn hiệu, quy cách, phẩm chất<br/>vật tư, dụng cụ sản phẩm, hàng hoá</td>'
			. '<td class="th" rowspan="2">Mã số</td>'
			. '<td class="th" rowspan="2">Đơn vị<br/>tính</td>'
			. '<td class="th" colspan="2">Số lượng</td>'
			. '<td class="th-empty"></td>'
			. '<td class="th-empty"></td>'
			. '</tr>'
			. '<tr>'
			. '<td class="th">Theo chứng từ</td>'
			. '<td class="th">Thực nhập</td>'
			. '<td class="th">Đơn giá</td>'
			. '<td class="th">Thành tiền</td>'
			. '</tr>'
			. '<tr class="codes"><td>A</td><td>B</td><td>C</td><td>D</td><td>1</td><td>2</td><td>3</td><td>4</td></tr>'
			. $rowsHtml
			. '<tr>'
			. '<td></td><td class="cong">Cộng</td>'
			. '<td class="c">x</td><td class="c">x</td><td class="c">x</td><td class="c">x</td><td class="c">x</td>'
			. '<td class="r"><strong>' . self::h($totalMoney) . '</strong></td>'
			. '</tr>'
			. '</table>'
			. '<div class="foot">'
			. '<p>- Tổng số tiền (viết bằng chữ): ' . self::h($amountWords) . '</p>'
			. '<p>- Số chứng từ gốc kèm theo: ................</p>'
			. '</div>'
			. '<div class="sign-block">'
			. '<div class="sign-date">' . $dateHtml . '</div>'
			. '<table class="signs"><tr>'
			. '<td><div class="role">Người lập phiếu<br/>(Ký, họ tên)</div><div class="pad"></div><div class="name">' . self::h($d['createdBy']) . '</div></td>'
			. '<td><div class="role">Người giao hàng<br/>(Ký, họ tên)</div><div class="pad"></div><div class="name">' . self::h($d['supplier']) . '</div></td>'
			. '<td><div class="role">Thủ kho<br/>(Ký, họ tên)</div><div class="pad"></div><div class="name">' . self::h($d['manager']) . '</div></td>'
			. '<td><div class="role">Kế toán trưởng<br/>(Hoặc bộ phận có nhu cầu nhập)<br/>(Ký, họ tên)</div><div class="pad"></div><div class="name"></div></td>'
			. '</tr></table>'
			. '</div>'
			. '<div class="note">Ghi chú: Tùy theo đặc điểm hoạt động sản xuất kinh doanh và yêu cầu quản lý của đơn vị mình, doanh nghiệp được xây dựng, thiết kế biểu mẫu chứng từ kế toán.</div>'
			. '</div></div></body></html>';
	}

	/**
	 * PDF bằng Cell (TCPDF thuần) — khớp Hình 3, tránh rowspan writeHTML lỗi.
	 */
	public static function buildPdf(array $d) {
		self::ensureTcpdf();
		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetCreator('Nguyên Khoa');
		$pdf->SetAuthor($d['company']);
		$pdf->SetTitle('PHIẾU NHẬP KHO ' . $d['docNo']);
		$pdf->SetMargins(16, 12, 16);
		$pdf->SetAutoPageBreak(true, 12);
		$pdf->AddPage();
		$font = 'dejavusans';
		$pdf->SetFont($font, '', 10);

		$m = $pdf->getMargins();
		$x0 = $m['left'];
		$pageW = $pdf->getPageWidth() - $m['left'] - $m['right'];

		// Header — khoảng cách gần mẫu giấy
		$pdf->SetFont($font, '', 10);
		$pdf->Cell($pageW * 0.48, 5.5, self::utf('Đơn vị: ' . ($d['company'] !== '' ? $d['company'] : '....................')), 0, 0, 'L');
		$pdf->SetFont($font, 'B', 10);
		$pdf->Cell($pageW * 0.52, 5.5, self::utf('Mẫu số 01 - VT'), 0, 1, 'R');
		$pdf->SetFont($font, '', 10);
		$pdf->Cell($pageW * 0.48, 5.5, self::utf('Bộ phận: Kho'), 0, 0, 'L');
		$pdf->SetFont($font, 'I', 7);
		$pdf->MultiCell($pageW * 0.52, 3.4, self::utf("(Kèm theo Thông tư số 99/2025/TT-BTC ngày 27 tháng 10 năm 2025\ncủa Bộ trưởng Bộ Tài chính)"), 0, 'R', false, 1);
		$pdf->Ln(6);

		// PHIẾU NHẬP KHO giữa trang; cả khối Số/Nợ/Có bên phải, bắt đầu dưới dòng ngày
		$yTitle = $pdf->GetY();
		$refW = 42;
		$pdf->SetFont($font, 'B', 16);
		$pdf->Cell($pageW, 8, self::utf('PHIẾU NHẬP KHO'), 0, 1, 'C');
		$pdf->SetFont($font, 'I', 11);
		$pdf->Cell($pageW, 6, self::utf(self::dateSpacedText($d['day'], $d['month'], $d['year'])), 0, 1, 'C');
		$yAfterDate = $pdf->GetY();
		$docNo = $d['docNo'] !== '' ? $d['docNo'] : '....................';
		$pdf->SetFont($font, '', 10);
		$pdf->SetXY($x0 + $pageW - $refW, $yAfterDate + 1.5);
		$pdf->MultiCell($refW, 5.5, self::utf("Số: " . $docNo . "\nNợ: ....................\nCó: ...................."), 0, 'L', false, 1);
		$pdf->SetY(max($pdf->GetY(), $yAfterDate + 2));
		$pdf->Ln(4);

		// Info — line-height rộng như mẫu
		$pdf->SetFont($font, '', 10);
		$deliverer = $d['supplier'] !== '' ? $d['supplier'] : ($d['createdBy'] !== '' ? $d['createdBy'] : '........................................');
		$pdf->MultiCell($pageW, 6.5, self::utf('- Họ và tên người giao: ' . $deliverer), 0, 'L', false, 1);
		$po = $d['poRef'] !== '' ? $d['poRef'] : '...............';
		$org = $d['supplier'] !== '' ? $d['supplier'] : '................................';
		$pdf->MultiCell(
			$pageW,
			6.5,
			self::utf('- Theo PO số ' . $po . ' ngày   ' . $d['day'] . '   tháng   ' . $d['month'] . '   năm   ' . $d['year'] . ' của ' . $org),
			0,
			'L',
			false,
			1
		);
		$wh = $d['whName'] !== '' ? $d['whName'] : '..................................................';
		$addr = $d['whAddress'] !== '' ? $d['whAddress'] : '...............................................';
		$pdf->MultiCell($pageW, 6.5, self::utf('Nhập tại kho: ' . $wh . '  địa điểm  ' . $addr), 0, 'L', false, 1);
		$pdf->Ln(4);

		$w = array(
			'a' => 10,
			'b' => 50,
			'c' => 17,
			'd' => 14,
			'1' => 20,
			'2' => 20,
			'3' => 22,
			'4' => 27,
		);
		$sum = array_sum($w);
		if (abs($sum - $pageW) > 0.2) {
			$w['b'] += ($pageW - $sum);
		}

		self::drawTableHeader($pdf, $font, $x0, $w);
		self::drawCodeRow($pdf, $font, $w);

		$pdf->SetFont($font, '', 8);
		$rowIndex = 0;
		$lines = $d['lines'];
		$minRows = max(3, count($lines));
		for ($n = 0; $n < $minRows; $n++) {
			$rowIndex++;
			if ($pdf->GetY() > 230) {
				$pdf->AddPage();
				self::drawTableHeader($pdf, $font, $x0, $w);
				self::drawCodeRow($pdf, $font, $w);
				$pdf->SetFont($font, '', 8);
			}
			if (isset($lines[$n])) {
				$line = $lines[$n];
				$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
				$price = (float) (isset($line['unit_price']) ? $line['unit_price'] : 0);
				$unit = trim((string) (isset($line['unit']) ? $line['unit'] : ''));
				self::drawDataRow($pdf, $x0, $w, array(
					(string) $rowIndex,
					self::lineDescText($line),
					self::skuText($line),
					$unit,
					self::formatQty($qty),
					self::formatQty($qty),
					self::money($price),
					self::money($qty * $price),
				));
			} else {
				// Dòng trống — không ghi STT (đúng mẫu giấy Hình 4)
				self::drawDataRow($pdf, $x0, $w, array('', '', '', '', '', '', '', ''), 9);
			}
		}

		$pdf->SetFont($font, 'B', 9);
		$h = 7;
		$pdf->Cell($w['a'], $h, '', 1, 0, 'C');
		$pdf->Cell($w['b'], $h, self::utf('Cộng'), 1, 0, 'C');
		$pdf->Cell($w['c'], $h, 'x', 1, 0, 'C');
		$pdf->Cell($w['d'], $h, 'x', 1, 0, 'C');
		$pdf->Cell($w['1'], $h, 'x', 1, 0, 'C');
		$pdf->Cell($w['2'], $h, 'x', 1, 0, 'C');
		$pdf->Cell($w['3'], $h, 'x', 1, 0, 'C');
		$pdf->Cell($w['4'], $h, self::utf(self::money($d['grandTotal'])), 1, 1, 'R');

		$pdf->Ln(5);
		$pdf->SetFont($font, '', 10);
		$words = $d['amountWords'] !== '' ? $d['amountWords'] : '................................................................';
		$pdf->MultiCell($pageW, 6.5, self::utf('- Tổng số tiền (viết bằng chữ): ' . $words), 0, 'L', false, 1);
		$pdf->MultiCell($pageW, 6.5, self::utf('- Số chứng từ gốc kèm theo: ................'), 0, 'L', false, 1);
		$pdf->Ln(6);

		if ($pdf->GetY() > 235) {
			$pdf->AddPage();
		}
		$pdf->SetFont($font, 'I', 10);
		$pdf->Cell($pageW * 0.5, 5, '', 0, 0, 'L');
		$pdf->Cell($pageW * 0.5, 5, self::utf(self::dateSpacedText($d['day'], $d['month'], $d['year'])), 0, 1, 'C');
		$pdf->Ln(3);

		$sigW = $pageW / 4;
		$sigY = $pdf->GetY();
		$labels = array(
			"Người lập phiếu\n(Ký, họ tên)",
			"Người giao hàng\n(Ký, họ tên)",
			"Thủ kho\n(Ký, họ tên)",
			"Kế toán trưởng\n(Hoặc bộ phận có nhu cầu nhập)\n(Ký, họ tên)",
		);
		$names = array($d['createdBy'], $d['supplier'], $d['manager'], '');
		$pdf->SetFont($font, 'B', 8);
		foreach ($labels as $i => $label) {
			$pdf->SetXY($x0 + $sigW * $i, $sigY);
			$pdf->MultiCell($sigW, 4.2, self::utf($label), 0, 'C', false, 0);
		}
		$pdf->SetY($sigY + 32);
		$pdf->SetFont($font, '', 9);
		foreach ($names as $i => $name) {
			$pdf->SetXY($x0 + $sigW * $i, $pdf->GetY());
			$pdf->Cell($sigW, 5, self::utf($name), 0, $i === 3 ? 1 : 0, 'C');
		}

		$pdf->Ln(10);
		$pdf->SetFont($font, 'I', 7);
		$pdf->MultiCell(
			$pageW,
			3.3,
			self::utf('Ghi chú: Tùy theo đặc điểm hoạt động sản xuất kinh doanh và yêu cầu quản lý của đơn vị mình, doanh nghiệp được xây dựng, thiết kế biểu mẫu chứng từ kế toán.'),
			0,
			'L',
			false,
			1
		);

		return $pdf;
	}

	/**
	 * Header đúng Hình 2:
	 * Hàng 1: Số lượng (gộp 2) | ô trống | ô trống
	 * Hàng 2: Theo chứng từ | Thực nhập | Đơn giá | Thành tiền
	 */
	protected static function drawTableHeader(TCPDF $pdf, $font, $x0, array $w) {
		$pdf->SetFont($font, 'B', 8);
		$y0 = $pdf->GetY();
		$hTop = 7;
		$hBot = 8;
		$hTotal = $hTop + $hBot;

		$pdf->MultiCell($w['a'], $hTotal, self::utf('STT'), 1, 'C', false, 0, $x0, $y0, true, 0, false, true, $hTotal, 'M', true);
		$pdf->MultiCell($w['b'], $hTotal, self::utf("Tên, nhãn hiệu, quy cách,\nphẩm chất vật tư, dụng cụ\nsản phẩm, hàng hoá"), 1, 'C', false, 0, '', '', true, 0, false, true, $hTotal, 'M', true);
		$pdf->MultiCell($w['c'], $hTotal, self::utf('Mã số'), 1, 'C', false, 0, '', '', true, 0, false, true, $hTotal, 'M', true);
		$pdf->MultiCell($w['d'], $hTotal, self::utf("Đơn vị\ntính"), 1, 'C', false, 0, '', '', true, 0, false, true, $hTotal, 'M', true);

		$qtyX = $x0 + $w['a'] + $w['b'] + $w['c'] + $w['d'];
		$qtyW = $w['1'] + $w['2'];

		// Hàng trên: Số lượng + 2 ô trống (có đủ gạch ngang/dọc)
		$pdf->SetXY($qtyX, $y0);
		$pdf->Cell($qtyW, $hTop, self::utf('Số lượng'), 1, 0, 'C');
		$pdf->Cell($w['3'], $hTop, '', 1, 0, 'C');
		$pdf->Cell($w['4'], $hTop, '', 1, 1, 'C');

		// Hàng dưới: Theo chứng từ | Thực nhập | Đơn giá | Thành tiền
		$pdf->SetFont($font, 'B', 7);
		$pdf->SetXY($qtyX, $y0 + $hTop);
		$pdf->Cell($w['1'], $hBot, self::utf('Theo chứng từ'), 1, 0, 'C');
		$pdf->Cell($w['2'], $hBot, self::utf('Thực nhập'), 1, 0, 'C');
		$pdf->SetFont($font, 'B', 8);
		$pdf->Cell($w['3'], $hBot, self::utf('Đơn giá'), 1, 0, 'C');
		$pdf->Cell($w['4'], $hBot, self::utf('Thành tiền'), 1, 1, 'C');
		$pdf->SetY($y0 + $hTotal);
	}

	protected static function drawCodeRow(TCPDF $pdf, $font, array $w) {
		$pdf->SetFont($font, 'B', 8);
		$h = 5;
		$pdf->Cell($w['a'], $h, 'A', 1, 0, 'C');
		$pdf->Cell($w['b'], $h, 'B', 1, 0, 'C');
		$pdf->Cell($w['c'], $h, 'C', 1, 0, 'C');
		$pdf->Cell($w['d'], $h, 'D', 1, 0, 'C');
		$pdf->Cell($w['1'], $h, '1', 1, 0, 'C');
		$pdf->Cell($w['2'], $h, '2', 1, 0, 'C');
		$pdf->Cell($w['3'], $h, '3', 1, 0, 'C');
		$pdf->Cell($w['4'], $h, '4', 1, 1, 'C');
	}

	protected static function drawDataRow(TCPDF $pdf, $x0, array $w, array $cells, $minH = 0) {
		$startY = $pdf->GetY();
		// TCPDF native: getStringHeight($w, $txt)
		$nameH = $pdf->getStringHeight($w['b'], self::utf($cells[1]));
		$rowH = max(8, $nameH + 1, $minH);

		$pdf->MultiCell($w['a'], $rowH, self::utf($cells[0]), 1, 'C', false, 0, $x0, $startY, true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['b'], $rowH, self::utf($cells[1]), 1, 'L', false, 0, '', '', true, 0, false, true, $rowH, 'T', true);
		$pdf->MultiCell($w['c'], $rowH, self::utf($cells[2]), 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['d'], $rowH, self::utf($cells[3]), 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['1'], $rowH, self::utf($cells[4]), 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['2'], $rowH, self::utf($cells[5]), 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['3'], $rowH, self::utf($cells[6]), 1, 'R', false, 0, '', '', true, 0, false, true, $rowH, 'M', true);
		$pdf->MultiCell($w['4'], $rowH, self::utf($cells[7]), 1, 'R', false, 1, '', '', true, 0, false, true, $rowH, 'M', true);
	}
}
