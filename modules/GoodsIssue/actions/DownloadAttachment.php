<?php
class GoodsIssue_DownloadAttachment_Action extends Vtiger_Action_Controller {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$attachmentId = (int) $request->get('attachmentid');
		$recordId = (int) $request->get('record');

		$res = $db->pquery(
			"SELECT filename, stored_name, filepath, filetype
			 FROM vtiger_goodsissue_attachments
			 WHERE attachmentid = ? AND issueid = ? AND deleted = 0",
			array($attachmentId, $recordId)
		);

		if ($db->num_rows($res) <= 0) {
			header('HTTP/1.1 404 Not Found');
			exit;
		}

		$row = $db->fetchByAssoc($res);
		$filePath = (string) $row['filepath'] . (string) $row['stored_name'];
		if (!is_file($filePath)) {
			header('HTTP/1.1 404 Not Found');
			exit;
		}

		$fileType = trim((string) $row['filetype']) !== '' ? (string) $row['filetype'] : 'application/octet-stream';
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $fileType);
		header('Content-Disposition: inline; filename="' . basename((string) $row['filename']) . '"');
		header('Content-Length: ' . filesize($filePath));
		readfile($filePath);
		exit;
	}
}

