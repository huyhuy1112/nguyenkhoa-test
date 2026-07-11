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
		return strtoupper((string) $request->get('app')) === 'SALES';
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

		$moduleModel = Vtiger_Module_Model::getInstance('Quotes');
		if (!$moduleModel) {
			return;
		}

		$preferredHeaders = array(
			'subject',
			'quotestage',
			'potential_id',
			'account_id',
			'assigned_user_id',
			'total',
			'hdnGrandTotal',
		);

		$resolvedHeaders = array();
		$seen = array();
		foreach ($preferredHeaders as $fieldName) {
			if (isset($seen[$fieldName])) {
				continue;
			}
			if ($this->isSalesListFieldAvailable($moduleModel, $fieldName)) {
				$resolvedHeaders[] = $fieldName;
				$seen[$fieldName] = true;
			}
		}

		if (empty($resolvedHeaders)) {
			return;
		}

		$this->clearQuotesPosListHeadersSession($request);
		$request->set('list_headers', $resolvedHeaders);
		$_REQUEST['list_headers'] = $resolvedHeaders;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}
		parent::process($request);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if ($this->isSalesListContext($request)) {
			$this->applyQuotesListPosDefaults($request);
		}
		parent::initializeListViewContents($request, $viewer);
	}
}
