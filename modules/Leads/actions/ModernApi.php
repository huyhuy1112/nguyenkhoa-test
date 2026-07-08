<?php
/*+***********************************************************************************
 * Modern Leads API — list / get / save / delete / segments (Phase 1).
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';
require_once 'modules/Leads/models/CommerceService.php';
require_once 'modules/Leads/models/ConvertService.php';
require_once 'modules/Leads/models/DetailFeedService.php';

class Leads_ModernApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$mode = strtolower((string)$request->get('mode'));
		if (in_array($mode, array('save', 'delete', 'segments_save', 'seed', 'link_order', 'link_activity', 'calendar_tasks_sync', 'convert', 'comment_save', 'bulk_assign_owner', 'dedupe_leads'), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$userId = (int)$current_user->id;

		try {
			if (!Leads_ModernService::isInstalled(PearDatabase::getInstance())) {
				Leads_ModernService::installSchema(PearDatabase::getInstance());
			}

			switch ($mode) {
				case 'list':
					$response->setResult(array(
						'success' => true,
						'leads' => Leads_ModernService::listLeads($userId),
						'assignable_users' => Leads_ModernService::listAssignableUsers(),
					));
					break;

				case 'get':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$lead = Leads_ModernService::getLead($id, $userId);
					if (!$lead) {
						throw new Exception('Lead not found.');
					}
					if ($request->get('with_feed')) {
						$leadId = (int)$lead['crmid'];
						try {
							$lead['comments'] = Leads_DetailFeedService::getComments($leadId);
							$lead['modUpdates'] = Leads_DetailFeedService::getUpdates($leadId);
						} catch (Exception $feedEx) {
							$lead['comments'] = array();
							$lead['modUpdates'] = array();
						}
					}
					$response->setResult(array('success' => true, 'lead' => $lead));
					break;

				case 'updates':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$response->setResult(array(
						'success' => true,
						'updates' => Leads_DetailFeedService::getUpdates($id),
					));
					break;

				case 'comments':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$response->setResult(array(
						'success' => true,
						'comments' => Leads_DetailFeedService::getComments($id),
					));
					break;

				case 'comment_save':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$text = $request->get('text');
					if ($text === null || $text === '') {
						$payload = $this->decodePayload($request);
						$text = isset($payload['text']) ? $payload['text'] : '';
					}
					$comment = Leads_DetailFeedService::saveComment($id, $text);
					$response->setResult(array(
						'success' => true,
						'comment' => $comment,
						'comments' => Leads_DetailFeedService::getComments($id),
					));
					break;

				case 'bulk_assign_owner':
					$payload = $this->decodePayload($request);
					$ids = isset($payload['ids']) && is_array($payload['ids']) ? $payload['ids'] : array();
					$owner = isset($payload['owner']) ? $payload['owner'] : $request->get('owner');
					$leads = Leads_ModernService::assignOwnerToLeads($ids, $owner);
					$response->setResult(array('success' => true, 'leads' => $leads));
					break;

				case 'dedupe_leads':
					$apply = (bool)$request->get('apply');
					$result = Leads_ModernService::dedupeModernLeadsByPhone(!$apply);
					$response->setResult(array('success' => true) + $result);
					break;

				case 'save':
					$payload = $this->decodePayload($request);
					$recordId = $request->get('record');
					if (!$recordId && isset($payload['id'])) {
						$recordId = $payload['id'];
					}
					$lead = Leads_ModernService::saveLead($payload, $recordId);
					$response->setResult(array('success' => true, 'lead' => $lead));
					break;

				case 'delete':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					Leads_ModernService::deleteLead($id);
					$response->setResult(array('success' => true));
					break;

				case 'segments_list':
					$response->setResult(array(
						'success' => true,
						'segments' => Leads_ModernService::getSegments($userId),
					));
					break;

				case 'segments_save':
					$payload = $this->decodePayload($request);
					$segments = isset($payload['segments']) && is_array($payload['segments']) ? $payload['segments'] : array();
					$response->setResult(array(
						'success' => true,
						'segments' => Leads_ModernService::saveSegments($userId, $segments),
					));
					break;

				case 'seed':
					$force = (bool)$request->get('force');
					$result = Leads_ModernService::seedDemoLeads($force);
					$response->setResult(array('success' => true) + $result);
					break;

				case 'link_order':
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$salesOrderId = $request->get('salesorder_id');
					if (!$salesOrderId) {
						$salesOrderId = $request->get('salesorderid');
					}
					Leads_CommerceService::linkSalesOrderToLead($leadId, $salesOrderId);
					$response->setResult(array(
						'success' => true,
						'lead' => Leads_ModernService::getLead($leadId, $userId),
					));
					break;

				case 'link_activity':
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$activityId = $request->get('activity_id');
					if ($activityId === null || $activityId === '') {
						$activityId = $request->get('activityid');
					}
					Leads_CommerceService::linkActivityToLead($leadId, $activityId);
					$response->setResult(array(
						'success' => true,
						'lead' => Leads_ModernService::getLead($leadId, $userId),
					));
					break;

				case 'calendar_tasks_sync':
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$payload = $this->decodePayload($request);
					$tasks = isset($payload['calendarTasks']) && is_array($payload['calendarTasks']) ? $payload['calendarTasks'] : array();
					$lead = Leads_ModernService::syncCalendarTasks($leadId, $tasks, $userId);
					$response->setResult(array('success' => true, 'lead' => $lead));
					break;

				case 'search_orders':
					$query = trim((string)$request->get('q'));
					$response->setResult(array(
						'success' => true,
						'orders' => Leads_CommerceService::searchSalesOrders($query),
					));
					break;

				case 'convert':
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$createAccount = (bool)$request->get('create_account');
					$orderCategory = $request->get('order_category');
					try {
						$result = Leads_ConvertService::convertLead($leadId, array(
							'create_account' => $createAccount,
							'order_category' => $orderCategory,
						));
						$response->setResult(array('success' => true) + $result);
					} catch (Exception $convertEx) {
						// Return debug info for browser console (BA/dev only).
						$debug = array(
							'lead_id' => $leadId,
							'create_account' => $createAccount,
							'order_category' => $orderCategory,
							'message' => $convertEx->getMessage(),
						);
						error_log('[MK_LEAD_CONVERT_FAIL] ' . json_encode($debug));
						$response->setResult(array(
							'success' => false,
							'error' => $convertEx->getMessage(),
							'debug' => $debug,
						));
					}
					break;

				default:
					throw new Exception('Unknown mode: ' . $mode);
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}

	protected function decodePayload(Vtiger_Request $request) {
		$raw = $request->getRaw('payload');
		if ($raw) {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$all = $request->getAll();
		unset($all['module'], $all['action'], $all['mode'], $all['__vtrftk']);
		return is_array($all) ? $all : array();
	}
}
