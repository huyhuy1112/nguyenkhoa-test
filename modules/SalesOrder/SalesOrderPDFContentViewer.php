<?php
/*+**********************************************************************************
 * Sales Order PDF line items — modern CRM table (HubSpot / Zoho style).
 ************************************************************************************/
include_once 'vtlib/Vtiger/PDF/inventory/ContentViewer2.php';

class SalesOrderPDFContentViewer extends Vtiger_PDF_InventoryTaxGroupContentViewer {

	const COLOR_HEADER_BG = array(8, 160, 69);
	const COLOR_HEADER_TEXT = array(255, 255, 255);
	const COLOR_ROW_ALT = array(248, 250, 252);
	const COLOR_BORDER = array(226, 232, 240);
	const COLOR_SUMMARY_BG = array(236, 253, 245);
	const COLOR_GRAND = array(6, 122, 54);

	function __construct() {
		parent::__construct();
		$this->cells = array(
			'Code' => 28,
			'Name' => 62,
			'Quantity' => 18,
			'Price' => 24,
			'Discount' => 18,
			'Total' => 28,
			'Tax' => 0,
		);
		$this->headerRowHeight = 7;
	}

	function initDisplay($parent) {
		$pdf = $parent->getPDF();
		$contentFrame = $parent->getContentFrame();

		if (!$parent->onLastPage()) {
			$this->displayWatermark($parent);
		}

		$offsetX = 0;
		$pdf->SetFillColor(self::COLOR_HEADER_BG[0], self::COLOR_HEADER_BG[1], self::COLOR_HEADER_BG[2]);
		$pdf->SetTextColor(self::COLOR_HEADER_TEXT[0], self::COLOR_HEADER_TEXT[1], self::COLOR_HEADER_TEXT[2]);
		$pdf->SetFont('freeserif', 'B', 8);
		$pdf->SetDrawColor(self::COLOR_HEADER_BG[0], self::COLOR_HEADER_BG[1], self::COLOR_HEADER_BG[2]);

		foreach ($this->cells as $cellName => $cellWidth) {
			if ($cellWidth <= 0) {
				continue;
			}
			$cellLabel = ($this->labelModel) ? $this->labelModel->get($cellName, $cellName) : $cellName;
			$pdf->MultiCell($cellWidth, $this->headerRowHeight, $cellLabel, 0, 'L', true, 0, $contentFrame->x + $offsetX, $contentFrame->y);
			$offsetX += $cellWidth;
		}
		$pdf->Ln(0);
		$pdf->SetTextColor(15, 23, 42);
		$pdf->SetFont('freeserif', '', 8);
		$contentFrame->y += $this->headerRowHeight;
	}

	function drawCellBorder($parent, $cellHeights = false) {
		// Minimal borders — row separators only (drawn per row in displayPreLastPage).
	}

	function displayPreLastPage($parent) {
		$models = $this->contentModels;
		$totalModels = php7_count($models);
		$pdf = $parent->getPDF();
		$parent->createPage();
		$contentFrame = $parent->getContentFrame();

		$contentLineX = $contentFrame->x;
		$contentLineY = $contentFrame->y;
		$overflowOffsetH = 8;
		$rowIndex = 0;

		for ($index = 0; $index < $totalModels; ++$index) {
			$model = $models[$index];
			$contentHeight = 1;

			foreach ($this->cells as $cellName => $cellWidth) {
				if ($cellWidth <= 0) {
					continue;
				}
				$contentString = $model->get($cellName);
				if (empty($contentString)) {
					continue;
				}
				$h = $pdf->GetStringHeight($contentString, $cellWidth);
				if ($h > $contentHeight) {
					$contentHeight = $h;
				}
			}
			$contentHeight = max(6, $contentHeight);

			if (ceil($contentLineY + $contentHeight) > ceil($contentFrame->h + $contentFrame->y)) {
				$parent->createPage();
				$contentFrame = $parent->getContentFrame();
				$contentLineX = $contentFrame->x;
				$contentLineY = $contentFrame->y;
				$rowIndex = 0;
			}

			if ($rowIndex % 2 === 1) {
				$pdf->SetFillColor(self::COLOR_ROW_ALT[0], self::COLOR_ROW_ALT[1], self::COLOR_ROW_ALT[2]);
				$rowW = array_sum(array_filter($this->cells));
				$pdf->Rect($contentLineX, $contentLineY, $rowW, $contentHeight, 'F');
			}

			$offsetX = 0;
			$pdf->SetFont('freeserif', '', 8);
			$pdf->SetTextColor(15, 23, 42);
			foreach ($this->cells as $cellName => $cellWidth) {
				if ($cellWidth <= 0) {
					continue;
				}
				$align = ($cellName === 'Price' || $cellName === 'Total' || $cellName === 'Discount') ? 'R' : 'L';
				$pdf->MultiCell($cellWidth, $contentHeight, $model->get($cellName), 0, $align, false, 0, $contentLineX + $offsetX, $contentLineY);
				$offsetX += $cellWidth;
			}

			$pdf->SetDrawColor(self::COLOR_BORDER[0], self::COLOR_BORDER[1], self::COLOR_BORDER[2]);
			$pdf->Line($contentLineX, $contentLineY + $contentHeight, $contentLineX + array_sum(array_filter($this->cells)), $contentLineY + $contentHeight);

			$contentLineY += $contentHeight;
			$rowIndex++;

			$commentContent = $model->get('Comment');
			if (!empty($commentContent)) {
				$commentWidth = $this->cells['Name'] + $this->cells['Code'];
				$commentX = $contentLineX + $this->cells['Code'];
				$commentHeight = max(5, $pdf->GetStringHeight($commentContent, $commentWidth));
				if (ceil($contentLineY + $commentHeight + $overflowOffsetH) > ceil($contentFrame->h + $contentFrame->y)) {
					$parent->createPage();
					$contentFrame = $parent->getContentFrame();
					$contentLineX = $contentFrame->x;
					$contentLineY = $contentFrame->y;
				}
				$pdf->SetFont('freeserif', 'I', 7.5);
				$pdf->SetTextColor(100, 116, 139);
				$pdf->MultiCell($commentWidth, $commentHeight, $commentContent, 0, 'L', false, 1, $commentX, $contentLineY);
				$pdf->SetFont('freeserif', '', 8);
				$pdf->SetTextColor(15, 23, 42);
				$contentLineY = $pdf->GetY();
			}
		}

		$this->drawSummaryBox($parent, $contentFrame, $contentLineY);
		$this->onSummaryPage = true;
	}

	protected function drawSummaryBox($parent, $contentFrame, $contentLineY) {
		if (!$this->contentSummaryModel) {
			return;
		}

		$pdf = $parent->getPDF();
		$summaryKeys = $this->contentSummaryModel->keys();
		$summaryCount = php7_count($summaryKeys);
		if ($summaryCount === 0) {
			return;
		}

		$rowH = 5.5;
		$summaryTotalHeight = $rowH * $summaryCount + 6;
		$boxW = 78;
		$boxX = $contentFrame->x + $contentFrame->w - $boxW;

		if (($contentFrame->h + $contentFrame->y) - ($contentLineY + 8) < $summaryTotalHeight) {
			$parent->createPage();
			$contentFrame = $parent->getContentFrame();
			$contentLineY = $contentFrame->y;
			$boxX = $contentFrame->x + $contentFrame->w - $boxW;
		}

		$boxY = $contentLineY + 6;
		$pdf->SetFillColor(self::COLOR_SUMMARY_BG[0], self::COLOR_SUMMARY_BG[1], self::COLOR_SUMMARY_BG[2]);
		$pdf->SetDrawColor(self::COLOR_BORDER[0], self::COLOR_BORDER[1], self::COLOR_BORDER[2]);
		$pdf->Rect($boxX, $boxY, $boxW, $summaryTotalHeight, 'DF');

		$labelW = 42;
		$valueW = $boxW - $labelW - 4;
		$lineY = $boxY + 3;
		$lastIndex = $summaryCount - 1;

		foreach ($summaryKeys as $i => $key) {
			$isGrand = (stripos($key, 'Grand') !== false || stripos($key, 'Tổng') !== false);
			if ($isGrand) {
				$pdf->SetFont('freeserif', 'B', 9);
				$pdf->SetTextColor(self::COLOR_GRAND[0], self::COLOR_GRAND[1], self::COLOR_GRAND[2]);
			} else {
				$pdf->SetFont('freeserif', '', 8);
				$pdf->SetTextColor(71, 85, 105);
			}
			$pdf->MultiCell($labelW, $rowH, $key, 0, 'L', false, 0, $boxX + 2, $lineY);
			$pdf->MultiCell($valueW, $rowH, $this->contentSummaryModel->get($key), 0, 'R', false, 1, $boxX + 2 + $labelW, $lineY);
			if ($isGrand && $i === $lastIndex) {
				$pdf->SetDrawColor(self::COLOR_GRAND[0], self::COLOR_GRAND[1], self::COLOR_GRAND[2]);
				$pdf->Line($boxX + 2, $lineY - 1, $boxX + $boxW - 2, $lineY - 1);
			}
			$lineY = $pdf->GetY();
		}
	}
}
