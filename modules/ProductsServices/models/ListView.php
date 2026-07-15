<?php
/*+***********************************************************************************
 * ProductsServices ListView — always show product name (productsservicesname).
 *************************************************************************************/

class ProductsServices_ListView_Model extends Vtiger_ListView_Model {

	protected function isModernProductsServicesListRequest() {
		$app = '';
		if (!empty($_REQUEST['app'])) {
			$app = strtoupper((string) $_REQUEST['app']);
		}
		return in_array($app, array('SALES', 'INVENTORY'), true) || $app === '';
	}

	/**
	 * Keep productsservicesname in the QueryGenerator field list.
	 */
	protected function ensureNameFieldInQuery() {
		$queryGenerator = $this->get('query_generator');
		if (!$queryGenerator) {
			return;
		}
		$nameKey = 'productsservicesname';
		$fields = $queryGenerator->getFields();
		if (!is_array($fields)) {
			$fields = array();
		}
		if (!in_array($nameKey, $fields, true)) {
			array_unshift($fields, $nameKey);
		}
		if (!in_array('id', $fields, true)) {
			$fields[] = 'id';
		}
		if (!in_array('starred', $fields, true)) {
			$fields[] = 'starred';
		}
		$queryGenerator->setFields(array_values(array_unique($fields)));
	}

	/**
	 * Ensure Tên hàng hoá is always the first data column (even if Custom View omitted it).
	 */
	public function getListViewHeaders() {
		$headers = parent::getListViewHeaders();
		if (!$this->isModernProductsServicesListRequest()) {
			return $headers;
		}

		$module = $this->getModule();
		$nameKey = 'productsservicesname';
		$nameField = null;

		if (isset($headers[$nameKey])) {
			$nameField = $headers[$nameKey];
			unset($headers[$nameKey]);
		} else {
			$nameField = Vtiger_Field_Model::getInstance($nameKey, $module);
			if ($nameField && in_array((int) $nameField->getPresence(), array(0, 2), true)) {
				$nameField->set('listViewRawFieldName', $nameField->get('column'));
			} else {
				$nameField = null;
			}
		}

		if ($nameField) {
			$headers = array($nameKey => $nameField) + $headers;
			$this->ensureNameFieldInQuery();
		}

		return $headers;
	}

	public function getListViewEntries($pagingModel) {
		if ($this->isModernProductsServicesListRequest()) {
			$this->ensureNameFieldInQuery();
		}
		return parent::getListViewEntries($pagingModel);
	}
}
