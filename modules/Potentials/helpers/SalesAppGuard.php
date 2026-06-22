<?php
/*+***********************************************************************************
 * Potentials (Opportunities) always live under SALES app shell in this CRM.
 *************************************************************************************/

class Potentials_SalesAppGuard {

	const APP = 'SALES';

	public static function enforce(Vtiger_Request $request) {
		if ($request->isAjax()) {
			return;
		}
		$module = $request->getModule();
		if ($module !== 'Potentials') {
			return;
		}
		$app = strtoupper(trim((string)$request->get('app')));
		if ($app === self::APP) {
			return;
		}
		$params = $_GET;
		if (!is_array($params)) {
			$params = array();
		}
		$params['module'] = 'Potentials';
		if (empty($params['view'])) {
			$params['view'] = $request->get('view') ?: 'List';
		}
		$params['app'] = self::APP;
		$url = 'index.php?' . http_build_query($params);
		header('Location: ' . $url);
		exit;
	}

	public static function assignViewer(Vtiger_Request $request, $viewer = null) {
		if ($viewer === null) {
			return;
		}
		$viewer->assign('SELECTED_MENU_CATEGORY', self::APP);
	}

	public static function appendAppToUrl($url) {
		$url = (string)$url;
		if ($url === '') {
			return $url;
		}
		if (stripos($url, 'app=') !== false) {
			return $url;
		}
		return $url . (strpos($url, '?') !== false ? '&' : '?') . 'app=' . self::APP;
	}
}
