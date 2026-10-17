<?php
/*+***********************************************************************************
 * RBAC theo phòng ban (Excel khách):
 *   BGĐ: CEO / Expert / Assistant — full
 *   Kinh doanh: Sale Manager / Sale
 *   Kế toán: KTT / Ke toan
 *   Cung ứng / Kho vận: Cung ung / Kho
 *
 * Profile permissions seeded by modules/Home/scripts/ApplyRbacMatrix.php.
 *************************************************************************************/

class Home_RbacMatrix_Helper {

	const PERSONA_BGD = 'bgd';
	const PERSONA_CEO = 'ceo';
	const PERSONA_SALE_MANAGER = 'sale_manager';
	const PERSONA_SALE = 'sale';
	const PERSONA_CHIEF_ACCOUNTANT = 'chief_accountant';
	const PERSONA_ACCOUNTANT = 'accountant';
	const PERSONA_SUPPLY = 'supply';
	const PERSONA_WAREHOUSE = 'warehouse';

	/** @deprecated keep aliases for older code paths */
	const PERSONA_ADMIN = 'bgd';
	const PERSONA_SUPERVISOR = 'sale_manager';

	/** Profile display names (seeded). */
	public static function profileNames() {
		return array(
			self::PERSONA_BGD => 'NK BGD',
			self::PERSONA_SALE_MANAGER => 'NK Sale Manager',
			self::PERSONA_SALE => 'NK Sale',
			self::PERSONA_CHIEF_ACCOUNTANT => 'NK KTT',
			self::PERSONA_ACCOUNTANT => 'NK Ke toan',
			self::PERSONA_SUPPLY => 'NK Cung ung',
			self::PERSONA_WAREHOUSE => 'NK Kho',
		);
	}

	/** Canonical role display names (seeded). */
	public static function roleNames() {
		return array(
			self::PERSONA_CEO => 'CEO',
			self::PERSONA_BGD => 'Expert', // Expert & Assistant share NK BGD profile
			self::PERSONA_SALE_MANAGER => 'Sale Manager',
			self::PERSONA_SALE => 'Sale',
			self::PERSONA_CHIEF_ACCOUNTANT => 'KTT',
			self::PERSONA_ACCOUNTANT => 'Ke toan',
			self::PERSONA_SUPPLY => 'Cung ung',
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
			return self::PERSONA_BGD;
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
		// BGĐ full: Expert, Assistant, Admin (legacy), Administrator
		if ($n === 'expert' || $n === 'assistant' || $n === 'assistant bgd'
			|| $n === 'admin' || $n === 'administrator' || $n === 'bgd') {
			return self::PERSONA_BGD;
		}
		// Kinh doanh
		if ($n === 'sale manager' || $n === 'sales manager' || $n === 'supervisor'
			|| $n === 'tpkd' || $n === 'truong phong kinh doanh') {
			return self::PERSONA_SALE_MANAGER;
		}
		if ($n === 'sale' || $n === 'sales' || $n === 'sales person' || $n === 'salesperson') {
			return self::PERSONA_SALE;
		}
		// Kế toán
		if ($n === 'ktt' || $n === 'chief accountant' || $n === 'ke toan truong'
			|| strpos($n, 'ktt') !== false) {
			return self::PERSONA_CHIEF_ACCOUNTANT;
		}
		if ($n === 'ke toan' || $n === 'ketoan' || $n === 'accountant' || $n === 'accounting'
			|| strpos($n, 'ke toan') !== false || strpos($n, 'aacute') !== false) {
			return self::PERSONA_ACCOUNTANT;
		}
		// Cung ứng / Kho
		if ($n === 'cung ung' || $n === 'supply' || $n === 'supply lead'
			|| strpos($n, 'cung ung') !== false) {
			return self::PERSONA_SUPPLY;
		}
		if ($n === 'kho' || $n === 'warehouse' || $n === 'ware house'
			|| $n === 'quan ly kho' || strpos($n, 'kho van') !== false) {
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
			self::PERSONA_BGD,
			self::PERSONA_CEO,
			self::PERSONA_SALE_MANAGER,
			self::PERSONA_SALE,
			self::PERSONA_CHIEF_ACCOUNTANT,
			self::PERSONA_ACCOUNTANT,
			self::PERSONA_SUPPLY,
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
		$value = str_replace(array('ế', 'ề', 'ể', 'ễ', 'ệ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'á', 'à', 'ả', 'ã', 'ạ', 'ư', 'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ơ', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'đ'),
			array('e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'a', 'a', 'a', 'a', 'a', 'u', 'u', 'u', 'u', 'u', 'u', 'o', 'o', 'o', 'o', 'o', 'o', 'i', 'i', 'i', 'i', 'i', 'y', 'y', 'y', 'y', 'y', 'd'),
			$value);
		$value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
		$value = preg_replace('/\s+/', ' ', trim($value));
		return $value;
	}
}
