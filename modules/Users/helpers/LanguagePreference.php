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

		self::migrateLegacyInstallerEnglishOnce();
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

	protected static function isLegacyEnglishLanguage($lang) {
		$lang = strtolower(trim((string)$lang));
		return $lang === '' || $lang === 'en_us' || $lang === 'en_gb';
	}

	protected static function getSiteDefaultLanguage() {
		$siteDefault = trim((string)vglobal('default_language'));
		if ($siteDefault === '' || self::isLegacyEnglishLanguage($siteDefault)) {
			return 'vi_vn';
		}
		return $siteDefault;
	}

	/**
	 * Vtiger installer seeds en_us for all users. One-time migrate to Vietnamese site default.
	 * Users who want English can set it again in Preferences (saved as en_us).
	 */
	protected static function migrateLegacyInstallerEnglishOnce() {
		if (self::getSiteDefaultLanguage() !== 'vi_vn') {
			return;
		}
		$flagFile = 'storage/mk_lang_migrated_vi_v1.flag';
		if (is_file($flagFile)) {
			return;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery("UPDATE vtiger_users SET language=? WHERE language IN ('en_us','en_gb')", array('vi_vn'));
		@file_put_contents($flagFile, gmdate('c'));
	}

	protected static function resolveLanguageForUserId($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '';
		}

		$siteDefault = self::getSiteDefaultLanguage();
		$lang = '';

		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT language FROM vtiger_users WHERE id = ?', array($userId));
		if ($res && $adb->num_rows($res) > 0) {
			$lang = trim((string)$adb->query_result($res, 0, 'language'));
		}

		if ($lang !== '') {
			return $lang;
		}

		if (!empty($_SESSION['authenticated_user_language'])) {
			$sessionLang = trim((string)$_SESSION['authenticated_user_language']);
			if ($sessionLang !== '') {
				return $sessionLang;
			}
		}

		global $current_user;
		if (!empty($current_user) && (int)$current_user->id === $userId && !empty($current_user->column_fields['language'])) {
			$profileLang = trim((string)$current_user->column_fields['language']);
			if ($profileLang !== '') {
				return $profileLang;
			}
		}

		return $siteDefault;
	}
}
