<?php
/*+***********************************************************************************
 * Notify assignee when Sales Order is created or reassigned.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/AssignNotificationHelper.php';

class SalesOrderAssignHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		Vtiger_AssignNotificationHelper::handleAfterSave(
			$eventName,
			$entityData,
			'SalesOrder',
			'subject',
			'Đơn hàng'
		);
	}
}
