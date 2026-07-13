<?php
/*+***********************************************************************************
 * PHIẾU CHUYỂN HÀNG — mẫu in chuyển kho nội bộ (không dùng 02-VT xuất bán).
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class Warehouse_OutboundTransferPdf_Helper {

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

	protected static function fmtNum($n) {
		$n = (float) $n;
		if (abs($n - round($n)) < 0.0005) {
			return number_format(round($n), 0, '.', ',');
		}
		return rtrim(rtrim(number_format($n, 2, '.', ','), '0'), '.');
	}

	protected static function normalizePayload(array $payload) {
		$issue = isset($payload['issue']) && is_array($payload['issue']) ? $payload['issue'] : array();
		$warehouse = isset($payload['warehouse']) && is_array($payload['warehouse']) ? $payload['warehouse'] : array();
		$toWh = isset($payload['toWarehouse']) && is_array($payload['toWarehouse']) ? $payload['toWarehouse'] : array();
		$lines = isset($issue['lines']) && is_array($issue['lines']) ? $issue['lines'] : array();
		$createdTs = strtotime((string) (isset($issue['createdAt']) ? $issue['createdAt'] : ''));
		if (!$createdTs) {
			$createdTs = time();
		}
		$normLines = array();
		foreach ($lines as $line) {
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$price = (float) (isset($line['unit_price']) ? $line['unit_price'] : 0);
			$sku = trim(decode_html((string) (isset($line['sku']) ? $line['sku'] : '')));
			if ($sku === '' && isset($line['line_note'])) {
				$sku = trim(decode_html((string) $line['line_note']));
			}
			$normLines[] = array(
				'sku' => $sku,
				'name' => trim(decode_html((string) (isset($line['name']) ? $line['name'] : ''))),
				'qtySend' => $qty,
				'qtyRecv' => $qty,
				'price' => $price,
			);
		}
		$company = trim(decode_html((string) (isset($payload['company']) ? $payload['company'] : '')));
		if ($company === '') {
			$company = Quotes_QuoteExcelExport_Helper::nkCompanyName();
		}
		$companyAddress = trim(decode_html((string) (isset($payload['companyAddress']) ? $payload['companyAddress'] : '')));
		if ($companyAddress === '') {
			$companyAddress = Quotes_QuoteExcelExport_Helper::nkAddress();
		}
		$companyPhone = trim(decode_html((string) (isset($payload['companyPhone']) ? $payload['companyPhone'] : '')));
		if ($companyPhone === '') {
			$companyPhone = Quotes_QuoteExcelExport_Helper::nkPhone();
		}
		$fromBranch = trim(decode_html((string) (isset($payload['branch']) ? $payload['branch'] : 'Chi nhánh trung tâm')));
		$toBranch = trim(decode_html((string) (isset($payload['toBranch']) ? $payload['toBranch'] : $fromBranch)));
		$toWhName = trim(decode_html((string) (isset($toWh['name']) ? $toWh['name'] : '')));
		if ($toWhName === '') {
			$toWhName = trim(decode_html((string) (isset($issue['receiver']) ? $issue['receiver'] : '')));
		}
		$sender = trim(decode_html((string) (isset($issue['createdBy']) ? $issue['createdBy'] : (isset($warehouse['manager']) ? $warehouse['manager'] : ''))));
		$receiver = trim(decode_html((string) (isset($issue['receiverPerson']) ? $issue['receiverPerson'] : '')));
		if ($receiver === '' && isset($issue['receiver']) && $toWhName !== (string) $issue['receiver']) {
			$receiver = trim(decode_html((string) $issue['receiver']));
		}
		$sendNote = trim(decode_html((string) (isset($issue['notes']) ? $issue['notes'] : '')));
		if ($sendNote === '' && !empty($issue['reason'])) {
			$sendNote = trim(decode_html((string) $issue['reason']));
		}
		$recvNote = trim(decode_html((string) (isset($issue['receiveNotes']) ? $issue['receiveNotes'] : '')));
		return array(
			'company' => $company,
			'companyAddress' => $companyAddress,
			'companyPhone' => $companyPhone,
			'docNo' => trim(decode_html((string) (isset($issue['id']) ? $issue['id'] : (isset($issue['soRef']) ? $issue['soRef'] : '')))),
			'datetime' => date('d/m/Y H:i', $createdTs),
			'fromBranch' => $fromBranch,
			'toBranch' => $toBranch,
			'fromWh' => trim(decode_html((string) (isset($warehouse['name']) ? $warehouse['name'] : ''))),
			'toWh' => $toWhName,
			'sender' => $sender,
			'receiver' => $receiver,
			'sendNote' => $sendNote,
			'recvNote' => $recvNote,
			'lines' => $normLines,
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
				. '<td class="c">' . self::h(self::fmtNum($line['qtySend'])) . '</td>'
				. '<td class="c">' . self::h(self::fmtNum($line['qtyRecv'])) . '</td>'
				. '<td class="r">' . self::h(self::fmtNum($line['price'])) . '</td>'
				. '</tr>';
		}
		if ($rows === '') {
			$rows = '<tr><td class="c" colspan="6">Không có dòng hàng</td></tr>';
		}
		return '<!DOCTYPE html><html><head><meta charset="utf-8"/><title>Phiếu chuyển hàng</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:"Times New Roman",Times,serif;color:#111;background:#e8e8e8}
.desk{padding:16px}
.sheet{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:14mm 16mm 12mm;box-shadow:0 2px 12px rgba(0,0,0,.18)}
.company{font-size:11pt;line-height:1.45;margin-bottom:6mm}
.company strong{font-size:12pt}
.center{text-align:center}
h1{margin:0;font-size:20pt;font-weight:700;letter-spacing:.4px}
.sub{margin-top:2mm;font-size:11pt}
.sub em{font-style:italic}
.meta{margin:7mm 0 5mm;font-size:12pt;line-height:1.85}
.meta p{margin:0}
table.main{width:100%;border-collapse:collapse;table-layout:fixed}
table.main th,table.main td{border:1px solid #000;padding:2mm 1.5mm;font-size:10.5pt;vertical-align:middle}
table.main th{font-weight:700;text-align:center;background:#fafafa}
td.c{text-align:center} td.r{text-align:right} td.l{text-align:left}
.notes{margin-top:6mm;font-size:11pt;line-height:1.7}
.notes p{margin:0 0 2mm}
.signs{width:100%;margin-top:14mm;border-collapse:collapse}
.signs td{width:50%;text-align:center;vertical-align:top;font-size:11pt;border:0}
.signs .role{font-weight:700;margin-bottom:22mm}
@page{size:A4 portrait;margin:0}
@media print{body{background:#fff}.desk{padding:0}.sheet{box-shadow:none;margin:0}}
</style></head><body><div class="desk"><div class="sheet">
<div class="company">
  <strong>' . self::h($d['company']) . '</strong><br/>
  ' . self::h($d['companyAddress']) . '<br/>
  SĐT: ' . self::h($d['companyPhone']) . '
</div>
<div class="center">
  <h1>PHIẾU CHUYỂN HÀNG</h1>
  <div class="sub">Mã phiếu: ' . self::h($d['docNo']) . '</div>
  <div class="sub"><em>Ngày chuyển: ' . self::h($d['datetime']) . '</em></div>
</div>
<div class="meta">
  <p>Chi nhánh chuyển: ' . self::h($d['fromBranch']) . (!empty($d['fromWh']) ? (' (' . self::h($d['fromWh']) . ')') : '') . '</p>
  <p>Người chuyển: ' . self::h($d['sender']) . '</p>
  <p>Chi nhánh nhận: ' . self::h($d['toBranch']) . (!empty($d['toWh']) ? (' (' . self::h($d['toWh']) . ')') : '') . '</p>
  <p>Người nhận: ' . self::h($d['receiver']) . '</p>
</div>
<table class="main">
  <colgroup>
    <col style="width:7%"/><col style="width:20%"/><col style="width:35%"/>
    <col style="width:12%"/><col style="width:12%"/><col style="width:14%"/>
  </colgroup>
  <thead><tr>
    <th>STT</th><th>Mã hàng</th><th>Tên hàng</th><th>SL chuyển</th><th>SL nhận</th><th>Giá chuyển</th>
  </tr></thead>
  <tbody>' . $rows . '</tbody>
</table>
<div class="notes">
  <p><strong>Ghi chú chi nhánh chuyển:</strong> ' . self::h($d['sendNote']) . '</p>
  <p><strong>Ghi chú chi nhánh nhận:</strong> ' . self::h($d['recvNote']) . '</p>
</div>
<table class="signs"><tr>
  <td><div class="role">Người chuyển</div><div>' . self::h($d['sender']) . '</div></td>
  <td><div class="role">Người nhận</div><div>' . self::h($d['receiver']) . '</div></td>
</tr></table>
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
		$pdf->SetFont('dejavusans', 'B', 11);
		$pdf->Cell(0, 5.5, self::utf($d['company']), 0, 1, 'L');
		$pdf->SetFont('dejavusans', '', 9);
		$pdf->MultiCell(0, 4.5, self::utf($d['companyAddress']), 0, 'L', false, 1);
		$pdf->Cell(0, 4.5, self::utf('SĐT: ' . $d['companyPhone']), 0, 1, 'L');
		$pdf->Ln(3);
		$pdf->SetFont('dejavusans', 'B', 15);
		$pdf->Cell(0, 8, self::utf('PHIẾU CHUYỂN HÀNG'), 0, 1, 'C');
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->Cell(0, 5.5, self::utf('Mã phiếu: ' . $d['docNo']), 0, 1, 'C');
		$pdf->SetFont('dejavusans', 'I', 10);
		$pdf->Cell(0, 5.5, self::utf('Ngày chuyển: ' . $d['datetime']), 0, 1, 'C');
		$pdf->Ln(2);
		$pdf->SetFont('dejavusans', '', 10);
		$fromLabel = $d['fromBranch'] . ($d['fromWh'] !== '' ? (' (' . $d['fromWh'] . ')') : '');
		$toLabel = $d['toBranch'] . ($d['toWh'] !== '' ? (' (' . $d['toWh'] . ')') : '');
		foreach (array(
			'Chi nhánh chuyển: ' . $fromLabel,
			'Người chuyển: ' . $d['sender'],
			'Chi nhánh nhận: ' . $toLabel,
			'Người nhận: ' . $d['receiver'],
		) as $line) {
			$pdf->MultiCell(0, 5.5, self::utf($line), 0, 'L', false, 1);
		}
		$pdf->Ln(2);
		$w = array(12, 36, 68, 22, 22, 22);
		$headers = array('STT', 'Mã hàng', 'Tên hàng', 'SL chuyển', 'SL nhận', 'Giá chuyển');
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
				$name = mb_strimwidth($name, 0, 48, '…', 'UTF-8');
			}
			$pdf->Cell($w[0], 6.5, (string) $i++, 1, 0, 'C');
			$pdf->Cell($w[1], 6.5, self::utf($line['sku']), 1, 0, 'L');
			$pdf->Cell($w[2], 6.5, self::utf($name), 1, 0, 'L');
			$pdf->Cell($w[3], 6.5, self::utf(self::fmtNum($line['qtySend'])), 1, 0, 'C');
			$pdf->Cell($w[4], 6.5, self::utf(self::fmtNum($line['qtyRecv'])), 1, 0, 'C');
			$pdf->Cell($w[5], 6.5, self::utf(self::fmtNum($line['price'])), 1, 1, 'R');
		}
		if (empty($d['lines'])) {
			$pdf->Cell(array_sum($w), 6.5, self::utf('Không có dòng hàng'), 1, 1, 'C');
		}
		$pdf->Ln(4);
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->MultiCell(0, 5.5, self::utf('Ghi chú chi nhánh chuyển: ' . $d['sendNote']), 0, 'L', false, 1);
		$pdf->MultiCell(0, 5.5, self::utf('Ghi chú chi nhánh nhận: ' . $d['recvNote']), 0, 'L', false, 1);
		$pdf->Ln(10);
		$y = $pdf->GetY();
		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->SetXY(14, $y);
		$pdf->Cell(90, 6, self::utf('Người chuyển'), 0, 0, 'C');
		$pdf->Cell(90, 6, self::utf('Người nhận'), 0, 1, 'C');
		$pdf->Ln(18);
		$pdf->SetFont('dejavusans', '', 9);
		$pdf->Cell(90, 5, self::utf($d['sender']), 0, 0, 'C');
		$pdf->Cell(90, 5, self::utf($d['receiver']), 0, 1, 'C');
		return $pdf;
	}
}
