<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Contacts_DownloadImportSample_Action extends Vtiger_Action_Controller {
	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'Import')
		);
	}

	public function process(Vtiger_Request $request) {
		$module = $request->getModule();
		if ($module !== 'Contacts') {
			throw new AppException('Invalid module');
		}

		// Safe, common Contact fields only (avoid address/custom/complex reference fields).
		$headers = array(
			'First Name',
			'Last Name',
			'Organization Name',
			'Email',
			'Mobile Phone',
			'Assigned To',
		);

		$exampleRow1 = array('John', 'Doe', 'Sample Organization', 'john.doe@example.com', '0900000000', 'Administrator');
		$exampleRow2 = array('Jane', 'Smith', 'Sample Organization', 'jane.smith@example.com', '0911111111', 'Administrator');

		$filename = 'Contacts_Import_Sample.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
		fputcsv($out, $headers);
		fputcsv($out, $exampleRow1);
		fputcsv($out, $exampleRow2);
		fclose($out);
	}
}

