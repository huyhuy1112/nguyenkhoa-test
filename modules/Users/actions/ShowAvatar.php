<?php
/*+***********************************************************************************
 * Serve user profile image inline for <img> tags (authenticated).
 *************************************************************************************/

class Users_ShowAvatar_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser || !$currentUser->getId()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId < 1) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT vtiger_attachments.attachmentsid, vtiger_attachments.path, vtiger_attachments.name,
				vtiger_attachments.storedname, vtiger_attachments.type
			FROM vtiger_attachments
			INNER JOIN vtiger_salesmanattachmentsrel
				ON vtiger_salesmanattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
			WHERE vtiger_salesmanattachmentsrel.smid = ?
			ORDER BY vtiger_attachments.attachmentsid DESC
			LIMIT 1',
			array($recordId)
		);

		if (!$result || $db->num_rows($result) < 1) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$row = $db->fetch_array($result);
		$attachmentId = $row['attachmentsid'];
		$filePath = $row['path'];
		$fileName = html_entity_decode($row['name'], ENT_QUOTES, vglobal('default_charset'));
		$storedFileName = $row['storedname'];
		$fileType = $row['type'] ? $row['type'] : 'image/png';

		$rootDir = rtrim(vglobal('root_directory'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$basePath = realpath($rootDir . $filePath);
		if ($basePath === false) {
			$basePath = realpath($filePath);
		}
		if ($basePath === false) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$candidates = array();
		if (!empty($storedFileName)) {
			$candidates[] = $basePath . DIRECTORY_SEPARATOR . $attachmentId . '_' . $storedFileName;
		}
		$candidates[] = $basePath . DIRECTORY_SEPARATOR . $attachmentId . '_' . $fileName;
		if (!empty($storedFileName)) {
			$candidates[] = $filePath . $attachmentId . '_' . $storedFileName;
		}
		$candidates[] = $filePath . $attachmentId . '_' . $fileName;

		$fullPath = false;
		foreach ($candidates as $candidate) {
			if ($candidate && file_exists($candidate) && is_readable($candidate) && @filesize($candidate) > 0) {
				$fullPath = $candidate;
				break;
			}
		}

		if ($fullPath === false) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$size = @filesize($fullPath);
		if ($size === false || $size === 0) {
			header('HTTP/1.0 500 Internal Server Error');
			return;
		}

		if (function_exists('ini_set')) {
			@ini_set('zlib.output_compression', 'Off');
		}
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		header('Content-Description: File Transfer');
		header('Content-Type: ' . $fileType);
		header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $size);
		header('Cache-Control: private, max-age=3600');
		header('Pragma: public');

		$fp = @fopen($fullPath, 'rb');
		if ($fp === false) {
			header('HTTP/1.0 500 Internal Server Error');
			return;
		}
		@fpassthru($fp);
		@fclose($fp);
		exit;
	}
}
