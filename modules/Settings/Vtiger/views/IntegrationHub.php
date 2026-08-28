<?php
/*+***********************************************************************************
 * Settings → Integration Hub (dashboard UI; legacy Integrations view kept as fallback).
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_IntegrationHub_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		NkApiConnection::ensureInstalled();
		$qualifiedName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('HUB_SUMMARY', NkApiConnection::hubSummary());
		$connections = NkApiConnection::catalogForHub();
		$viewer->assign('HUB_CONNECTIONS', $connections);
		$viewer->assign('HUB_DEFAULT_CONNECTION', !empty($connections[0]) ? $connections[0] : array());
		$viewer->assign(
			'HUB_CONNECTIONS_JSON',
			json_encode($connections, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
		);
		$viewer->assign('HUB_ACTIVITY', NkApiConnection::recentActivity(8));
		$viewer->assign('LEGACY_INTEGRATIONS_URL', NkApiConnection::MENU_LINK);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('IntegrationHub.tpl', $qualifiedName);
	}

	function getPageTitle(Vtiger_Request $request) {
		return vtranslate('LBL_NK_INTEGRATION_HUB', $request->getModule(false));
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/IntegrationHub.mock.js',
			'~layouts/v7/modules/Settings/Vtiger/resources/IntegrationHub.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		self::appendAssetCacheVer($jsScriptInstances, 'src', '20260817_hub8');
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/IntegrationHub.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		self::appendAssetCacheVer($cssInstances, 'href', '20260817_hub8');
		return array_merge($headerCssInstances, $cssInstances);
	}
}
