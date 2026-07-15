<?php
/*+***********************************************************************************
 * ProductsServices ListView — always show product name (productsservicesname).
 *************************************************************************************/

class ProductsServices_ListView_Model extends Vtiger_ListView_Model {

	const NAME_FIELD = 'productsservicesname';

	protected function isModernProductsServicesListRequest() {
		$app = '';
		if (!empty($_REQUEST['app'])) {
			$app = strtoupper((string) $_REQUEST['app']);
		}
		return in_array($app, array('SALES', 'INVENTORY'), true) || $app === '';
	}

	/**
	 * Keep productsservicesname first in the QueryGenerator field list.
	 */
	public function forceProductNameColumn() {
		$queryGenerator = $this->get('query_generator');
		if (!$queryGenerator) {
			return;
		}
		$nameKey = self::NAME_FIELD;
		$fields = $queryGenerator->getFields();
		if (!is_array($fields)) {
			$fields = array();
		}
		$fields = array_values(array_filter($fields, function ($f) use ($nameKey) {
			return $f !== $nameKey && $f !== '';
		}));
		array_unshift($fields, $nameKey);
		if (!in_array('id', $fields, true)) {
			$fields[] = 'id';
		}
		if (!in_array('starred', $fields, true)) {
			$fields[] = 'starred';
		}
		$queryGenerator->setFields(array_values(array_unique($fields)));
	}

	/** @deprecated use forceProductNameColumn */
	protected function ensureNameFieldInQuery() {
		$this->forceProductNameColumn();
	}

	protected function resolveNameFieldModel() {
		$module = $this->getModule();
		$nameKey = self::NAME_FIELD;
		$nameField = Vtiger_Field_Model::getInstance($nameKey, $module);
		if ($nameField) {
			$nameField->set('listViewRawFieldName', $nameField->get('column') ? $nameField->get('column') : $nameKey);
			return $nameField;
		}

		$queryGenerator = $this->get('query_generator');
		$moduleFields = $queryGenerator ? $queryGenerator->getModuleFields() : null;
		if (is_array($moduleFields) && isset($moduleFields[$nameKey])) {
			$nameField = Vtiger_Field_Model::getInstance($nameKey, $module);
			if ($nameField) {
				$nameField->set('listViewRawFieldName', $nameField->get('column') ? $nameField->get('column') : $nameKey);
				return $nameField;
			}
		}
		return null;
	}

	/**
	 * Ensure Tên hàng hoá is always the first data column (even if Custom View / session omitted it).
	 */
	public function getListViewHeaders() {
		$headers = parent::getListViewHeaders();
		if (!$this->isModernProductsServicesListRequest()) {
			return $headers;
		}

		$this->forceProductNameColumn();

		$nameKey = self::NAME_FIELD;
		$nameField = null;
		if (isset($headers[$nameKey])) {
			$nameField = $headers[$nameKey];
			unset($headers[$nameKey]);
		} else {
			$nameField = $this->resolveNameFieldModel();
		}

		if ($nameField) {
			$headers = array($nameKey => $nameField) + $headers;
		}

		return $headers;
	}

	public function getListViewEntries($pagingModel) {
		if ($this->isModernProductsServicesListRequest()) {
			$this->forceProductNameColumn();
		}
		return parent::getListViewEntries($pagingModel);
	}
}
