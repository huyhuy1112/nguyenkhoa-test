<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Quotes_ListView_Model extends Inventory_ListView_Model {

	public function getListViewEntries($pagingModel) {
		$listViewRecordModels = parent::getListViewEntries($pagingModel);
		if (empty($listViewRecordModels)) {
			return $listViewRecordModels;
		}

		foreach ($listViewRecordModels as $recordId => $recordModel) {
			$corrected = $this->resolveDisplayGrandTotal($recordModel);
			if ($corrected === null) {
				continue;
			}
			$formatted = CurrencyField::convertToUserFormat($corrected, null, true);
			$recordModel->set('hdnGrandTotal', $formatted);
			$recordModel->set('total', $formatted);
			$listViewRecordModels[$recordId] = $recordModel;
		}

		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		foreach ($listViewRecordModels as $recordId => $recordModel) {
			$listViewRecordModels[$recordId] = Vtiger_MkSalesCustomerName_Helper::applyListCustomerColumn($recordModel);
		}

		return $listViewRecordModels;
	}

	/**
	 * When quote header total is out of scale with line items, scale by line/header subtotal.
	 */
	protected function resolveDisplayGrandTotal(Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return null;
		}

		$db = PearDatabase::getInstance();
		$headerResult = $db->pquery(
			'SELECT subtotal, total FROM vtiger_quotes WHERE quoteid = ?',
			array($recordId)
		);
		if (!$headerResult || $db->num_rows($headerResult) === 0) {
			return null;
		}
		$headerSubTotal = (float) $db->query_result($headerResult, 0, 'subtotal');
		$headerTotal = (float) $db->query_result($headerResult, 0, 'total');

		$lineResult = $db->pquery(
			'SELECT COALESCE(SUM(quantity * listprice), 0) AS line_subtotal FROM vtiger_inventoryproductrel WHERE id = ?',
			array($recordId)
		);
		$lineSubTotal = (float) $db->query_result($lineResult, 0, 'line_subtotal');
		if ($lineSubTotal <= 0) {
			return null;
		}
		if ($headerSubTotal <= 0) {
			return $lineSubTotal;
		}
		if ($lineSubTotal > ($headerSubTotal * 50)) {
			// Prefer header when lines look absurd (corrupted duplicate), never amplify.
			if ($headerTotal > 0) {
				return $headerTotal;
			}
			return $headerSubTotal;
		}
		if ($headerTotal > 0 && $headerTotal < ($lineSubTotal * 0.5)) {
			return $lineSubTotal;
		}

		return null;
	}
}
