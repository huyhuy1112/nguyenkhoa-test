<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Quotes_List_View extends Inventory_List_View {

	protected function isSalesListContext(Vtiger_Request $request) {
		$app = strtoupper(trim((string) $request->get('app')));
		if ($app === 'SALES') {
			return true;
		}
		// AJAX/PJAX reload sometimes omits app; Quotes list is SALES POS in this product.
		if ($app === '' && $request->getModule() === 'Quotes') {
			return true;
		}
		return false;
	}

	protected function isSalesListFieldAvailable(Vtiger_Module_Model $moduleModel, $fieldName) {
		if (!$moduleModel || $fieldName === '') {
			return false;
		}
		$fields = $moduleModel->getFields();
		if (is_array($fields) && isset($fields[$fieldName])) {
			return true;
		}
		$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
		return ($fieldModel && $fieldModel->isViewable());
	}

	protected function clearQuotesPosListHeadersSession(Vtiger_Request $request) {
		$customView = new CustomView();
		$cvId = $request->get('viewname');
		if (empty($cvId)) {
			$cvId = $customView->getViewId('Quotes');
		}
		if (!empty($cvId)) {
			Vtiger_ListView_Model::deleteParamsSession('Quotes_' . $cvId, array('list_headers'));
		}
	}

	/**
	 * SALES Quotes list: keep Tổng cộng as the last column (after Phụ trách).
	 */
	protected function applyQuotesListPosDefaults(Vtiger_Request $request) {
		if (!$this->isSalesListContext($request)) {
			return;
		}

		require_once 'include/utils/MkEntityNumbering.php';
		MkEntityNumbering::ensureModuleSequence('Quotes');

		$moduleModel = Vtiger_Module_Model::getInstance('Quotes');
		if (!$moduleModel) {
			return;
		}

		// Cột list: Số | KH | Sale | Tổng cộng (có thuế) | Trạng thái — không dùng Hoạt động (potential_id)
		// `total` = grand total đã lưu (gồm thuế); ListView_Model format lại hiển thị.
		$preferredHeaders = array(
			'quote_no',
			'account_id',
			'assigned_user_id',
			'total',
			'hdnGrandTotal',
			'quotestage',
		);

		$resolvedHeaders = array();
		$seen = array();
		$hasTotal = false;
		foreach ($preferredHeaders as $fieldName) {
			if (isset($seen[$fieldName])) {
				continue;
			}
			// Chỉ 1 cột tổng tiền (ưu tiên hdnGrandTotal = grand total có thuế)
			if ($fieldName === 'total' || $fieldName === 'hdnGrandTotal') {
				if ($hasTotal) {
					continue;
				}
			}
			if ($this->isSalesListFieldAvailable($moduleModel, $fieldName)) {
				$resolvedHeaders[] = $fieldName;
				$seen[$fieldName] = true;
				if ($fieldName === 'total' || $fieldName === 'hdnGrandTotal') {
					$hasTotal = true;
				}
			}
		}

		if (empty($resolvedHeaders)) {
			return;
		}

		$this->clearQuotesPosListHeadersSession($request);
		$request->set('list_headers', $resolvedHeaders);
		$_REQUEST['list_headers'] = $resolvedHeaders;
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		// Normalize app so POS + franchise filter always apply on AJAX reloads.
		if (strtoupper(trim((string) $request->get('app'))) === '') {
			$request->set('app', 'SALES');
			$_REQUEST['app'] = 'SALES';
		}

		// After SO→Quote duplicate redirect: show newest retail quote on page 1.
		if ($request->get('mk_highlight') !== '' && $request->get('mk_highlight') !== null) {
			$request->set('mk_quote_scope', 'all');
			$request->set('orderby', 'quote_no');
			$request->set('sortorder', 'DESC');
			$request->set('page', '1');
			$_REQUEST['mk_quote_scope'] = 'all';
			$_REQUEST['orderby'] = 'quote_no';
			$_REQUEST['sortorder'] = 'DESC';
			$_REQUEST['page'] = '1';
		}

		$scope = $this->resolveQuoteListScope($request);
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}

		// Scope must land on the ListView model BEFORE parent runs getQuery/count.
		if (!$this->listViewModel) {
			$moduleName = $request->getModule();
			$cvId = $this->viewName;
			if (empty($cvId)) {
				$customView = new CustomView();
				$cvId = $customView->getViewId($moduleName);
				$this->viewName = $cvId;
			}
			$listHeaders = $request->get('list_headers', array());
			$this->listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId, $listHeaders);
		}
		$this->listViewModel->set('mk_quote_scope', $scope);
		$viewer->assign('MK_QUOTE_SCOPE', $scope);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');

		parent::initializeListViewContents($request, $viewer);

		// Parent may rebuild listViewModel in edge paths — re-assert scope for subsequent uses.
		$scope = $this->resolveQuoteListScope($request);
		$viewer->assign('MK_QUOTE_SCOPE', $scope);
		if ($this->listViewModel) {
			$this->listViewModel->set('mk_quote_scope', $scope);
		}
	}

	/**
	 * @return string all|franchise|retail
	 */
	protected function resolveQuoteListScope(Vtiger_Request $request) {
		$raw = strtolower(trim((string) $request->get('mk_quote_scope')));
		if ($raw === '' && isset($_REQUEST['mk_quote_scope'])) {
			$raw = strtolower(trim((string) $_REQUEST['mk_quote_scope']));
		}
		if ($raw === '' && isset($_GET['mk_quote_scope'])) {
			$raw = strtolower(trim((string) $_GET['mk_quote_scope']));
		}
		if ($raw === '' && isset($_POST['mk_quote_scope'])) {
			$raw = strtolower(trim((string) $_POST['mk_quote_scope']));
		}
		if ($raw === 'franchise' || $raw === 'nhuong_quyen' || $raw === 'nq') {
			return 'franchise';
		}
		if ($raw === 'retail' || $raw === 'ban_le') {
			return 'retail';
		}
		return 'all';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		// AJAX skips preProcess — ensure defaults + scope path still run via initialize.
		if (strtoupper(trim((string) $request->get('app'))) === '') {
			$request->set('app', 'SALES');
			$_REQUEST['app'] = 'SALES';
		}
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}
		parent::process($request);
	}
}
