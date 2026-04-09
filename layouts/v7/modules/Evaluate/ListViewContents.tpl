{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
	.eval-page { max-width: 1400px; margin: 0 auto; }
	.eval-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
	@media (max-width: 991px) { .eval-kpi-row { grid-template-columns: repeat(2, 1fr); } }
	@media (max-width: 575px) { .eval-kpi-row { grid-template-columns: 1fr; } }
	.eval-kpi {
		background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 16px 18px;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.06);
	}
	.eval-kpi__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 6px; }
	.eval-kpi__value { font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.15; letter-spacing: -0.02em; }
	.eval-kpi__hint { font-size: 11px; color: #94a3b8; margin-top: 6px; }
	.eval-kpi--accent { border-color: #c7d2fe; background: linear-gradient(135deg, #eef2ff 0%, #ffffff 55%); }
	.eval-insights { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
	@media (max-width: 991px) { .eval-insights { grid-template-columns: repeat(2, 1fr); } }
	@media (max-width: 575px) { .eval-insights { grid-template-columns: 1fr; } }
	.eval-insight {
		background: #fff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		padding: 12px 14px;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
	}
	.eval-insight__label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px; }
	.eval-insight__name { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.35; word-break: break-word; }
	.eval-insight__meta { font-size: 12px; color: #475569; margin-top: 6px; }
	.eval-chart-wrap { position: relative; min-height: 220px; }
	.eval-chart-wrap--rank { min-height: 280px; max-height: 420px; }
	.eval-empty { padding: 20px; color: #64748b; text-align: center; font-size: 13px; }
	.eval-table-wrap { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
	.eval-table-wrap table { margin-bottom: 0; }
	.eval-table-wrap thead th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0 !important; }
	.eval-table-wrap tbody tr.eval-row--pos td { background: rgba(34, 197, 94, 0.06); }
	.eval-table-wrap tbody tr.eval-row--neg td { background: rgba(239, 68, 68, 0.06); }
	.eval-table-wrap tbody tr.eval-row--neg td { border-color: #fecaca; }
	.eval-section-title { font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; letter-spacing: -0.02em; }
	.eval-muted { color: #94a3b8; font-size: 12px; font-weight: 600; }
</style>

<div class="mk">
<div class="mk-page eval-page">
	<div class="mk-header">
		<div>
			<div class="mk-title">Evaluate</div>
			<div class="mk-subtitle">Campaign performance — compare cost, revenue, and ROI</div>
		</div>
	</div>

	<div class="mk-filter" style="margin-bottom:16px;">
		<form method="get" class="form-horizontal">
			<input type="hidden" name="module" value="Evaluate" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="MARKETING" />

			<div class="row">
				<div class="col-sm-4">
					<label>Campaign name</label>
					<input type="text" name="name" value="{$FILTER_NAME|escape:'html'}" class="form-control input-sm" placeholder="Search…" />
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
					<button type="submit" class="btn btn-primary btn-sm mk-btn-primary" style="width:100%;">Apply filters</button>
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

	<div class="eval-kpi-row">
		<div class="eval-kpi">
			<div class="eval-kpi__label">Total campaigns</div>
			<div class="eval-kpi__value">{$KPI_TOTAL_CAMPAIGNS}</div>
			<div class="eval-kpi__hint">Matching current filters</div>
		</div>
		<div class="eval-kpi">
			<div class="eval-kpi__label">Total cost</div>
			<div class="eval-kpi__value" id="evalTotalCost">{$KPI_TOTAL_COST|string_format:"%.0f"}</div>
			<div class="eval-kpi__hint">Sum of effective costs</div>
		</div>
		<div class="eval-kpi">
			<div class="eval-kpi__label">Total revenue</div>
			<div class="eval-kpi__value" id="evalTotalRevenue">{$KPI_TOTAL_REVENUE|string_format:"%.0f"}</div>
			<div class="eval-kpi__hint">Sum of effective revenue</div>
		</div>
		<div class="eval-kpi eval-kpi--accent">
			<div class="eval-kpi__label">Average ROI</div>
			<div class="eval-kpi__value" id="evalAvgRoi">{$KPI_AVG_ROI|string_format:"%.2f"}%</div>
			<div class="eval-kpi__hint">Portfolio average (cost &gt; 0)</div>
		</div>
	</div>

	<div class="eval-insights">
		<div class="eval-insight">
			<div class="eval-insight__label">Best ROI</div>
			{if $INSIGHTS.best_roi}
				<div class="eval-insight__name">{$INSIGHTS.best_roi.campaignname|escape:'html'}</div>
				<div class="eval-insight__meta">{$INSIGHTS.best_roi.roi|string_format:"%.2f"}% ROI</div>
			{else}
				<div class="eval-insight__name eval-muted">—</div>
			{/if}
		</div>
		<div class="eval-insight">
			<div class="eval-insight__label">Lowest ROI</div>
			{if $INSIGHTS.worst_roi}
				<div class="eval-insight__name">{$INSIGHTS.worst_roi.campaignname|escape:'html'}</div>
				<div class="eval-insight__meta">{$INSIGHTS.worst_roi.roi|string_format:"%.2f"}% ROI</div>
			{else}
				<div class="eval-insight__name eval-muted">—</div>
			{/if}
		</div>
		<div class="eval-insight">
			<div class="eval-insight__label">Highest revenue</div>
			{if $INSIGHTS.highest_revenue}
				<div class="eval-insight__name">{$INSIGHTS.highest_revenue.campaignname|escape:'html'}</div>
				<div class="eval-insight__meta">{$INSIGHTS.highest_revenue.revenue|string_format:"%.0f"} revenue</div>
			{else}
				<div class="eval-insight__name eval-muted">—</div>
			{/if}
		</div>
		<div class="eval-insight">
			<div class="eval-insight__label">Highest cost</div>
			{if $INSIGHTS.highest_cost}
				<div class="eval-insight__name">{$INSIGHTS.highest_cost.campaignname|escape:'html'}</div>
				<div class="eval-insight__meta">{$INSIGHTS.highest_cost.cost|string_format:"%.0f"} cost</div>
			{else}
				<div class="eval-insight__name eval-muted">—</div>
			{/if}
		</div>
	</div>

	<div class="mk-grid-2" style="margin-bottom:16px;">
		<div class="mk-panel">
			<div class="mk-section-title">Cost vs revenue by campaign</div>
			<div class="eval-chart-wrap"><canvas id="evalCostRevenueChart" height="200"></canvas></div>
		</div>
		<div class="mk-panel">
			<div class="mk-section-title">ROI by campaign</div>
			<div class="eval-chart-wrap"><canvas id="evalRoiChart" height="200"></canvas></div>
		</div>
	</div>

	<div class="mk-panel" style="margin-bottom:16px;">
		<div class="eval-section-title">Campaign ROI ranking</div>
		<p class="eval-muted" style="margin:-12px 0 12px 0;">Top 20 campaigns by ROI — compare winners and laggards at a glance.</p>
		<div class="eval-chart-wrap eval-chart-wrap--rank"><canvas id="evalRoiRankingChart"></canvas></div>
	</div>

	{if $MONTHLY_SUMMARY|@count gt 0}
	<div class="mk-panel" style="margin-bottom:16px;">
		<div class="eval-section-title">Monthly roll-up</div>
		<p class="eval-muted" style="margin:-12px 0 12px 0;">Aggregated by campaign month</p>
		<div class="table-responsive eval-table-wrap">
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>Month</th>
						<th style="text-align:right;">Campaigns</th>
						<th style="text-align:right;">Cost</th>
						<th style="text-align:right;">Revenue</th>
						<th style="text-align:right;">Avg ROI</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$MONTHLY_SUMMARY item=row}
						<tr>
							<td>{$row.month|escape:'html'}</td>
							<td style="text-align:right;">{$row.total_campaigns}</td>
							<td style="text-align:right;">{$row.total_cost|string_format:"%.0f"}</td>
							<td style="text-align:right;">{$row.total_revenue|string_format:"%.0f"}</td>
							<td style="text-align:right;">{$row.avg_roi|string_format:"%.2f"}%</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
	{/if}

	<div class="mk-panel">
		<div class="eval-section-title">All campaigns</div>
		<p class="eval-muted" style="margin:-12px 0 12px 0;">Profit = revenue − cost. Rows tinted green/red by ROI sign.</p>
		<div class="table-responsive eval-table-wrap">
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>Campaign</th>
						<th>Status</th>
						<th>Created</th>
						<th style="text-align:right;">Cost</th>
						<th style="text-align:right;">Revenue</th>
						<th style="text-align:right;">Profit</th>
						<th style="text-align:right;">ROI %</th>
					</tr>
				</thead>
				<tbody>
					{if $CAMPAIGNS|@count gt 0}
						{foreach from=$CAMPAIGNS item=C}
							<tr class="{if $C.roi > 0.0001}eval-row--pos{elseif $C.roi < -0.0001}eval-row--neg{/if}">
								<td><a href="{$C.link|escape:'html'}" target="_blank" rel="noopener">{$C.campaignname|escape:'html'}</a></td>
								<td>{$C.campaignstatus|escape:'html'}</td>
								<td><span style="font-size:12px;">{$C.createdtime|escape:'html'}</span></td>
								<td style="text-align:right;">{$C.cost|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$C.revenue|string_format:"%.0f"}</td>
								<td style="text-align:right;">{$C.profit|string_format:"%.0f"}</td>
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
