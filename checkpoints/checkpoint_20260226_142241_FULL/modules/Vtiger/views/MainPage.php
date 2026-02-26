<?php
/*+***********************************************************************************
 * Main Page (Management) - custom landing view inspired by dashboard layout.
 * No data persistence; placeholder cards only.
 *************************************************************************************/

class Vtiger_MainPage_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->view('MainPage.tpl', 'Vtiger');
	}
}
