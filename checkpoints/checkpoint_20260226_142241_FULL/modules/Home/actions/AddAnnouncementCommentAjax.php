<?php
/*+***********************************************************************************
 * Add a comment to an announcement (by creatorid). Supports file upload.
 *************************************************************************************/

class Home_AddAnnouncementCommentAjax_Action extends Vtiger_Action_Controller {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		try {
			$announcementId = (int)$request->get('id');
			$commentText = $request->get('comment_text');
			if ($commentText === null) $commentText = '';
			if (!$announcementId || (trim($commentText) === '' && empty($_FILES['filename']['tmp_name']))) {
				$response->setError('Missing id or comment_text');
				$response->emit();
				return;
			}
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$ann = Settings_Vtiger_Announcement_Model::getById($announcementId, $currentUser->getId());
			if (!$ann) {
				$response->setError('Not found or access denied');
				$response->emit();
				return;
			}
			$attachmentId = null;
			if (!empty($_FILES['filename']['tmp_name']) && $_FILES['filename']['error'] === UPLOAD_ERR_OK) {
				$attachmentId = Settings_Vtiger_Announcement_Model::uploadCommentFile($_FILES['filename']);
			}
			$cid = Settings_Vtiger_Announcement_Model::addComment($announcementId, $currentUser->getId(), trim($commentText) ?: ' ', $attachmentId);
			$comments = Settings_Vtiger_Announcement_Model::getComments($announcementId);
			$response->setResult(array('success' => true, 'id' => $cid, 'comments' => $comments));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
