<?php
require_once 'modules/Vtiger/models/RelationListView.php';

/**
 * Potentials RelationListView model.
 * Customizes headers only for Potentials → ProductsServices related list.
 */
class Potentials_RelationListView_Model extends Vtiger_RelationListView_Model {
	public function getHeaders() {
		$relatedModuleModel = $this->getRelatedModuleModel();
		$relatedModuleName = $relatedModuleModel ? $relatedModuleModel->getName() : '';
		if ($relatedModuleName !== 'ProductsServices') {
			return parent::getHeaders();
		}

		$psModule = $this->getRelatedModuleModel();
		if (!$psModule) {
			return parent::getHeaders();
		}

		$fieldModels = array();

		$nameField = $psModule->getField('productsservicesname');
		if ($nameField) {
			$nameField->set('label', 'Name');
			$fieldModels[] = $nameField;
		}

		$typeField = $psModule->getField('item_type');
		if (!$typeField) {
			$typeField = $psModule->getField('type');
		}
		if ($typeField) {
			$typeField->set('label', 'Type');
			$fieldModels[] = $typeField;
		}

		$priceField = $psModule->getField('price');
		if ($priceField) {
			$priceField->set('label', 'Price');
			$fieldModels[] = $priceField;
		}

		if (!empty($fieldModels)) {
			return $fieldModels;
		}

		return parent::getHeaders();
	}

	public function getEntries($pagingModel) {
		return parent::getEntries($pagingModel);
	}
}

