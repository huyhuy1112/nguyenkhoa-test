<?php
require_once 'modules/HelpDesk/models/MaterialAlertService.php';

class HelpDesk_MaterialAlertsApi_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted($request->getModule(), 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$mode = (string) $request->get('mode');
		$response = new Vtiger_Response();
		try {
			if ($mode === 'save_settings') {
				$payload = json_decode((string) $request->get('payload'), true);
				if (!is_array($payload)) {
					$payload = array();
				}
				$response->setResult(array('success' => true, 'settings' => HelpDesk_MaterialAlertService::saveSettings($payload)));
			} elseif ($mode === 'refresh') {
				$response->setResult(array('success' => true) + HelpDesk_MaterialAlertService::refresh());
			} elseif ($mode === 'transition') {
				HelpDesk_MaterialAlertService::transition((int) $request->get('id'), (string) $request->get('next'), array(
					'result_note' => $request->get('result_note'),
					'evidence_ref' => $request->get('evidence_ref'),
					'next_task' => $request->get('next_task'),
					'next_owner' => $request->get('next_owner'),
					'next_due' => $request->get('next_due'),
					'snooze_until' => $request->get('snooze_until'),
				));
				$response->setResult(array('success' => true));
			} elseif ($mode === 'summary') {
				$response->setResult(array('success' => true) + HelpDesk_MaterialAlertService::summary());
			} elseif ($mode === 'metrics') {
				$response->setResult(array('success' => true, 'metrics' => HelpDesk_MaterialAlertService::contactMetrics((int) $request->get('contact_id'))));
			} else {
				throw new Exception('Mode không hợp lệ.');
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
