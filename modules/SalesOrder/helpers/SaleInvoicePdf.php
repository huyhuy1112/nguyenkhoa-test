<?php
/*+***********************************************************************************
 * SalesOrder / Quotes invoice PDF — same layout as Excel "HÓA ĐƠN ĐẶT HÀNG".
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
require_once 'modules/Quotes/helpers/QuoteBaService.php';
include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';

class SalesOrder_SaleInvoicePdf_Helper {

	const FONT = 'dejavusans';
	const GREEN = array(8, 160, 69);

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
	 * @param CRMEntity $focus
	 * @param string $moduleName
	 * @return Vtiger_PDF_TCPDF
	 */
	public static function build(CRMEntity $focus, $moduleName = 'SalesOrder') {
		$ctx = Quotes_QuoteExcelExport_Helper::getSaleExportContext($focus);
		$companyName = Quotes_QuoteExcelExport_Helper::nkCompanyName();
		$address = Quotes_QuoteExcelExport_Helper::nkAddress();
		$phone = Quotes_QuoteExcelExport_Helper::nkPhone();
		$logoPath = Quotes_QuoteExcelExport_Helper::resolveLogoPathPublic();
		if ($logoPath === '' && !empty($ctx['company']['logo_path'])) {
			$logoPath = $ctx['company']['logo_path'];
		}

		$docNo = $ctx['quote_no'] !== '' ? $ctx['quote_no'] : (($moduleName === 'SalesOrder' ? 'SO' : 'DH') . $focus->id);
		$dateLabel = Quotes_QuoteExcelExport_Helper::formatDateViLongPublic($ctx['quote_date']);
		$customer = $ctx['account_name'] !== '' ? $ctx['account_name'] : $ctx['receiver'];
		$customerPhone = (string) ($ctx['phone'] ?? '');
		$customerAddress = (string) ($ctx['address'] ?? '');
		$notes = isset($ctx['notes']) ? trim((string) $ctx['notes']) : Quotes_QuoteExcelExport_Helper::resolveExportNotesPublic($focus, $ctx['terms_html'] ?? '');

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
		$pdf->SetTitle('HÓA ĐƠN ĐẶT HÀNG ' . $docNo);
		$pdf->SetMargins(14, 12, 14);
		$pdf->SetAutoPageBreak(true, 14);
		$pdf->AddPage();
		$pdf->SetFont(self::FONT, '', 10);

		$margins = $pdf->getMargins();
		$pageW = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
		$x = $margins['left'];
		$y = $pdf->GetY();

		// Logo centered (same as Excel)
		if ($logoPath !== '' && is_readable($logoPath) && Quotes_QuoteBaService_Helper::isValidQuoteLogoImage($logoPath)) {
			$logoW = 28;
			$pdf->Image($logoPath, ($pdf->getPageWidth() - $logoW) / 2, $y, $logoW, 0, '', '', '', false, 300);
			$pdf->SetY($y + 22);
		}

		$pdf->SetFont(self::FONT, 'B', 12);
		$pdf->Cell($pageW, 6, self::utf($companyName), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 9);
		$pdf->MultiCell($pageW, 5, self::utf($address), 0, 'C', false, 1);
		$pdf->Cell($pageW, 5, self::utf($phone), 0, 1, 'C');
		$pdf->Ln(4);

		$pdf->SetFont(self::FONT, 'B', 13);
		$pdf->Cell($pageW, 7, self::utf('HÓA ĐƠN ĐẶT HÀNG'), 0, 1, 'C');
		$pdf->SetFont(self::FONT, '', 10);
		$pdf->Cell($pageW, 5, self::utf('Mã đơn hàng: ' . $docNo), 0, 1, 'C');
		$pdf->Cell($pageW, 5, self::utf($dateLabel), 0, 1, 'C');
		$pdf->Ln(4);

		$pdf->SetFont(self::FONT, '', 10);
		self::writeLabelValue($pdf, $pageW, 'Khách hàng:', $customer !== '' ? $customer : '—');
		self::writeLabelValue($pdf, $pageW, 'SĐT:', $customerPhone !== '' ? $customerPhone : '—');
		self::writeLabelValue($pdf, $pageW, 'Địa chỉ:', $customerAddress !== '' ? $customerAddress : '—');
		self::writeLabelValue($pdf, $pageW, 'Ghi chú:', $notes !== '' ? $notes : '—');
		$pdf->Ln(3);

		// Table: Đơn giá | SL | T.Tiền
		$colItem = $pageW * 0.62;
		$colQty = $pageW * 0.14;
		$colMoney = $pageW * 0.24;
		$pdf->SetFillColor(self::GREEN[0], self::GREEN[1], self::GREEN[2]);
		$pdf->SetTextColor(255, 255, 255);
		$pdf->SetFont(self::FONT, 'B', 10);
		$pdf->Cell($colItem, 8, self::utf('Đơn giá'), 0, 0, 'L', true);
		$pdf->Cell($colQty, 8, self::utf('SL'), 0, 0, 'C', true);
		$pdf->Cell($colMoney, 8, self::utf('T.Tiền'), 0, 1, 'R', true);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetDrawColor(226, 232, 240);
		$pdf->Line($x, $pdf->GetY(), $x + $pageW, $pdf->GetY());

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

				$pdf->SetFont(self::FONT, 'B', 10);
				$pdf->MultiCell($colItem, 5, self::utf($name), 0, 'L', false, 1, $x, $startY, true, 0, false, true, 0, 'T', false);
				$nameBottom = $pdf->GetY();
				$pdf->SetFont(self::FONT, '', 10);
				$pdf->SetXY($x, $nameBottom);
				$pdf->Cell($colItem, 5, self::utf($priceText), 0, 1, 'L');
				$rowBottom = $pdf->GetY();
				$rowH = max(12, $rowBottom - $startY);

				$pdf->SetXY($x + $colItem, $startY);
				$pdf->MultiCell($colQty, $rowH, self::utf($qtyText), 0, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'T', false);
				$pdf->SetXY($x + $colItem + $colQty, $startY);
				$pdf->MultiCell($colMoney, $rowH, self::utf($totalText), 0, 'R', false, 1, '', '', true, 0, false, true, $rowH, 'T', false);
				$pdf->SetY($startY + $rowH);
				$pdf->SetDrawColor(241, 245, 249);
				$pdf->Line($x, $pdf->GetY(), $x + $pageW, $pdf->GetY());
			}
		}

		$pdf->Ln(4);
		$pdf->SetDrawColor(203, 213, 225);
		$pdf->Line($x, $pdf->GetY(), $x + $pageW, $pdf->GetY());
		$pdf->Ln(3);

		$totals = array(
			array('Tổng tiền hàng', $subTotal, false),
			array('Thuế', $taxAmount, false),
			array('Chiết khấu', $discountAmount, false),
			array('Tổng thanh toán', $grandTotal, true),
		);

		$leftW = $pageW * 0.48;
		$rightW = $pageW * 0.52;
		$totalsStartY = $pdf->GetY();

		// Totals on the right (match Excel)
		$ty = $totalsStartY;
		foreach ($totals as $item) {
			$pdf->SetXY($x + $leftW, $ty);
			if ($item[2]) {
				$pdf->SetFont(self::FONT, 'B', 11);
				$pdf->SetTextColor(self::GREEN[0], self::GREEN[1], self::GREEN[2]);
			} else {
				$pdf->SetFont(self::FONT, 'B', 10);
				$pdf->SetTextColor(71, 85, 105);
			}
			$pdf->Cell($rightW * 0.55, 6, self::utf($item[0] . ':'), 0, 0, 'L');
			$pdf->SetTextColor(15, 23, 42);
			if ($item[2]) {
				$pdf->SetTextColor(self::GREEN[0], self::GREEN[1], self::GREEN[2]);
			}
			$pdf->Cell($rightW * 0.45, 6, self::utf(Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($item[1])), 0, 1, 'R');
			$ty += 6;
		}
		$pdf->SetTextColor(0, 0, 0);

		// Amount in words below totals (left), same as Excel
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

	protected static function writeLabelValue(Vtiger_PDF_TCPDF $pdf, $pageW, $label, $value) {
		$labelW = 28;
		$pdf->SetFont(self::FONT, 'B', 10);
		$pdf->Cell($labelW, 5, self::utf($label), 0, 0, 'L');
		$pdf->SetFont(self::FONT, '', 10);
		$pdf->MultiCell($pageW - $labelW, 5, self::utf($value), 0, 'L', false, 1);
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
			if (!empty($productId)) {
				try {
					if (strcasecmp($entityType, 'Services') === 0) {
						$focusService->retrieve_entity_info($productId, 'Services');
						$usageUnit = decode_html($focusService->column_fields['service_usageunit'] ?? '');
					} else {
						$focusProduct->retrieve_entity_info($productId, 'Products');
						$usageUnit = decode_html($focusProduct->column_fields['usageunit'] ?? '');
					}
				} catch (Exception $e) {
					$usageUnit = '';
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
			$total = ($quantity * $listPrice) - $discount;
			if ($total <= 0 && $productTotal > 0) {
				$total = $productTotal - $discount;
			}
			if ($total <= 0 && $totalAfterDiscount > 0) {
				$total = $totalAfterDiscount;
			}

			$lines[] = array(
				'name' => $productName,
				'unit' => trim((string) $usageUnit),
				'qty' => $quantity,
				'price' => $listPrice,
				'total' => $total,
			);
		}

		// Backfill zero line money from header subtotal.
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
