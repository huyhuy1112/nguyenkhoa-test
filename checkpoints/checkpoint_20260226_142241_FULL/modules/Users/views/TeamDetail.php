<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Team Detail View - Users Sub-feature
 * 
 * ARCHITECTURE:
 * - Teams is a Users sub-feature, not standalone module
 * - Permission check uses Users module permission
 */
class Users_TeamDetail_View extends Vtiger_Index_View {

	/**
	 * Check permission - uses Users module permission
	 */
	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => '');
		return $permissions;
	}

	/**
	 * Process the request
	 */
	public function process(Vtiger_Request $request) {
		// PHASE 1: Placeholder - will show team detail in future
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', 'Users');
		$viewer->view('TeamDetail.tpl', 'Users');
	}
}
