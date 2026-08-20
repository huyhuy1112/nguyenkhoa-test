<?php
/*+***********************************************************************************
 * Modern ServiceContracts API — list + franchise Create/Edit (spreadsheet fields).
 *************************************************************************************/

require_once 'modules/ServiceContracts/models/ModernService.php';

class ServiceContracts_ModernApi_Action extends Vtiger_Action_Controller {

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
		if (in_array($mode, array(
			'delete', 'save_tags', 'save_next_action', 'save', 'save_franchise', 'save_inline', 'last_touch_call_log',
			'generate_affiliate', 'create_affiliate', 'set_affiliate_visible',
		), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		if (session_status() === PHP_SESSION_ACTIVE) {
			@session_write_close();
		}
		$response = new Vtiger_Response();
		$mode = strtolower((string) $request->get('mode'));
		$userId = (int) $current_user->id;

		try {
			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'contracts' => ServiceContracts_ModernService::listContracts($userId),
						'assignable_users' => ServiceContracts_ModernService::listAssignableUsers(),
					));
					break;
				case 'get':
				case 'get_franchise':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'contract' => ServiceContracts_ModernService::getFranchise($recordId),
						'picklists' => ServiceContracts_ModernService::franchisePicklists(),
					));
					break;
				case 'meta':
				case 'picklists':
					$excludeId = (int) $request->get('record');
					if ($excludeId <= 0) {
						$excludeId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'picklists' => ServiceContracts_ModernService::franchisePicklists(),
						'assignable_users' => ServiceContracts_ModernService::listAssignableUsers(),
						'payment_options' => array('Chuyển khoản', 'Tiền mặt', 'Thẻ', 'Ví'),
						'referrers' => ServiceContracts_ModernService::listReferrerOptions(
							$excludeId > 0 ? $excludeId : null
						),
						'affiliate_tiers' => ServiceContracts_ModernService::listAffiliateTiers(),
					));
					break;
				case 'list_referrers':
					$excludeId = (int) $request->get('record');
					if ($excludeId <= 0) {
						$excludeId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'referrers' => ServiceContracts_ModernService::listReferrerOptions(
							$excludeId > 0 ? $excludeId : null
						),
						'affiliate_tiers' => ServiceContracts_ModernService::listAffiliateTiers(),
					));
					break;
				case 'save_inline':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$payload = $request->get('payload');
					if (is_string($payload)) {
						$decoded = json_decode($payload, true);
						$payload = is_array($decoded) ? $decoded : array();
					}
					if (!is_array($payload)) {
						$payload = array();
					}
					foreach (array(
						'franchise_status', 'contact_status', 'referrer', 'assigned_user_id', 'description', 'start_date',
						'interaction_1', 'interaction_2', 'interaction_3', 'interaction_materials',
					) as $k) {
						if (!isset($payload[$k]) && $request->get($k) !== null && $request->get($k) !== '') {
							$payload[$k] = $request->get($k);
						}
					}
					$saved = ServiceContracts_ModernService::saveInlineFranchise($recordId, $payload, $userId);
					$response->setResult(array('success' => true, 'contract' => $saved));
					break;
				case 'save':
				case 'save_franchise':
					$payload = $request->get('payload');
					if (is_string($payload)) {
						$decoded = json_decode($payload, true);
						$payload = is_array($decoded) ? $decoded : array();
					}
					if (!is_array($payload)) {
						$payload = array();
					}
					// Also accept flat request fields (form POST).
					$keys = array(
						'id', 'record', 'full_name', 'phone', 'received_date', 'business_note',
						'franchise_status', 'data_source', 'referrer', 'contact_status',
						'interaction_1', 'interaction_2', 'interaction_3', 'interaction_materials',
						'referral_code', 'registration_date', 'sale_owner', 'sale_owner_id',
						'contract_signed_date', 'store_count', 'payment_condition', 'payment_date', 'tags',
						'affiliate_tier_prefix',
					);
					foreach ($keys as $k) {
						if (!isset($payload[$k]) && $request->get($k) !== null && $request->get($k) !== '') {
							$payload[$k] = $request->get($k);
						}
					}
					if (empty($payload['id']) && $request->get('record')) {
						$payload['id'] = $request->get('record');
					}
					$saved = ServiceContracts_ModernService::saveFranchise($payload, $userId);
					$response->setResult(array(
						'success' => true,
						'contract' => $saved,
					));
					break;
				case 'save_next_action':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$nextAction = $request->get('next_action');
					$saved = ServiceContracts_ModernService::saveNextAction($recordId, $nextAction);
					$response->setResult(array('success' => true, 'next_action' => $saved));
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
					$response->setResult(ServiceContracts_ModernService::saveTags($recordId, $tagsRaw, $userId));
					break;
				case 'delete':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					ServiceContracts_ModernService::deleteContract($recordId);
					$response->setResult(array('success' => true));
					break;
				case 'resolve_referral':
					$code = (string) $request->get('code');
					$asOf = $request->get('as_of');
					$tier = ServiceContracts_ModernService::resolveReferralTier(
						$code,
						$asOf ? (string) $asOf : null
					);
					$response->setResult(array('success' => true, 'tier' => $tier));
					break;
				case 'generate_affiliate':
				case 'create_affiliate':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$before = ServiceContracts_ModernService::getAffiliateCode($recordId);
					$code = ServiceContracts_ModernService::generateAffiliateCode($recordId);
					ServiceContracts_ModernService::setAffiliateVisible($recordId, true);
					$contract = ServiceContracts_ModernService::getFranchise($recordId);
					$response->setResult(array(
						'success' => true,
						'affiliate_code' => $code,
						'already_existed' => ($before !== ''),
						'affiliate_visible' => 1,
						'contract' => $contract,
					));
					break;
				case 'set_affiliate_visible':
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$visibleRaw = $request->get('visible');
					$visible = !($visibleRaw === '0' || $visibleRaw === 0 || $visibleRaw === false || $visibleRaw === 'false');
					$response->setResult(ServiceContracts_ModernService::setAffiliateVisible($recordId, $visible));
					break;
				case 'check_duplicate':
					$phone = (string) $request->get('phone');
					$excludeId = (int) $request->get('record');
					if ($excludeId <= 0) {
						$excludeId = (int) $request->get('id');
					}
					$check = ServiceContracts_ModernService::checkDuplicateByPhone(
						$phone,
						$excludeId > 0 ? $excludeId : null
					);
					$response->setResult(array(
						'success' => true,
						'duplicate' => $check,
					));
					break;
				case 'last_touch_call_list':
					require_once 'modules/ServiceContracts/models/LastTouchCallService.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => ServiceContracts_LastTouchCallService::getSummary($recordId),
					));
					break;
				case 'last_touch_call_log':
					require_once 'modules/ServiceContracts/models/LastTouchCallService.php';
					$recordId = (int) $request->get('record');
					if ($recordId <= 0) {
						$recordId = (int) $request->get('id');
					}
					$result = $request->get('call_result');
					if ($result === null || $result === '') {
						$result = $request->get('result');
					}
					$note = $request->get('note');
					if ($note === null || $note === '') {
						$note = $request->get('call_note');
					}
					$logged = ServiceContracts_LastTouchCallService::logCall(
						$recordId,
						(string) $result,
						(string) $note,
						$userId
					);
					$response->setResult(array_merge(array('success' => true), $logged, array(
						'lastTouchCalls' => $logged,
					)));
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
