<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class Quotes_ExportExcelForSale_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		@ini_set('display_errors', '0');
		@ini_set('zlib.output_compression', '0');

		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$focus = CRMEntity::getInstance($moduleName);
		$focus->retrieve_entity_info($recordId, $moduleName);
		$focus->apply_field_security();
		$focus->id = $recordId;

		try {
			require_once 'libraries/PHPExcel/PHPExcel.php';

			$objPHPExcel = Quotes_QuoteExcelExport_Helper::buildSaleWorkbook($focus, $moduleName);
			$fileName = Quotes_QuoteExcelExport_Helper::buildSaleFilename($focus, $recordId, $moduleName);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="' . $fileName . '"');
			header('Cache-Control: max-age=0');
			header('Pragma: public');

			while (ob_get_level() > 0) {
				@ob_end_clean();
			}
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');
			exit;
		} catch (Exception $e) {
			throw new AppException($e->getMessage());
		}
	}
}
