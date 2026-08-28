<?php
/*+***********************************************************************************
 * ProductsServices ListView — fixed catalog columns.
 *************************************************************************************/

class ProductsServices_ListView_Model extends Vtiger_ListView_Model {

	const NAME_FIELD = 'productsservicesname';

	/** BA list columns (server headers; client catalog may enrich stock / SO / dates). */
	const CANONICAL_HEADERS = array(
		'sku',
		'productsservicesname',
		'price_lt_1m',
		'price_tuibao',
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
		$module = $this->getModule();
		$fields = array();
		foreach (self::CANONICAL_HEADERS as $fieldName) {
			if ($module && Vtiger_Field_Model::getInstance($fieldName, $module)) {
				$fields[] = $fieldName;
			}
		}
		if (empty($fields)) {
			$fields = array(self::NAME_FIELD);
		}
		if ($module && Vtiger_Field_Model::getInstance('needs_qc', $module)) {
			if (!in_array('needs_qc', $fields, true)) {
				$fields[] = 'needs_qc';
			}
		}
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

	public function getListViewCount() {
		$this->forceProductNameColumn();
		return parent::getListViewCount();
	}
}
