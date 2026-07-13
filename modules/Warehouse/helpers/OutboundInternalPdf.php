<?php
/*+***********************************************************************************
 * PHIẾU XUẤT DÙNG NỘI BỘ — mẫu in kiểu KiotViet (không dùng 02-VT xuất bán).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class Warehouse_OutboundInternalPdf_Helper {

	public static function output(array $payload, $fileName, $dest = 'D') {
		self::flushOutputBuffers();
		self::disableCsrf();
		$pdf = self::buildPdf(self::normalizePayload($payload));
		return $pdf->Output($fileName, $dest);
	}

	public static function renderHtmlPreview(array $payload) {
		self::flushOutputBuffers();
		self::disableCsrf();
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

	protected static function disableCsrf() {
		if (isset($GLOBALS['csrf']) && is_array($GLOBALS['csrf'])) {
			$GLOBALS['csrf']['rewrite'] = false;
			$GLOBALS['csrf']['frame-breaker'] = false;
		}
	}

	protected static function ensureTcpdf() {
		if (class_exists('TCPDF')) {
			return;
		}
		$root = dirname(__DIR__, 3);
		foreach (array(
			$root . '/vendor/tecnickcom/tcpdf/tcpdf.php',
			$root . '/vendor/autoload.php',
			$root . '/vtiger-prod/tecnickcom/tcpdf/tcpdf.php',
			$root . '/libraries/tcpdf/tcpdf.php',
			'vendor/tecnickcom/tcpdf/tcpdf.php',
			'vendor/autoload.php',
		) as $path) {
			if (!is_readable($path)) {
				continue;
			}
			require_once $path;
			if (class_exists('TCPDF')) {
				return;
			}
		}
		throw new Exception('TCPDF không khả dụng — không tạo được PDF.');
	}

	protected static function h($text) {
		return htmlspecialchars(decode_html((string) $text), ENT_QUOTES, 'UTF-8');
	}

	protected static function utf($text) {
		return decode_html((string) $text);
	}

	protected static function fmtNum($n, $decimals = 2) {
		$n = (float) $n;
		if (abs($n - round($n)) < 0.0005) {
			return number_format(round($n), 0, '.', ',');
		}
		return rtrim(rtrim(number_format($n, $decimals, '.', ','), '0'), '.');
	}

	protected static function normalizePayload(array $payload) {
		$issue = isset($payload['issue']) && is_array($payload['issue']) ? $payload['issue'] : array();
		$warehouse = isset($payload['warehouse']) && is_array($payload['warehouse']) ? $payload['warehouse'] : array();
		$lines = isset($issue['lines']) && is_array($issue['lines']) ? $issue['lines'] : array();
		$createdTs = strtotime((string) (isset($issue['createdAt']) ? $issue['createdAt'] : ''));
		if (!$createdTs) {
			$createdTs = time();
		}
		$totalQty = 0.0;
		$totalValue = 0.0;
		$normLines = array();
		foreach ($lines as $line) {
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$cost = (float) (isset($line['unit_price']) ? $line['unit_price'] : 0);
			$lineValue = $qty * $cost;
			$totalQty += $qty;
			$totalValue += $lineValue;
			$sku = trim(decode_html((string) (isset($line['sku']) ? $line['sku'] : '')));
			if ($sku === '' && isset($line['line_note'])) {
				$sku = trim(decode_html((string) $line['line_note']));
			}
			$normLines[] = array(
				'sku' => $sku,
				'name' => trim(decode_html((string) (isset($line['name']) ? $line['name'] : ''))),
				'qty' => $qty,
				'cost' => $cost,
				'value' => $lineValue,
				'note' => trim(decode_html((string) (isset($line['note']) ? $line['note'] : (isset($line['lot']) ? ('Lô ' . $line['lot']) : '')))),
			);
		}
		$company = trim(decode_html((string) (isset($payload['company']) ? $payload['company'] : '')));
		if ($company === '') {
			$company = Quotes_QuoteExcelExport_Helper::nkCompanyName();
		}
		$notes = trim(decode_html((string) (isset($issue['notes']) ? $issue['notes'] : '')));
		$exportType = trim(decode_html((string) (isset($issue['exportTypeLabel']) ? $issue['exportTypeLabel'] : '')));
		if ($exportType === '') {
			$exportType = 'Xuất dùng nội bộ';
		}
		return array(
			'company' => $company,
			'docNo' => trim(decode_html((string) (isset($issue['id']) ? $issue['id'] : ''))),
			'datetime' => date('d/m/Y H:i', $createdTs),
			'branch' => trim(decode_html((string) (isset($payload['branch']) ? $payload['branch'] : 'Chi nhánh trung tâm'))),
			'whName' => trim(decode_html((string) (isset($warehouse['name']) ? $warehouse['name'] : ''))),
			'exporter' => trim(decode_html((string) (isset($issue['createdBy']) ? $issue['createdBy'] : (isset($warehouse['manager']) ? $warehouse['manager'] : '')))),
			'receiver' => trim(decode_html((string) (isset($issue['receiver']) ? $issue['receiver'] : ''))),
			'exportType' => $exportType,
			'notes' => $notes,
			'lines' => $normLines,
			'totalQty' => $totalQty,
			'totalValue' => $totalValue,
		);
	}

	protected static function buildHtml(array $d) {
		$rows = '';
		$i = 1;
		foreach ($d['lines'] as $line) {
			$rows .= '<tr>'
				. '<td class="c">' . $i++ . '</td>'
				. '<td class="l">' . self::h($line['sku']) . '</td>'
				. '<td class="l">' . self::h($line['name']) . '</td>'
				. '<td class="c">' . self::h(self::fmtNum($line['qty'])) . '</td>'
				. '<td class="r">' . self::h(self::fmtNum($line['cost'])) . '</td>'
				. '<td class="r">' . self::h(self::fmtNum($line['value'])) . '</td>'
				. '<td class="l">' . self::h($line['note']) . '</td>'
				. '</tr>';
		}
		if ($rows === '') {
			$rows = '<tr><td class="c" colspan="7">Không có dòng hàng</td></tr>';
		}
		$notesHtml = nl2br(self::h($d['notes'] !== '' ? $d['notes'] : ''));
		return '<!DOCTYPE html><html><head><meta charset="utf-8"/><title>Xuất dùng nội bộ</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:"Times New Roman",Times,serif;color:#111;background:#e8e8e8}
.desk{padding:16px}
.sheet{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:14mm 16mm 12mm;box-shadow:0 2px 12px rgba(0,0,0,.18)}
.center{text-align:center}
h1{margin:0;font-size:22pt;font-weight:700}
.code{margin-top:3mm;font-size:12pt}.code strong{font-weight:700}
.dt{margin-top:1.5mm;font-size:11pt}
.meta{margin:8mm 0 5mm;font-size:12pt;line-height:1.85}
.meta p{margin:0}
table.main{width:100%;border-collapse:collapse;table-layout:fixed;margin:0}
table.main th,table.main td{border:1px solid #000;padding:2mm 1.5mm;font-size:10.5pt;vertical-align:middle}
table.main th{font-weight:700;text-align:center;background:#fafafa}
td.c,th.c{text-align:center} td.r{text-align:right} td.l{text-align:left}
.totals{margin-top:4mm;text-align:right;font-size:12pt;line-height:1.8}
.totals strong{font-weight:700}
.bottom{display:table;width:100%;margin-top:16mm;min-height:42mm}
.bottom-l,.bottom-r{display:table-cell;vertical-align:top}
.bottom-l{width:62%;font-size:11pt;line-height:1.6}
.bottom-r{width:38%;text-align:center;font-size:11pt;vertical-align:bottom}
.bottom-r .role{font-weight:700;margin:0 0 28mm}
.bottom-r .sign-name{margin-top:0}
.note-label{font-weight:700;margin-bottom:2mm}
.note-body{min-height:18mm;white-space:pre-wrap}
@page{size:A4 portrait;margin:0}
@media print{body{background:#fff}.desk{padding:0}.sheet{box-shadow:none;margin:0}}
</style></head><body><div class="desk"><div class="sheet">
<div class="center">
  <h1>Xuất dùng nội bộ</h1>
  <div class="code">Mã xuất dùng nội bộ: <strong>' . self::h($d['docNo']) . '</strong></div>
  <div class="dt">' . self::h($d['datetime']) . '</div>
</div>
<div class="meta">
  <p>Chi nhánh: ' . self::h($d['branch']) . '</p>
  <p>Kho: ' . self::h($d['whName']) . '</p>
  <p>Người xuất: ' . self::h($d['exporter']) . '</p>
  <p>Người nhận: ' . self::h($d['receiver']) . '</p>
  <p>Loại xuất: ' . self::h($d['exportType']) . '</p>
</div>
<table class="main">
  <colgroup>
    <col style="width:6%"/><col style="width:18%"/><col style="width:30%"/>
    <col style="width:10%"/><col style="width:12%"/><col style="width:12%"/><col style="width:12%"/>
  </colgroup>
  <thead><tr>
    <th>STT</th><th>Mã hàng</th><th>Tên hàng</th><th>SL xuất</th><th>Giá vốn</th><th>Giá trị xuất</th><th>Ghi chú</th>
  </tr></thead>
  <tbody>' . $rows . '</tbody>
</table>
<div class="totals">
  <div>Tổng số lượng: <strong>' . self::h(self::fmtNum($d['totalQty'])) . '</strong></div>
  <div>Tổng giá trị: <strong>' . self::h(self::fmtNum($d['totalValue'])) . '</strong></div>
</div>
<div class="bottom">
  <div class="bottom-l">
    <div class="note-label">Ghi chú</div>
    <div class="note-body">' . ($notesHtml !== '' ? $notesHtml : '&nbsp;') . '</div>
  </div>
  <div class="bottom-r">
    <div class="role">Người lập</div>
    <div class="sign-name">' . self::h($d['exporter']) . '</div>
  </div>
</div>
</div></div></body></html>';
	}

	protected static function buildPdf(array $d) {
		self::ensureTcpdf();
		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(14, 12, 14);
		$pdf->SetAutoPageBreak(true, 12);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', 'B', 16);
		$pdf->Cell(0, 8, self::utf('Xuất dùng nội bộ'), 0, 1, 'C');
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->Cell(0, 6, self::utf('Mã xuất dùng nội bộ: ') . self::utf($d['docNo']), 0, 1, 'C');
		$pdf->Cell(0, 5, self::utf($d['datetime']), 0, 1, 'C');
		$pdf->Ln(3);
		$pdf->SetFont('dejavusans', '', 10);
		foreach (array(
			'Chi nhánh: ' . $d['branch'],
			'Kho: ' . $d['whName'],
			'Người xuất: ' . $d['exporter'],
			'Người nhận: ' . $d['receiver'],
			'Loại xuất: ' . $d['exportType'],
		) as $line) {
			$pdf->MultiCell(0, 5.5, self::utf($line), 0, 'L', false, 1);
		}
		$pdf->Ln(2);
		$w = array(12, 32, 55, 18, 22, 24, 19);
		$headers = array('STT', 'Mã hàng', 'Tên hàng', 'SL xuất', 'Giá vốn', 'Giá trị xuất', 'Ghi chú');
		$pdf->SetFont('dejavusans', 'B', 8);
		$pdf->SetFillColor(250, 250, 250);
		foreach ($headers as $idx => $h) {
			$pdf->Cell($w[$idx], 7, self::utf($h), 1, 0, 'C', true);
		}
		$pdf->Ln();
		$pdf->SetFont('dejavusans', '', 8);
		$i = 1;
		foreach ($d['lines'] as $line) {
			$name = $line['name'];
			if (function_exists('mb_strimwidth')) {
				$name = mb_strimwidth($name, 0, 40, '…', 'UTF-8');
			}
			$pdf->Cell($w[0], 6.5, (string) $i++, 1, 0, 'C');
			$pdf->Cell($w[1], 6.5, self::utf($line['sku']), 1, 0, 'L');
			$pdf->Cell($w[2], 6.5, self::utf($name), 1, 0, 'L');
			$pdf->Cell($w[3], 6.5, self::utf(self::fmtNum($line['qty'])), 1, 0, 'C');
			$pdf->Cell($w[4], 6.5, self::utf(self::fmtNum($line['cost'])), 1, 0, 'R');
			$pdf->Cell($w[5], 6.5, self::utf(self::fmtNum($line['value'])), 1, 0, 'R');
			$pdf->Cell($w[6], 6.5, self::utf($line['note']), 1, 1, 'L');
		}
		if (empty($d['lines'])) {
			$pdf->Cell(array_sum($w), 6.5, self::utf('Không có dòng hàng'), 1, 1, 'C');
		}
		$pdf->Ln(2);
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->Cell(0, 6, self::utf('Tổng số lượng: ') . self::utf(self::fmtNum($d['totalQty'])), 0, 1, 'R');
		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->Cell(0, 6, self::utf('Tổng giá trị: ') . self::utf(self::fmtNum($d['totalValue'])), 0, 1, 'R');
		$pdf->Ln(10);
		$pdf->SetFont('dejavusans', 'B', 10);
		$y = $pdf->GetY();
		$pdf->SetXY(14, $y);
		$pdf->Cell(110, 6, self::utf('Ghi chú'), 0, 1, 'L');
		$pdf->SetFont('dejavusans', '', 9);
		$pdf->MultiCell(110, 5, self::utf($d['notes'] !== '' ? $d['notes'] : ' '), 0, 'L', false, 0);
		$pdf->SetXY(130, $y);
		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->Cell(60, 6, self::utf('Người lập'), 0, 1, 'C');
		$pdf->Ln(28);
		$pdf->SetX(130);
		$pdf->SetFont('dejavusans', '', 9);
		$pdf->Cell(60, 5, self::utf($d['exporter']), 0, 1, 'C');
		return $pdf;
	}
}
