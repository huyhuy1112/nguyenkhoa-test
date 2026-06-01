<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ModComments_DownloadFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$attachmentId = $request->get('fileid');

		$recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $moduleName);
		$attachments = $recordModel->getFileDetails($attachmentId);
		if (empty($attachments)) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$fileDetails = is_array($attachments[0]) ? $attachments[0] : $attachments;
		$filePath = $fileDetails['path'];
		$fileName = html_entity_decode($fileDetails['name'], ENT_QUOTES, vglobal('default_charset'));
		$storedFileName = $fileDetails['storedname'];
		$fileType = $fileDetails['type'];
		$attachmentsId = $fileDetails['attachmentsid'];

		$rootDir = rtrim(vglobal('root_directory'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$basePath = realpath($rootDir . $filePath);
		if ($basePath === false) {
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$fullPath = $basePath . DIRECTORY_SEPARATOR . $attachmentsId . '_' . $storedFileName;
		if (empty($storedFileName) || !file_exists($fullPath) || @filesize($fullPath) === 0) {
			$fullPath = $basePath . DIRECTORY_SEPARATOR . $attachmentsId . '_' . $fileName;
		}

		if (!file_exists($fullPath)) {
			header('HTTP/1.0 404 Not Found');
			return;
		}
		if (!is_readable($fullPath)) {
			header('HTTP/1.0 403 Forbidden');
			return;
		}

		$size = @filesize($fullPath);
		if ($size === false || $size === 0) {
			header('HTTP/1.0 500 Internal Server Error');
			return;
		}

		// Disable compression and clear all output buffers to avoid corrupting binary output.
		if (function_exists('ini_set')) {
			@ini_set('zlib.output_compression', 'Off');
		}
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		header('Content-Description: File Transfer');
		header('Content-Type: ' . $fileType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $size);
		header('Cache-Control: private');
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
