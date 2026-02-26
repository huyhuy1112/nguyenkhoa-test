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
 * Team List View - Users Sub-feature
 * 
 * ARCHITECTURE:
 * - Teams is NOT a standalone module
 * - Teams is a Users sub-feature under Management app
 * - This view extends Users_List_View to reuse Users functionality
 * - Permission check relies on Users module permission ONLY
 * 
 * PHASE 1 IMPLEMENTATION:
 * - Displays Users list with "Teams" page title
 * - Future: Will display team-specific user groupings
 */
class Users_TeamList_View extends Users_List_View {

	/**
	 * Pre-process settings menu
	 */
	public function preProcessSettings(Vtiger_Request $request) {
		parent::preProcessSettings($request);
		$viewer = $this->getViewer($request);
		// Set active block for Teams menu highlighting
		$viewer->assign('ACTIVE_BLOCK', array('block' => 'LBL_USER_MANAGEMENT', 'menu' => 'Teams'));
	}

	/**
	 * Check permission - uses Users module permission
	 */
	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		// Permission check relies on Users module permission
		// No Teams-specific permission needed
		return $permissions;
	}

	/**
	 * Process the request
	 * PHASE 1: Display Users list with Teams context
	 */
	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		
		// Set page title to "Teams"
		$viewer->assign('PAGE_TITLE', 'Teams');
		$viewer->assign('QUALIFIED_MODULE', 'Users');
		
		// Ensure module is Users (not Teams)
		$request->set('module', 'Users');
		$request->set('view', 'List');
		
		// Initialize list view contents
		$this->initializeListViewContents($request, $viewer);
		
		// Render with Teams context
		$viewer->assign('IS_TEAMS_VIEW', true);
		$viewer->view('ListViewContents.tpl', 'Users');
	}
}
