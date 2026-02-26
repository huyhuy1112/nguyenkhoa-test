<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectTask_GetComments_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$moduleModel || !$recordId || !$userPrivilegesModel->hasModuleActionPermission($moduleModel->getId(), 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$comments = ModComments_Record_Model::getRecentComments($recordId);
		$payload = array();
		foreach ($comments as $commentModel) {
			$userModel = $commentModel->getCommentedByModel();
			$userName = $userModel ? $userModel->getName() : '';
			$attachments = array();
			try {
				$fileInfos = $commentModel->getFileNameAndDownloadURL();
				if (!empty($fileInfos) && is_array($fileInfos)) {
					foreach ($fileInfos as $f) {
						if (!empty($f['url']) && !empty($f['rawFileName'])) {
							$attachments[] = array(
								'url' => $f['url'],
								'name' => $f['rawFileName'],
							);
						}
					}
				}
			} catch (Exception $e) {
			}
			$payload[] = array(
				'id' => $commentModel->getId(),
				'comment_text' => $commentModel->get('commentcontent'),
				'userName' => $userName,
				'time' => $commentModel->getCommentedTime(),
				'attachments' => $attachments,
			);
		}
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult(array('comments' => $payload));
		$response->emit();
	}
}
