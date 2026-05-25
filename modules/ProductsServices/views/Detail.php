<?php

class ProductsServices_Detail_View extends Vtiger_Detail_View {

	/**
	 * Summary tab: stock Calendar activities (Add Task / Add Event) for this record.
	 */
	public function getActivities(Vtiger_Request $request) {
		$calendarModuleName = 'Calendar';
		$calendarModuleModel = Vtiger_Module_Model::getInstance($calendarModuleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($calendarModuleModel->getId())) {
			return '';
		}

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$pageNumber = $request->get('page');
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('limit', 10);

		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$moduleModel = $recordModel->getModule();

		$relatedActivities = $moduleModel->getCalendarActivities('', $pagingModel, 'all', $recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ACTIVITIES', $relatedActivities);

		return $viewer->view('RelatedActivities.tpl', $moduleName, true);
	}
}
