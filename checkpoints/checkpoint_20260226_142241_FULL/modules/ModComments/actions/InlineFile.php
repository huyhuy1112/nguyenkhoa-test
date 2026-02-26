<?php
/*+***********************************************************************************
 * Serve file inline (e.g. for <img> display). Uses Content-Disposition: inline for images.
 *************************************************************************************/

class ModComments_InlineFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $moduleName);
		$attachmentId = $request->get('fileid');
		$attachments = $recordModel->getFileDetails($attachmentId);
		if (empty($attachments)) {
			header("HTTP/1.0 404 Not Found");
			return;
		}
		$fileDetails = is_array($attachments[0]) ? $attachments[0] : $attachments;
		$filePath = $fileDetails['path'];
		$fileName = $fileDetails['name'];
		$storedFileName = $fileDetails['storedname'];
		$fileType = $fileDetails['type'];
		$fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
		$savedFile = !empty($storedFileName) ? $fileDetails['attachmentsid'] . "_" . $storedFileName : $fileDetails['attachmentsid'] . "_" . $fileName;
		$fullPath = $filePath . $savedFile;
		if (!file_exists($fullPath) || !is_readable($fullPath)) {
			header("HTTP/1.0 404 Not Found");
			return;
		}
		$content = @file_get_contents($fullPath);
		if ($content === false) {
			header("HTTP/1.0 500 Internal Server Error");
			return;
		}
		$fileSize = strlen($content);
		$imageTypes = array('image/gif', 'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/bmp');
		$disposition = in_array(strtolower($fileType), $imageTypes) ? 'inline' : 'attachment';
		header("Content-Type: " . $fileType);
		header("Content-Disposition: " . $disposition . "; filename=\"" . $fileName . "\"");
		header("Content-Length: " . $fileSize);
		header("Cache-Control: private");
		echo $content;
	}
}
