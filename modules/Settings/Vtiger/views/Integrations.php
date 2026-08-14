<?php
/*+***********************************************************************************
 * Settings → Tích hợp hệ thống (admin only via Settings_Vtiger_Index_View).
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_Integrations_View extends Settings_Vtiger_Index_View {

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
		self::appendAssetCacheVer($jsScriptInstances, 'src', '20260814_integ_ui4');
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/Integrations.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		self::appendAssetCacheVer($cssInstances, 'href', '20260814_integ_ui4');
		return array_merge($headerCssInstances, $cssInstances);
	}

	/**
	 * Vtiger file_exists() uses the full path; ?mk_v= in the path breaks loading.
	 * @param array $assets Vtiger_CssScript_Model[]|Vtiger_JsScript_Model[]
	 * @param string $key href|src
	 * @param string $ver
	 */
	protected static function appendAssetCacheVer(array $assets, $key, $ver) {
		foreach ($assets as $asset) {
			if (!$asset || !method_exists($asset, 'get')) {
				continue;
			}
			$url = $asset->get($key);
			if ($url === '' || $url === null) {
				continue;
			}
			$sep = (strpos($url, '?') !== false) ? '&' : '?';
			$asset->set($key, $url . $sep . 'mk_v=' . rawurlencode($ver));
		}
	}
}
