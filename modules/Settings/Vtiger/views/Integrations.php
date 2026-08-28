<?php
/*+***********************************************************************************
 * Settings → Tích hợp hệ thống (admin only via Settings_Vtiger_Index_View).
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_Integrations_View extends Settings_Vtiger_Index_View {

	/**
	 * After Zalo OAuth, browser Referer is oauth.zaloapp.com.
	 * Default validateReferer would throw "Illegal request" → ACCESS_DENIED page.
	 */
	public function validateRequest(Vtiger_Request $request) {
		$zaloOauth = $request->get('zalo_oauth');
		if ($zaloOauth !== null && $zaloOauth !== '') {
			return true;
		}
		$ref = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
		if ($ref !== '' && (stripos($ref, 'oauth.zaloapp.com') !== false || stripos($ref, 'zaloapp.com') !== false)) {
			return true;
		}
		return parent::validateRequest($request);
	}

	public function process(Vtiger_Request $request) {
		NkApiConnection::ensureInstalled();
		$qualifiedName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$connections = NkApiConnection::catalogForAdmin();
		$viewer->assign('CONNECTIONS', $connections);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('Integrations.tpl', $qualifiedName);
	}

	function getPageTitle(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		return vtranslate('LBL_NK_SYSTEM_INTEGRATIONS', $qualifiedModuleName);
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/Integrations.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		self::appendAssetCacheVer($jsScriptInstances, 'src', '20260827_zalo_oauth2');
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/Integrations.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		self::appendAssetCacheVer($cssInstances, 'href', '20260827_zalo_oauth2');
		return array_merge($headerCssInstances, $cssInstances);
	}
}
