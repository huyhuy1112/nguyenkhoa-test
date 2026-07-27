<?php
/*+***********************************************************************************
 * Quotes Edit — premium Create workspace (SALES, new record). Stock Inventory Save + line items.
 *************************************************************************************/

class Quotes_Edit_View extends Inventory_Edit_View {

	protected function isMkModernQuoteCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		return true;
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$user = Users_Record_Model::getCurrentUserModel();
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		require_once 'include/utils/MkEntityNumbering.php';
		MkEntityNumbering::ensureModuleSequence('Quotes');
		$baContext = Quotes_QuoteBaService_Helper::getBaContext();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Quotes');
		$viewer->assign('MK_MODERN_QUOTE_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $this->isDuplicateRequest($request));
		$viewer->assign('MK_QUOTE_OWNER_NAME', trim($user->getName()));
		$viewer->assign('MK_QUOTE_BA_CONFIG_JSON', Zend_Json::encode($baContext));
		$viewer->assign('MK_QUOTE_NEXT_NO', MkEntityNumbering::previewNextNumber('Quotes'));
		require_once 'modules/Inventory/helpers/ProductCatalog.php';
		Inventory_ProductCatalog_Helper::assignToViewer($viewer);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernQuoteCreate($request)) {
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
		if ($this->isMkModernQuoteCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernQuoteCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernQuoteCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css',
		);
		return array_merge($headerCssInstances, $this->checkAndConvertCssStyles($cssFileNames));
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.js',
		);
		return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts($jsFileNames));
	}
}
