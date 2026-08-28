<?php
/*+***********************************************************************************
 * Notify assignee when Quote is created or reassigned.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/AssignNotificationHelper.php';

class QuotesAssignHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		Vtiger_AssignNotificationHelper::handleAfterSave(
			$eventName,
			$entityData,
			'Quotes',
			'subject',
			'Báo giá'
		);
	}
}
