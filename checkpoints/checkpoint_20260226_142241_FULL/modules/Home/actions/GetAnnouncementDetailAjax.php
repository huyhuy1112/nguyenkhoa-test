<?php
/*+***********************************************************************************
 * Get one announcement by creatorid and its comments (for detail view).
 *************************************************************************************/

class Home_GetAnnouncementDetailAjax_Action extends Vtiger_Action_Controller {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		try {
			$id = (int)$request->get('id');
			if (!$id) {
				$response->setError('Missing id');
				$response->emit();
				return;
			}
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$ann = Settings_Vtiger_Announcement_Model::getById($id, $currentUser->getId());
			if (!$ann) {
				$response->setError('Not found or access denied');
				$response->emit();
				return;
			}
			$comments = Settings_Vtiger_Announcement_Model::getComments($id);
			$ann['isCreator'] = ($ann['creatorid'] == $currentUser->getId());
			$response->setResult(array(
				'announcement' => $ann,
				'comments' => $comments,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateReadAccess();
	}
}
