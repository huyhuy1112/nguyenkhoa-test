<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

include_once 'include/utils/utils.php';

/**
 * Teams Module Class
 * Standalone module for team management under Management menu
 */
class Teams extends CRMEntity {
	var $log;
	var $db;
	
	// Explicit module name
	var $moduleName = 'Teams';
	public static $moduleNameStatic = 'Teams';
	
	var $table_name = "vtiger_teams";
	var $table_index = 'teamid';
	
	var $tab_name = Array('vtiger_crmentity', 'vtiger_teams');
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_teams' => 'teamid'
	);
	
	// Map teamid to crmid for vtiger_crmentity integration
	var $id_column = 'teamid';
	
	var $entity_table = "vtiger_crmentity";
	
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_teamscf', 'teamid');
	
	var $column_fields = Array();
	var $sortby_fields = Array('teamname', 'status', 'createdtime');
	
	// This is the list of fields that are in the lists.
	var $list_fields = Array(
		'Team Name' => Array('teams' => 'teamname'),
		'Status' => Array('teams' => 'status'),
		'Owner' => Array('crmentity' => 'smownerid')
	);
	
	var $list_fields_name = Array(
		'Team Name' => 'teamname',
		'Status' => 'status',
		'Owner' => 'assigned_user_id'
	);
	
	var $list_link_field = 'teamname';
	
	var $search_fields = Array(
		'Team Name' => Array('teams' => 'teamname')
	);
	
	var $search_fields_name = Array(
		'Team Name' => 'teamname'
	);
	
	// Required fields
	var $required_fields = Array('teamname', 'assigned_user_id');
	
	// Mandatory fields
	var $mandatory_fields = Array('teamname', 'assigned_user_id', 'createdtime', 'modifiedtime');
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->column_fields = getColumnFields(get_class($this));
		$this->db = PearDatabase::getInstance();
		global $log;
		$this->log = $log;
	}
	
	/**
	 * Save module - handle team members and groups
	 */
	function save_module($module) {
		// Team members and groups are handled in Save action after record is saved
		// This method is kept for future extension if needed
	}
	
	/**
	 * Get users count for a team
	 */
	function getUsersCount($teamId) {
		$result = $this->db->pquery(
			"SELECT COUNT(*) as count FROM vtiger_team_user_rel WHERE teamid = ?",
			array($teamId)
		);
		if ($result && $this->db->num_rows($result) > 0) {
			return intval($this->db->query_result($result, 0, 'count'));
		}
		return 0;
	}
	
	/**
	 * Get groups count for a team
	 */
	function getGroupsCount($teamId) {
		$result = $this->db->pquery(
			"SELECT COUNT(*) as count FROM vtiger_team_group_rel WHERE teamid = ?",
			array($teamId)
		);
		if ($result && $this->db->num_rows($result) > 0) {
			return intval($this->db->query_result($result, 0, 'count'));
		}
		return 0;
	}
	
	/**
	 * Archive team (soft delete)
	 */
	function archive($teamId) {
		$this->db->pquery(
			"UPDATE vtiger_teams SET status = 'Archived', deleted = 1 WHERE teamid = ?",
			array($teamId)
		);
	}
}
