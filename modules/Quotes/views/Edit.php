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
			if ($priceChannel !== 'tuibao' && $accountId > 0) {
				try {
					$db = PearDatabase::getInstance();
					$accRs = $db->pquery(
						'SELECT account_no FROM vtiger_account WHERE accountid = ? LIMIT 1',
						array($accountId)
					);
					if ($accRs && $db->num_rows($accRs)) {
						$accountNo = strtoupper(trim(decode_html((string) $db->query_result($accRs, 0, 'account_no'))));
						if (strpos($accountNo, 'TUIBAO') === 0) {
							$priceChannel = 'tuibao';
						}
					}
				} catch (Exception $e) {
					// Keep retail when the account code cannot be read.
				}
			}
		}
		if ($priceChannel !== 'tuibao') {
			$contactId = (int) $request->get('contact_id');
			if ($contactId <= 0 && $recordId > 0) {
				try {
					if (!isset($rec) || !$rec) {
						$rec = Vtiger_Record_Model::getInstanceById($recordId, 'Quotes');
					}
					$contactId = (int) $rec->get('contact_id');
				} catch (Exception $e) {
					$contactId = 0;
				}
			}
			if ($contactId > 0) {
				try {
					$db = PearDatabase::getInstance();
					$cRs = $db->pquery(
						'SELECT contact_no FROM vtiger_contactdetails WHERE contactid = ? LIMIT 1',
						array($contactId)
					);
					if ($cRs && $db->num_rows($cRs)) {
						$contactNo = trim(decode_html((string) $db->query_result($cRs, 0, 'contact_no')));
						if (preg_match('/^MIUTEA_/i', $contactNo)) {
							$priceChannel = 'miutea';
						}
					}
				} catch (Exception $e) {
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
		// Modern Quotes edit already loads its inventory CSS in EditViewPreProcess.tpl.
		// Avoid loading the same asset twice to prevent flash from legacy/default styles.
		return parent::getHeaderCss($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		// Modern Quotes edit already loads its inventory JS in EditViewPreProcess.tpl.
		// Avoid duplicate init / re-render after first paint.
		return parent::getHeaderScripts($request);
	}
}
