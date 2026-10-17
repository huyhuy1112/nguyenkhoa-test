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

	public function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	public function postProcessTplName(Vtiger_Request $request) {
		return 'ListViewPostProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		/* Teams uses a custom List.tpl — do not run Vtiger_List_View list-model preProcess (causes fatal/blank). */
		Vtiger_Index_View::preProcess($request, false);

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if ($moduleModel) {
			$this->setModuleInfo($request, $moduleModel);
		}

		$app = strtoupper((string) $request->get('app'));
		if ($app === '') {
			$app = 'MANAGEMENT';
		}
		$viewer->assign('SELECTED_MENU_CATEGORY', $app);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CUSTOM_VIEWS', array());

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view($this->postProcessTplName($request), $request->getModule());
		Vtiger_Index_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$scripts = parent::getHeaderScripts($request);
		$js = array(
			'layouts.v7.modules.Teams.resources.Person'
		);
		return array_merge($scripts, $this->checkAndConvertJsScripts($js));
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$canAdd = ($currentUser->isAdminUser() || Users_Privileges_Model::isPermitted('Users', 'CreateView'));
		$canDeactivate = $currentUser->isAdminUser();

		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_user_activity (
			userid INT PRIMARY KEY,
			last_seen DATETIME
		) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());

		if ($currentUser && $currentUser->getId()) {
			$currentUserId = (int)$currentUser->getId();
			$db->pquery(
				"INSERT INTO vtiger_user_activity (userid, last_seen) VALUES (?, NOW())
				 ON DUPLICATE KEY UPDATE last_seen = NOW()",
				array($currentUserId)
			);
		}

		$people = array();
		$res = $db->pquery(
			"SELECT u.id, u.first_name, u.last_name, u.user_name, u.email1 AS email, u.is_admin, u.status, ua.last_seen, u.date_joined_company
			 FROM vtiger_users u
			 LEFT JOIN vtiger_user_activity ua ON ua.userid = u.id
			 WHERE u.deleted = 0
			 ORDER BY u.is_admin DESC, u.last_name ASC, u.first_name ASC",
			array()
		);
		$userIds = array();
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$userIds[] = (int)$row['id'];
			$people[] = $row;
		}

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

		$now = time();
		$currentUserId = (int)$currentUser->getId();
		$normalized = array();
		foreach ($people as $row) {
			$uid = (int)$row['id'];
			$lastSeen = $row['last_seen'];
			$statusLabel = 'Never logged in';
			$isOnline = false;
			$isInactive = ($row['status'] === 'Inactive');

			if ($uid === $currentUserId && !$isInactive) {
				$isOnline = true;
				$statusLabel = 'Online';
			} elseif ($isInactive) {
				$statusLabel = 'Inactive';
			} elseif (!empty($lastSeen)) {
				$lastSeenTimestamp = strtotime($lastSeen);
				if ($lastSeenTimestamp === false) {
					$lastSeenTimestamp = strtotime(str_replace(' ', 'T', $lastSeen));
				}
				if ($lastSeenTimestamp !== false) {
					$diff = $now - $lastSeenTimestamp;
					if ($diff <= 120 && $diff >= 0) {
						$isOnline = true;
						$statusLabel = 'Online';
					} else {
						$mins = (int)floor($diff / 60);
						if ($mins < 60) {
							$statusLabel = $mins . ' phút trước';
						} else {
							$hrs = (int)floor($mins / 60);
							if ($hrs < 24) {
								$statusLabel = $hrs . ' giờ trước';
							} else {
								$statusLabel = (int)floor($hrs / 24) . ' ngày trước';
							}
						}
					}
				}
			}
			$fullName = trim($row['first_name'] . ' ' . $row['last_name']);
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
				'role_name' => isset($roleMap[$uid]) ? $roleMap[$uid] : '',
				'date_joined_company' => $dateJoined,
				'date_joined_company_raw' => $dateJoinedRaw,
				'is_online' => $isOnline,
				'is_inactive' => $isInactive,
				'status_label' => $statusLabel,
				'is_admin' => ($row['is_admin'] == 'on' || $row['is_admin'] == 1),
			);
		}

		$peopleByRole = array();
		foreach ($normalized as $p) {
			$role = trim($p['role_name']) !== '' ? $p['role_name'] : 'Chưa có vai trò';
			if (!isset($peopleByRole[$role])) {
				$peopleByRole[$role] = array();
			}
			$peopleByRole[$role][] = $p;
		}
		uksort($peopleByRole, function ($a, $b) {
			if (stripos($a, 'Owner') !== false || stripos($a, 'Admin') !== false) {
				return -1;
			}
			if (stripos($b, 'Owner') !== false || stripos($b, 'Admin') !== false) {
				return 1;
			}
			return strcasecmp($a, $b);
		});

		$viewer->assign('PEOPLE', $normalized);
		$viewer->assign('PEOPLE_BY_ROLE', $peopleByRole);
		$viewer->assign('ACTIVE_TAB', 'people');
		$viewer->assign('CAN_ADD_PERSON', $canAdd);
		$viewer->assign('CAN_DEACTIVATE', $canDeactivate);
		$viewer->view('List.tpl', $request->getModule());
	}
}
