<?php
/*+***********************************************************************************
 * Ghi lịch sử xóa document trước khi xóa.
 *************************************************************************************/

class Documents_Delete_Action extends Vtiger_Delete_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if ($recordId && (class_exists('Documents_History_Helper') || file_exists('modules/Documents/DocumentHistory.php'))) {
			require_once 'modules/Documents/DocumentHistory.php';
			Documents_History_Helper::log($recordId, Documents_History_Helper::ACTION_DELETE, '');
		}
		parent::process($request);
	}
}
