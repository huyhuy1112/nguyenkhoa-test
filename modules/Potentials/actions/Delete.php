<?php
/*+***********************************************************************************
 * Potentials delete — redirect list URL always includes app=SALES.
 *************************************************************************************/

require_once 'modules/Vtiger/actions/Delete.php';
require_once 'modules/Potentials/helpers/SalesAppGuard.php';

class Potentials_Delete_Action extends Vtiger_Delete_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$ajaxDelete = $request->get('ajaxDelete');
		$recurringEditMode = $request->get('recurringEditMode');

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$recordModel->set('recurringEditMode', $recurringEditMode);
		$moduleModel = $recordModel->getModule();

		$recordModel->delete();
		$cv = new CustomView();
		$cvId = $cv->getViewId($moduleName);
		deleteRecordFromDetailViewNavigationRecords($recordId, $cvId, $moduleName);
		$listViewUrl = Potentials_SalesAppGuard::appendAppToUrl($moduleModel->getListViewUrl());

		if ($ajaxDelete) {
			$response = new Vtiger_Response();
			$response->setResult($listViewUrl);
			return $response;
		}
		header('Location: ' . $listViewUrl);
	}
}
