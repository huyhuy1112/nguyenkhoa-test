<?php
/**
 * Plans import sample CSV.
 */
class Plans_DownloadImportSample_Action extends Vtiger_Action_Controller {
	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'Import'),
		);
	}

	protected function getPlanStatusPicklistValues() {
		try {
			$moduleModel = Vtiger_Module_Model::getInstance('Plans');
			$fieldModel = Vtiger_Field_Model::getInstance('plan_status', $moduleModel);
			if ($fieldModel) {
				$valuesMap = $fieldModel->getPicklistValues();
				if (is_array($valuesMap) && count($valuesMap) > 0) {
					return array_keys($valuesMap);
				}
			}
		} catch (Exception $e) {
		}
		return array('Planning', 'Active', 'Completed', 'Cancelled');
	}

	public function process(Vtiger_Request $request) {
		$module = $request->getModule();
		if ($module !== 'Plans') {
			throw new AppException('Invalid module');
		}

		$statusValues = $this->getPlanStatusPicklistValues();
		$exampleStatus1 = in_array('Planning', $statusValues) ? 'Planning' : (isset($statusValues[0]) ? $statusValues[0] : 'Planning');
		$exampleStatus2 = in_array('Active', $statusValues) ? 'Active' : (isset($statusValues[1]) ? $statusValues[1] : $exampleStatus1);

		$headers = array(
			'Plan Name',
			'Status',
			'Start Date',
			'End Date',
			'Assigned To',
			'Description',
		);

		$exampleRow1 = array('Sample Plan 1', $exampleStatus1, '2026-04-01', '2026-06-30', 'Administrator', 'Plan description');
		$exampleRow2 = array('Sample Plan 2', $exampleStatus2, '2026-05-01', '2026-07-31', 'Administrator', 'Another plan');

		$filename = 'Plans_Import_Sample.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, $headers);
		fputcsv($out, $exampleRow1);
		fputcsv($out, $exampleRow2);
		fclose($out);
	}
}
