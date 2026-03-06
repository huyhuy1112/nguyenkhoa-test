<?php
/*+***********************************************************************************
 * Activities_List_View – List with pagination and filters.
 * URL: index.php?module=Activities&view=List
 ************************************************************************************/

class Activities_List_View extends Vtiger_List_View {
	public function requiresPermission(Vtiger_Request $request) {
		return [];
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$adb   = PearDatabase::getInstance();
		$page  = max(1, (int)$request->get('page'));
		$limit = 20;
		$offset = ($page - 1) * $limit;

		$statusFilter = trim($request->get('status'));
		$typeFilter   = trim($request->get('activity_type'));
		$staffFilter  = (int)$request->get('assigned_user_id');
		$dateFilter   = trim($request->get('activity_date'));
		$sortFilter   = strtolower(trim((string)$request->get('sort')));
		$keyword      = trim($request->get('q'));

		$where = " WHERE ce.deleted = 0 ";
		$params = [];
		if ($statusFilter !== '' && $statusFilter !== null) {
			$where .= " AND a.status = ? ";
			$params[] = $statusFilter;
		}
		if ($typeFilter !== '' && $typeFilter !== null) {
			$where .= " AND a.activity_type = ? ";
			$params[] = $typeFilter;
		}
		if ($staffFilter > 0) {
			$where .= " AND ce.smownerid = ? ";
			$params[] = $staffFilter;
		}
		if ($dateFilter !== '' && $dateFilter !== null) {
			$where .= " AND DATE(a.activity_date) = ? ";
			$params[] = $dateFilter;
		}
		if ($keyword !== '' && $keyword !== null) {
			$where .= " AND (a.activity_type LIKE ? OR a.content LIKE ?) ";
			$params[] = "%{$keyword}%";
			$params[] = "%{$keyword}%";
		}

		$orderDirection = ($sortFilter === 'oldest') ? 'ASC' : 'DESC';

		$sql = "
			SELECT a.activityid, a.activity_type, a.content, a.activity_date,
			       a.status, a.organizationid, a.projectid, a.ticketid,
			       ce.smownerid AS assigned_user_id, u.first_name, u.last_name,
			       org.accountname AS org_name,
			       pr.projectname AS project_name
			  FROM vtiger_activities a
			  JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			  LEFT JOIN vtiger_users u ON u.id = ce.smownerid
			  LEFT JOIN vtiger_account org ON org.accountid = a.organizationid
			  LEFT JOIN vtiger_project pr ON pr.projectid = a.projectid
			{$where}
			 ORDER BY a.activity_date {$orderDirection}, a.activityid {$orderDirection}
			 LIMIT $offset, $limit";
		$res = $adb->pquery($sql, $params);
		$rows = [];
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$rows[] = $row;
			}
		}

		$countRes = $adb->pquery(
			"SELECT COUNT(*) AS cnt FROM vtiger_activities a JOIN vtiger_crmentity ce ON ce.crmid=a.activityid {$where}",
			$params
		);
		$total = $countRes ? (int)$adb->query_result($countRes, 0, 'cnt') : 0;
		$maxPage = max(1, ceil($total / $limit));

		$statusOptions = ['Scheduled', 'Ready', 'Completed', 'Skipped'];
		$typeOptions = ['Follow up','Anniversary','Meeting','Gift','Intro','Other'];
		$users = [];
		$uRes = $adb->pquery(
			"SELECT id, first_name, last_name
			   FROM vtiger_users
			  WHERE status='Active'
		   ORDER BY first_name, last_name",
			[]
		);
		while ($uRes && ($u = $adb->fetchByAssoc($uRes))) {
			$users[] = $u;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', 'Activities');
		$viewer->assign('ACTIVITIES', $rows);
		$viewer->assign('PAGE', $page);
		$viewer->assign('MAX_PAGE', $maxPage);
		$viewer->assign('TOTAL', $total);
		$viewer->assign('STATUS_OPTIONS', $statusOptions);
		$viewer->assign('TYPE_OPTIONS', $typeOptions);
		$viewer->assign('USERS', $users);
		$viewer->assign('STATUS_FILTER', $statusFilter);
		$viewer->assign('TYPE_FILTER', $typeFilter);
		$viewer->assign('STAFF_FILTER', $staffFilter);
		$viewer->assign('DATE_FILTER', $dateFilter);
		$viewer->assign('SORT_FILTER', $sortFilter);
		$viewer->assign('KEYWORD', $keyword);
		$viewer->view('List.tpl', 'Activities');
	}
}
