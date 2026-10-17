<?php

require_once 'modules/ProductsServices/models/FormLayout.php';

class ProductsServices_DetailRecordStructure_Model extends Vtiger_DetailRecordStructure_Model {

	public function getStructure() {
		require_once 'modules/ProductsServices/helpers/PriceSetup.php';
		ProductsServices_PriceSetup_Helper::ensure();
		ProductsServices_FormLayout_Helper::ensureImageFieldVisible();
		$values = parent::getStructure();
		$this->structuredValues = ProductsServices_FormLayout_Helper::apply($values);
		return $this->structuredValues;
	}
}
