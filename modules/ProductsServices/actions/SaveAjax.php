<?php
/**
 * Inline save — store the unit label the staff picked (Cái), not the old code (pcs).
 */
class ProductsServices_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function getRecordModelFromRequest(Vtiger_Request $request) {
		require_once 'modules/ProductsServices/helpers/UnitOptions.php';
		ProductsServices_UnitOptions_Helper::ensurePicklist();
		if ($request->has('unit')) {
			$request->set('unit', ProductsServices_UnitOptions_Helper::normalize($request->get('unit')));
		}
		if ($request->get('field') === 'unit') {
			$request->set('value', ProductsServices_UnitOptions_Helper::normalize($request->get('value')));
		}
		return parent::getRecordModelFromRequest($request);
	}
}
