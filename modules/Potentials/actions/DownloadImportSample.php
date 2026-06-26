<?php
/*+***********************************************************************************
 * Potentials (Orders) import sample — BA template (Khách hàng + Thứ tự dự án + Project).
 *************************************************************************************/

class Potentials_DownloadImportSample_Action extends Vtiger_Action_Controller {
	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'Import'),
		);
	}

	public function process(Vtiger_Request $request) {
		if ($request->getModule() !== 'Potentials') {
			throw new AppException('Invalid module');
		}

		$headers = array(
			'Khách hàng',
			'Thứ tự dự án',
			'Project Name',
			'Project Code',
			'Description',
			'Opportunity Name',
			'Opportunity No',
			'Organization Name',
			'Contact Name',
			'Amount',
			'Type',
			'Model',
			'Close Date',
			'Source',
			'Next Step',
			'Assigned To',
			'Sales Stage',
			'Campaign Source',
			'Probability',
			'Modified Time',
			'Created Time',
			'Modified By',
			'Created By',
			'Source',
			'Order Category',
		);

		$row1 = array(
			'13',
			'01',
			'Triển khai ERP',
			'',
			'',
			'',
			'',
			'CÔNG TY TNHH GIẢI PHÁP VÀ DỊCH VỤ SSPACE',
			'Xuân Phúc',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'Prospecting',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'Project',
		);

		$filename = 'Opportunities_Import_Mau.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, $headers);
		fputcsv($out, $row1);
		fclose($out);
	}
}
