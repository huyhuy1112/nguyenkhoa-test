<?php

require_once 'modules/ProductsServices/models/FormLayout.php';

class ProductsServices_EditRecordStructure_Model extends Vtiger_EditRecordStructure_Model {

	public function getStructure() {
		$values = parent::getStructure();
		$this->structuredValues = ProductsServices_FormLayout_Helper::apply($values);
		return $this->structuredValues;
	}
}
