<?php
/**
 * ProductsServices Save — after create/edit, return to the Hàng hoá list (not Detail).
 */
class ProductsServices_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		if (!$request->get('relationOperation') && !$request->get('returntab_label')) {
			$request->set('returnToList', '1');
			if (!$request->get('returnmodule')) {
				$request->set('returnmodule', 'ProductsServices');
			}
			if (!$request->get('returnview')) {
				$request->set('returnview', 'List');
			}
			$appName = (string) $request->get('appName');
			if ($appName === '') {
				$app = strtoupper((string) $request->get('app'));
				if ($app !== 'SALES' && $app !== 'INVENTORY') {
					$app = 'INVENTORY';
				}
				$request->set('appName', '&app=' . $app);
			}
		}
		parent::process($request);
	}
}
