<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get users count
	 */
	public function getUsersCount() {
		$teamId = $this->getId();
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT COUNT(*) as count FROM vtiger_team_user_rel WHERE teamid = ?",
			array($teamId)
		);
		if ($result && $db->num_rows($result) > 0) {
			return intval($db->query_result($result, 0, 'count'));
		}
		return 0;
	}

	/**
	 * Function to get groups count
	 */
	public function getGroupsCount() {
		$teamId = $this->getId();
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT COUNT(*) as count FROM vtiger_team_group_rel WHERE teamid = ?",
			array($teamId)
		);
		if ($result && $db->num_rows($result) > 0) {
			return intval($db->query_result($result, 0, 'count'));
		}
		return 0;
	}

	/**
	 * Function to get team users
	 */
	public function getTeamUsers() {
		$teamId = $this->getId();
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT u.id, u.user_name, u.first_name, u.last_name 
			 FROM vtiger_users u
			 INNER JOIN vtiger_team_user_rel tur ON u.id = tur.userid
			 WHERE tur.teamid = ? AND u.status = 'Active' AND u.deleted = 0
			 ORDER BY u.last_name, u.first_name",
			array($teamId)
		);
		$users = array();
		if ($result) {
			while ($row = $db->fetchByAssoc($result)) {
				$users[] = $row;
			}
		}
		return $users;
	}

	/**
	 * Function to get team groups
	 */
	public function getTeamGroups() {
		$teamId = $this->getId();
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT g.groupid, g.groupname 
			 FROM vtiger_groups g
			 INNER JOIN vtiger_team_group_rel tgr ON g.groupid = tgr.groupid
			 WHERE tgr.teamid = ?
			 ORDER BY g.groupname",
			array($teamId)
		);
		$groups = array();
		if ($result) {
			while ($row = $db->fetchByAssoc($result)) {
				$groups[] = $row;
			}
		}
		return $groups;
	}

	/**
	 * Function to update team members
	 */
	public function updateTeamMembers($teamId, $userIds, $groupIds) {
		$db = PearDatabase::getInstance();
		
		// Delete existing user relationships
		$db->pquery("DELETE FROM vtiger_team_user_rel WHERE teamid = ?", array($teamId));
		
		// Insert new user relationships
		if (is_array($userIds)) {
			foreach ($userIds as $userId) {
				if (!empty($userId)) {
					$db->pquery(
						"INSERT INTO vtiger_team_user_rel (teamid, userid) VALUES (?, ?)",
						array($teamId, $userId)
					);
				}
			}
		}
		
		// Delete existing group relationships
		$db->pquery("DELETE FROM vtiger_team_group_rel WHERE teamid = ?", array($teamId));
		
		// Insert new group relationships
		if (is_array($groupIds)) {
			foreach ($groupIds as $groupId) {
				if (!empty($groupId)) {
					$db->pquery(
						"INSERT INTO vtiger_team_group_rel (teamid, groupid) VALUES (?, ?)",
						array($teamId, $groupId)
					);
				}
			}
		}
	}
}
