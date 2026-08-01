<?php
/*+***********************************************************************************
 * KPI Dashboard access — matrix personas + CEO + is_admin.
 *************************************************************************************/

require_once 'modules/Home/helpers/RbacMatrix.php';

class Home_AdminKpiAccess_Helper {

	/**
	 * @param Users_Record_Model|null $userModel
	 * @return bool
	 */
	public static function isAllowed($userModel = null) {
		return Home_RbacMatrix_Helper::canAccessDashboard($userModel);
	}

	/**
	 * @param Users_Record_Model|null $userModel
	 * @return string|null
	 */
	public static function getPersona($userModel = null) {
		return Home_RbacMatrix_Helper::resolvePersona($userModel);
	}
}
