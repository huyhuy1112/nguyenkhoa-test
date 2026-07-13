<?php
/*+***********************************************************************************
 * Export PHIẾU XUẤT KHO — HTML preview in iframe, PDF download separately.
 *************************************************************************************/

require_once 'modules/Warehouse/models/WhMgmtService.php';
require_once 'modules/Warehouse/helpers/OutboundIssuePdf.php';

// Ensure TCPDF is loaded (absolute paths — không phụ thuộc cwd).
if (!class_exists('TCPDF')) {
	$__whAppRoot = dirname(__DIR__, 3); // modules/Warehouse/actions -> app root
	$__whTcpdfCandidates = array(
		$__whAppRoot . '/vendor/tecnickcom/tcpdf/tcpdf.php',
		$__whAppRoot . '/vendor/autoload.php',
		$__whAppRoot . '/vtiger-prod/tecnickcom/tcpdf/tcpdf.php',
		'vendor/tecnickcom/tcpdf/tcpdf.php',
		'vendor/autoload.php',
	);
	foreach ($__whTcpdfCandidates as $__whTcpdfPath) {
		if (!is_readable($__whTcpdfPath)) {
			continue;
		}
		require_once $__whTcpdfPath;
		if (class_exists('TCPDF')) {
			break;
		}
	}
	unset($__whAppRoot, $__whTcpdfCandidates, $__whTcpdfPath);
}

class Warehouse_ExportOutboundPDF_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$warehouseCode = trim((string) $request->get('warehouse'));
		if ($warehouseCode === '') {
			$warehouseCode = trim((string) $request->get('wh'));
		}
		$issueCode = trim((string) $request->get('record'));
		if ($issueCode === '') {
			$issueCode = trim((string) $request->get('code'));
		}

		try {
			if ($warehouseCode === '' || $issueCode === '') {
				throw new Exception('Thiếu mã kho hoặc mã phiếu xuất.');
			}
			$payload = Warehouse_WhMgmtService::getOutboundIssuePrintPayload($warehouseCode, $issueCode);
			$isPreview = $request->get('preview') === '1' || $request->get('mode') === 'inline';
			$format = strtolower((string) $request->get('format'));

			if ($isPreview || $format === 'html') {
				Warehouse_OutboundIssuePdf_Helper::renderHtmlPreview($payload);
			}

			if (!class_exists('TCPDF')) {
				throw new Exception('TCPDF không khả dụng — không tạo được PDF. Kiểm tra vendor/tecnickcom/tcpdf.');
			}

			$fileName = 'PhieuXuatKho_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $issueCode) . '.pdf';
			Warehouse_OutboundIssuePdf_Helper::output($payload, $fileName, 'D');
			exit;
		} catch (Exception $e) {
			while (ob_get_level() > 0) {
				@ob_end_clean();
			}
			header('Content-Type: text/html; charset=UTF-8');
			$status = method_exists($e, 'getCode') && (int) $e->getCode() >= 400 ? (int) $e->getCode() : 500;
			if ($status < 400 || $status > 599) {
				$status = 500;
			}
			http_response_code($status);
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"/><title>Lỗi in phiếu</title></head><body style="font-family:sans-serif;padding:24px;color:#b91c1c">'
				. '<h3>Không tải được phiếu xuất kho</h3><p>'
				. htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
				. '</p></body></html>';
			exit;
		}
	}
}
