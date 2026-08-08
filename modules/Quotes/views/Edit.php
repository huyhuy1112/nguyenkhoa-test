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

		// Resolve price channel early (PreProcess injects window.MK_PRICE_CHANNEL).
		$priceChannel = 'retail';
		$scId = (int) $request->get('servicecontract_id');
		if ($scId <= 0) {
			$scId = (int) $request->get('mk_servicecontract_id');
		}
		$recordId = (int) $request->get('record');
		if ($scId <= 0 && $recordId > 0) {
			try {
				require_once 'modules/Quotes/helpers/QuoteBaService.php';
				Quotes_QuoteBaService_Helper::ensureServiceContractLinkColumn();
				$db = PearDatabase::getInstance();
				$rs = $db->pquery(
					'SELECT mk_servicecontract_id FROM vtiger_quotes WHERE quoteid = ?',
					array($recordId)
				);
				if ($rs && $db->num_rows($rs)) {
					$scId = (int) $db->query_result($rs, 0, 'mk_servicecontract_id');
				}
			} catch (Exception $e) {
				$scId = 0;
			}
		}
		if ($scId > 0) {
			$priceChannel = 'tuibao';
			$viewer->assign('MK_SERVICECONTRACT_ID', $scId);
		} else {
			$accountId = 0;
			if ($recordId > 0) {
				try {
					$rec = Vtiger_Record_Model::getInstanceById($recordId, 'Quotes');
					$accountId = (int) $rec->get('account_id');
				} catch (Exception $e) {
					$accountId = 0;
				}
			}
			if ($accountId > 0 && is_file('modules/ProductsServices/models/PricingEngine.php')) {
				require_once 'modules/ProductsServices/models/PricingEngine.php';
				if (ProductsServices_PricingEngine_Model::isTuibaoAccount($accountId)) {
					$priceChannel = 'tuibao';
				}
			}
		}
		$viewer->assign('MK_PRICE_CHANNEL', $priceChannel);
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

		// Carry existing SC link into form when editing (or URL create param).
		$viewer = $this->getViewer($request);
		$recordId = (int) $request->get('record');
		$scId = 0;
		if ($recordId > 0) {
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			Quotes_QuoteBaService_Helper::ensureServiceContractLinkColumn();
			$db = PearDatabase::getInstance();
			$rs = $db->pquery(
				'SELECT mk_servicecontract_id FROM vtiger_quotes WHERE quoteid = ?',
				array($recordId)
			);
			if ($rs && $db->num_rows($rs)) {
				$scId = (int) $db->query_result($rs, 0, 'mk_servicecontract_id');
			}
		}
		if ($scId <= 0) {
			$scId = (int) $request->get('servicecontract_id');
			if ($scId <= 0) {
				$scId = (int) $request->get('mk_servicecontract_id');
			}
		}
		if ($scId > 0) {
			$viewer->assign('MK_SERVICECONTRACT_ID', $scId);
			// Inventory_Edit_View::process reads servicecontract_id for MK_PRICE_CHANNEL.
			$request->set('servicecontract_id', $scId);
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
