<?php
/*+***********************************************************************************
 * Custom record model: ProductsServices
 *
 * Purpose: Make Inventory line-item popup selection work with the unified
 * "Products & Services" selector while keeping existing inventory calculations.
 *************************************************************************************/

class ProductsServices_Record_Model extends Products_Record_Model {

	/**
	 * Lazily loaded underlying Products or Services record (same crmid when unified row mirrors inventory).
	 * null = not resolved yet, false = resolution failed / skip further attempts.
	 *
	 * @var Vtiger_Record_Model|false|null
	 */
	protected $underlyingInventoryRecord = null;

	/**
	 * Map ProductsServices.item_type => underlying inventory module.
	 *
	 * Uses raw column data only — never $this->get('item_type'), which would re-enter get() and
	 * previously combined with getInstanceById-on-every-get caused pathological load / memory use.
	 *
	 * Note: Inventory calculations rely on core Products/Services tables.
	 */
	protected function getUnderlyingInventoryModuleName() {
		$data = $this->getData();
		if (!is_array($data)) {
			$data = array();
		}
		$itemType = isset($data['item_type']) ? $data['item_type'] : '';
		if (empty($itemType)) {
			return 'Products';
		}
		$itemTypeLower = strtolower(trim($itemType));
		if ($itemTypeLower === 'product' || $itemTypeLower === 'products') {
			return 'Products';
		}
		if ($itemTypeLower === 'service' || $itemTypeLower === 'services') {
			return 'Services';
		}
		return 'Products';
	}

	/**
	 * Single cached load of the underlying inventory record for delegation.
	 *
	 * @return Products_Record_Model|Services_Record_Model|null
	 */
	protected function getUnderlyingInventoryRecord() {
		if ($this->underlyingInventoryRecord !== null) {
			return $this->underlyingInventoryRecord === false ? null : $this->underlyingInventoryRecord;
		}
		$id = $this->getId();
		if (empty($id)) {
			$this->underlyingInventoryRecord = false;
			return null;
		}
		$moduleName = $this->getUnderlyingInventoryModuleName();
		$record = null;
		try {
			$record = Vtiger_Record_Model::getInstanceById($id, $moduleName);
		} catch (Exception $e) {
			$record = null;
		}
		$this->underlyingInventoryRecord = $record ? $record : false;
		return $record;
	}

	/**
	 * Override module name used by InventoryUtils (prices/taxes/base currency).
	 */
	public function getModuleName() {
		return $this->getUnderlyingInventoryModuleName();
	}

	/**
	 * Detail navigation must stay on ProductsServices module.
	 *
	 * Inventory/tax logic needs getModuleName() to resolve to Products/Services, but
	 * UI navigation for ProductsServices records must not route to Products/Services,
	 * otherwise users can hit Permission denied on the wrong module.
	 */
	public function getDetailViewUrl() {
		return 'index.php?module=ProductsServices&view=Detail&record=' . $this->getId();
	}

	public function getEditViewUrl() {
		return 'index.php?module=ProductsServices&view=Edit&record=' . $this->getId();
	}

	/**
	 * Provide Inventory_GetTaxes_Action compatible fields by delegating to
	 * the underlying Products/Services record.
	 *
	 * Important: only touch keys that need delegation. The previous implementation called
	 * getInstanceById on every get() (label, priceDetails, name fields, etc.), which could
	 * exhaust memory and stack under GetTaxes / getName / getPriceDetails.
	 */
	public function get($key) {
		if ($key === 'item_type') {
			return parent::get($key);
		}

		$delegatedKeys = array('unit_price', 'purchase_cost', 'qtyinstock', 'description');
		if (!in_array($key, $delegatedKeys, true)) {
			return parent::get($key);
		}

		$underlyingRecord = $this->getUnderlyingInventoryRecord();
		if ($underlyingRecord) {
			$value = $underlyingRecord->get($key);
			if (!empty($value) || $value === '0' || $value === 0) {
				return $value;
			}
		}

		$data = $this->getData();
		if (!is_array($data)) {
			$data = array();
		}

		if ($key === 'unit_price') {
			foreach (array('price', 'retail_price', 'wholesale_price') as $field) {
				if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
					return $data[$field];
				}
			}
			return parent::get($key);
		}
		if ($key === 'purchase_cost') {
			$pc = parent::get('purchase_cost');
			if ($pc !== null && $pc !== '') {
				return $pc;
			}
			if (isset($data['wholesale_price']) && $data['wholesale_price'] !== '' && $data['wholesale_price'] !== null) {
				return $data['wholesale_price'];
			}
			return parent::get($key);
		}
		if ($key === 'qtyinstock') {
			$q = parent::get('qtyinstock');
			if ($q !== null && $q !== '') {
				return $q;
			}
			if (isset($data['stock']) && $data['stock'] !== '' && $data['stock'] !== null) {
				return $data['stock'];
			}
			return parent::get($key);
		}
		if ($key === 'description') {
			return parent::get($key);
		}

		return parent::get($key);
	}
}
