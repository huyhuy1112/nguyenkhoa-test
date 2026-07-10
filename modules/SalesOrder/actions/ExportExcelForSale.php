<?php
/*+***********************************************************************************
 * SalesOrder Excel export — same Arial layout as Quotes, order naming.
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteExcelExport.php';

class SalesOrder_ExportExcelForSale_Action extends Vtiger_Action_Controller {

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

			$objPHPExcel = Quotes_QuoteExcelExport_Helper::buildSaleWorkbook($focus, 'SalesOrder');
			$fileName = Quotes_QuoteExcelExport_Helper::buildSaleFilename($focus, $recordId, 'SalesOrder');

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
