<?php
/**
 * Serve product photo for list/detail thumbs (authenticated fallback when public.php key is unavailable).
 */
require_once 'modules/ProductsServices/models/Record.php';

class ProductsServices_DownloadImage_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'DetailView'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// Read-only <img src> — skip CSRF write check.
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$aid = (int) $request->get('aid');
		$file = ProductsServices_Record_Model::resolveImageFile($recordId, $aid);
		if (!$file || empty($file['path']) || !is_readable($file['path'])) {
			header('HTTP/1.1 404 Not Found');
			header('Content-Type: text/plain; charset=UTF-8');
			echo 'Not found';
			exit;
		}
		$mime = !empty($file['type']) ? $file['type'] : 'image/jpeg';
		if (stripos($mime, 'image/') !== 0) {
			$mime = 'image/jpeg';
		}
		$size = @filesize($file['path']);
		if ($size === false || $size <= 0) {
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		if (function_exists('ini_set')) {
			@ini_set('zlib.output_compression', 'Off');
		}
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		$name = !empty($file['name']) ? $file['name'] : ('image-' . $recordId);
		header('Content-Type: ' . $mime);
		header('Content-Disposition: inline; filename="' . rawurlencode($name) . '"');
		header('Content-Length: ' . (string) $size);
		header('Cache-Control: private, max-age=86400');
		header('Pragma: public');
		$fp = @fopen($file['path'], 'rb');
		if ($fp === false) {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
		@fpassthru($fp);
		@fclose($fp);
		exit;
	}
}
