<?php
/*+***********************************************************************************
 * Notify assignee when Lead is created or reassigned.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/AssignNotificationHelper.php';

class LeadsAssignHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		Vtiger_AssignNotificationHelper::handleAfterSave(
			$eventName,
			$entityData,
			'Leads',
			'',
			'Lead',
			function ($entityData, $recordId) {
				global $adb;
				$fn = trim((string) $entityData->get('firstname'));
				$ln = trim((string) $entityData->get('lastname'));
				$name = trim($fn . ' ' . $ln);
				if ($name !== '') {
					return $name;
				}
				$company = trim((string) $entityData->get('company'));
				if ($company !== '') {
					return $company;
				}
				$res = $adb->pquery(
					'SELECT firstname, lastname, company FROM vtiger_leaddetails WHERE leadid = ?',
					array($recordId)
				);
				if ($res && $adb->num_rows($res) > 0) {
					$fn = trim((string) $adb->query_result($res, 0, 'firstname'));
					$ln = trim((string) $adb->query_result($res, 0, 'lastname'));
					$name = trim($fn . ' ' . $ln);
					if ($name !== '') {
						return $name;
					}
					$company = trim((string) $adb->query_result($res, 0, 'company'));
					if ($company !== '') {
						return $company;
					}
				}
				return '';
			}
		);
	}
}
