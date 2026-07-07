<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

vimport('~~/modules/SalesOrder/SalesOrderPDFController.php');

class SalesOrder_ExportPDF_Action extends Inventory_ExportPDF_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$controllerClassName = 'Vtiger_SalesOrderPDFController';
		$controller = new $controllerClassName($moduleName);
		$controller->loadRecord($recordId);

		$fileName = $moduleName . '_' . getModuleSequenceNumber($moduleName, $recordId);
		$isPreview = $request->get('preview') === '1' || $request->get('mode') === 'inline';
		$controller->Output($fileName . '.pdf', $isPreview ? 'I' : 'D');
	}
}
