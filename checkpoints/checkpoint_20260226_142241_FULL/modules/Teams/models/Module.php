<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_Module_Model extends Vtiger_Module_Model {
	/**
	 * Ensure required schema for Add Person flow exists (safe to call multiple times).
	 */
	public static function ensureAddPersonSchema() {
		$db = PearDatabase::getInstance();

		// 1) Ensure vtiger_teams exists (legacy installs already have it)
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_teams (
			teamid INT AUTO_INCREMENT PRIMARY KEY,
			team_name VARCHAR(255) NOT NULL,
			description TEXT,
			ownerid INT NOT NULL,
			createdtime DATETIME DEFAULT CURRENT_TIMESTAMP,
			modifiedtime DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		// Add missing columns to existing vtiger_teams (best-effort; ignore failures)
		$colsRes = $db->pquery("SHOW COLUMNS FROM vtiger_teams", array());
		$cols = array();
		while ($colsRes && ($row = $db->fetchByAssoc($colsRes))) {
			$cols[strtolower($row['field'])] = true;
		}
		if (!isset($cols['team_name'])) {
			@$db->pquery("ALTER TABLE vtiger_teams ADD COLUMN team_name VARCHAR(255) NULL", array());
		}
		if (!isset($cols['ownerid'])) {
			@$db->pquery("ALTER TABLE vtiger_teams ADD COLUMN ownerid INT NULL", array());
		}
		if (!isset($cols['createdtime'])) {
			@$db->pquery("ALTER TABLE vtiger_teams ADD COLUMN createdtime DATETIME NULL", array());
		}
		if (!isset($cols['modifiedtime'])) {
			@$db->pquery("ALTER TABLE vtiger_teams ADD COLUMN modifiedtime DATETIME NULL", array());
		}

		// 2) Team members table
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_team_members (
			id INT AUTO_INCREMENT PRIMARY KEY,
			teamid INT NOT NULL,
			userid INT NOT NULL,
			roleid VARCHAR(255),
			title VARCHAR(255),
			projectid INT NULL,
			is_active TINYINT(1) DEFAULT 1,
			createdtime DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uniq_team_user (teamid, userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
	}

	/**
	 * Ensure required schema for Group management exists (safe to call multiple times).
	 */
	public static function ensureGroupSchema() {
		$db = PearDatabase::getInstance();

		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_team_groups (
			groupid INT AUTO_INCREMENT PRIMARY KEY,
			teamid INT NOT NULL,
			group_name VARCHAR(255) NOT NULL,
			assign_type ENUM('ALL','USERS','PROJECT') NOT NULL DEFAULT 'ALL',
			projectid INT NULL,
			createdtime DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY idx_teamid (teamid),
			KEY idx_assign_type (assign_type)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_team_group_users (
			groupid INT NOT NULL,
			userid INT NOT NULL,
			UNIQUE KEY uniq_group_user (groupid, userid),
			KEY idx_userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
	}

	/**
	 * Tables for Project/ProjectTask: assign to team group or multiple users.
	 * - vtiger_project_team_groups: projectid -> team_groupid (assign project to a team group)
	 * - vtiger_project_assignees: projectid -> userid (additional assignees)
	 * - vtiger_projecttask_team_groups: projecttaskid -> team_groupid (assign task to a team group)
	 * - vtiger_projecttask_assignees: projecttaskid -> userid (additional assignees)
	 */
	public static function ensureProjectAssignSchema() {
		$db = PearDatabase::getInstance();
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_project_team_groups (
			projectid INT NOT NULL,
			team_groupid INT NOT NULL,
			PRIMARY KEY (projectid),
			KEY idx_team_groupid (team_groupid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_project_assignees (
			projectid INT NOT NULL,
			userid INT NOT NULL,
			PRIMARY KEY (projectid, userid),
			KEY idx_userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_projecttask_team_groups (
			projecttaskid INT NOT NULL,
			team_groupid INT NOT NULL,
			PRIMARY KEY (projecttaskid),
			KEY idx_team_groupid (team_groupid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_projecttask_assignees (
			projecttaskid INT NOT NULL,
			userid INT NOT NULL,
			PRIMARY KEY (projecttaskid, userid),
			KEY idx_userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
	}

	/**
	 * Ensure vtiger_users has date_joined_company column (ngày vào công ty).
	 */
	public static function ensureDateJoinedCompanyColumn() {
		$db = PearDatabase::getInstance();
		$res = $db->pquery("SHOW COLUMNS FROM vtiger_users LIKE ?", array('date_joined_company'));
		if (!$res || $db->num_rows($res) === 0) {
			$db->pquery("ALTER TABLE vtiger_users ADD COLUMN date_joined_company DATE NULL DEFAULT NULL", array());
		}
	}

	/**
	 * Ensure user presence table exists.
	 */
	public static function ensureUserActivitySchema() {
		$db = PearDatabase::getInstance();
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_user_activity (
			userid INT PRIMARY KEY,
			last_seen DATETIME
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
	}

	/**
	 * Function to get supported utility actions for a module
	 */
	function getUtilityActionsNames() {
		return array('Import', 'Export');
	}

	/**
	 * Function to get the url for default view of the module
	 * @return <string> - url
	 */
	public function getDefaultUrl() {
		return 'index.php?module='.$this->get('name').'&view=List';
	}

	/**
	 * Provide basic action links for module header (Add Record, Import)
	 * so Management nav bar shows same structure as other modules.
	 */
	public function getModuleBasicLinks() {
		$basicLinks = array();
		$moduleName = $this->get('name');
		if (Users_Privileges_Model::isPermitted($moduleName, 'CreateView')) {
			$basicLinks[] = array(
				'linktype' => 'BASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $this->getCreateRecordUrl() . '&app=Management',
				'linkicon' => 'fa-plus'
			);
		}
		if (Users_Privileges_Model::isPermitted($moduleName, 'Import')) {
			$basicLinks[] = array(
				'linktype' => 'BASIC',
				'linklabel' => 'LBL_IMPORT',
				'linkurl' => $this->getImportUrl() . '&app=Management',
				'linkicon' => 'fa-download'
			);
		}
		return $basicLinks;
	}

	/**
	 * Provide setting links for Customize dropdown in module header.
	 */
	public function getSettingLinks() {
		$settingsLinks = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser->isAdminUser() && Users_Privileges_Model::isPermitted($this->get('name'), 'DetailView')) {
			$settingsLinks[] = array(
				'linktype' => 'LISTVIEWSETTING',
				'linklabel' => 'LBL_EDIT_FIELDS',
				'linkurl' => 'index.php?parent=Settings&module=LayoutEditor&sourceModule=' . $this->get('name'),
				'linkicon' => ''
			);
		}
		return $settingsLinks;
	}
}
