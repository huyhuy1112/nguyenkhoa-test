<?php
/*+***********************************************************************************
 * SalesOrder Edit — premium Create workspace (SALES, new record). Stock Inventory Save + line items.
 * TOOLS app keeps custom Tools Orders editor.
 *************************************************************************************/

class SalesOrder_Edit_View extends Inventory_Edit_View {

	protected function isToolsOrdersContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	protected function isMkModernSalesOrderCreate(Vtiger_Request $request) {
		if ($this->isToolsOrdersContext($request)) {
			return false;
		}
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'SalesOrder');
		$viewer->assign('MK_MODERN_SALES_ORDER_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $this->isDuplicateRequest($request));
		require_once 'modules/Inventory/helpers/ProductCatalog.php';
		Inventory_ProductCatalog_Helper::assignToViewer($viewer);
	}

	protected function redirectInventoryToSales(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app === 'INVENTORY' && empty($request->get('record'))) {
			header('Location: index.php?module=SalesOrder&view=Edit&app=SALES');
			exit;
		}
	}

	protected function getToolsOrderFieldModels($moduleModel) {
		$fieldNames = array(
			'subject',
			'team_group',
			'purpose',
			'internal_cost',
			'needed_time',
			'internal_order_status',
			'approved_by',
			'approval_note',
			'created_user_id',
		);
		$result = array();
		foreach ($fieldNames as $fieldName) {
			$fieldModel = $moduleModel->getField($fieldName);
			if ($fieldModel) {
				$result[$fieldName] = $fieldModel;
			}
		}
		return $result;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isToolsOrdersContext($request)) {
			parent::preProcess($request, false);
			$viewer = $this->getViewer($request);
			$viewer->assign('SELECTED_MENU_CATEGORY', 'TOOLS');
			$viewer->assign('VIEW', 'Edit');
			$viewer->assign('MENU_SELECTED_MODULENAME', 'SalesOrder');
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		if ($this->isMkModernSalesOrderCreate($request)) {
			$this->redirectInventoryToSales($request);
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
		if ($this->isToolsOrdersContext($request)) {
			return 'ToolsOrdersEditPreProcess.tpl';
		}
		if ($this->isMkModernSalesOrderCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isToolsOrdersContext($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('ToolsOrdersEditPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		if ($this->isMkModernSalesOrderCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isToolsOrdersContext($request)) {
			$viewer = $this->getViewer($request);
			$moduleName = $request->getModule();
			$recordId = $request->get('record');
			if (!empty($recordId)) {
				$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
				$viewer->assign('MODE', 'edit');
				$viewer->assign('RECORD_ID', $recordId);
			} else {
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
				$viewer->assign('MODE', '');
			}

			$moduleModel = $recordModel->getModule();
			$fieldList = $moduleModel->getFields();
			$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);
			foreach ($requestFieldList as $fieldName => $fieldValue) {
				$fieldModel = $fieldList[$fieldName];
				if ($fieldModel->isEditable()) {
					$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
				}
			}

			$viewer->assign('MODULE', $moduleName);
			$viewer->assign('RECORD', $recordModel);
			$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
			$viewer->assign('CURRENTDATE', date('Y-n-j'));
			$viewer->assign('SELECTED_MENU_CATEGORY', $request->get('app'));
			$viewer->assign('TOOLS_ORDERS_MODE', true);
			$fieldsMap = $this->getToolsOrderFieldModels($moduleModel);
			foreach ($fieldsMap as $fieldName => $fieldModel) {
				$fieldModel->set('fieldvalue', $recordModel->get($fieldName));
			}
			$viewer->assign('FIELDS_MAP', $fieldsMap);
			$viewer->assign('IS_RELATION_OPERATION', $request->get('relationOperation'));
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
			$viewer->assign('TOOLS_VALIDATION_ERROR', $request->get('validation_error'));

			if ($request->get('returnview')) {
				$request->setViewerReturnValues($viewer);
			}

			$viewer->view('ToolsOrdersEditView.tpl', $moduleName);
			return;
		}

		if ($this->isMkModernSalesOrderCreate($request)) {
			$this->redirectIfQuoteAlreadyHasSalesOrder($request);
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	protected function redirectIfQuoteAlreadyHasSalesOrder(Vtiger_Request $request) {
		if (!empty($request->get('record'))) {
			return;
		}
		$quoteId = (int) $request->get('quote_id');
		if ($quoteId <= 0) {
			return;
		}
		$quoteModel = Vtiger_Record_Model::getInstanceById($quoteId, 'Quotes');
		if (!$quoteModel instanceof Quotes_Record_Model || !$quoteModel->hasLinkedSalesOrder()) {
			return;
		}
		$redirectUrl = 'index.php?module=SalesOrder&view=List&app=SALES';
		header('Location: ' . $redirectUrl);
		exit;
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isToolsOrdersContext($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/SalesOrder/resources/SalesOrderToolsEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		if (!$this->isMkModernSalesOrderCreate($request)) {
			return $headerCssInstances;
		}
		// Odoo + SalesOrderMkEdit CSS loaded once in EditViewPreProcess.tpl
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->isToolsOrdersContext($request)) {
			$jsFileNames = array(
				'~layouts/v7/modules/SalesOrder/resources/SalesOrderToolsEdit.js',
			);
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			return array_merge($headerScriptInstances, $jsScriptInstances);
		}
		if (!$this->isMkModernSalesOrderCreate($request)) {
			return $headerScriptInstances;
		}
		// SalesOrderMkEdit + MkInventoryOdooEdit loaded in EditViewPreProcess.tpl
		return $headerScriptInstances;
	}
}
