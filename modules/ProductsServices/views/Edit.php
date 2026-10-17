<?php
/*+***********************************************************************************
 * ProductsServices Edit — premium Create workspace (SALES, new record).
 *************************************************************************************/

class ProductsServices_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernProductsServicesCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string)$request->get('app'));
		return $app === 'INVENTORY' || $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$user = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$app = strtoupper((string)$request->get('app'));
		$menuCategory = ($app === 'SALES') ? 'SALES' : 'INVENTORY';
		$viewer->assign('SELECTED_MENU_CATEGORY', $menuCategory);
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'ProductsServices');
		$viewer->assign('MK_MODERN_PRODUCTSSERVICES_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		require_once 'modules/ProductsServices/helpers/PriceSetup.php';
		ProductsServices_PriceSetup_Helper::ensure();
		if ($this->isMkModernProductsServicesCreate($request)) {
			parent::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernProductsServicesCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernProductsServicesCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernProductsServicesCreate($request)) {
			$this->assignModernContext($request);
			$this->assignQtyRules($request);
		}
		parent::process($request);
	}

	protected function assignQtyRules(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$payload = array('sell_unit' => '', 'per_thung' => 0, 'rules' => array());
		$recordId = (int) $request->get('record');
		if ($recordId > 0) {
			$db = PearDatabase::getInstance();
			$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('kl_qty_rules'));
			if ($check && $db->num_rows($check) > 0) {
				$rs = $db->pquery(
					'SELECT kl_qty_rules, unit FROM vtiger_productsservices WHERE productsservicesid = ?',
					array($recordId)
				);
				$row = ($rs && $db->num_rows($rs) > 0) ? $db->fetchByAssoc($rs, -1, false) : null;
				if (is_array($row)) {
					$decoded = json_decode((string) $row['kl_qty_rules'], true);
					if (is_array($decoded)) {
						$payload = $decoded;
					}
					if (empty($payload['sell_unit']) && !empty($row['unit'])) {
						$payload['sell_unit'] = (string) $row['unit'];
					}
				}
			}
		}
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
		if (!is_string($json) || $json === '') {
			$json = '{"sell_unit":"","per_thung":0,"rules":[]}';
		}
		$viewer->assign('MK_KL_QTY_RULES_JSON', $json);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernProductsServicesCreate($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if (!$this->isMkModernProductsServicesCreate($request)) {
			return $headerScriptInstances;
		}
		$jsFileNames = array(
			'~layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
