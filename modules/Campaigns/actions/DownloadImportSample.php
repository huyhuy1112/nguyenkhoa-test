<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Campaigns_DownloadImportSample_Action extends Vtiger_Action_Controller {
	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'Import')
		);
	}

	protected function getCampaignStatusPicklistValues() {
		try {
			$moduleModel = Vtiger_Module_Model::getInstance('Campaigns');
			$fieldModel = Vtiger_Field_Model::getInstance('campaignstatus', $moduleModel);
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
		if ($module !== 'Campaigns') {
			throw new AppException('Invalid module');
		}

		$statusValues = $this->getCampaignStatusPicklistValues();
		// Prefer explicit sample statuses if they exist in picklist.
		$exampleStatus1 = in_array('Planning', $statusValues) ? 'Planning' : (isset($statusValues[0]) ? $statusValues[0] : 'Planning');
		$exampleStatus2 = in_array('Active', $statusValues) ? 'Active' : (isset($statusValues[1]) ? $statusValues[1] : $exampleStatus1);

		$headers = array(
			'Campaign Name',
			'Campaign Status',
			'Campaign Type',
			'Start Date',
			'Expected Close Date',
			'Expected Revenue',
			'Assigned To',
		);

		// Keep examples simple and importable.
		$exampleRow1 = array('Sample Campaign 1', $exampleStatus1, 'Advertisement', '2026-04-25', '2026-05-25', '1000', 'Administrator');
		$exampleRow2 = array('Sample Campaign 2', $exampleStatus2, 'Email', '2026-05-01', '2026-06-01', '2000', 'Administrator');

		$filename = 'Campaigns_Import_Sample.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');

		$out = fopen('php://output', 'w');
		// UTF-8 BOM for Excel compatibility
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, $headers);
		fputcsv($out, $exampleRow1);
		fputcsv($out, $exampleRow2);
		fclose($out);
	}
}

