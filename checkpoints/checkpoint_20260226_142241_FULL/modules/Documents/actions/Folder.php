<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Folder_Action extends Vtiger_Action_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		}
	}

	public function save($request) {
		$moduleName = $request->getModule();
		$folderName = $request->get('foldername');
		$folderDesc = $request->get('folderdesc');
		$result = array();

		if (!empty ($folderName)) {  
            $saveMode = $request->get('savemode');
            $folderModel = Documents_Folder_Model::getInstance();
            if($saveMode == 'edit') {
                $folderId = $request->get('folderid');
                $folderModel = Documents_Folder_Model::getInstanceById($folderId);
                $folderModel->set('mode','edit');                
            }
			
			$folderModel->set('foldername', $folderName);
			$folderModel->set('description', $folderDesc);

			if ($folderModel->checkDuplicate()) {
				throw new AppException(vtranslate('LBL_FOLDER_EXISTS', $moduleName));
				exit;
			}

			$folderModel->save();
			$sharedUserIds = $request->get('shared_user_ids');
			$sharedGroupIds = $request->get('shared_group_ids');
			if (is_array($sharedUserIds) || is_array($sharedGroupIds)) {
				$folderModel->saveSharing(
					is_array($sharedUserIds) ? $sharedUserIds : array(),
					is_array($sharedGroupIds) ? $sharedGroupIds : array()
				);
			}
			$result = array('success'=>true, 'message'=>vtranslate('LBL_FOLDER_SAVED', $moduleName), 'info'=>$folderModel->getInfoArray());

			// Nếu submit thường (không AJAX): redirect về trang Documents list thay vì trả JSON
			if (!$request->isAjax()) {
				$returnUrl = $request->get('return_url');
				if (empty($returnUrl) || strpos($returnUrl, 'index.php') !== 0) {
					$returnUrl = 'index.php?module=Documents&view=List';
				}
				header('Location: ' . $returnUrl);
				exit;
			}

			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
		}
	}


	public function delete($request) {
		$moduleName = $request->getModule();
		$folderId = $request->get('folderid');
		$result = array('success' => false, 'message' => '');

		if (empty($folderId)) {
			$result['message'] = vtranslate('LBL_FOLDER_NOT_FOUND', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$folderModel = Documents_Folder_Model::getInstanceById($folderId);
		$folderIdVal = $folderModel->getId();
		if (empty($folderIdVal)) {
			$result['message'] = vtranslate('LBL_FOLDER_NOT_FOUND', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$folderName = $folderModel->getName();
		if ($folderName === 'Default' || $folderName === 'Google Drive' || $folderName === 'Dropbox') {
			$result['message'] = vtranslate('LBL_FOLDER_CANNOT_DELETE_SYSTEM', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			$createdBy = $folderModel->get('createdby');
			if ((int)$createdBy !== (int)$currentUser->getId()) {
				$result['message'] = vtranslate('LBL_NO_PERMISSION_TO_DELETE_FOLDER', $moduleName);
				$response = new Vtiger_Response();
				$response->setResult($result);
				$response->emit();
				return;
			}
		}

		if ($folderModel->hasDocuments()) {
			$result['message'] = vtranslate('LBL_FOLDER_HAS_DOCUMENTS', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$folderModel->delete();
		$result = array('success' => true, 'message' => vtranslate('LBL_FOLDER_DELETED', $moduleName));

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}
