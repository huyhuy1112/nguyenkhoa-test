<?php
/*+**********************************************************************************
 * Sales Order PDF footer — CRM-style notes & terms section.
 ************************************************************************************/
include_once 'vtlib/Vtiger/PDF/inventory/FooterViewer.php';

class SalesOrderPDFFooterViewer extends Vtiger_PDF_InventoryFooterViewer {

	function display($parent) {
		$pdf = $parent->getPDF();
		$footerFrame = $parent->getFooterFrame();
		if (!$this->model) {
			return;
		}

		$x = $footerFrame->x;
		$y = $footerFrame->y;
		$w = $footerFrame->w;

		$description = trim((string) $this->model->get(self::$DESCRIPTION_DATA_KEY));
		$terms = trim((string) $this->model->get(self::$TERMSANDCONDITION_DATA_KEY));

		$pdf->SetDrawColor(226, 232, 240);
		$pdf->Line($x, $y, $x + $w, $y);
		$y += 4;

		if ($description !== '') {
			$y = $this->drawSection($pdf, $x, $y, $w, $this->labelModel->get(self::$DESCRIPTION_LABEL_KEY), $description);
			$y += 3;
		}

		if ($terms !== '') {
			if ($description !== '') {
				$pdf->Line($x, $y, $x + $w, $y);
				$y += 3;
			}
			$y = $this->drawSection($pdf, $x, $y, $w, $this->labelModel->get(self::$TERMSANDCONDITION_LABEL_KEY), $terms);
		}

		$y += 4;
		$pdf->SetFont('freeserif', 'I', 8);
		$pdf->SetTextColor(100, 116, 139);
		$pdf->MultiCell($w, 4.5, 'Cam on quy khach da tin tuong / Thank you for your business.', 0, 'C', false, 1, $x, $y);

		$pdf->MultiCell($footerFrame->w, max(0, $footerFrame->h - ($pdf->GetY() - $footerFrame->y)), '', 0, 'L', 0, 1, $footerFrame->x, $footerFrame->y);
	}

	protected function drawSection($pdf, $x, $y, $w, $title, $body) {
		$pdf->SetFont('freeserif', 'B', 8.5);
		$pdf->SetTextColor(30, 58, 95);
		$pdf->MultiCell($w, 5, (string) $title, 0, 'L', false, 1, $x, $y);
		$pdf->SetFont('freeserif', '', 7.8);
		$pdf->SetTextColor(51, 65, 85);
		$pdf->MultiCell($w, 4, (string) $body, 0, 'L', false, 1, $x, $pdf->GetY() + 1);
		return $pdf->GetY();
	}

	function totalHeight($parent) {
		if (!$this->model || !$this->onEveryPage()) {
			return parent::totalHeight($parent);
		}
		$pdf = $parent->getPDF();
		$w = $parent->getTotalWidth();
		$height = 8;

		$description = trim((string) $this->model->get(self::$DESCRIPTION_DATA_KEY));
		if ($description !== '') {
			$height += 6 + $pdf->GetStringHeight($description, $w);
		}

		$terms = trim((string) $this->model->get(self::$TERMSANDCONDITION_DATA_KEY));
		if ($terms !== '') {
			$height += 8 + $pdf->GetStringHeight($terms, $w);
		}

		return $height + 10;
	}
}
