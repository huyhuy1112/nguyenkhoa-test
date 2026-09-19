<?php
/*+***********************************************************************************
 * Public webhook endpoint for Zalo OA contact form -> Leads.
 * URL: index.php?module=Leads&action=ZaloOaWebhook
 *************************************************************************************/

require_once 'modules/Leads/models/ZaloOaLeadIngestService.php';

class Leads_ZaloOaWebhook_Action extends Vtiger_Action_Controller {

	/**
	 * Zalo calls this URL without a CRM session — must stay public.
	 */
	public function loginRequired() {
		return false;
	}

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		// Public webhook endpoint: do not enforce CRM login permissions.
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		// Webhook requests are external and cannot pass vtiger CSRF token.
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$raw = file_get_contents('php://input');
			$payload = array();
			if (is_string($raw) && trim($raw) !== '') {
				$decoded = json_decode($raw, true);
				if (is_array($decoded)) {
					$payload = $decoded;
				}
			}
			if (empty($payload)) {
				$payload = $request->getAll();
				if (!is_array($payload)) {
					$payload = array();
				}
			}

			$result = Leads_ZaloOaLeadIngestService::ingestWebhook($payload);
			$response->setResult(array('ok' => true) + $result);
		} catch (Exception $e) {
			$response->setError('Error', $e->getMessage(), $e->getMessage());
		}
		$response->emit();
	}
}

