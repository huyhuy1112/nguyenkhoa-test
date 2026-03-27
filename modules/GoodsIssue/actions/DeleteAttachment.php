<?php
class GoodsIssue_DeleteAttachment_Action extends Vtiger_Action_Controller {
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$attachmentId = (int) $request->get('attachmentid');
		$recordId = (int) $request->get('record');

		$res = $db->pquery(
			"SELECT filepath, stored_name
			 FROM vtiger_goodsissue_attachments
			 WHERE attachmentid = ? AND issueid = ? AND deleted = 0",
			array($attachmentId, $recordId)
		);

		if ($db->num_rows($res) > 0) {
			$row = $db->fetchByAssoc($res);
			$filePath = (string) $row['filepath'] . (string) $row['stored_name'];
			if (is_file($filePath)) {
				@unlink($filePath);
			}
			$db->pquery(
				"UPDATE vtiger_goodsissue_attachments SET deleted = 1 WHERE attachmentid = ?",
				array($attachmentId)
			);
		}

		header('Location: index.php?module=GoodsIssue&view=Edit&record=' . (int) $recordId . '&app=INVENTORY');
		exit;
	}
}

