<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Plans_ListView_Model extends Vtiger_ListView_Model {
	/**
	 * Plans-only: rename "Add Record" to "Add Plan".
	 */
	public function getBasicLinks() {
		$basicLinks = array();
		$moduleModel = $this->getModule();
		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'CreateView');
		if ($createPermission) {
			$basicLinks[] = array(
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'Add Plan',
				'linkurl' => $moduleModel->getCreateRecordUrl(),
				'linkicon' => '',
			);
		}
		return $basicLinks;
	}
}

