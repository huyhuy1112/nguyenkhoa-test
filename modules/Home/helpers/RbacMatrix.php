<?php
/*+***********************************************************************************
 * RBAC matrix personas (Admin / Supervisor / Sale / Kế toán / Kho) + CEO.
 * Profile permissions are seeded by modules/Home/scripts/ApplyRbacMatrix.php.
 *************************************************************************************/

class Home_RbacMatrix_Helper {

	const PERSONA_ADMIN = 'admin';
	const PERSONA_SUPERVISOR = 'supervisor';
	const PERSONA_SALE = 'sale';
	const PERSONA_ACCOUNTANT = 'accountant';
	const PERSONA_WAREHOUSE = 'warehouse';
	const PERSONA_CEO = 'ceo';

	/** Profile display names (seeded). */
	public static function profileNames() {
		return array(
			self::PERSONA_ADMIN => 'NK Admin',
			self::PERSONA_SUPERVISOR => 'NK Supervisor',
			self::PERSONA_SALE => 'NK Sale',
			self::PERSONA_ACCOUNTANT => 'NK Ke toan',
			self::PERSONA_WAREHOUSE => 'NK Kho',
		);
	}

	/** Canonical role display names (seeded / renamed). */
	public static function roleNames() {
		return array(
			self::PERSONA_ADMIN => 'Admin',
			self::PERSONA_SUPERVISOR => 'Supervisor',
			self::PERSONA_SALE => 'Sale',
			self::PERSONA_ACCOUNTANT => 'Ke toan',
			self::PERSONA_WAREHOUSE => 'Kho',
		);
	}

	/**
	 * Resolve persona from user (is_admin / role name).
	 * @param Users_Record_Model|null $userModel
	 * @return string|null
	 */
	public static function resolvePersona($userModel = null) {
		if (!$userModel) {
			$userModel = Users_Record_Model::getCurrentUserModel();
		}
		if (!$userModel) {
			return null;
		}
		if (method_exists($userModel, 'isAdminUser') && $userModel->isAdminUser()) {
			return self::PERSONA_ADMIN;
		}
		$roleName = self::getRoleName($userModel);
		if ($roleName === '') {
			return null;
		}
		return self::personaFromRoleName($roleName);
	}

	/**
	 * @param string $roleName
	 * @return string|null
	 */
	public static function personaFromRoleName($roleName) {
		$n = self::normalize($roleName);
		if ($n === 'ceo' || preg_match('/\bceo\b/', $n)) {
			return self::PERSONA_CEO;
		}
		if ($n === 'admin' || $n === 'administrator') {
			return self::PERSONA_ADMIN;
		}
		if ($n === 'supervisor' || $n === 'sales manager') {
			return self::PERSONA_SUPERVISOR;
		}
		if ($n === 'sale' || $n === 'sales' || $n === 'sales person' || $n === 'salesperson') {
			return self::PERSONA_SALE;
		}
		// "Ke toan" / "Kế toán" / HTML-entity mangled variants
		if ($n === 'ke toan' || $n === 'ketoan' || $n === 'accountant' || $n === 'accounting'
			|| strpos($n, 'ke toan') !== false || strpos($n, 'aacute') !== false) {
			return self::PERSONA_ACCOUNTANT;
		}
		if ($n === 'kho' || $n === 'warehouse' || $n === 'ware house') {
			return self::PERSONA_WAREHOUSE;
		}
		return null;
	}

	/**
	 * Dashboard (KPI shell) allowed for matrix personas + CEO + is_admin.
	 * @param Users_Record_Model|null $userModel
	 * @return bool
	 */
	public static function canAccessDashboard($userModel = null) {
		$persona = self::resolvePersona($userModel);
		if ($persona === null) {
			return false;
		}
		return in_array($persona, array(
			self::PERSONA_ADMIN,
			self::PERSONA_CEO,
			self::PERSONA_SUPERVISOR,
			self::PERSONA_SALE,
			self::PERSONA_ACCOUNTANT,
			self::PERSONA_WAREHOUSE,
		), true);
	}

	/**
	 * @param Users_Record_Model $userModel
	 * @return string
	 */
	public static function getRoleName($userModel) {
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) {
			return '';
		}
		try {
			$db = PearDatabase::getInstance();
			$r = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', array($roleId));
			if ($db->num_rows($r)) {
				return trim((string) $db->query_result($r, 0, 'rolename'));
			}
		} catch (Exception $e) {
			return '';
		}
		return '';
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public static function normalize($value) {
		$value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = strtolower(trim($value));
		if (function_exists('iconv')) {
			$trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if ($trans !== false && $trans !== '') {
				$value = strtolower($trans);
			}
		}
		$value = str_replace(array('ế', 'ề', 'ể', 'ễ', 'ệ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'á', 'à', 'ả', 'ã', 'ạ'), 'e', $value);
		$value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
		$value = preg_replace('/\s+/', ' ', trim($value));
		return $value;
	}
}
