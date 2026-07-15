<?php
/*+***********************************************************************************
 * ProductsServices ListView — fixed catalog columns.
 *************************************************************************************/

class ProductsServices_ListView_Model extends Vtiger_ListView_Model {

	const NAME_FIELD = 'productsservicesname';

	const CANONICAL_HEADERS = array(
		'productsservicesname',
		'item_type',
		'price',
		'supplier',
		'unit',
	);

	public static function getInstance($moduleName, $viewId = '0', $listHeaders = array()) {
		$listHeaders = self::CANONICAL_HEADERS;
		return parent::getInstance($moduleName, $viewId, $listHeaders);
	}

	public static function ensureNameInHeaderList($headers) {
		return self::CANONICAL_HEADERS;
	}

	public function forceProductNameColumn() {
		$queryGenerator = $this->get('query_generator');
		if (!$queryGenerator) {
			return false;
		}
		$fields = self::CANONICAL_HEADERS;
		if (!in_array('id', $fields, true)) {
			$fields[] = 'id';
		}
		if (!in_array('starred', $fields, true)) {
			$fields[] = 'starred';
		}
		$queryGenerator->setFields(array_values(array_unique($fields)));
		return true;
	}

	protected function resolveNameFieldModel() {
		$module = $this->getModule();
		$nameKey = self::NAME_FIELD;
		$nameField = Vtiger_Field_Model::getInstance($nameKey, $module);
		if ($nameField) {
			$col = $nameField->get('column');
			$nameField->set('listViewRawFieldName', $col ? $col : $nameKey);
			return $nameField;
		}
		if (method_exists($module, 'getField')) {
			$nameField = $module->getField($nameKey);
			if ($nameField) {
				$col = $nameField->get('column');
				$nameField->set('listViewRawFieldName', $col ? $col : $nameKey);
				return $nameField;
			}
		}
		return null;
	}

	public function getListViewHeaders() {
		$this->forceProductNameColumn();
		$headers = parent::getListViewHeaders();
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
		$ordered = array();
		foreach (self::CANONICAL_HEADERS as $key) {
			if (isset($headers[$key])) {
				$ordered[$key] = $headers[$key];
			}
		}
		return $ordered;
	}

	public function getListViewEntries($pagingModel) {
		$this->forceProductNameColumn();
		return parent::getListViewEntries($pagingModel);
	}
}
