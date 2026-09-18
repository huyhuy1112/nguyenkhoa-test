<?php
/*+***********************************************************************************
 * Settings → Round Robin phân bổ Lead (admin).
 *************************************************************************************/

require_once 'modules/Leads/models/RoundRobinService.php';

class Settings_Vtiger_LeadRoundRobin_View extends Settings_Vtiger_Index_View {

	public function preProcessSettings(Vtiger_Request $request, $display = true) {
		try {
			Leads_RoundRobinService::ensureSchema();
		} catch (Exception $e) {
			// ignore
		}
		parent::preProcessSettings($request, $display);
	}

	public function process(Vtiger_Request $request) {
		Leads_RoundRobinService::ensureSchema();
		$qualifiedName = $request->getModule(false);
		$snapshot = Leads_RoundRobinService::getPoolSnapshot();

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('RR_SNAPSHOT', $snapshot);
		$viewer->view('LeadRoundRobin.tpl', $qualifiedName);
	}

	function getPageTitle(Vtiger_Request $request) {
		return vtranslate('LBL_NK_LEAD_ROUND_ROBIN', $request->getModule(false));
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/LeadRoundRobin.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		if (method_exists($this, 'appendAssetCacheVer')) {
			self::appendAssetCacheVer($jsScriptInstances, 'src', '20260918_rr1');
		}
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Settings/Vtiger/resources/LeadRoundRobin.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		if (method_exists($this, 'appendAssetCacheVer')) {
			self::appendAssetCacheVer($cssInstances, 'href', '20260918_rr1');
		}
		return array_merge($headerCssInstances, $cssInstances);
	}
}
