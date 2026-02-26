<?php
/*+***********************************************************************************
 * Save announcement from Main Page (any logged-in user can add/update their announcement).
 *************************************************************************************/

class Home_SaveAnnouncementAjax_Action extends Vtiger_Action_Controller {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		try {
			if (!class_exists('Settings_Vtiger_Announcement_Model')) {
				$response->setError('Announcement model not available.');
				$response->emit();
				return;
			}
			$currentUser = Users_Record_Model::getCurrentUserModel();
			// Tạo announcement mới mỗi lần Add (không load bản cũ để tránh ghi đè)
			$model = new Settings_Vtiger_Announcement_Model();
			$model->set('announcement', $request->get('announcement'));
			$model->set('title', $request->get('title') ?: 'Announcement');
			$subIds = $request->get('subscriber_ids');
			if (is_array($subIds)) {
				$model->set('subscriber_ids', $subIds);
			} elseif (is_string($subIds) || is_numeric($subIds)) {
				$model->set('subscriber_ids', $subIds);
			}
			$grpIds = $request->get('subscriber_group_ids');
			if (is_array($grpIds)) {
				$model->set('subscriber_group_ids', implode(',', array_map('intval', $grpIds)));
			} elseif (is_string($grpIds) || is_numeric($grpIds)) {
				$model->set('subscriber_group_ids', $grpIds);
			}
			$model->save();
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
