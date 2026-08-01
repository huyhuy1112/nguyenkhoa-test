<?php
/*+***********************************************************************************
 * Admin KPI Dashboard — Admin / CEO access gate.
 *************************************************************************************/

class Home_AdminKpiAccess_Helper {

	/**
	 * @param Users_Record_Model|null $userModel
	 * @return bool
	 */
	public static function isAllowed($userModel = null) {
		if (!$userModel) {
			$userModel = Users_Record_Model::getCurrentUserModel();
		}
		if (!$userModel) {
			return false;
		}
		if (method_exists($userModel, 'isAdminUser') && $userModel->isAdminUser()) {
			return true;
		}
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) {
			return false;
		}
		try {
			$db = PearDatabase::getInstance();
			$r = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', array($roleId));
			if ($db->num_rows($r)) {
				$name = strtolower(trim((string) $db->query_result($r, 0, 'rolename')));
				return ($name === 'ceo' || (bool) preg_match('/\bceo\b/', $name));
			}
		} catch (Exception $e) {
			return false;
		}
		return false;
	}
}
