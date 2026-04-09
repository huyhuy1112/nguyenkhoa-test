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

	public function process(Vtiger_Request $request) {
		global $adb;

		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);

		$name = trim((string)$request->get('name'));
		$from = trim((string)$request->get('from'));
		$to   = trim((string)$request->get('to'));
		$status = $request->get('status');
		if (!is_array($status)) {
			$status = $status ? array($status) : array();
		}
		// Default: Planning + Completed if nothing selected
		if (count($status) === 0) {
			$status = array('Planning', 'Completed');
		}

		$where = array('ce.deleted = 0');
		$params = array();

		if ($name !== '') {
			$where[] = 'c.campaignname LIKE ?';
			$params[] = '%' . $name . '%';
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

		// -------------------------
		// 1) Base rows for charts + list
		// -------------------------
		$sql = "SELECT
					c.campaignid,
					c.campaignname,
					c.campaignstatus,
					{$costExpr} AS actualcost,
					{$revenueExpr} AS expectedrevenue,
					ce.createdtime
				FROM vtiger_campaign c
				INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
				LEFT JOIN vtiger_campaignscf cf ON cf.campaignid = c.campaignid
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
			$rows[] = array(
				'campaignid' => (int)$row['campaignid'],
				'campaignname' => (string)$row['campaignname'],
				'campaignstatus' => (string)$row['campaignstatus'],
				'cost' => $cost,
				'revenue' => $revenue,
				'profit' => $profit,
				'roi' => $roi,
				'createdtime' => $row['createdtime'],
				'link' => 'index.php?module=Campaigns&view=Detail&record=' . (int)$row['campaignid'],
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
			'total_cost' => 0,
			'total_revenue' => 0,
			'avg_roi' => 0,
		);

		$kpiSql = "SELECT
						COUNT(c.campaignid) as total_campaigns,
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

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('FILTER_NAME', $name);
		$viewer->assign('FILTER_FROM', $from);
		$viewer->assign('FILTER_TO', $to);
		$viewer->assign('FILTER_STATUS', $status);

		// Map for Smarty (avoid in_array in template)
		$statusMap = array();
		foreach ($status as $s) $statusMap[(string)$s] = true;
		$viewer->assign('FILTER_STATUS_MAP', $statusMap);

		// Keep existing KPI vars (for backward compatibility with template)
		$viewer->assign('KPI_TOTAL_CAMPAIGNS', (int)$kpi['total_campaigns']);
		$viewer->assign('KPI_TOTAL_COST', (float)$kpi['total_cost']);
		$viewer->assign('KPI_TOTAL_REVENUE', (float)$kpi['total_revenue']);
		$viewer->assign('KPI_AVG_ROI', (float)$kpi['avg_roi']);

		// New variables requested
		$viewer->assign('KPI', $kpi);
		$viewer->assign('TOP_CAMPAIGNS', $topCampaigns);
		$viewer->assign('MONTHLY_SUMMARY', $monthlySummary);
		$viewer->assign('INSIGHTS', $insights);

		// Table data (detail list)
		$viewer->assign('CAMPAIGNS', $rows);

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

