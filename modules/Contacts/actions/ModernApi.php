<?php
/*+***********************************************************************************
 * Modern Contacts API — list for SALES Lovable-style UI.
 *************************************************************************************/

require_once 'modules/Contacts/models/ModernService.php';

class Contacts_ModernApi_Action extends Vtiger_Action_Controller {

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
		if (in_array($mode, array('delete', 'class_reg_add', 'credential_save', 'save_tags', 'save_inline_fields', 'save_offline_attend', 'last_touch_call_log', 'edubit_renew', 'edubit_sync_progress', 'edubit_sync_all', 'edubit_provision'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		if (session_status() === PHP_SESSION_ACTIVE) {
			@session_write_close();
		}
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$userId = (int)$current_user->id;

		try {
			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'contacts' => Contacts_ModernService::listContacts($userId),
						'assignable_users' => Contacts_ModernService::listAssignableUsers(),
					));
					break;
				case 'class_reg_list':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'class_reg' => Contacts_ModernService::getClassRegSummary($recordId),
					));
					break;
				case 'class_reg_add':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$date = $request->get('registered_on');
					if ($date === null || $date === '') {
						$date = $request->get('date');
					}
					$kind = $request->get('entry_kind');
					if ($kind === null || $kind === '') {
						$kind = $request->get('kind');
					}
					if ($kind === null || $kind === '') {
						$kind = 'register';
					}
					$classCode = $request->get('class_code');
					if ($classCode === null || $classCode === '') {
						$classCode = $request->get('class');
					}
					// Combo MQBB+PCTH: ghi 2 lần đăng ký + tặng 2 khóa online.
					if (strtolower(trim((string) $classCode)) === 'combo_mqbb_pcth') {
						$summary = Contacts_ModernService::addClassRegLog($recordId, $date, $userId, $kind, 'mqbb');
						$summaryPcth = Contacts_ModernService::addClassRegLog($recordId, $date, $userId, $kind, 'pcth');
						$response->setResult(array(
							'success' => true,
							'class_reg' => $summaryPcth,
							'combo' => true,
							'gift' => array(
								'gift_online' => true,
								'course_ids' => array('29403', '29218'),
								'hint' => 'Combo: đã ghi MQBB + PCTH và tặng online cùng khóa.',
							),
						));
						break;
					}
					$summary = Contacts_ModernService::addClassRegLog($recordId, $date, $userId, $kind, $classCode);
					$response->setResult(array(
						'success' => true,
						'class_reg' => $summary,
						'gift' => isset($summary['gift']) ? $summary['gift'] : null,
					));
					break;
				case 'credential_get':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'credentials' => Contacts_ModernService::getCredentialState($recordId),
					));
					break;
				case 'credential_save':
					$recordId = (int)$request->get('record');
					if ($recordId <= 0) {
						$recordId = (int)$request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'credentials' => Contacts_ModernService::saveCredentialFields(
							$recordId,
							$request->get('da_cap_bang'),
							$request->get('da_cap_tai_khoan')
						),
					));
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
					$response->setResult(Contacts_ModernService::saveTags($recordId, $tagsRaw, $userId));
					break;
				case 'save_inline_fields':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$all = method_exists($request, 'getAll') ? $request->getAll() : $_REQUEST;
					$phoneArg = array_key_exists('phone', $all) ? $request->get('phone') : null;
					$addressArg = null;
					if (array_key_exists('address', $all)) {
						$addressArg = $request->get('address');
					} elseif (array_key_exists('mailingstreet', $all)) {
						$addressArg = $request->get('mailingstreet');
					}
					$bizArg = array_key_exists('business_model', $all) ? $request->get('business_model') : null;
					$response->setResult(Contacts_ModernService::saveInlineFields($recordId, $phoneArg, $addressArg, $bizArg));
					break;
				case 'save_offline_attend':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$response->setResult(Contacts_ModernService::saveOfflineAttend(
						$recordId,
						$request->get('class_code'),
						$request->get('datetime')
					));
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					Contacts_ModernService::deleteContact($recordId);
					$response->setResult(array('success' => true));
					break;
				case 'last_touch_call_list':
					require_once 'modules/Contacts/models/LastTouchCallService.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => Contacts_LastTouchCallService::getSummary($recordId),
					));
					break;
				case 'last_touch_call_log':
					require_once 'modules/Contacts/models/LastTouchCallService.php';
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
					$logged = Contacts_LastTouchCallService::logCall($recordId, $result, $note, $userId);
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => $logged,
						'logged' => isset($logged['logged']) ? $logged['logged'] : null,
					));
					break;
				case 'edubit_renew':
					require_once 'modules/Leads/models/OnlineGd12Service.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
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
					if ($request->get('reason') !== null && $request->get('reason') !== '') {
						$payload['reason'] = $request->get('reason');
					}
					$saved = Leads_OnlineGd12Service::renewEdubitAccessForContact($recordId, $payload, $userId);
					$response->setResult($saved);
					break;
				case 'edubit_sync_progress':
					require_once 'modules/Leads/models/OnlineGd12Service.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$saved = Leads_OnlineGd12Service::syncEdubitProgressForContact($recordId, $userId);
					$response->setResult($saved);
					break;
				case 'edubit_sync_all':
					require_once 'modules/Leads/models/OnlineGd12Service.php';
					$limit = (int) $request->get('limit');
					if ($limit <= 0) {
						$limit = 150;
					}
					$saved = Leads_OnlineGd12Service::syncEdubitProgressForAllContacts($limit, $userId);
					$response->setResult($saved);
					break;
				case 'product_catalog':
					require_once 'modules/Contacts/helpers/ProductCatalog.php';
					$response->setResult(array(
						'success' => true,
						'catalog' => Contacts_ProductCatalog::catalogPayload(),
					));
					break;
				case 'edubit_provision':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$payload = array();
					$raw = $request->getRaw('payload');
					if (is_array($raw)) {
						$payload = $raw;
					} elseif (is_string($raw) && $raw !== '') {
						$decoded = json_decode($raw, true);
						if (is_array($decoded)) {
							$payload = $decoded;
						}
					}
					if (empty($payload['course_id']) && $request->get('course_id') !== '') {
						$payload['course_id'] = $request->get('course_id');
					}
					if (empty($payload['course_ids']) && $request->get('course_ids') !== '') {
						$payload['course_ids'] = $request->get('course_ids');
					}
					if (empty($payload['email']) && $request->get('email') !== '') {
						$payload['email'] = $request->get('email');
					}
					if (empty($payload['name']) && $request->get('name') !== '') {
						$payload['name'] = $request->get('name');
					}
					if (empty($payload['phone']) && $request->get('phone') !== '') {
						$payload['phone'] = $request->get('phone');
					}
					$saved = Contacts_ModernService::provisionEdubitForContact($recordId, $payload, $userId);
					$response->setResult($saved);
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
