<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Teams_List_View extends Vtiger_List_View {

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$scripts = parent::getHeaderScripts($request);
		$js = array(
			'layouts.v7.modules.Teams.resources.Group',
			'layouts.v7.modules.Teams.resources.Person'
		);
		return array_merge($scripts, $this->checkAndConvertJsScripts($js));
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$tab = strtolower(trim($request->get('tab')));
		if (!in_array($tab, array('groups', 'settings'))) {
			$tab = 'people';
		}

		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$canAdd = ($currentUser->isAdminUser() || Users_Privileges_Model::isPermitted('Users', 'CreateView'));
		$canDeactivate = $currentUser->isAdminUser();

		if ($tab === 'groups') {
			$groups = array();
			$res = $db->pquery(
				"SELECT g.groupid, g.group_name, g.assign_type,
						(SELECT COUNT(*) FROM vtiger_team_group_users gu WHERE gu.groupid = g.groupid) AS member_count
				 FROM vtiger_team_groups g ORDER BY g.group_name",
				array()
			);
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$groups[] = $row;
			}
			$viewer->assign('GROUPS', $groups);
		} elseif ($tab === 'people') {
			// Ensure user_activity table exists
			$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_user_activity (
				userid INT PRIMARY KEY,
				last_seen DATETIME
			) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
			
			// Auto-update last_seen for current user (if logged in)
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if ($currentUser && $currentUser->getId()) {
				$currentUserId = (int)$currentUser->getId();
				$db->pquery(
					"INSERT INTO vtiger_user_activity (userid, last_seen) VALUES (?, NOW())
					 ON DUPLICATE KEY UPDATE last_seen = NOW()",
					array($currentUserId)
				);
			}

			// Groups for sidebar (ProofHub style)
			$groupsForSidebar = array();
			$grpRes = $db->pquery(
				"SELECT g.groupid, g.group_name,
					(SELECT COUNT(*) FROM vtiger_team_group_users gu
					 INNER JOIN vtiger_users u ON u.id = gu.userid
					 WHERE gu.groupid = g.groupid AND u.deleted = 0 AND u.status = 'Active') AS active_count
				 FROM vtiger_team_groups g ORDER BY g.group_name",
				array()
			);
			while ($grpRes && ($gRow = $db->fetchByAssoc($grpRes))) {
				$groupsForSidebar[] = $gRow;
			}
			$selectedGroupId = (int) $request->get('groupid');
			
			$people = array();
			$baseSql = "SELECT u.id, u.first_name, u.last_name, u.user_name, u.email1 AS email, u.is_admin, u.status, ua.last_seen, u.date_joined_company
				 FROM vtiger_users u
				 LEFT JOIN vtiger_user_activity ua ON ua.userid = u.id
				 WHERE u.deleted = 0";
			$params = array();
			if ($selectedGroupId > 0) {
				$baseSql .= " AND u.id IN (SELECT userid FROM vtiger_team_group_users WHERE groupid = ?)";
				$params[] = $selectedGroupId;
			}
			$baseSql .= " ORDER BY u.is_admin DESC, u.last_name ASC, u.first_name ASC";
			$res = $db->pquery($baseSql, $params);
			$userIds = array();
			while ($res && ($row = $db->fetchByAssoc($res))) {
				$userIds[] = (int)$row['id'];
				$people[] = $row;
			}

			// Role per user (ProofHub: Access role)
			$roleMap = array();
			if (!empty($userIds)) {
				$roleRes = $db->pquery(
					"SELECT u2r.userid, r.rolename
					 FROM vtiger_user2role u2r
					 INNER JOIN vtiger_role r ON r.roleid = u2r.roleid
					 WHERE u2r.userid IN (" . generateQuestionMarks($userIds) . ")",
					$userIds
				);
				while ($roleRes && ($rRow = $db->fetchByAssoc($roleRes))) {
					$roleMap[(int)$rRow['userid']] = $rRow['rolename'];
				}
			}

			// Groups per user
			$groupMap = array();
			if (!empty($userIds)) {
				$grpRes = $db->pquery(
					"SELECT tgu.userid, tg.group_name
					 FROM vtiger_team_group_users tgu
					 INNER JOIN vtiger_team_groups tg ON tg.groupid = tgu.groupid
					 WHERE tgu.userid IN (" . generateQuestionMarks($userIds) . ")",
					$userIds
				);
				while ($grpRes && ($r = $db->fetchByAssoc($grpRes))) {
					$uid = (int)$r['userid'];
					if (!isset($groupMap[$uid])) $groupMap[$uid] = array();
					$groupMap[$uid][] = $r['group_name'];
				}
			}

			// Projects per user (by smownerid in vtiger_crmentity)
			$projectMap = array();
			$projectCount = array();
			if (!empty($userIds)) {
				// Get projects assigned to users via smownerid
				$pRes = $db->pquery(
					"SELECT ce.smownerid AS userid, p.projectid, p.projectname, p.projectstatus
					 FROM vtiger_project p
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.projectid
					 WHERE ce.deleted = 0 
					   AND ce.setype = 'Project'
					   AND ce.smownerid IN (" . generateQuestionMarks($userIds) . ")
					 ORDER BY p.projectname",
					$userIds
				);
				while ($pRes && ($r = $db->fetchByAssoc($pRes))) {
					$uid = (int)$r['userid'];
					if (!isset($projectMap[$uid])) {
						$projectMap[$uid] = array();
						$projectCount[$uid] = 0;
					}
					$projectMap[$uid][] = array(
						'id' => (int)$r['projectid'],
						'name' => $r['projectname'],
						'status' => $r['projectstatus']
					);
					$projectCount[$uid] = count($projectMap[$uid]);
				}
			}

			// Final normalize
			$now = time();
			$currentUserId = (int)$currentUser->getId();
			$normalized = array();
			foreach ($people as $row) {
				$uid = (int)$row['id'];
				$lastSeen = $row['last_seen'];
				$statusLabel = 'Never logged in';
				$isOnline = false;
				$isInactive = ($row['status'] === 'Inactive');
				
				// Current user is always online if not inactive
				if ($uid === $currentUserId && !$isInactive) {
					$isOnline = true;
					$statusLabel = 'Online';
				} elseif ($isInactive) {
					$statusLabel = 'Inactive';
				} elseif (!empty($lastSeen)) {
					// Parse last_seen as MySQL DATETIME and convert to timestamp
					$lastSeenTimestamp = strtotime($lastSeen);
					if ($lastSeenTimestamp === false) {
						// If parsing fails, try alternative format
						$lastSeenTimestamp = strtotime(str_replace(' ', 'T', $lastSeen));
					}
					
					if ($lastSeenTimestamp !== false) {
						$diff = $now - $lastSeenTimestamp;
						// Online if last_seen is within 2 minutes (120 seconds) - more accurate for real-time status
						if ($diff <= 120 && $diff >= 0) {
							$isOnline = true;
							$statusLabel = 'Online';
						} else {
							$mins = (int)floor($diff/60);
							if ($mins < 60) {
								$statusLabel = $mins . 'm ago';
							} else {
								$hrs = (int)floor($mins/60);
								if ($hrs < 24) {
									$statusLabel = $hrs . 'h ago';
								} else {
									$statusLabel = (int)floor($hrs/24) . 'd ago';
								}
							}
						}
					}
				}
				$fullName = trim($row['first_name'].' '.$row['last_name']);
				$initial = $fullName !== '' ? strtoupper(mb_substr($fullName, 0, 1)) : '?';
				$dateJoinedRaw = isset($row['date_joined_company']) && $row['date_joined_company'] !== '' && $row['date_joined_company'] !== null
					? $row['date_joined_company']
					: '';
				$dateJoined = $dateJoinedRaw !== '' ? DateTimeField::convertToUserFormat($dateJoinedRaw) : '';
				$normalized[] = array(
					'id' => $uid,
					'full_name' => $fullName,
					'initial' => $initial,
					'user_name' => $row['user_name'],
					'email' => $row['email'],
					'groups' => isset($groupMap[$uid]) ? $groupMap[$uid] : array(),
					'role_name' => isset($roleMap[$uid]) ? $roleMap[$uid] : '',
					'date_joined_company' => $dateJoined,
					'date_joined_company_raw' => $dateJoinedRaw,
					'project_count' => isset($projectCount[$uid]) ? $projectCount[$uid] : 0,
					'projects' => isset($projectMap[$uid]) ? $projectMap[$uid] : array(),
					'is_online' => $isOnline,
					'is_inactive' => $isInactive,
					'status_label' => $statusLabel,
					'is_admin' => ($row['is_admin'] == 'on' || $row['is_admin'] == 1),
				);
			}
			$activeResult = $db->pquery("SELECT COUNT(*) FROM vtiger_users WHERE deleted = 0 AND status = 'Active'", array());
			$allPeopleActiveCount = (int) $db->query_result($activeResult, 0, 0);
			// ProofHub style: nhóm theo role (Owner, Normal User...)
			$peopleByRole = array();
			foreach ($normalized as $p) {
				$role = trim($p['role_name']) !== '' ? $p['role_name'] : 'No role';
				if (!isset($peopleByRole[$role])) $peopleByRole[$role] = array();
				$peopleByRole[$role][] = $p;
			}
			uksort($peopleByRole, function ($a, $b) {
				if (stripos($a, 'Owner') !== false || stripos($a, 'Admin') !== false) return -1;
				if (stripos($b, 'Owner') !== false || stripos($b, 'Admin') !== false) return 1;
				return strcasecmp($a, $b);
			});
			$viewer->assign('PEOPLE', $normalized);
			$viewer->assign('PEOPLE_BY_ROLE', $peopleByRole);
			$viewer->assign('GROUPS_SIDEBAR', $groupsForSidebar);
			$viewer->assign('SELECTED_GROUP_ID', $selectedGroupId);
			$viewer->assign('ALL_PEOPLE_ACTIVE_COUNT', $allPeopleActiveCount);
			$viewer->assign('CAN_DEACTIVATE', $canDeactivate);
		}

		$viewer->assign('ACTIVE_TAB', $tab);
		$viewer->assign('CAN_ADD_PERSON', $canAdd);
		$viewer->assign('CAN_ADD_GROUP', $canAdd);
		$viewer->view('List.tpl', $request->getModule());
	}
}
