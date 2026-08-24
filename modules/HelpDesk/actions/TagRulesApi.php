<?php
/*+***********************************************************************************
 * HelpDesk Tag Rule Engine API (DB-backed).
 * URL: index.php?module=HelpDesk&action=TagRulesApi&mode=...
 *************************************************************************************/

require_once 'modules/HelpDesk/models/TagRuleEngineService.php';

class HelpDesk_TagRulesApi_Action extends Vtiger_Action_Controller {

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
		$write = array(
			'save_tag', 'delete_tag', 'save_group', 'delete_group', 'save_rule', 'delete_rule', 'set_rule_active',
			'save_scenario', 'delete_scenario', 'reseed', 'dismiss', 'apply_lead',
			'save_affiliate_tier', 'delete_affiliate_tier', 'set_affiliate_tier_active',
			'save_sheet_scoring', 'reset_sheet_scoring',
		);
		if (in_array($mode, $write, true)) {
			$request->validateWriteAccess();
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$response = new Vtiger_Response();
		$mode = strtolower((string)$request->get('mode'));
		$svc = HelpDesk_TagRuleEngineService::getInstance();

		try {
			switch ($mode) {
				case 'bootstrap':
				case 'state':
					$state = $svc->bootstrap((int)$current_user->id);
					$response->setResult(array(
						'success' => true,
						'state' => $state,
						'alerts' => isset($state['alerts']) ? $state['alerts'] : array(),
					));
					break;

				case 'alerts':
					$alerts = $svc->getAlerts((int)$current_user->id, 200);
					$response->setResult(array(
						'success' => true,
						'alerts' => $alerts,
						'state' => $svc->bootstrap(),
					));
					break;

				case 'match':
					$tags = $request->get('tags');
					if (!is_array($tags)) {
						$payload = $this->decodePayload($request);
						$tags = isset($payload['tags']) && is_array($payload['tags']) ? $payload['tags'] : array();
					}
					$leadId = (int)$request->get('lead_id');
					if ($leadId > 0 && empty($tags)) {
						$tags = $svc->getLeadTagLabels($leadId);
					}
					$response->setResult(array(
						'success' => true,
						'match' => $svc->matchRules($tags, true),
					));
					break;

				case 'save_tag':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id && empty($payload['id'])) {
						$payload['id'] = $id;
					}
					$tag = $svc->upsertTag($payload, true);
					$response->setResult(array('success' => true, 'tag' => $tag, 'state' => $svc->bootstrap()));
					break;

				case 'save_group':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id && empty($payload['id'])) {
						$payload['id'] = $id;
					}
					// Creating a parent tag for Lead create form by default when from UI "tag cha"
					if (!isset($payload['show_on_create'])) {
						$payload['show_on_create'] = 1;
					}
					$group = $svc->upsertGroup($payload, true);
					$response->setResult(array('success' => true, 'group' => $group, 'state' => $svc->bootstrap()));
					break;

				case 'delete_group':
					$svc->deleteGroup((string)$request->get('id'));
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'delete_tag':
					$svc->deleteTag((string)$request->get('id'));
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'save_rule':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id && empty($payload['id'])) {
						$payload['id'] = $id;
					}
					$rule = $svc->upsertRule($payload, true);
					$response->setResult(array('success' => true, 'rule' => $rule, 'state' => $svc->bootstrap()));
					break;

				case 'set_rule_active':
					$id = (string)$request->get('id');
					$active = $request->get('is_active');
					$activeBool = ($active === true || $active === 1 || $active === '1' || $active === 'true');
					$rule = $svc->setRuleActive($id, $activeBool);
					$response->setResult(array('success' => true, 'rule' => $rule, 'state' => $svc->bootstrap()));
					break;

				case 'delete_rule':
					$svc->deleteRule((string)$request->get('id'));
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'save_scenario':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id && empty($payload['id'])) {
						$payload['id'] = $id;
					}
					$sc = $svc->upsertScenario($payload, true);
					$response->setResult(array('success' => true, 'scenario' => $sc, 'state' => $svc->bootstrap()));
					break;

				case 'delete_scenario':
					$svc->deleteScenario((string)$request->get('id'));
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'save_affiliate_tier':
					$payload = $this->decodePayload($request);
					$id = $request->get('id');
					if ($id && empty($payload['id'])) {
						$payload['id'] = $id;
					}
					$tier = $svc->upsertAffiliateTier($payload, true);
					$response->setResult(array('success' => true, 'tier' => $tier, 'state' => $svc->bootstrap()));
					break;

				case 'set_affiliate_tier_active':
					$id = (string)$request->get('id');
					$active = $request->get('is_active');
					$activeBool = ($active === true || $active === 1 || $active === '1' || $active === 'true');
					$tier = $svc->setAffiliateTierActive($id, $activeBool);
					$response->setResult(array('success' => true, 'tier' => $tier, 'state' => $svc->bootstrap()));
					break;

				case 'delete_affiliate_tier':
					$svc->deleteAffiliateTier((string)$request->get('id'));
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'resolve_affiliate':
					$code = (string)$request->get('code');
					if ($code === '') {
						$payload = $this->decodePayload($request);
						$code = isset($payload['code']) ? (string)$payload['code'] : '';
					}
					$asOf = $request->get('as_of');
					$response->setResult(array(
						'success' => true,
						'tier' => $svc->resolveAffiliateReward($code, $asOf ? (string)$asOf : null),
					));
					break;

				case 'get_sheet_scoring':
					$response->setResult(array(
						'success' => true,
						'sheet_scoring' => $svc->getSheetScoringConfig(),
						'state' => $svc->bootstrap(),
					));
					break;

				case 'save_sheet_scoring':
					$payload = $this->decodePayload($request);
					$cfg = $svc->saveSheetScoringConfig(is_array($payload) ? $payload : array());
					$response->setResult(array(
						'success' => true,
						'sheet_scoring' => $cfg,
						'state' => $svc->bootstrap(),
					));
					break;

				case 'reset_sheet_scoring':
					$cfg = $svc->resetSheetScoringConfig();
					$response->setResult(array(
						'success' => true,
						'sheet_scoring' => $cfg,
						'state' => $svc->bootstrap(),
					));
					break;

				case 'reseed':
					$svc->seedIfEmpty(true);
					$svc->seedAffiliateTiersIfEmpty(true);
					$response->setResult(array('success' => true, 'state' => $svc->bootstrap()));
					break;

				case 'dismiss':
					$leadId = (int)$request->get('lead_id');
					$ruleId = (string)$request->get('rule_id');
					$days = $request->get('days');
					$until = null;
					if ($days === null || $days === '' || $days === 'done') {
						$until = date('Y-m-d H:i:s', time() + 3650 * 86400);
					} else {
						$until = date('Y-m-d H:i:s', time() + ((int)$days) * 86400);
					}
					$svc->upsertDismissal((int)$current_user->id, $leadId, $ruleId, $until);
					$response->setResult(array(
						'success' => true,
						'alerts' => $svc->getAlerts((int)$current_user->id, 200),
					));
					break;

				case 'apply_lead':
					$leadId = (int)$request->get('lead_id');
					if ($leadId <= 0) {
						$leadId = (int)$request->get('record');
					}
					$action = $svc->applyNextActionToLead($leadId);
					$response->setResult(array(
						'success' => true,
						'next_action' => $action,
						'match' => $svc->matchRules($svc->getLeadTagLabels($leadId), true),
					));
					break;

				default:
					throw new Exception('Unknown mode: ' . $mode);
			}
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}

		$response->emit();
	}

	protected function decodePayload(Vtiger_Request $request) {
		$raw = $request->get('payload');
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$json = $request->getRaw('payload');
		if (is_string($json) && $json !== '') {
			$decoded = json_decode($json, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		return array();
	}
}
