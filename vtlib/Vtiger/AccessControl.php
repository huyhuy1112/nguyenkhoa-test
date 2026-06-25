<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_AccessControl {

	protected $privileges;
	protected static $PRIVILEGE_ATTRS = array('is_admin', 'current_user_role', 'current_user_parent_role_seq',
		'current_user_profiles', 'profileGlobalPermission', 'profileTabsPermission', 'profileActionPermission',
		'current_user_groups', 'subordinate_roles', 'parent_roles', 'subordinate_roles_users', 'user_info'
	);

	protected function __construct() {
		$this->privileges = array();
	}

	protected function loadUserPrivilegesWithId($id) {
		if (!isset($this->privileges[$id])) {
			$privFile = 'user_privileges/user_privileges_'.$id.'.php';
			if (!file_exists($privFile)) {
				// Production safety: privilege flat files might not be generated yet (or were cleaned).
				// Try to generate them on demand instead of fatalling on require().
				$genFile = 'modules/Users/CreateUserPrivilegeFile.php';
				if (file_exists($genFile)) {
					require_once $genFile;
					if (function_exists('createUserPrivilegesfile')) {
						@createUserPrivilegesfile($id);
					}
				}
			}
			$privilege = new stdClass;
			if (file_exists($privFile)) {
				checkFileAccessForInclusion($privFile);
				require($privFile);
				$vars = get_defined_vars();
				foreach (self::$PRIVILEGE_ATTRS as $attr) {
					if (isset($attr) && isset($vars[$attr])) {
						$privilege->$attr = $vars[$attr];
					}
				}
			} else {
				// Last-resort fallback: avoid fatal 500 if server cannot write user_privileges directory.
				// We populate minimal attributes from DB so detail view can still render.
				$privilege->is_admin = false;
				$privilege->user_info = array();
				if (class_exists('PearDatabase')) {
					$db = PearDatabase::getInstance();
					$res = $db->pquery('SELECT is_admin, userlabel, roleid FROM vtiger_users WHERE id = ?', array((int)$id));
					if ($res && $db->num_rows($res) > 0) {
						$isAdminFlag = $db->query_result($res, 0, 'is_admin');
						$privilege->is_admin = ($isAdminFlag === 'on' || $isAdminFlag === '1' || $isAdminFlag === 1);
						$privilege->current_user_roles = $db->query_result($res, 0, 'roleid');
						$privilege->user_info = array(
							'userlabel' => $db->query_result($res, 0, 'userlabel'),
						);
					}
				}
			}

			$this->privileges[$id] = $privilege;
		}
		return $this->privileges[$id];
	}

	protected static $singleton = null;
	public static function loadUserPrivileges($id) {
		if (self::$singleton == null) {
			self::$singleton = new self();
		}
		return self::$singleton->loadUserPrivilegesWithId($id);
	}

	public static function clearUserPrivileges($id) {
		if (self::$singleton == null) {
			self::$singleton = new self();
		}

		if ( self::$singleton->privileges ) {
			unset(self::$singleton->privileges[$id]);
		}
	}

}
