<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Inventory/models/RelationListView.php';

/**
 * Quotes RelationListView model.
 * Customizes headers for Quotes → ProductsServices related list (match Order detail).
 */
class Quotes_RelationListView_Model extends Inventory_RelationListView_Model {

	/**
	 * Core Vtiger_RelationListView_Model::getInstance() instantiates this class with `new`
	 * (does not call our static getInstance). When tab_label differs from DB label, relation is false.
	 */
	public function getRelationModel() {
		$relationModel = parent::getRelationModel();
		if ($relationModel) {
			return $relationModel;
		}

		$relatedModuleModel = $this->getRelatedModuleModel();
		if (!$relatedModuleModel || $relatedModuleModel->getName() !== 'ProductsServices') {
			return $relationModel;
		}

		$parentModuleModel = $this->getParentRecordModel()->getModule();
		$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModuleModel, false);
		if (!$relationModel) {
			self::ensureProductsServicesRelationIfMissing();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModuleModel, false);
		}
		if ($relationModel) {
			$this->setRelationModel($relationModel);
		}
		return $relationModel;
	}

	/**
	 * Idempotent: create/update Quotes → ProductsServices related list in vtiger_relatedlists.
	 */
	protected static function ensureProductsServicesRelationIfMissing() {
		static $attempted = false;
		if ($attempted) {
			return;
		}
		$attempted = true;

		require_once 'vtlib/Vtiger/Module.php';
		$quotes = Vtiger_Module::getInstance('Quotes');
		$ps = Vtiger_Module::getInstance('ProductsServices');
		if (!$quotes || !$ps) {
			return;
		}

		global $adb;
		$tabId = $quotes->getId();
		$relatedTabId = $ps->getId();
		$targetLabel = 'Product And Service';
		$targetName = 'get_related_list';
		$targetActions = 'ADD,SELECT';

		$res = $adb->pquery(
			'SELECT relation_id, name, label, actions FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
			array($tabId, $relatedTabId)
		);

		if ($res && $adb->num_rows($res) > 0) {
			$relationId = (int) $adb->query_result($res, 0, 'relation_id');
			$currentName = (string) $adb->query_result($res, 0, 'name');
			$currentLabel = (string) $adb->query_result($res, 0, 'label');
			$currentActions = (string) $adb->query_result($res, 0, 'actions');
			$actionsUpper = strtoupper(str_replace(' ', '', $currentActions));
			$needUpdate = (trim($currentName) !== $targetName)
				|| (strpos($actionsUpper, 'SELECT') === false)
				|| (trim($currentLabel) !== $targetLabel);
			if ($needUpdate) {
				$adb->pquery(
					'UPDATE vtiger_relatedlists SET name = ?, actions = ?, label = ? WHERE relation_id = ?',
					array($targetName, $targetActions, $targetLabel, $relationId)
				);
			}
			return;
		}

		try {
			$quotes->setRelatedList($ps, $targetLabel, array('ADD', 'SELECT'), $targetName);
		} catch (Exception $e) {
			// Leave relation unresolved; RelatedList view handles missing relation gracefully.
		}
	}

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
			$headers = array();
			foreach ($fieldModels as $fieldModel) {
				$headers[$fieldModel->getName()] = $fieldModel;
			}
			return $headers;
		}

		return parent::getHeaders();
	}

	public function getEntries($pagingModel) {
		if (!$this->getRelationModel()) {
			return array();
		}
		return parent::getEntries($pagingModel);
	}

	public function getLinks() {
		if (!$this->getRelationModel()) {
			return array('LISTVIEWBASIC' => array());
		}
		return parent::getLinks();
	}

	public function getRelatedEntriesCount() {
		if (!$this->getRelationModel()) {
			return 0;
		}
		return parent::getRelatedEntriesCount();
	}
}
