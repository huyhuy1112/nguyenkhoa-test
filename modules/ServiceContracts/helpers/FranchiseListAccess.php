<?php
/*+***********************************************************************************
 * Sales and Kế toán can open Khách hàng nhượng quyền tiềm năng (ServiceContracts).
 * Admin already can. Other roles stay on their profile.
 ************************************************************************************/

class ServiceContracts_FranchiseListAccess_Helper {

	public static function currentUserCanSee() {
		global $current_user;
		if (empty($current_user) || empty($current_user->id)) {
			return false;
		}
		$roleName = '';
		if (!empty($current_user->roleid) && function_exists('getRoleName')) {
			$roleName = (string) getRoleName($current_user->roleid);
		}
		if ($roleName === '' && class_exists('Users_Record_Model')) {
			try {
				$userModel = Users_Record_Model::getCurrentUserModel();
				if ($userModel && method_exists($userModel, 'getUserRoleName')) {
					$roleName = (string) $userModel->getUserRoleName();
				}
			} catch (Exception $e) {
				$roleName = '';
			}
		}
		return self::roleCanSee($roleName);
	}

	public static function roleCanSee($roleName) {
		$raw = trim((string) $roleName);
		if ($raw === '') {
			return false;
		}
		if (stripos($raw, 'sale') !== false) {
			return true;
		}
		$fold = self::fold($raw);
		if (strpos($fold, 'ke toan') !== false || strpos($fold, 'ketoan') !== false) {
			return true;
		}
		if (strpos($fold, 'accounting') !== false || strpos($fold, 'accountant') !== false) {
			return true;
		}
		return false;
	}

	protected static function fold($value) {
		$value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
		$map = array(
			'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
			'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
			'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
			'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
			'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
			'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
			'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
			'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
			'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
			'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
			'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
			'đ' => 'd',
		);
		return strtr($value, $map);
	}
}
