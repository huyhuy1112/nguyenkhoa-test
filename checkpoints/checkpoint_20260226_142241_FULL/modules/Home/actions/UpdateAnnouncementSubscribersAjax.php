<?php
/*+***********************************************************************************
 * Update subscribers of an announcement (creator only).
 *************************************************************************************/

class Home_UpdateAnnouncementSubscribersAjax_Action extends Vtiger_Action_Controller {

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
			$subscriberIds = $request->get('subscriber_ids');
			$subscriberGroupIds = $request->get('subscriber_group_ids');
			if (!is_array($subscriberIds)) {
				$subscriberIds = $subscriberIds === null || $subscriberIds === '' ? array() : array_map('intval', array_filter(explode(',', (string)$subscriberIds)));
			}
			if (!is_array($subscriberGroupIds)) {
				$subscriberGroupIds = $subscriberGroupIds === null || $subscriberGroupIds === '' ? array() : array_map('intval', array_filter(explode(',', (string)$subscriberGroupIds)));
			}
			$ok = Settings_Vtiger_Announcement_Model::updateSubscribers($id, $currentUser->getId(), $subscriberIds, $subscriberGroupIds);
			if (!$ok) {
				$response->setError('Not found or you are not the creator');
				$response->emit();
				return;
			}
			$ann = Settings_Vtiger_Announcement_Model::getById($id, $currentUser->getId());
			$response->setResult(array('success' => true, 'announcement' => $ann));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
