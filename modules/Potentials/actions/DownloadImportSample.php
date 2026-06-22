<?php
/*+***********************************************************************************
 * Potentials (Orders) import sample — Vietnamese headers matching SALES export.
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
			'Ghi chú',
			'Tiêu đề',
			'Mã Orders',
			'Tên Khách hàng',
			'Tên Liên hệ',
			'Loại Order',
			'Giá trị dự kiến',
			'Nguồn Order',
			'Ngày dự kiến kết thúc',
			'Phụ trách',
			'Bước tiếp theo_D',
			'Nguồn chiến dịch',
			'Trạng thái Order',
			'Xác suất',
			'Dự đoán giá trị',
			'Phân loại Order',
		);

		$row1 = array(
			'Order demo 1',
			'Dự án pha chế Q1',
			'',
			'Công ty TNHH Demo',
			'Nguyễn Văn A',
			'New Business',
			'15000000',
			'Website',
			'30-06-2026',
			'Administrator',
			'Gọi xác nhận nhu cầu',
			'',
			'Prospecting',
			'30',
			'15000000',
			'Internal',
		);

		$filename = 'Orders_Import_Mau.csv';
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
