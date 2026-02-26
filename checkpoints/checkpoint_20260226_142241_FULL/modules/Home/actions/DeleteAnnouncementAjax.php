<?php
/*+***********************************************************************************
 * Delete an announcement (creator only).
 *************************************************************************************/

class Home_DeleteAnnouncementAjax_Action extends Vtiger_Action_Controller {

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
			$ok = Settings_Vtiger_Announcement_Model::deleteById($id, $currentUser->getId());
			if (!$ok) {
				$response->setError('Not found or you are not the creator');
				$response->emit();
				return;
			}
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
