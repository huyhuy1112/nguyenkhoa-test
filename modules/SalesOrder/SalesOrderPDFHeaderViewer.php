<?php
/*+**********************************************************************************
 * Sales Order PDF header — Zoho Invoice / HubSpot quote inspired layout.
 ************************************************************************************/
include_once 'vtlib/Vtiger/PDF/inventory/HeaderViewer.php';

class SalesOrderPDFHeaderViewer extends Vtiger_PDF_InventoryHeaderViewer {

	const COLOR_PRIMARY = array(8, 160, 69);
	const COLOR_ACCENT = array(8, 160, 69);
	const COLOR_MUTED = array(100, 116, 139);
	const COLOR_TEXT = array(30, 41, 59);
	const COLOR_BOX_BG = array(247, 249, 252);
	const COLOR_BOX_BORDER = array(218, 226, 235);

	protected function textBlockHeight($pdf, $text, $width, $lineH = 4.2) {
		$text = trim((string) $text);
		if ($text === '') {
			return 0;
		}
		return max($lineH, $pdf->GetStringHeight($text, $width));
	}

	protected function buildParties($centerCol, $rightCol) {
		$billTo = array();
		foreach ($centerCol as $label => $value) {
			if ($value !== '' && $value !== null) {
				$billTo[] = array('label' => $label, 'value' => $value);
			}
		}

		$billingAddress = '';
		$shippingAddress = '';
		$docDates = array();
		if (!empty($rightCol['dates']) && is_array($rightCol['dates'])) {
			foreach ($rightCol['dates'] as $label => $value) {
				if ($value !== '' && $value !== null) {
					$docDates[] = array('label' => $label, 'value' => $value);
				}
			}
		}
		foreach ($rightCol as $label => $value) {
			if (is_array($value) || $value === '' || $value === null) {
				continue;
			}
			$lower = strtolower((string) $label);
			if (strpos($lower, 'billing') !== false) {
				$billingAddress = (string) $value;
				continue;
			}
			if (strpos($lower, 'shipping') !== false) {
				$shippingAddress = (string) $value;
			}
		}
		if ($billingAddress !== '') {
			$billTo[] = array('label' => '', 'value' => $billingAddress, 'multiline' => true);
		}

		return array($billTo, $shippingAddress, $docDates);
	}

	function totalHeight($parent) {
		if (!$this->onEveryPage() || !$this->model) {
			return parent::totalHeight($parent);
		}

		$pdf = $parent->getPDF();
		$w = $parent->getTotalWidth();
		$boxW = ($w - 8) / 2;
		$modelColumns = $this->model->get('columns');
		list($billTo, $shippingAddress) = $this->buildParties($modelColumns[1], $modelColumns[2]);

		$fromText = trim((string) ($modelColumns[0]['summary'] ?? '') . "\n" . (string) ($modelColumns[0]['content'] ?? ''));
		$fromH = $this->textBlockHeight($pdf, $fromText, $boxW - 6) + 8;

		$billText = '';
		foreach ($billTo as $row) {
			$billText .= ($row['label'] !== '' ? $row['label'] . ': ' : '') . $row['value'] . "\n";
		}
		$billH = $this->textBlockHeight($pdf, $billText, $boxW - 6) + 8;

		$shipH = 0;
		if ($shippingAddress !== '') {
			$shipH = $this->textBlockHeight($pdf, $shippingAddress, $w - 6) + 14;
		}

		$partyH = max($fromH, $billH);
		return 6 + 24 + 6 + $partyH + $shipH + 10;
	}

	protected function drawFilledBox($pdf, $x, $y, $w, $h) {
		$pdf->SetFillColor(self::COLOR_BOX_BG[0], self::COLOR_BOX_BG[1], self::COLOR_BOX_BG[2]);
		$pdf->SetDrawColor(self::COLOR_BOX_BORDER[0], self::COLOR_BOX_BORDER[1], self::COLOR_BOX_BORDER[2]);
		$pdf->Rect($x, $y, $w, $h, 'DF');
	}

	protected function drawStatusBadge($pdf, $x, $y, $status, $alignRight = 0) {
		$status = trim((string) $status);
		if ($status === '') {
			return $y;
		}

		$pdf->SetFont('freeserif', 'B', 7.5);
		$badgeText = strtoupper($status);
		$badgeW = min(50, $pdf->GetStringWidth($badgeText) + 8);
		$badgeX = $alignRight > 0 ? ($alignRight - $badgeW) : $x;

		$pdf->SetFillColor(self::COLOR_ACCENT[0], self::COLOR_ACCENT[1], self::COLOR_ACCENT[2]);
		$pdf->SetTextColor(255, 255, 255);
		$pdf->Rect($badgeX, $y, $badgeW, 5.5, 'F');
		$pdf->MultiCell($badgeW, 5.5, $badgeText, 0, 'C', false, 1, $badgeX, $y + 0.8);
		return $pdf->GetY() + 2;
	}

	protected function drawDateGrid($pdf, $x, $y, $w, $docDates) {
		if (empty($docDates)) {
			return $y;
		}

		$colW = $w / min(2, php7_count($docDates));
		$gridY = $y;
		$i = 0;
		foreach ($docDates as $meta) {
			$cellX = $x + ($i * $colW);
			$pdf->SetFont('freeserif', 'B', 7);
			$pdf->SetTextColor(self::COLOR_MUTED[0], self::COLOR_MUTED[1], self::COLOR_MUTED[2]);
			$pdf->MultiCell($colW - 2, 3.8, strtoupper((string) $meta['label']), 0, 'R', false, 1, $cellX, $gridY);
			$pdf->SetFont('freeserif', '', 8.5);
			$pdf->SetTextColor(self::COLOR_TEXT[0], self::COLOR_TEXT[1], self::COLOR_TEXT[2]);
			$pdf->MultiCell($colW - 2, 4.5, (string) $meta['value'], 0, 'R', false, 1, $cellX, $pdf->GetY());
			$i++;
		}
		return max($pdf->GetY(), $gridY + 10);
	}

	protected function padHeaderToFrame($pdf, $headerFrame) {
		$targetBottom = $headerFrame->y + $headerFrame->h;
		$currentY = $pdf->GetY();
		if ($currentY < $targetBottom) {
			$pdf->MultiCell($headerFrame->w, $targetBottom - $currentY, '', 0, 'L', 0, 1, $headerFrame->x, $currentY);
		}
	}

	function display($parent) {
		$pdf = $parent->getPDF();
		$headerFrame = $parent->getHeaderFrame();
		if (!$this->model) {
			return;
		}

		$x = $headerFrame->x;
		$y = $headerFrame->y;
		$w = $headerFrame->w;
		$boxW = ($w - 8) / 2;
		$rightBoxX = $x + $boxW + 8;

		$modelColumns = $this->model->get('columns');
		$leftCol = $modelColumns[0];
		list($billTo, $shippingAddress, $docDates) = $this->buildParties($modelColumns[1], $modelColumns[2]);
		$status = trim((string) $this->model->get('status'));

		// Brand accent strip (Zoho-style)
		$pdf->SetFillColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
		$pdf->Rect($x, $y, $w, 2.5, 'F');
		$y += 5;

		$titleBlockX = $x + $w * 0.42;
		$titleBlockW = $w * 0.58;
		$logoY = $y;

		$logoPath = $leftCol['logo'] ?? '';
		if ($logoPath && is_readable($logoPath)) {
			$info = @getimagesize($logoPath);
			$imgW = 42;
			$imgH = 14;
			if ($info && !empty($info[0]) && !empty($info[1])) {
				$imgH = min(16, max(11, $imgW * ($info[1] / $info[0])));
			}
			$pdf->Image($logoPath, $x, $logoY, $imgW, $imgH);
		} else {
			$pdf->SetFont('freeserif', 'B', 11);
			$pdf->SetTextColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
			$pdf->MultiCell($w * 0.4, 6, (string) ($leftCol['summary'] ?? ''), 0, 'L', false, 1, $x, $logoY);
		}

		$pdf->SetFont('freeserif', 'B', 20);
		$pdf->SetTextColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
		$pdf->MultiCell($titleBlockW, 9, 'SALES ORDER', 0, 'R', false, 1, $titleBlockX, $y);

		$title = (string) $this->model->get('title');
		$pdf->SetFont('freeserif', '', 10);
		$pdf->SetTextColor(self::COLOR_TEXT[0], self::COLOR_TEXT[1], self::COLOR_TEXT[2]);
		$pdf->MultiCell($titleBlockW, 5, $title, 0, 'R', false, 1, $titleBlockX, $pdf->GetY() + 0.5);

		$badgeY = $this->drawStatusBadge($pdf, $titleBlockX, $pdf->GetY() + 1, $status, $x + $w);
		$dateY = $this->drawDateGrid($pdf, $titleBlockX, $badgeY, $titleBlockW, $docDates);

		$y = max($logoY + 18, $dateY) + 6;

		$fromText = trim((string) ($leftCol['summary'] ?? '') . "\n" . (string) ($leftCol['content'] ?? ''));
		$billText = '';
		foreach ($billTo as $row) {
			$billText .= ($row['label'] !== '' ? $row['label'] . ': ' : '') . $row['value'] . "\n";
		}
		$boxInnerW = $boxW - 6;
		$fromInnerH = $this->textBlockHeight($pdf, $fromText, $boxInnerW) + 2;
		$billInnerH = $this->textBlockHeight($pdf, $billText, $boxInnerW) + 2;
		$boxH = max($fromInnerH, $billInnerH) + 10;

		$this->drawFilledBox($pdf, $x, $y, $boxW, $boxH);
		$this->drawFilledBox($pdf, $rightBoxX, $y, $boxW, $boxH);

		$innerY = $y + 3;
		$pdf->SetFont('freeserif', 'B', 7.5);
		$pdf->SetTextColor(self::COLOR_MUTED[0], self::COLOR_MUTED[1], self::COLOR_MUTED[2]);
		$pdf->MultiCell($boxInnerW, 4, 'FROM / NGUOI BAN', 0, 'L', false, 1, $x + 3, $innerY);
		$pdf->MultiCell($boxInnerW, 4, 'BILL TO / KHACH HANG', 0, 'L', false, 1, $rightBoxX + 3, $innerY);

		$contentY = $innerY + 5;
		$pdf->SetFont('freeserif', 'B', 8.5);
		$pdf->SetTextColor(self::COLOR_TEXT[0], self::COLOR_TEXT[1], self::COLOR_TEXT[2]);
		$pdf->MultiCell($boxInnerW, 4.5, (string) ($leftCol['summary'] ?? ''), 0, 'L', false, 1, $x + 3, $contentY);
		$pdf->SetFont('freeserif', '', 8);
		$pdf->SetTextColor(71, 85, 105);
		$pdf->MultiCell($boxInnerW, 4.2, (string) ($leftCol['content'] ?? ''), 0, 'L', false, 1, $x + 3, $pdf->GetY() + 0.3);

		$billY = $contentY;
		foreach ($billTo as $row) {
			if (!empty($row['multiline'])) {
				$pdf->SetFont('freeserif', '', 8);
				$pdf->SetTextColor(71, 85, 105);
				$pdf->MultiCell($boxInnerW, 4.2, (string) $row['value'], 0, 'L', false, 1, $rightBoxX + 3, $billY);
				$billY = $pdf->GetY() + 0.3;
				continue;
			}
			$pdf->SetFont('freeserif', 'B', 7.5);
			$pdf->SetTextColor(self::COLOR_MUTED[0], self::COLOR_MUTED[1], self::COLOR_MUTED[2]);
			$pdf->MultiCell(30, 4.2, $row['label'], 0, 'L', false, 0, $rightBoxX + 3, $billY);
			$pdf->SetFont('freeserif', '', 8);
			$pdf->SetTextColor(self::COLOR_TEXT[0], self::COLOR_TEXT[1], self::COLOR_TEXT[2]);
			$pdf->MultiCell($boxInnerW - 30, 4.2, (string) $row['value'], 0, 'L', false, 1, $rightBoxX + 33, $billY);
			$billY = $pdf->GetY() + 0.3;
		}

		$y = $y + $boxH + 4;

		if ($shippingAddress !== '') {
			$shipH = $this->textBlockHeight($pdf, $shippingAddress, $w - 6) + 10;
			$this->drawFilledBox($pdf, $x, $y, $w, $shipH);
			$pdf->SetFont('freeserif', 'B', 7.5);
			$pdf->SetTextColor(self::COLOR_MUTED[0], self::COLOR_MUTED[1], self::COLOR_MUTED[2]);
			$pdf->MultiCell($w - 6, 4, 'SHIP TO / DIA CHI GIAO HANG', 0, 'L', false, 1, $x + 3, $y + 3);
			$pdf->SetFont('freeserif', '', 8);
			$pdf->SetTextColor(71, 85, 105);
			$pdf->MultiCell($w - 6, 4.2, $shippingAddress, 0, 'L', false, 1, $x + 3, $pdf->GetY() + 0.5);
			$y = $y + $shipH + 2;
		}

		$pdf->SetDrawColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
		$pdf->SetLineWidth(0.25);
		$pdf->Line($x, $y, $x + $w, $y);
		$pdf->SetY($y + 3);
		$this->padHeaderToFrame($pdf, $headerFrame);
	}
}
