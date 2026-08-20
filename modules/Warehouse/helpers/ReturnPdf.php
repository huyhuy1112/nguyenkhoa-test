<?php
/*+***********************************************************************************
 * PHIẾU THU HỒI / TRẢ HÀNG — HTML preview + PDF (TCPDF).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';
require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class Warehouse_ReturnPdf_Helper {

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

	protected static function flushOutputBuffers() {
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
	}

	protected static function ensureTcpdf() {
		if (class_exists('TCPDF')) {
			return;
		}
		$root = dirname(__DIR__, 3);
		$paths = array(
			$root . '/vendor/tecnickcom/tcpdf/tcpdf.php',
			$root . '/vendor/autoload.php',
			$root . '/vtiger-prod/tecnickcom/tcpdf/tcpdf.php',
			$root . '/libraries/tcpdf/tcpdf.php',
			'vendor/tecnickcom/tcpdf/tcpdf.php',
			'vendor/autoload.php',
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
		}
		if (!class_exists('TCPDF')) {
			throw new Exception('TCPDF không khả dụng — không tạo được PDF.');
		}
	}

	protected static function normalizePayload(array $payload) {
		$slip = isset($payload['slip']) && is_array($payload['slip']) ? $payload['slip'] : array();
		$company = trim(decode_html((string) (isset($payload['company']) ? $payload['company'] : 'Nguyên Khoa')));
		if ($company === '') {
			$company = 'Nguyên Khoa';
		}
		$warehouse = isset($payload['warehouse']) ? $payload['warehouse'] : '';
		if (is_array($warehouse)) {
			$warehouse = isset($warehouse['name']) ? $warehouse['name'] : '';
		}
		$lines = isset($slip['lines']) && is_array($slip['lines']) ? $slip['lines'] : array();
		$createdTs = strtotime((string) (isset($slip['createdAt']) ? $slip['createdAt'] : ''));
		if (!$createdTs) {
			$createdTs = time();
		}
		$grandTotal = 0.0;
		foreach ($lines as $line) {
			$grandTotal += ((float) (isset($line['qty']) ? $line['qty'] : 0))
				* ((float) (isset($line['price']) ? $line['price'] : 0));
		}
		$docType = strtolower((string) (isset($slip['docType']) ? $slip['docType'] : 'return'));
		$title = $docType === 'recall' ? 'PHIẾU THU HỒI' : 'PHIẾU TRẢ HÀNG';
		return array(
			'company' => $company,
			'warehouse' => trim(decode_html((string) $warehouse)),
			'title' => $title,
			'docNo' => trim(decode_html((string) (isset($slip['code']) ? $slip['code'] : (isset($slip['id']) ? $slip['id'] : '')))),
			'sourceLabel' => trim(decode_html((string) (isset($slip['sourceLabel']) ? $slip['sourceLabel'] : ''))),
			'sourceType' => strtolower((string) (isset($slip['sourceType']) ? $slip['sourceType'] : 'retail')),
			'status' => (string) (isset($slip['status']) ? $slip['status'] : ''),
			'refund' => !empty($slip['refund']),
			'refundAmount' => (float) (isset($slip['refundAmount']) ? $slip['refundAmount'] : 0),
			'note' => trim(decode_html((string) (isset($slip['note']) ? $slip['note'] : ''))),
			'lines' => $lines,
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
			return '0';
		}
		return Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($amount);
	}

	protected static function statusLabel($status) {
		$map = array(
			'draft' => 'Nháp',
			'confirmed' => 'Đã nhập kho',
			'cancelled' => 'Đã hủy',
		);
		$status = strtolower(trim((string) $status));
		return isset($map[$status]) ? $map[$status] : $status;
	}

	public static function buildHtml(array $d) {
		$sourceKind = $d['sourceType'] === 'franchise' ? 'Chi nhánh nhượng quyền' : 'Khách lẻ';
		$rows = '';
		$i = 0;
		foreach ($d['lines'] as $line) {
			$i++;
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$price = (float) (isset($line['price']) ? $line['price'] : 0);
			$rows .= '<tr>'
				. '<td class="c">' . $i . '</td>'
				. '<td>' . self::h(isset($line['name']) ? $line['name'] : '') . '</td>'
				. '<td class="c">' . self::h(isset($line['sku']) ? $line['sku'] : '') . '</td>'
				. '<td class="c">' . self::h(isset($line['lot']) ? $line['lot'] : '') . '</td>'
				. '<td class="c">' . self::h(!empty($line['expiry']) ? $line['expiry'] : '') . '</td>'
				. '<td class="c">' . self::h(self::formatQty($qty)) . '</td>'
				. '<td class="r">' . self::h(self::money($price)) . '</td>'
				. '<td class="r">' . self::h(self::money($qty * $price)) . '</td>'
				. '</tr>';
		}
		$refundLine = $d['refund']
			? ('Có — ' . self::h(self::money($d['refundAmount'])))
			: 'Không hoàn tiền (chỉ nhập kho)';

		return '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"/>'
			. '<title>' . self::h($d['title']) . ' ' . self::h($d['docNo']) . '</title>'
			. '<style>
*{box-sizing:border-box}
body{margin:0;background:#e8eaed;color:#111;font-family:"Times New Roman",Times,serif;font-size:13px;line-height:1.4}
.sheet{width:210mm;min-height:297mm;margin:18px auto;background:#fff;padding:14mm 16mm;box-shadow:0 2px 12px rgba(0,0,0,.16)}
.top{display:flex;justify-content:space-between;margin-bottom:10mm}
h1{margin:0;font-size:20pt;text-align:center}
.sub{text-align:center;font-style:italic;margin:2mm 0 8mm}
table.main{width:100%;border-collapse:collapse;margin-top:6mm}
table.main th,table.main td{border:1px solid #111;padding:2mm 1.5mm}
table.main th{background:#f3f4f6;font-size:11px}
.c{text-align:center}.r{text-align:right}
.info p{margin:2mm 0}
.sign{display:flex;justify-content:space-between;margin-top:16mm;text-align:center}
.sign div{width:30%}
@media print{body{background:#fff}.sheet{box-shadow:none;margin:0}}
</style></head><body><div class="sheet">'
			. '<div class="top"><div><strong>' . self::h($d['company']) . '</strong><br/>Kho: ' . self::h($d['warehouse']) . '</div>'
			. '<div>Số: <strong>' . self::h($d['docNo']) . '</strong><br/>Trạng thái: ' . self::h(self::statusLabel($d['status'])) . '</div></div>'
			. '<h1>' . self::h($d['title']) . '</h1>'
			. '<div class="sub">Ngày ' . self::h($d['day']) . ' tháng ' . self::h($d['month']) . ' năm ' . self::h($d['year']) . '</div>'
			. '<div class="info">'
			. '<p>Nguồn: <strong>' . self::h($sourceKind) . '</strong> — ' . self::h($d['sourceLabel']) . '</p>'
			. '<p>Hoàn tiền: ' . $refundLine . '</p>'
			. ($d['note'] !== '' ? '<p>Ghi chú: ' . self::h($d['note']) . '</p>' : '')
			. '</div>'
			. '<table class="main"><thead><tr>'
			. '<th>STT</th><th>Tên hàng</th><th>SKU</th><th>Lô</th><th>HSD</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th>'
			. '</tr></thead><tbody>' . $rows . '</tbody>'
			. '<tfoot><tr><td colspan="7" class="r"><strong>Tổng</strong></td><td class="r"><strong>'
			. self::h(self::money($d['grandTotal'])) . '</strong></td></tr></tfoot></table>'
			. '<p>Bằng chữ: ' . self::h($d['amountWords']) . '</p>'
			. '<div class="sign"><div>Người lập phiếu<br/><br/><br/><br/>....................</div>'
			. '<div>Thủ kho<br/><br/><br/><br/>....................</div>'
			. '<div>Người trả / thu hồi<br/><br/><br/><br/>....................</div></div>'
			. '</div></body></html>';
	}

	protected static function buildPdf(array $d) {
		self::ensureTcpdf();
		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(12, 12, 12);
		$pdf->SetAutoPageBreak(true, 12);
		$pdf->AddPage();
		$html = self::buildHtml($d);
		if (preg_match('/<div class="sheet">.*<\/div>\s*<\/body>/s', $html, $m)) {
			$html = $m[0];
		}
		$pdf->writeHTML($html, true, false, true, false, '');
		return $pdf;
	}
}
