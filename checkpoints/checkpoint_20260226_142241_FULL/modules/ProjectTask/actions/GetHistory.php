<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License.
 * The Original Code is: vtiger CRM Open Source.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectTask_GetHistory_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleModel = Vtiger_Module_Model::getInstance('ProjectTask');
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$recordId || !$userPrivilegesModel->hasModuleActionPermission($moduleModel->getId(), 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', 1);
		$pagingModel->set('limit', 50);

		$updates = ModTracker_Record_Model::getUpdates($recordId, $pagingModel, 'ProjectTask');
		$history = array();

		foreach ($updates as $model) {
			$userModel = $model->getModifiedBy();
			$userName = $userModel ? $userModel->getName() : '';
			$changedon = $model->getActivityTime();
			$status = $model->get('status');
			$label = 'Updated';
			if ($model->isCreate()) $label = 'Created';
			elseif ($model->isUpdate()) $label = 'Updated';
			elseif ($model->isDelete()) $label = 'Deleted';

			$changes = array();
			if ($model->isUpdate() || $model->isCreate()) {
				foreach ($model->getFieldInstances() as $fieldInstance) {
					$changes[] = array(
						'field' => $fieldInstance->getFieldInstance()->get('label'),
						'pre' => $fieldInstance->get('prevalue'),
						'post' => $fieldInstance->get('postvalue'),
					);
				}
			}

			$history[] = array(
				'userName' => $userName,
				'time' => $changedon,
				'action' => $label,
				'changes' => $changes,
			);
		}

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array('history' => $history));
		$response->emit();
	}
}
