{* Plans Summary — KPIs, navy timeline strip, campaign table (Figma) *}
{strip}
<div class="pm-wrap" id="PlanManagerRoot" data-plan-id="{if $RECORD_ID}{$RECORD_ID}{else}{$RECORD->getId()}{/if}">

	<div class="pm-kpi-row" id="pmKpiRow" aria-live="polite">
		<div class="pm-kpi">
			<div class="pm-kpi__label">Total campaigns</div>
			<div class="pm-kpi__value" id="pmKpiTotal">—</div>
		</div>
		<div class="pm-kpi">
			<div class="pm-kpi__label">Completed</div>
			<div class="pm-kpi__value" id="pmKpiCompleted">—</div>
		</div>
		<div class="pm-kpi">
			<div class="pm-kpi__label">Completion rate</div>
			<div class="pm-kpi__value" id="pmKpiRate">—</div>
		</div>
		<div class="pm-kpi pm-kpi--accent">
			<div class="pm-kpi__label">Total planned cost</div>
			<div class="pm-kpi__value" id="pmKpiCost">—</div>
		</div>
	</div>

	<div class="pm-summary-banner" id="pmInsightStrip" aria-live="polite">
		<div class="pm-summary-banner__item">
			<span class="pm-summary-banner__label">Earliest start</span>
			<span class="pm-summary-banner__value" id="pmInEarliest">—</span>
		</div>
		<div class="pm-summary-banner__item">
			<span class="pm-summary-banner__label">Latest end</span>
			<span class="pm-summary-banner__value" id="pmInLatest">—</span>
		</div>
		<div class="pm-summary-banner__item">
			<span class="pm-summary-banner__label">Longest run</span>
			<span class="pm-summary-banner__value" id="pmInLongest">—</span>
		</div>
		<div class="pm-summary-banner__item pm-summary-banner__item--mix">
			<span class="pm-summary-banner__label">Campaign mix</span>
			<div class="pm-summary-banner__mix">
				<div class="pm-mix-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
					<div class="pm-mix-bar__fill" id="pmMixBarFill" style="width:50%;"></div>
				</div>
				<span class="pm-summary-banner__value" id="pmInMix">—</span>
			</div>
		</div>
	</div>

	<div class="pm-panel">
		<div class="pm-panel__head">
			<ul class="nav nav-tabs pm-tabs" role="tablist">
				<li role="presentation" class="active">
					<a href="#pmCampaignTab" aria-controls="pmCampaignTab" role="tab" data-toggle="tab">Campaign Management</a>
				</li>
				<li role="presentation">
					<a href="#pmScheduleTab" aria-controls="pmScheduleTab" role="tab" data-toggle="tab">Plan Schedule</a>
				</li>
			</ul>
			<button type="button" class="pm-btn-add" id="pmAddCampaignBtn">+ Add Campaign</button>
		</div>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="pmCampaignTab">
				<div class="table-responsive pm-table-wrap">
					<table class="table table-condensed pm-table">
						<thead>
							<tr>
								<th>Campaign name</th>
								<th>Start</th>
								<th>End</th>
								<th>Status</th>
								<th class="pm-num">Cost</th>
								<th class="pm-num">Revenue</th>
								<th class="pm-num">ROI %</th>
								<th class="pm-actions">Actions</th>
							</tr>
						</thead>
						<tbody id="pmCampaignTableBody">
							<tr><td colspan="8" class="pm-empty">Loading…</td></tr>
						</tbody>
					</table>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane pm-schedule-pane" id="pmScheduleTab">
				<p class="pm-schedule-intro">Grouped by start date. Click a card for campaign details.</p>
				<div id="pmSchedule" class="pm-timeline" role="list"></div>
			</div>
		</div>
	</div>
</div>

<script type="application/json" id="PlanCampaignData">{$CAMPAIGNS_JSON nofilter}</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Plans/resources/PlanManager.js')}"></script>
{/strip}
