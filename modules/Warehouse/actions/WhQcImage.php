<?php
/**
 * Serve QC attachment images for warehouse inbound receipts.
 */
require_once 'modules/Warehouse/models/WhMgmtService.php';

class Warehouse_WhQcImage_Action extends Vtiger_Action_Controller {

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

	public function validateRequest(Vtiger_Request $request) {
		// Read-only file serve — skip CSRF write validation.
	}

	public function process(Vtiger_Request $request) {
		$whId = trim((string) $request->get('whId'));
		if ($whId === '') {
			$whId = trim((string) $request->get('id'));
		}
		$code = trim((string) $request->get('code'));
		$imageId = trim((string) $request->get('imageId'));

		try {
			Warehouse_WhMgmtService::ensureInstalled();
			$info = Warehouse_WhMgmtService::resolveQcImageFile($whId, $code, $imageId);
			if (!$info || empty($info['path']) || !is_readable($info['path'])) {
				header('HTTP/1.1 404 Not Found');
				echo 'Not found';
				exit;
			}
			$mime = !empty($info['mime']) ? (string) $info['mime'] : 'application/octet-stream';
			header('Content-Type: ' . $mime);
			header('Cache-Control: private, max-age=86400');
			header('Content-Length: ' . filesize($info['path']));
			readfile($info['path']);
		} catch (Exception $e) {
			header('HTTP/1.1 404 Not Found');
			echo 'Not found';
		}
		exit;
	}
}

?>
