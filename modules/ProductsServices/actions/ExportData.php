<?php
/**
 * Goods list export as .xlsx. The shared Vtiger export writes .csv.
 */
class ProductsServices_ExportData_Action extends Vtiger_ExportData_Action {

	public function output($request, $headers, $entries) {
		require_once 'libraries/PHPExcel/PHPExcel.php';

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		$book = new PHPExcel();
		$book->getProperties()
			->setCreator('Nguyen Khoa CRM')
			->setTitle('Bao cao hang hoa');
		$book->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
		$sheet = $book->setActiveSheetIndex(0);
		$sheet->setTitle('Hang hoa');

		$col = 0;
		foreach ((array) $headers as $header) {
			$sheet->setCellValueByColumnAndRow($col, 1, decode_html((string) $header));
			$sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
			$col++;
		}
		if ($col > 0) {
			$lastCol = PHPExcel_Cell::stringFromColumnIndex($col - 1);
			$sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
		}

		$rowNum = 2;
		foreach ((array) $entries as $row) {
			$col = 0;
			foreach ((array) $row as $cell) {
				$sheet->setCellValueByColumnAndRow($col, $rowNum, decode_html(strip_tags((string) $cell)));
				$col++;
			}
			$rowNum++;
		}

		$fileName = 'Bao_cao_hang_hoa.xlsx';
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Cache-Control: max-age=0');
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');

		$writer = PHPExcel_IOFactory::createWriter($book, 'Excel2007');
		$writer->save('php://output');
		exit;
	}
}
