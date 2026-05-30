{strip}
<div class="mk-eval-dashboard">
	{include file="partials/EvaluateListHeader.tpl"|vtemplate_path:$MODULE}

	<form method="get" class="mk-eval-filter" id="evalFilterForm">
		<input type="hidden" name="module" value="Evaluate" />
		<input type="hidden" name="view" value="List" />
		<input type="hidden" name="app" value="MARKETING" />
		<input type="hidden" name="filter_applied" value="1" />
		<div class="mk-eval-filter__grid">
			<div class="mk-eval-filter__field">
				<label for="evalCampaignSelect">Campaign name</label>
				<select id="evalCampaignSelect" name="campaignid" class="form-control">
					<option value="">{vtranslate('Campaigns', 'Campaigns')}</option>
					{foreach from=$CAMPAIGN_OPTIONS item=OPT}
						<option value="{$OPT.id}"{if $FILTER_CAMPAIGN_ID eq $OPT.id} selected="selected"{/if}>{$OPT.name|escape:'html'}</option>
					{/foreach}
				</select>
			</div>
			<div class="mk-eval-filter__field">
				<label for="evalFrom">From</label>
				<input type="date" id="evalFrom" name="from" value="{$FILTER_FROM|escape:'html'}" class="form-control" placeholder="MM/DD/YYYY" />
			</div>
			<div class="mk-eval-filter__field">
				<label for="evalTo">To</label>
				<input type="date" id="evalTo" name="to" value="{$FILTER_TO|escape:'html'}" class="form-control" placeholder="MM/DD/YYYY" />
			</div>
			<div class="mk-eval-filter__apply">
				<button type="submit" class="mk-eval-filter__btn">
					<span class="fa fa-filter" aria-hidden="true"></span>
					Apply filters
				</button>
			</div>
		</div>
		<div class="mk-eval-filter__status">
			<label class="mk-eval-filter__check">
				<input type="checkbox" name="in_progress" value="1"{if $FILTER_IN_PROGRESS} checked="checked"{/if} />
				In progress
			</label>
			<label class="mk-eval-filter__check">
				<input type="checkbox" name="completed" value="1"{if $FILTER_COMPLETED} checked="checked"{/if} />
				Completed
			</label>
		</div>
	</form>

	<div class="mk-eval-kpi-row">
		<div class="mk-eval-kpi">
			<div class="mk-eval-kpi__label">Total campaigns</div>
			<div class="mk-eval-kpi__value">{$KPI_TOTAL_CAMPAIGNS}</div>
		</div>
		<div class="mk-eval-kpi">
			<div class="mk-eval-kpi__label">Completed</div>
			<div class="mk-eval-kpi__value">{$KPI_COMPLETED}</div>
		</div>
		<div class="mk-eval-kpi">
			<div class="mk-eval-kpi__label">Completion rate</div>
			<div class="mk-eval-kpi__value">{$KPI_COMPLETION_RATE|string_format:"%.1f"}%</div>
		</div>
		<div class="mk-eval-kpi mk-eval-kpi--accent">
			<div class="mk-eval-kpi__label">Total planned cost</div>
			<div class="mk-eval-kpi__value">{$KPI_TOTAL_PLANNED_COST|string_format:"%.0f"}</div>
		</div>
	</div>

	<div class="mk-eval-charts-2">
		<div class="mk-eval-panel">
			<h2 class="mk-eval-panel__title">Cost vs revenue by campaign</h2>
			<div class="mk-eval-chart-wrap"><canvas id="evalCostRevenueChart" height="200"></canvas></div>
		</div>
		<div class="mk-eval-panel">
			<h2 class="mk-eval-panel__title">ROI by campaign</h2>
			<div class="mk-eval-chart-wrap"><canvas id="evalRoiChart" height="200"></canvas></div>
		</div>
	</div>

	<div class="mk-eval-panel">
		<h2 class="mk-eval-panel__title">Campaign ROI ranking</h2>
		<p class="mk-eval-panel__sub">Top 20 campaigns by ROI — compare winners and laggards at a glance.</p>
		<div class="mk-eval-chart-wrap mk-eval-chart-wrap--rank"><canvas id="evalRoiRankingChart"></canvas></div>
	</div>

	{if $MONTHLY_SUMMARY|@count gt 0}
	<div class="mk-eval-panel">
		<h2 class="mk-eval-panel__title">Monthly roll-up</h2>
		<p class="mk-eval-panel__sub">Aggregated by campaign month</p>
		<div class="table-responsive mk-eval-table-wrap">
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
							<td style="text-align:right;" class="{if $row.avg_roi > 0}mk-eval-roi-pos{elseif $row.avg_roi < 0}mk-eval-roi-neg{/if}">{$row.avg_roi|string_format:"%.2f"}%</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
	{/if}

	<div class="mk-eval-panel mk-eval-list-section">
		<div class="mk-eval-list-toolbar">
			<span>Showing {$LIST_FROM} to {$LIST_TO} of {$LIST_TOTAL} campaigns</span>
			<a href="index.php?module=Campaigns&amp;view=List&amp;app=MARKETING" class="btn btn-default btn-sm">Open full list</a>
		</div>
		<div class="table-responsive mk-eval-table-wrap" style="border-top:none;border-radius:0 0 12px 12px;">
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>Campaign name</th>
						<th>Type</th>
						<th>Status</th>
						<th style="text-align:right;">Expected revenue</th>
						<th>Close date</th>
						<th>Assigned to</th>
						<th style="text-align:right;">ROI %</th>
					</tr>
				</thead>
				<tbody>
					{if $LIST_CAMPAIGNS|@count gt 0}
						{foreach from=$LIST_CAMPAIGNS item=C}
							<tr class="{if $C.roi > 0.0001}eval-row--pos{elseif $C.roi < -0.0001}eval-row--neg{/if}">
								<td><a href="{$C.link|escape:'html'}">{$C.campaignname|escape:'html'}</a></td>
								<td>
									{if $C.campaigntype neq ''}
										<span class="mk-eval-type-tag">{$C.campaigntype|escape:'html'}</span>
									{else}
										<span class="text-muted">—</span>
									{/if}
								</td>
								<td>
									<span class="mk-eval-status">
										<span class="mk-eval-status__dot mk-eval-status__dot--{$C.status_class|escape:'html'}" aria-hidden="true"></span>
										{$C.campaignstatus|escape:'html'}
									</span>
								</td>
								<td style="text-align:right;">{$C.revenue|string_format:"%.0f"}</td>
								<td>{$C.closingdate_display|escape:'html'}</td>
								<td>{$C.assigned_to|escape:'html'}</td>
								<td style="text-align:right;" class="{if $C.roi > 0}mk-eval-roi-pos{elseif $C.roi < 0}mk-eval-roi-neg{/if}">{$C.roi|string_format:"%.2f"}</td>
							</tr>
						{/foreach}
					{else}
						<tr><td colspan="7" class="mk-eval-empty">No campaigns matched your filters.</td></tr>
					{/if}
				</tbody>
			</table>
		</div>
		{if $LIST_TOTAL_PAGES gt 1}
		<nav class="mk-eval-pagination" aria-label="Campaign pagination">
			{if $LIST_PAGE gt 1}
				<a href="{$LIST_PAGE_URL_PREFIX}&amp;list_page={$LIST_PAGE-1}" aria-label="Previous">&lsaquo;</a>
			{else}
				<span class="is-disabled" aria-hidden="true">&lsaquo;</span>
			{/if}
			{foreach from=$LIST_PAGE_NUMBERS item=PN}
				{if $PN eq '…'}
					<span>…</span>
				{elseif $PN eq $LIST_PAGE}
					<span class="is-active" aria-current="page">{$PN}</span>
				{else}
					<a href="{$LIST_PAGE_URL_PREFIX}&amp;list_page={$PN}">{$PN}</a>
				{/if}
			{/foreach}
			{if $LIST_PAGE lt $LIST_TOTAL_PAGES}
				<a href="{$LIST_PAGE_URL_PREFIX}&amp;list_page={$LIST_PAGE+1}" aria-label="Next">&rsaquo;</a>
			{else}
				<span class="is-disabled" aria-hidden="true">&rsaquo;</span>
			{/if}
		</nav>
		{/if}
	</div>
</div>

<script type="application/json" id="EvaluateDashboardData">{$EVALUATE_DATA nofilter}</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Evaluate/resources/EvaluateDashboard.js')}?mk_v=dark_global_v4"></script>
{/strip}
