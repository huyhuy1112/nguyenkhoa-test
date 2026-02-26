<?php
/*+***********************************************************************************
 * Serve announcement comment attachment inline (e.g. for <img> display).
 *************************************************************************************/

class Home_InlineAnnouncementCommentFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$commentId = (int)$request->get('record');
		$fileId = (int)$request->get('fileid');
		if (!$commentId || !$fileId) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Home'));
		}
		$db = PearDatabase::getInstance();
		$row = $db->pquery('SELECT c.id, c.announcement_id, c.filename FROM vtiger_announcement_comments c WHERE c.id=?', array($commentId));
		if ($db->num_rows($row) == 0) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Home'));
		}
		$filename = trim($db->query_result($row, 0, 'filename'));
		$ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', $filename, -1, PREG_SPLIT_NO_EMPTY)));
		if (!in_array($fileId, $ids)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Home'));
		}
		$annId = (int)$db->query_result($row, 0, 'announcement_id');
		$ann = Settings_Vtiger_Announcement_Model::getById($annId, Users_Record_Model::getCurrentUserModel()->getId());
		if (!$ann) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Home'));
		}
	}

	public function process(Vtiger_Request $request) {
		$fileId = (int)$request->get('fileid');
		$db = PearDatabase::getInstance();
		$res = $db->pquery('SELECT attachmentsid, name, path, storedname, type FROM vtiger_attachments WHERE attachmentsid=?', array($fileId));
		if ($db->num_rows($res) == 0) {
			header('HTTP/1.0 404 Not Found');
			return;
		}
		$row = $db->fetchByAssoc($res);
		$path = $row['path'];
		$name = $row['name'];
		$storedName = $row['storedname'];
		$fileType = $row['type'] ?: 'application/octet-stream';
		$savedFile = !empty($storedName) ? $fileId . '_' . $storedName : $fileId . '_' . $name;
		$fullPath = $path . $savedFile;
		if (!file_exists($fullPath) || !is_readable($fullPath)) {
			header('HTTP/1.0 404 Not Found');
			return;
		}
		$content = @file_get_contents($fullPath);
		if ($content === false) {
			header('HTTP/1.0 500 Internal Server Error');
			return;
		}
		$fileName = html_entity_decode($name, ENT_QUOTES, vglobal('default_charset'));
		$imageTypes = array('image/gif', 'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/bmp');
		$disposition = in_array(strtolower($fileType), $imageTypes) ? 'inline' : 'attachment';
		header('Content-Type: ' . $fileType);
		header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"');
		header('Content-Length: ' . strlen($content));
		header('Cache-Control: private');
		echo $content;
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}
}
