<?php
/*+***********************************************************************************
 * Modern Leads API — list / get / save / delete / segments (Phase 1).
 *************************************************************************************/

require_once 'modules/Leads/models/ModernService.php';
require_once 'modules/Leads/models/CommerceService.php';
require_once 'modules/Leads/models/ConvertService.php';
require_once 'modules/Leads/models/DetailFeedService.php';
require_once 'modules/Leads/models/SalesVerifyService.php';

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
		if (in_array($mode, array(
			'save', 'save_next_action', 'save_inline_category_tags', 'delete', 'segments_save', 'seed',
			'link_order', 'link_activity', 'calendar_tasks_sync', 'convert', 'comment_save', 'bulk_assign_owner',
			'dedupe_leads', 'last_touch_call_log',
			'sheet_settings_save', 'sheet_poll_now', 'merge_leads', 'restore_lead', 'purge_lead', 'soft_delete',
			'sales_verify_save', 'online_verify_save',
			'product_upsert', 'product_remove', 'product_set_stage',
		), true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		// Keep AJAX JSON clean even when config.inc.php enables display_errors (local/dev).
		$prevDisplayErrors = ini_get('display_errors');
		ini_set('display_errors', '0');
		$obLevel = ob_get_level();
		ob_start();
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$userId = (int)$current_user->id;

		try {
			if (!Leads_ModernService::isInstalled(PearDatabase::getInstance())) {
				Leads_ModernService::installSchema(PearDatabase::getInstance());
			}

			switch ($mode) {
				case 'list':
					require_once 'modules/Leads/models/LeadProductsService.php';
					$response->setResult(array(
						'success' => true,
						'leads' => Leads_ModernService::listLeads($userId),
						'assignable_users' => Leads_ModernService::listAssignableUsers(),
						'product_catalog' => Leads_LeadProductsService::catalog(),
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
					if ($recordId === null || $recordId === '') {
						if (isset($payload['crmid']) && $payload['crmid'] !== '') {
							$recordId = $payload['crmid'];
						} elseif (isset($payload['id']) && $payload['id'] !== '') {
							$recordId = $payload['id'];
						}
					}
					$lead = Leads_ModernService::saveLead($payload, $recordId);
					$response->setResult(array('success' => true, 'lead' => $lead));
					break;

				case 'save_next_action':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$nextAction = $request->get('next_action');
					if ($nextAction === null) {
						$payload = $this->decodePayload($request);
						$nextAction = isset($payload['next_action']) ? $payload['next_action'] : '';
						if (($recordId === null || $recordId === '') && isset($payload['id'])) {
							$recordId = $payload['id'];
						}
					}
					$saved = Leads_ModernService::updateNextAction($recordId, $nextAction);
					$response->setResult(array('success' => true, 'next_action' => $saved));
					break;

				case 'save_inline_category_tags':
					$recordId = $request->get('record');
					if ($recordId === null || $recordId === '') {
						$recordId = $request->get('id');
					}
					$payload = $this->decodePayload($request);
					$cats = array();
					$map = array(
						'source' => array('source', 'mk_source'),
						'customer' => array('customer', 'mk_customer'),
						'purchase' => array('purchase', 'mk_stage'),
						'tier' => array('tier', 'mk_tier'),
					);
					foreach ($map as $catKey => $aliases) {
						foreach ($aliases as $alias) {
							if (isset($payload[$alias])) {
								$cats[$catKey] = $payload[$alias];
								break;
							}
							if ($request->has($alias)) {
								$cats[$catKey] = $request->get($alias);
								break;
							}
						}
					}
					$result = Leads_ModernService::updateInlineCategoryTags($recordId, $cats, $userId);
					$response->setResult(array('success' => true) + $result);
					break;

				case 'delete':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					$purge = (bool)$request->get('purge');
					if ($purge) {
						Leads_ModernService::purgeLead($id);
					} else {
						Leads_ModernService::softDeleteLead($id);
					}
					$response->setResult(array('success' => true));
					break;

				case 'soft_delete':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					Leads_ModernService::softDeleteLead($id);
					$response->setResult(array('success' => true));
					break;

				case 'restore_lead':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					Leads_ModernService::restoreLead($id);
					$response->setResult(array(
						'success' => true,
						'lead' => Leads_ModernService::getLead($id, $userId),
					));
					break;

				case 'purge_lead':
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					Leads_ModernService::purgeLead($id);
					$response->setResult(array('success' => true));
					break;

				case 'list_trash':
					$response->setResult(array(
						'success' => true,
						'leads' => Leads_ModernService::listTrashLeads($userId),
					));
					break;

				case 'merge_leads':
					$payload = $this->decodePayload($request);
					$keeper = isset($payload['keeper_id']) ? $payload['keeper_id'] : $request->get('keeper_id');
					$discard = isset($payload['discard_id']) ? $payload['discard_id'] : $request->get('discard_id');
					$lead = Leads_ModernService::mergeLeads($keeper, $discard, $userId);
					$response->setResult(array('success' => true, 'lead' => $lead));
					break;

				case 'sheet_settings_get':
					require_once 'modules/Leads/models/SheetImportService.php';
					if (!is_admin($current_user)) {
						throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
					}
					// Never return private_key / full SA JSON to browser
					$settings = Leads_SheetImportService::getSettingsForAdmin();
					$response->setResult(array('success' => true, 'settings' => $settings));
					break;

				case 'sheet_settings_save':
					require_once 'modules/Leads/models/SheetImportService.php';
					if (!is_admin($current_user)) {
						throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
					}
					$payload = $this->decodePayload($request);
					Leads_SheetImportService::saveSettings($payload, $userId);
					Leads_SheetImportService::registerCron();
					$response->setResult(array(
						'success' => true,
						'settings' => Leads_SheetImportService::getSettingsForAdmin(),
					));
					break;

				case 'sheet_poll_now':
					require_once 'modules/Leads/models/SheetImportService.php';
					if (!is_admin($current_user)) {
						throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
					}
					$result = Leads_SheetImportService::pollOnce();
					$response->setResult(array('success' => !empty($result['success'])) + $result);
					break;

				case 'sales_verify_options':
					$response->setResult(array(
						'success' => true,
						'options' => Leads_SalesVerifyService::optionsCatalog(),
					));
					break;

				case 'sales_verify_preview':
					$payload = $this->decodePayload($request);
					$result = Leads_SalesVerifyService::compute(array(
						'c1' => isset($payload['c1']) ? $payload['c1'] : '',
						'c2' => isset($payload['c2']) ? $payload['c2'] : '',
						'c3' => isset($payload['c3']) ? $payload['c3'] : '',
						'c4' => isset($payload['c4']) ? $payload['c4'] : 0,
						'c5' => isset($payload['c5']) ? $payload['c5'] : 0,
					));
					$response->setResult(array('success' => true, 'result' => $result));
					break;

				case 'sales_verify_save':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					if (($id === null || $id === '') && isset($payload['id'])) {
						$id = $payload['id'];
					}
					$saved = Leads_SalesVerifyService::saveForLead($id, $payload, $userId);
					$response->setResult($saved);
					break;

				case 'online_verify_preview':
					require_once 'modules/Leads/models/OnlineGd12Service.php';
					$payload = $this->decodePayload($request);
					$result = Leads_OnlineGd12Service::compute(
						isset($payload['q1']) ? $payload['q1'] : (isset($payload['c1']) ? $payload['c1'] : ''),
						isset($payload['q2']) ? $payload['q2'] : (isset($payload['c2']) ? $payload['c2'] : ''),
						isset($payload['q3']) ? $payload['q3'] : (isset($payload['c3']) ? $payload['c3'] : ''),
						isset($payload['q4']) ? $payload['q4'] : (isset($payload['c4']) ? $payload['c4'] : '')
					);
					$response->setResult(array('success' => !empty($result['success']), 'result' => $result));
					break;

				case 'online_verify_save':
					require_once 'modules/Leads/models/OnlineGd12Service.php';
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id === null || $id === '') {
						$id = $request->get('record');
					}
					if (($id === null || $id === '') && isset($payload['id'])) {
						$id = $payload['id'];
					}
					$saved = Leads_OnlineGd12Service::saveForLead($id, $payload, $userId);
					$response->setResult($saved);
					break;

				case 'sheet_poll_status':
					require_once 'modules/Leads/models/SheetImportService.php';
					$settings = Leads_SheetImportService::getSettings();
					$importCount = 0;
					try {
						$adb = PearDatabase::getInstance();
						$cntRes = $adb->pquery(
							'SELECT COUNT(*) AS c FROM ' . Leads_SheetImportService::TABLE_IMPORT,
							array()
						);
						if ($cntRes && $adb->num_rows($cntRes) > 0) {
							$importCount = (int) $adb->query_result($cntRes, 0, 'c');
						}
					} catch (Exception $e) {
						$importCount = 0;
					}
					$response->setResult(array(
						'success' => true,
						'enabled' => !empty($settings['enabled']),
						'last_poll_at' => $settings['last_poll_at'],
						'last_error' => $settings['last_error'],
						'last_result' => $settings['last_result'],
						'import_count' => $importCount,
					));
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

				case 'last_touch_call_list':
					require_once 'modules/Leads/models/LastTouchCallService.php';
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => Leads_LastTouchCallService::getSummary($leadId),
					));
					break;

				case 'last_touch_call_log':
					require_once 'modules/Leads/models/LastTouchCallService.php';
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					$result = $request->get('call_result');
					if ($result === null || $result === '') {
						$result = $request->get('result');
					}
					$note = $request->get('note');
					if ($note === null) {
						$note = $request->get('call_note');
					}
					$logged = Leads_LastTouchCallService::logCall($leadId, $result, $note, $userId);
					$response->setResult(array(
						'success' => true,
						'lastTouchCalls' => $logged,
						'lead' => isset($logged['lead']) ? $logged['lead'] : null,
						'convert' => isset($logged['convert']) ? $logged['convert'] : null,
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

				case 'product_catalog':
					require_once 'modules/Leads/models/LeadProductsService.php';
					$response->setResult(array(
						'success' => true,
						'catalog' => Leads_LeadProductsService::catalog(),
					));
					break;

				case 'product_upsert':
					require_once 'modules/Leads/models/LeadProductsService.php';
					$payload = $this->decodePayload($request);
					$leadId = $request->get('id');
					if ($leadId === null || $leadId === '') {
						$leadId = $request->get('record');
					}
					if (($leadId === null || $leadId === '') && isset($payload['id'])) {
						$leadId = $payload['id'];
					}
					$leadId = Leads_ModernService::resolveLeadRecordId($leadId);
					$group = $request->get('group');
					if ($group === null || $group === '') {
						$group = isset($payload['group']) ? $payload['group'] : '';
					}
					$productName = $request->get('product_name');
					if ($productName === null) {
						$productName = isset($payload['product_name']) ? $payload['product_name'] : '';
					}
					$product = Leads_LeadProductsService::upsertProduct($leadId, $group, $productName, $userId);
					$response->setResult(array(
						'success' => true,
						'product' => $product,
						'lead' => Leads_ModernService::getLead($leadId, $userId),
					));
					break;

				case 'product_remove':
					require_once 'modules/Leads/models/LeadProductsService.php';
					$payload = $this->decodePayload($request);
					$productId = $request->get('product_id');
					if ($productId === null || $productId === '') {
						$productId = isset($payload['product_id']) ? $payload['product_id'] : $request->get('id');
					}
					$leadId = Leads_LeadProductsService::removeProduct($productId, $userId);
					$response->setResult(array(
						'success' => true,
						'lead' => Leads_ModernService::getLead($leadId, $userId),
					));
					break;

				case 'product_set_stage':
					require_once 'modules/Leads/models/LeadProductsService.php';
					$payload = $this->decodePayload($request);
					$productId = $request->get('product_id');
					if ($productId === null || $productId === '') {
						$productId = isset($payload['product_id']) ? $payload['product_id'] : $request->get('id');
					}
					$stage = $request->get('stage');
					if ($stage === null || $stage === '') {
						$stage = isset($payload['stage']) ? $payload['stage'] : '';
					}
					$product = Leads_LeadProductsService::setStage($productId, $stage, $userId);
					$leadId = isset($product['leadid']) ? $product['leadid'] : 0;
					$response->setResult(array(
						'success' => true,
						'product' => $product,
						'lead' => $leadId ? Leads_ModernService::getLead($leadId, $userId) : null,
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

		// Discard any accidental notice/warning HTML before JSON emit.
		while (ob_get_level() > $obLevel) {
			ob_end_clean();
		}
		ini_set('display_errors', $prevDisplayErrors);
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
