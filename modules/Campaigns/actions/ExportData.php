<?php
/*+***********************************************************************************
 * Campaigns CSV export: UTF-8 BOM for Excel + entity decode on phase comment columns.
 *************************************************************************************/

class Campaigns_ExportData_Action extends Vtiger_ExportData_Action {

	/**
	 * @param Vtiger_Request $request
	 * @param array $headers
	 * @param array $entries
	 */
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ', '_', decode_html(vtranslate($moduleName, $moduleName)));
		$fileName = str_replace(',', '_', $fileName);
		$exportType = $this->getExportContentType($request);
		if (empty($exportType)) {
			$exportType = 'text/csv';
		}

		header('Content-Disposition: attachment;filename=' . $fileName . '.csv');
		header('Content-Type: ' . $exportType . '; charset=UTF-8');
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: post-check=0, pre-check=0', false);

		ob_clean();
		echo "\xEF\xBB\xBF";
		$fp = fopen('php://output', 'a+');
		fputcsv($fp, $headers);

		foreach ($entries as $row) {
			fputcsv($fp, $row);
		}
	}

	/**
	 * @param array $arr
	 * @return array
	 */
	function sanitizeValues($arr) {
		$arr = parent::sanitizeValues($arr);
		foreach ($arr as $fieldName => &$value) {
			if (is_string($value) && preg_match('/^phase[1-5]_comment$/', (string) $fieldName)) {
				$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
		}
		unset($value);
		return $arr;
	}
}
