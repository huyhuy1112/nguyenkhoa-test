<?php
/*+**********************************************************************************
 * Record-level tag-based access helper (Contacts/Accounts).
 *
 * REAL tag schema for this DB:
 * - vtiger_freetags (id, tag, raw_tag)
 * - vtiger_freetagged_objects (tag_id, object_id, module, tagger_id, tagged_on)
 *************************************************************************************/

class TagAccessHelper {
	/**
	 * Admin and CEO (role name) can see all.
	 */
	public static function isPrivilegedUser(Users_Record_Model $userModel) {
		if ($userModel->isAdminUser()) {
			return true;
		}
		$roleName = trim((string)$userModel->getUserRoleName());
		return (strcasecmp($roleName, 'CEO') === 0);
	}

	/**
	 * Map user role -> allowed tag names (array).
	 * Secure default: return empty array (show nothing) when not mapped.
	 */
	public static function getUserAccessTags(Users_Record_Model $userModel) {
		$roleName = trim((string)$userModel->getUserRoleName());

		$map = array(
			'Sales Person' => array('Sale', 'Sales', 'Sales Person'),
			'Sales Manager' => array('Sale', 'Sales', 'Sales Person'),
			'Vice President' => array('Marketing'),
			'CEO' => array(), // privileged bypass; keep empty here
		);

		$tags = array_key_exists($roleName, $map) ? $map[$roleName] : array();
		error_log('[TagACL] role=' . $roleName . ' mappedTags=' . json_encode($tags, JSON_UNESCAPED_UNICODE));
		return $tags;
	}
}

