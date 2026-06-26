<?php
/*+***********************************************************************************
 * Apply the signed-in user's vtiger language preference (DB is source of truth).
 *************************************************************************************/

class Users_LanguagePreference_Helper {

	public static function applyForCurrentUser() {
		global $current_user;
		$userId = 0;
		if (!empty($current_user) && !empty($current_user->id)) {
			$userId = (int)$current_user->id;
		}
		if ($userId <= 0 && !empty($_SESSION['authenticated_user_id'])) {
			$userId = (int)$_SESSION['authenticated_user_id'];
		}
		if ($userId <= 0) {
			return;
		}

		$lang = self::resolveLanguageForUserId($userId);
		if ($lang === '') {
			return;
		}
		self::applyLanguage($lang);
	}

	public static function applyLanguage($lang) {
		$lang = trim((string)$lang);
		if ($lang === '') {
			return;
		}

		$_SESSION['authenticated_user_language'] = $lang;
		if (class_exists('Vtiger_Language_Handler')) {
			Vtiger_Language_Handler::resetCachedLanguage();
		}
		vglobal('current_language', $lang);

		global $current_user;
		if (!empty($current_user)) {
			$current_user->column_fields['language'] = $lang;
			if (property_exists($current_user, 'language')) {
				$current_user->language = $lang;
			}
		}
		if (class_exists('Users_Record_Model')) {
			Users_Record_Model::$currentUserModels = array();
		}
	}

	protected static function resolveLanguageForUserId($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '';
		}

		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT language FROM vtiger_users WHERE id = ?', array($userId));
		if ($res && $adb->num_rows($res) > 0) {
			$lang = trim((string)$adb->query_result($res, 0, 'language'));
			if ($lang !== '') {
				return $lang;
			}
		}

		if (!empty($_SESSION['authenticated_user_language'])) {
			return trim((string)$_SESSION['authenticated_user_language']);
		}

		global $current_user;
		if (!empty($current_user) && (int)$current_user->id === $userId && !empty($current_user->column_fields['language'])) {
			return trim((string)$current_user->column_fields['language']);
		}

		return trim((string)vglobal('default_language'));
	}
}
