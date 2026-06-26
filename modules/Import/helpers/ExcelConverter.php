<?php
/*+***********************************************************************************
 * Convert Excel (.xlsx / .xls) uploads to CSV for vtiger Import module.
 *************************************************************************************/

class Import_ExcelConverter_Helper {

	public static function isExcelUpload($fileName) {
		$ext = strtolower(pathinfo((string)$fileName, PATHINFO_EXTENSION));
		return in_array($ext, array('xlsx', 'xls'), true);
	}

	public static function convertToCsv($sourcePath, $destPath, $delimiter = ',', $originalFileName = null) {
		if (!is_readable($sourcePath)) {
			return false;
		}
		require_once 'libraries/PHPExcel/PHPExcel.php';
		$nameForExt = $originalFileName ? (string) $originalFileName : (string) $sourcePath;
		$ext = strtolower(pathinfo($nameForExt, PATHINFO_EXTENSION));
		if ($ext === '') {
			$ext = 'xlsx';
		}
		try {
			$readerType = ($ext === 'xls') ? 'Excel5' : 'Excel2007';
			$reader = PHPExcel_IOFactory::createReader($readerType);
			$reader->setReadDataOnly(true);
			$book = $reader->load($sourcePath);
		} catch (Exception $e) {
			return false;
		}
		$sheet = $book->getSheet(0);
		$handle = fopen($destPath, 'w');
		if (!$handle) {
			return false;
		}
		fwrite($handle, "\xEF\xBB\xBF");
		$rowCount = (int)$sheet->getHighestRow();
		$colCount = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
		for ($row = 1; $row <= $rowCount; $row++) {
			$cells = array();
			for ($col = 0; $col < $colCount; $col++) {
				$value = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
				if ($value instanceof PHPExcel_RichText) {
					$value = $value->getPlainText();
				}
				$cells[] = is_scalar($value) ? (string)$value : '';
			}
			fputcsv($handle, $cells, $delimiter);
		}
		fclose($handle);
		return true;
	}
}
