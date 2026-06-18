<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Evaluate Dashboard (non-entity module)
 * IMPORTANT: must not use Vtiger_List_View pipeline (QueryGenerator/Webservices meta).
 */
class Evaluate_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		// Non-entity dashboard: allow List access via module index permission.
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Index');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'Index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MARKETING');
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	private function parseMoney($value) {
		if ($value === null) {
			return 0.0;
		}
		$value = str_replace(',', '', (string)$value);
		return (float)$value;
	}

	private function hasColumn($table, $column, PearDatabase $adb) {
		$r = $adb->pquery("SHOW COLUMNS FROM {$table} LIKE ?", array($column));
		return ($r && $adb->num_rows($r) > 0);
	}

	private function pickCfColumnExpr($candidates, $tableAlias, PearDatabase $adb) {
		foreach ($candidates as $col) {
			if ($this->hasColumn('vtiger_campaignscf', $col, $adb)) {
				return "{$tableAlias}.{$col}";
			}
		}
		return "0";
	}

	private function statusCssClass($status) {
		$s = strtolower(trim((string)$status));
		if ($s === 'completed') {
			return 'completed';
		}
		if ($s === 'active') {
			return 'active';
		}
		return 'planning';
	}

	private function buildListPageNumbers($current, $total) {
		if ($total <= 7) {
			return range(1, max(1, $total));
		}
		$pages = array(1);
		if ($current > 3) {
			$pages[] = '…';
		}
		$start = max(2, $current - 1);
		$end = min($total - 1, $current + 1);
		for ($i = $start; $i <= $end; $i++) {
			$pages[] = $i;
		}
		if ($current < $total - 2) {
			$pages[] = '…';
		}
		$pages[] = $total;
		return array_values(array_unique($pages, SORT_REGULAR));
	}

	public function process(Vtiger_Request $request) {
		global $adb;

		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);

		$from = trim((string)$request->get('from'));
		$to   = trim((string)$request->get('to'));
		$campaignId = (int)$request->get('campaignid');
		$filterApplied = $request->has('filter_applied');
		$filterInProgress = $filterApplied ? $request->has('in_progress') : true;
		$filterCompleted = $filterApplied ? $request->has('completed') : true;
		if (!$filterInProgress && !$filterCompleted) {
			$filterInProgress = true;
			$filterCompleted = true;
		}
		$status = array();
		if ($filterInProgress) {
			$status = array_merge($status, array('Planning', 'Active'));
		}
		if ($filterCompleted) {
			$status[] = 'Completed';
		}
		$status = array_values(array_unique($status));

		$where = array('ce.deleted = 0');
		$params = array();

		if ($campaignId > 0) {
			$where[] = 'c.campaignid = ?';
			$params[] = $campaignId;
		}
		if ($from !== '') {
			$where[] = 'ce.createdtime >= ?';
			$params[] = $from . ' 00:00:00';
		}
		if ($to !== '') {
			$where[] = 'ce.createdtime <= ?';
			$params[] = $to . ' 23:59:59';
		}
		if (count($status) > 0) {
			$where[] = 'c.campaignstatus IN (' . generateQuestionMarks($status) . ')';
			$params = array_merge($params, $status);
		} else {
			$where[] = '1=0';
		}

		// -------------------------
		// Effective money fields (fallback to campaignscf if present)
		// -------------------------
		$cfExpectedRevenue = $this->pickCfColumnExpr(
			array('cf_expectedrevenue', 'cf_expected_revenue', 'cf_revenue', 'cf_actual_revenue', 'cf_expectedrevenue_amount'),
			'cf',
			$adb
		);
		$cfActualCost = $this->pickCfColumnExpr(
			array('cf_actualcost', 'cf_actual_cost', 'cf_cost', 'cf_total_cost', 'cf_actualcost_amount'),
			'cf',
			$adb
		);
		$cfBudgetCost = $this->pickCfColumnExpr(
			array('cf_budgetcost', 'cf_budget_cost', 'cf_budget', 'cf_planned_cost', 'cf_budgetcost_amount'),
			'cf',
			$adb
		);

		$costExpr = "COALESCE(NULLIF(c.actualcost,0), NULLIF(c.budgetcost,0), NULLIF({$cfActualCost},0), NULLIF({$cfBudgetCost},0), 0)";
		$revenueExpr = "COALESCE(NULLIF(c.expectedrevenue,0), NULLIF({$cfExpectedRevenue},0), 0)";
		$plannedCostExpr = "COALESCE(NULLIF(c.budgetcost,0), NULLIF({$cfBudgetCost},0), 0)";

		// Campaign dropdown options
		$campaignOptions = array();
		$optRes = $adb->pquery(
			"SELECT c.campaignid, c.campaignname
			 FROM vtiger_campaign c
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
			 WHERE ce.deleted = 0
			 ORDER BY c.campaignname ASC",
			array()
		);
		for ($oi = 0; $optRes && $oi < $adb->num_rows($optRes); $oi++) {
			$campaignOptions[] = array(
				'id' => (int)$adb->query_result($optRes, $oi, 'campaignid'),
				'name' => (string)$adb->query_result($optRes, $oi, 'campaignname'),
			);
		}

		// -------------------------
		// 1) Base rows for charts + list
		// -------------------------
		$sql = "SELECT
					c.campaignid,
					c.campaignname,
					c.campaignstatus,
					c.campaigntype,
					c.closingdate,
					{$costExpr} AS actualcost,
					{$revenueExpr} AS expectedrevenue,
					ce.createdtime,
					TRIM(CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,''))) AS assigned_to
				FROM vtiger_campaign c
				INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
				LEFT JOIN vtiger_campaignscf cf ON cf.campaignid = c.campaignid
				LEFT JOIN vtiger_users u ON u.id = ce.smownerid
				WHERE " . implode(' AND ', $where) . "
				ORDER BY ce.createdtime DESC";

		$res = $adb->pquery($sql, $params);
		$rows = array();

		$campaignNames = array();
		$costs = array();
		$revenues = array();
		$rois = array();

		$totalCost = 0.0;
		$totalRevenue = 0.0;

		for ($i = 0; $res && $i < $adb->num_rows($res); $i++) {
			$row = $adb->fetchByAssoc($res, $i);

			$cost = $this->parseMoney($row['actualcost']);
			$revenue = $this->parseMoney($row['expectedrevenue']);
			$roi = $cost > 0 ? (($revenue - $cost) / $cost) * 100.0 : 0.0;

			$campaignNames[] = (string)$row['campaignname'];
			$costs[] = $cost;
			$revenues[] = $revenue;
			$rois[] = $roi;

			$totalCost += $cost;
			$totalRevenue += $revenue;

			$profit = $revenue - $cost;
			$closing = $row['closingdate'];
			$closingDisplay = $closing ? date('d-m-Y', strtotime($closing)) : '—';
			$assigned = trim((string)$row['assigned_to']);
			if ($assigned === '') {
				$assigned = '—';
			}
			$rows[] = array(
				'campaignid' => (int)$row['campaignid'],
				'campaignname' => (string)$row['campaignname'],
				'campaignstatus' => (string)$row['campaignstatus'],
				'status_class' => $this->statusCssClass($row['campaignstatus']),
				'campaigntype' => (string)$row['campaigntype'],
				'closingdate' => $closing,
				'closingdate_display' => $closingDisplay,
				'assigned_to' => $assigned,
				'cost' => $cost,
				'revenue' => $revenue,
				'profit' => $profit,
				'roi' => $roi,
				'createdtime' => $row['createdtime'],
				'link' => 'index.php?module=Campaigns&view=Detail&record=' . (int)$row['campaignid'] . '&app=MARKETING',
			);
		}

		// Insight summaries (same filtered dataset)
		$insights = array(
			'best_roi' => null,
			'worst_roi' => null,
			'highest_revenue' => null,
			'highest_cost' => null,
		);
		$withCost = array();
		foreach ($rows as $r) {
			if ($r['cost'] > 0.0000001) {
				$withCost[] = $r;
			}
		}
		if (!empty($withCost)) {
			usort($withCost, function ($a, $b) {
				return ($a['roi'] <=> $b['roi']);
			});
			$insights['worst_roi'] = $withCost[0];
			$insights['best_roi'] = $withCost[count($withCost) - 1];
		}
		$byRev = $rows;
		usort($byRev, function ($a, $b) {
			return ($b['revenue'] <=> $a['revenue']);
		});
		if (!empty($byRev)) {
			$insights['highest_revenue'] = $byRev[0];
		}
		$byCost = $rows;
		usort($byCost, function ($a, $b) {
			return ($b['cost'] <=> $a['cost']);
		});
		if (!empty($byCost)) {
			$insights['highest_cost'] = $byCost[0];
		}

		// Horizontal ROI ranking chart: sort by ROI desc, cap labels for readability
		$rankingForChart = $rows;
		usort($rankingForChart, function ($a, $b) {
			return ($b['roi'] <=> $a['roi']);
		});
		$rankingForChart = array_slice($rankingForChart, 0, 20);

		// -------------------------
		// 2) KPI query (SQL aggregate)
		// -------------------------
		$kpi = array(
			'total_campaigns' => 0,
			'completed' => 0,
			'completion_rate' => 0.0,
			'total_planned_cost' => 0.0,
			'total_cost' => 0,
			'total_revenue' => 0,
			'avg_roi' => 0,
		);

		$kpiSql = "SELECT
						COUNT(c.campaignid) as total_campaigns,
						SUM(CASE WHEN c.campaignstatus = 'Completed' THEN 1 ELSE 0 END) as completed,
						SUM({$plannedCostExpr}) as total_planned_cost,
						SUM({$costExpr}) as total_cost,
						SUM({$revenueExpr}) as total_revenue,
						AVG((({$revenueExpr})-({$costExpr}))/NULLIF(({$costExpr}),0)*100) as avg_roi
				   FROM vtiger_campaign c
				   INNER JOIN vtiger_crmentity ce ON ce.crmid=c.campaignid
				   LEFT JOIN vtiger_campaignscf cf ON cf.campaignid = c.campaignid
				   WHERE " . implode(' AND ', $where);

		$kpiRes = $adb->pquery($kpiSql, $params);
		if ($kpiRes && $adb->num_rows($kpiRes) > 0) {
			$kpi['total_campaigns'] = (int)$adb->query_result($kpiRes, 0, 'total_campaigns');
			$kpi['total_cost'] = $this->parseMoney($adb->query_result($kpiRes, 0, 'total_cost'));
			$kpi['total_revenue'] = $this->parseMoney($adb->query_result($kpiRes, 0, 'total_revenue'));
			$kpi['avg_roi'] = (float)$adb->query_result($kpiRes, 0, 'avg_roi');
		}

		// -------------------------
		// 3) Top campaigns (TOP 5 by ROI)
		// -------------------------
		$topCampaigns = array();
		$topSql = "SELECT
						c.campaignname,
						{$costExpr} as actualcost,
						{$revenueExpr} as expectedrevenue,
						((({$revenueExpr})-({$costExpr}))/NULLIF(({$costExpr}),0)*100) as roi
				   FROM vtiger_campaign c
				   INNER JOIN vtiger_crmentity ce ON ce.crmid=c.campaignid
				   LEFT JOIN vtiger_campaignscf cf ON cf.campaignid = c.campaignid
				   WHERE " . implode(' AND ', $where) . "
				   ORDER BY roi DESC
				   LIMIT 5";
		$topRes = $adb->pquery($topSql, $params);
		for ($i = 0; $topRes && $i < $adb->num_rows($topRes); $i++) {
			$topCampaigns[] = array(
				'campaignname' => (string)$adb->query_result($topRes, $i, 'campaignname'),
				'actualcost' => $this->parseMoney($adb->query_result($topRes, $i, 'actualcost')),
				'expectedrevenue' => $this->parseMoney($adb->query_result($topRes, $i, 'expectedrevenue')),
				'roi' => (float)$adb->query_result($topRes, $i, 'roi'),
			);
		}

		// -------------------------
		// 4) Monthly summary (GROUP BY month)
		// -------------------------
		$monthlySummary = array();
		$monthlySql = "SELECT
							DATE_FORMAT(ce.createdtime,'%Y-%m') as month,
							COUNT(c.campaignid) as total_campaigns,
							SUM({$costExpr}) as total_cost,
							SUM({$revenueExpr}) as total_revenue,
							AVG((({$revenueExpr})-({$costExpr}))/NULLIF(({$costExpr}),0)*100) as avg_roi
					   FROM vtiger_campaign c
					   INNER JOIN vtiger_crmentity ce ON ce.crmid=c.campaignid
					   LEFT JOIN vtiger_campaignscf cf ON cf.campaignid = c.campaignid
					   WHERE " . implode(' AND ', $where) . "
					   GROUP BY month
					   ORDER BY month";
		$monthlyRes = $adb->pquery($monthlySql, $params);
		for ($i = 0; $monthlyRes && $i < $adb->num_rows($monthlyRes); $i++) {
			$monthlySummary[] = array(
				'month' => (string)$adb->query_result($monthlyRes, $i, 'month'),
				'total_campaigns' => (int)$adb->query_result($monthlyRes, $i, 'total_campaigns'),
				'total_cost' => $this->parseMoney($adb->query_result($monthlyRes, $i, 'total_cost')),
				'total_revenue' => $this->parseMoney($adb->query_result($monthlyRes, $i, 'total_revenue')),
				'avg_roi' => (float)$adb->query_result($monthlyRes, $i, 'avg_roi'),
			);
		}

		$listPage = max(1, (int)$request->get('list_page', 1));
		$listPerPage = 14;
		$listTotal = count($rows);
		$listTotalPages = max(1, (int)ceil($listTotal / $listPerPage));
		if ($listPage > $listTotalPages) {
			$listPage = $listTotalPages;
		}
		$listOffset = ($listPage - 1) * $listPerPage;
		$listCampaigns = array_slice($rows, $listOffset, $listPerPage);
		$listFrom = $listTotal > 0 ? $listOffset + 1 : 0;
		$listTo = min($listOffset + $listPerPage, $listTotal);

		$pageUrlParams = array(
			'module' => 'Evaluate',
			'view' => 'List',
			'app' => 'MARKETING',
		);
		if ($from !== '') {
			$pageUrlParams['from'] = $from;
		}
		if ($to !== '') {
			$pageUrlParams['to'] = $to;
		}
		if ($campaignId > 0) {
			$pageUrlParams['campaignid'] = $campaignId;
		}
		if ($filterInProgress) {
			$pageUrlParams['in_progress'] = '1';
		}
		if ($filterCompleted) {
			$pageUrlParams['completed'] = '1';
		}
		if ($filterApplied) {
			$pageUrlParams['filter_applied'] = '1';
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('FILTER_FROM', $from);
		$viewer->assign('FILTER_TO', $to);
		$viewer->assign('FILTER_CAMPAIGN_ID', $campaignId);
		$viewer->assign('FILTER_IN_PROGRESS', $filterInProgress);
		$viewer->assign('FILTER_COMPLETED', $filterCompleted);
		$viewer->assign('CAMPAIGN_OPTIONS', $campaignOptions);

		$viewer->assign('KPI_TOTAL_CAMPAIGNS', (int)$kpi['total_campaigns']);
		$viewer->assign('KPI_COMPLETED', (int)$kpi['completed']);
		$viewer->assign('KPI_COMPLETION_RATE', (float)$kpi['completion_rate']);
		$viewer->assign('KPI_TOTAL_PLANNED_COST', (float)$kpi['total_planned_cost']);
		$viewer->assign('KPI_TOTAL_COST', (float)$kpi['total_cost']);
		$viewer->assign('KPI_TOTAL_REVENUE', (float)$kpi['total_revenue']);
		$viewer->assign('KPI_AVG_ROI', (float)$kpi['avg_roi']);

		$viewer->assign('KPI', $kpi);
		$viewer->assign('TOP_CAMPAIGNS', $topCampaigns);
		$viewer->assign('MONTHLY_SUMMARY', $monthlySummary);
		$viewer->assign('INSIGHTS', $insights);
		$viewer->assign('CAMPAIGNS', $rows);
		$viewer->assign('LIST_CAMPAIGNS', $listCampaigns);
		$viewer->assign('LIST_PAGE', $listPage);
		$viewer->assign('LIST_TOTAL', $listTotal);
		$viewer->assign('LIST_FROM', $listFrom);
		$viewer->assign('LIST_TO', $listTo);
		$viewer->assign('LIST_TOTAL_PAGES', $listTotalPages);
		$viewer->assign('LIST_PAGE_NUMBERS', $this->buildListPageNumbers($listPage, $listTotalPages));
		$viewer->assign('LIST_PAGE_URL_PREFIX', 'index.php?' . http_build_query($pageUrlParams));

		// JSON for charts (no monthly line chart — replaced by ROI ranking in JS)
		$rankingLabels = array();
		$rankingRois = array();
		foreach ($rankingForChart as $rc) {
			$name = (string)$rc['campaignname'];
			if (strlen($name) > 36) {
				$name = substr($name, 0, 33) . '…';
			}
			$rankingLabels[] = $name;
			$rankingRois[] = (float)$rc['roi'];
		}
		$dashboardPayload = array(
			'campaigns' => $campaignNames,
			'costs' => $costs,
			'revenues' => $revenues,
			'rois' => $rois,
			'rankingLabels' => $rankingLabels,
			'rankingRois' => $rankingRois,
		);
		$viewer->assign('EVALUATE_DATA', json_encode($dashboardPayload, JSON_UNESCAPED_UNICODE));

		$viewer->view('ListView.tpl', $moduleName);
	}
}

