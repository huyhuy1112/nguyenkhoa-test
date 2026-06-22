<?php
/*+***********************************************************************************
 * Accounts (Tổ chức) import sample — Vietnamese headers matching SALES export.
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
			'Tên',
			'Tên đầy đủ',
			'Mã số thuế',
			'Địa chỉ trụ sở chính',
			'Số điện thoại liên hệ',
			'Email liên lạc',
			'Trang web',
			'Ngành nghề kinh doanh',
			'Phụ trách',
		);

		$row1 = array(
			'Công ty Demo',
			'Công ty TNHH Demo',
			'0312345678',
			'123 Nguyễn Huệ, Quận 1, TP.HCM',
			'0901234567',
			'demo@example.com',
			'https://example.com',
			'F&B',
			'Administrator',
		);

		$filename = 'ToChuc_Import_Mau.csv';
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
