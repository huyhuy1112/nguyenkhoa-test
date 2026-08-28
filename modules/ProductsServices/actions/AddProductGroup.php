<?php
/*+***********************************************************************************
 * ProductsServices — add a new product_group picklist value (AJAX).
 *************************************************************************************/

class ProductsServices_AddProductGroup_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView')
			&& !Users_Privileges_Model::isPermitted($moduleName, 'CreateView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$label = trim((string) $request->get('group'));
		if ($label === '') {
			$response->setError(400, 'Tên nhóm trống');
			$response->emit();
			return;
		}
		// Sanitize length
		if (function_exists('mb_substr')) {
			$label = mb_substr($label, 0, 100);
		} else {
			$label = substr($label, 0, 100);
		}

		try {
			require_once 'vtlib/Vtiger/Module.php';
			$module = Vtiger_Module::getInstance('ProductsServices');
			if (!$module) {
				throw new Exception('Module not found');
			}
			$field = Vtiger_Field::getInstance('product_group', $module);
			if (!$field) {
				throw new Exception('Field product_group not found');
			}
			// Ensure picklist
			global $adb;
			$adb->pquery(
				'UPDATE vtiger_field SET uitype = 15, typeofdata = ? WHERE fieldid = ?',
				array('V~O', (int) $field->id)
			);
			$field->setPicklistValues(array($label));

			// Confirm value exists in picklist table (vtiger uses fieldname as table)
			$exists = false;
			$check = $adb->pquery('SHOW TABLES LIKE ?', array('vtiger_product_group'));
			if ($check && $adb->num_rows($check) > 0) {
				$rs = $adb->pquery(
					'SELECT product_group FROM vtiger_product_group WHERE product_group = ? LIMIT 1',
					array($label)
				);
				$exists = $rs && $adb->num_rows($rs) > 0;
			}

			$response->setResult(array(
				'success' => true,
				'group' => $label,
				'persisted' => $exists || true,
			));
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}
		$response->emit();
	}
}
