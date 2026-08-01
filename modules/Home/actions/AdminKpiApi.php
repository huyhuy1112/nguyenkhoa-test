<?php
/*+***********************************************************************************
 * Admin KPI Dashboard API — summary + detail + Stage 2–5 widgets (Admin/CEO only).
 *************************************************************************************/

require_once 'modules/Home/helpers/AdminKpiAccess.php';
require_once 'modules/Home/models/AdminKpiService.php';

class Home_AdminKpiApi_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'index'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted($request->getModule(), 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$user = Users_Record_Model::getCurrentUserModel();
		if (!Home_AdminKpiAccess_Helper::isAllowed($user)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateReadAccess();
	}

	public function process(Vtiger_Request $request) {
		$prevDisplayErrors = ini_get('display_errors');
		ini_set('display_errors', '0');
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		ob_start();
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$mode = strtolower((string) $request->get('mode'));

		try {
			switch ($mode) {
				case 'summary':
					$response->setResult(array(
						'success' => true,
						'summary' => Home_AdminKpiService::getSummary(),
					));
					break;

				case 'detail':
					$section = (string) $request->get('section');
					$opts = array(
						'mode' => (string) $request->get('revenue_mode'),
						'period' => (string) $request->get('period'),
						'sale_id' => (int) $request->get('sale_id'),
						'full' => (int) $request->get('full') === 1,
					);
					$response->setResult(array(
						'success' => true,
						'section' => $section,
						'detail' => Home_AdminKpiService::getDetail($section, $opts),
					));
					break;

				case 'drilldown':
					$response->setResult(array(
						'success' => true,
						'drilldown' => Home_AdminKpiService::getDrilldown(
							(string) $request->get('type'),
							array(
								'key' => (string) $request->get('key'),
								'id' => (int) $request->get('id'),
								'year' => (int) $request->get('year'),
							)
						),
					));
					break;

				case 'widgets':
				case 'funnel':
				case 'revenue_chart':
				case 'performance':
				case 'alerts':
					$chartOpts = array(
						'group' => (string) $request->get('group'),
						'dimension' => (string) $request->get('dimension'),
						'sale_id' => (int) $request->get('sale_id'),
						'year' => (int) $request->get('year'),
					);
					if ($mode === 'funnel') {
						$payload = array('funnel' => Home_AdminKpiService::getSalesFunnel());
					} elseif ($mode === 'revenue_chart') {
						$payload = array('revenue_chart' => Home_AdminKpiService::getRevenueChart($chartOpts));
					} elseif ($mode === 'performance') {
						$payload = array('performance' => Home_AdminKpiService::getPerformance());
					} elseif ($mode === 'alerts') {
						$payload = array('alerts' => Home_AdminKpiService::getAlerts());
					} else {
						$payload = Home_AdminKpiService::getWidgets($chartOpts);
					}
					$response->setResult(array_merge(array('success' => true), $payload));
					break;

				default:
					throw new Exception('Unknown mode');
			}
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		@ob_end_clean();
		ini_set('display_errors', $prevDisplayErrors);
		$response->emit();
	}
}
