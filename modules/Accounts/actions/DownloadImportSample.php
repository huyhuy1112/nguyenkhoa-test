<?php
/*+***********************************************************************************
 * Accounts (Tổ chức) import sample — BA template (Customer Code + Organization Name).
 *************************************************************************************/

class Accounts_DownloadImportSample_Action extends Vtiger_Action_Controller {
	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'Import'),
		);
	}

	public function process(Vtiger_Request $request) {
		if ($request->getModule() !== 'Accounts') {
			throw new AppException('Invalid module');
		}

		$headers = array(
			'Customer Code',
			'Organization Name',
			'Website',
			'Ticker Symbol',
			'Primary Phone',
			'Fax',
			'Member Of',
			'Industry',
			'Employees',
			'Annual Revenue',
			'Secondary Email',
			'Ownership',
			'Rating',
			'Type',
			'SIC Code',
			'Email Opt Out',
			'Assigned To',
			'Utility Owner',
			'Created Time',
			'Modified Time',
			'Modified By',
			'Source',
			'Company Code',
			'Description',
			'Billing Address',
			'Shipping Address',
		);

		$row1 = array(
			'13',
			'CÔNG TY TNHH GIẢI PHÁP VÀ DỊCH VỤ SSPACE',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'sspace',
			'',
			'123 Đường ABC, Quận 1, TP.HCM',
			'',
		);

		$filename = 'Organizations_Import_Mau.csv';
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
