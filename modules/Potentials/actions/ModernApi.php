<?php
/*+***********************************************************************************
 * Modern Potentials API — list for SALES Lovable-style UI.
 *************************************************************************************/

require_once 'modules/Potentials/models/ModernService.php';

class Potentials_ModernApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted($request->getModule(), 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$mode = strtolower((string) $request->get('mode'));
		if (in_array($mode, array('save_confirm_tag', 'save_inline_location', 'save_inline_phone', 'save_inline_business_model', 'save_tags', 'delete', 'last_touch_call_log', 'offline_checkin', 'offline_reschedule', 'offline_unreachable'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$userId = (int)$current_user->id;

		try {
			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'opportunities' => Potentials_ModernService::listPotentials($userId),
						'assignable_users' => Potentials_ModernService::listAssignableUsers(),
					));
					break;
				case 'save_confirm_tag':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$confirm = $request->get('confirm');
					if ($confirm === null) {
						$confirm = $request->get('confirm_tag');
					}
					$result = Potentials_ModernService::setConfirmTag($recordId, $confirm);
					$response->setResult(array('success' => true) + $result);
					break;
				case 'save_inline_location':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$result = Potentials_ModernService::saveInlineLocation(
						$recordId,
						$request->get('mk_region'),
						$request->get('mk_address')
					);
					$response->setResult($result);
					break;
				case 'save_inline_phone':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$result = Potentials_ModernService::saveInlinePhone(
						$recordId,
						$request->get('phone')
					);
					$response->setResult($result);
					break;
				case 'save_inline_business_model':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$result = Potentials_ModernService::saveInlineBusinessModel(
						$recordId,
						$request->get('business_model')
					);
					$response->setResult($result);
					break;
				case 'save_tags':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$tagsRaw = $request->get('tags');
					if (is_string($tagsRaw)) {
						$decoded = json_decode($tagsRaw, true);
						$tagsRaw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $tagsRaw);
					}
					if (!is_array($tagsRaw)) {
						$tagsRaw = array();
					}
					$result = Potentials_ModernService::saveTags($recordId, $tagsRaw, $userId);
					$response->setResult($result);
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					Potentials_ModernService::deletePotential($recordId);
					$response->setResult(array('success' => true));
					break;
				case 'last_touch_call_list':
					require_once 'modules/Potentials/models/LastTouchCallService.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => Potentials_LastTouchCallService::getSummary($recordId),
					));
					break;
				case 'last_touch_call_log':
					require_once 'modules/Potentials/models/LastTouchCallService.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$result = $request->get('call_result');
					if ($result === null || $result === '') {
						$result = $request->get('result');
					}
					$note = $request->get('note');
					if ($note === null) {
						$note = '';
					}
					$logged = Potentials_LastTouchCallService::logCall($recordId, $result, $note, $userId);
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => $logged,
						'logged' => isset($logged['logged']) ? $logged['logged'] : null,
					));
					break;
				case 'offline_checkin':
					require_once 'modules/Leads/models/OfflineGd11Service.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$action = $request->get('offline_action');
					if ($action === null || $action === '') {
						$action = $request->get('action_name');
					}
					$checkin = Leads_OfflineGd11Service::checkinFromPotential($recordId, $action, $userId);
					if (empty($checkin['success'])) {
						throw new Exception(isset($checkin['error']) ? $checkin['error'] : 'Check-in thất bại');
					}
					// Refresh Opp row fields for UI patch
					$list = Potentials_ModernService::listPotentials($userId);
					$opp = null;
					foreach ($list as $row) {
						if ((int) $row['crmid'] === $recordId || (string) $row['id'] === (string) $recordId) {
							$opp = $row;
							break;
						}
					}
					$response->setResult(array(
						'success' => true,
						'status' => isset($checkin['status']) ? $checkin['status'] : '',
						'status_label' => isset($checkin['status_label']) ? $checkin['status_label'] : '',
						'drop' => isset($checkin['drop']) ? $checkin['drop'] : '',
						'checked_in_at' => isset($checkin['checked_in_at']) ? $checkin['checked_in_at'] : '',
						'tags' => isset($checkin['opp_tags']) ? $checkin['opp_tags'] : array(),
						'opportunity' => $opp,
					));
					break;
				case 'offline_reschedule':
					require_once 'modules/Leads/models/OfflineGd11Service.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$action = $request->get('offline_action');
					if ($action === null || $action === '') {
						$action = $request->get('action_name');
					}
					$payloadRaw = $request->get('payload');
					$payload = array();
					if (is_string($payloadRaw) && $payloadRaw !== '') {
						$decoded = json_decode($payloadRaw, true);
						if (is_array($decoded)) {
							$payload = $decoded;
						}
					} elseif (is_array($payloadRaw)) {
						$payload = $payloadRaw;
					}
					if (empty($action) && !empty($payload['action'])) {
						$action = $payload['action'];
					}
					$reschedule = Leads_OfflineGd11Service::rescheduleFromPotential(
						$recordId,
						$action,
						$payload,
						$userId
					);
					if (empty($reschedule['success'])) {
						throw new Exception(isset($reschedule['error']) ? $reschedule['error'] : 'Xếp lịch lại thất bại');
					}
					$list = Potentials_ModernService::listPotentials($userId);
					$opp = null;
					foreach ($list as $row) {
						if ((int) $row['crmid'] === $recordId || (string) $row['id'] === (string) $recordId) {
							$opp = $row;
							break;
						}
					}
					$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
					$status = isset($reschedule['status']) ? $reschedule['status'] : '';
					$statusLabel = isset($reschedule['status_label']) ? $reschedule['status_label'] : '';
					if ($status === '' && !empty($reschedule['lead']['offline_status'])) {
						$status = $reschedule['lead']['offline_status'];
					}
					if ($statusLabel === '' && !empty($reschedule['lead']['offline_status_label'])) {
						$statusLabel = $reschedule['lead']['offline_status_label'];
					}
					if ($classDate === '' && !empty($reschedule['lead']['offline_class_date'])) {
						$classDate = $reschedule['lead']['offline_class_date'];
					}
					$response->setResult(array(
						'success' => true,
						'status' => $status,
						'status_label' => $statusLabel,
						'drop' => isset($reschedule['drop']) ? $reschedule['drop'] : '',
						'class_date' => $classDate,
						'tags' => isset($reschedule['opp_tags']) ? $reschedule['opp_tags'] : array(),
						'opportunity' => $opp,
					));
					break;
				case 'offline_unreachable':
					require_once 'modules/Leads/models/OfflineGd11Service.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$unreachable = Leads_OfflineGd11Service::markUnreachableFromPotential($recordId, $userId);
					if (empty($unreachable['success'])) {
						throw new Exception(isset($unreachable['error']) ? $unreachable['error'] : 'Không ghi được “Không gọi được”');
					}
					$response->setResult($unreachable);
					break;
				default:
					throw new Exception('Unsupported mode.');
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
