{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
	.eval-empty { padding:16px; color:#64748b; text-align:center; }
</style>

<div class="mk">
<div class="mk-page">
	<div class="mk-header">
		<div>
			<div class="mk-title">Evaluate Dashboard</div>
			<div class="mk-subtitle">Marketing Campaign performance overview</div>
		</div>
	</div>

	<div class="mk-filter" style="margin-bottom:12px;">
		<form method="get" class="form-horizontal">
			<input type="hidden" name="module" value="Evaluate" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="MARKETING" />

			<div class="row">
				<div class="col-sm-4">
					<label>Campaign name</label>
					<input type="text" name="name" value="{$FILTER_NAME|escape:'html'}" class="form-control input-sm" placeholder="Search..." />
				</div>
				<div class="col-sm-3">
					<label>From</label>
					<input type="date" name="from" value="{$FILTER_FROM|escape:'html'}" class="form-control input-sm" />
				</div>
				<div class="col-sm-3">
					<label>To</label>
					<input type="date" name="to" value="{$FILTER_TO|escape:'html'}" class="form-control input-sm" />
				</div>
				<div class="col-sm-2" style="padding-top:20px;">
					<button type="submit" class="btn btn-primary btn-sm mk-btn-primary" style="width:100%;">Filter</button>
				</div>
			</div>

			<div style="margin-top:10px;">
				<label style="display:block; margin-bottom:6px;">Status</label>
				<label class="checkbox-inline" style="margin-right:10px;">
					<input type="checkbox" name="status[]" value="Planning"
						{if isset($FILTER_STATUS_MAP['Planning'])}checked="checked"{/if} />
					Planning
				</label>
				<label class="checkbox-inline" style="margin-right:10px;">
					<input type="checkbox" name="status[]" value="Completed"
						{if isset($FILTER_STATUS_MAP['Completed'])}checked="checked"{/if} />
					Completed
				</label>
			</div>
		</form>
	</div>

	<div class="mk-kpis" style="margin-bottom:12px;">
		<div class="mk-kpi">
			<div class="mk-kpi__label">Total Campaigns</div>
			<div class="mk-kpi__value">{$KPI_TOTAL_CAMPAIGNS}</div>
		</div>
		<div class="mk-kpi">
			<div class="mk-kpi__label">Total Cost</div>
			<div class="mk-kpi__value" id="evalTotalCost">{$KPI_TOTAL_COST|string_format:"%.0f"}</div>
		</div>
		<div class="mk-kpi">
			<div class="mk-kpi__label">Total Revenue</div>
			<div class="mk-kpi__value" id="evalTotalRevenue">{$KPI_TOTAL_REVENUE|string_format:"%.0f"}</div>
		</div>
		<div class="mk-kpi">
			<div class="mk-kpi__label">Average ROI</div>
			<div class="mk-kpi__value" id="evalAvgRoi">{$KPI_AVG_ROI|string_format:"%.2f"}%</div>
		</div>
	</div>

	<div class="mk-grid-2" style="margin-bottom:12px;">
		<div class="mk-panel">
			<div class="mk-section-title">Cost vs Revenue</div>
			<canvas id="evalCostRevenueChart" height="160"></canvas>
		</div>
		<div class="mk-panel">
			<div class="mk-section-title">ROI by Campaign</div>
			<canvas id="evalRoiChart" height="160"></canvas>
		</div>
	</div>

	<div class="mk-panel" style="margin-bottom:12px;">
		<div class="mk-section-title">Monthly Trend</div>
		<canvas id="evalMonthlyChart" height="140"></canvas>
	</div>

	<div class="mk-panel" style="margin-bottom:12px;">
		<div class="mk-section-title">Top Performing Campaigns</div>
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Campaign Name</th>
						<th style="text-align:right;">Cost</th>
						<th style="text-align:right;">Revenue</th>
						<th style="text-align:right;">ROI %</th>
					</tr>
				</thead>
				<tbody>
					{if $TOP_CAMPAIGNS|@count gt 0}
						{foreach from=$TOP_CAMPAIGNS item=row}
							<tr>
								<td>{$row.campaignname|escape:'html'}</td>
								<td style="text-align:right;">{$row.actualcost|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$row.expectedrevenue|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$row.roi|string_format:"%.2f"}</td>
							</tr>
						{/foreach}
					{else}
						<tr><td colspan="4" class="eval-empty">No data</td></tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>

	<div class="mk-panel" style="margin-bottom:12px;">
		<div class="mk-section-title">Monthly Performance</div>
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Month</th>
						<th style="text-align:right;">Total Campaigns</th>
						<th style="text-align:right;">Total Cost</th>
						<th style="text-align:right;">Total Revenue</th>
						<th style="text-align:right;">Avg ROI</th>
					</tr>
				</thead>
				<tbody>
					{if $MONTHLY_SUMMARY|@count gt 0}
						{foreach from=$MONTHLY_SUMMARY item=row}
							<tr>
								<td>{$row.month|escape:'html'}</td>
								<td style="text-align:right;">{$row.total_campaigns}</td>
								<td style="text-align:right;">{$row.total_cost|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$row.total_revenue|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$row.avg_roi|string_format:"%.2f"}%</td>
							</tr>
						{/foreach}
					{else}
						<tr><td colspan="5" class="eval-empty">No data</td></tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>

	<div class="mk-panel">
		<div class="mk-section-title">Campaigns</div>
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
						<th>Start</th>
						<th>End</th>
						<th style="text-align:right;">Cost</th>
						<th style="text-align:right;">Revenue</th>
						<th style="text-align:right;">ROI (%)</th>
					</tr>
				</thead>
				<tbody>
					{if $CAMPAIGNS|@count gt 0}
						{foreach from=$CAMPAIGNS item=C}
							<tr>
								<td><a href="{$C.link|escape:'html'}" target="_blank">{$C.campaignname|escape:'html'}</a></td>
								<td>{$C.campaignstatus|escape:'html'}</td>
								<td>{$C.createdtime|escape:'html'}</td>
								<td></td>
								<td style="text-align:right;">{$C.cost}</td>
								<td style="text-align:right;">{$C.revenue}</td>
								<td style="text-align:right;">{$C.roi|string_format:"%.2f"}</td>
							</tr>
						{/foreach}
					{else}
						<tr><td colspan="7" class="eval-empty">No campaigns matched your filters.</td></tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>
</div>
</div>

<script type="application/json" id="EvaluateDashboardData">
{$EVALUATE_DATA nofilter}
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Evaluate/resources/EvaluateDashboard.js')}"></script>
{/strip}

