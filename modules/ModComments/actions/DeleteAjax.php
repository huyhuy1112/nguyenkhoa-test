<?php
/*+***********************************************************************************
 * Ajax delete for ModComments — allowed for CRM admin users on parent record detail view.
 *************************************************************************************/

class ModComments_DeleteAjax_Action extends Vtiger_DeleteAjax_Action {

	public function requiresPermission(Vtiger_Request $request) {
		if (ModComments_Module_Model::canCurrentUserAdminDeleteComment()) {
			return array();
		}
		return parent::requiresPermission($request);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (empty($recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}

		if (ModComments_Module_Model::canCurrentUserAdminDeleteComment()) {
			ModComments_Module_Model::assertAdminCanDeleteComment($recordId);
			return true;
		}

		if (!Users_Privileges_Model::isPermitted($moduleName, 'Delete', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
		return true;
	}
}
