{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
	.pm-wrap { padding: 0; }
	.pm-page { max-width: 1400px; margin: 0 auto; }
	.pm-kpi-row {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 12px;
		margin-bottom: 14px;
	}
	@media (max-width: 991px) { .pm-kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
	@media (max-width: 575px) { .pm-kpi-row { grid-template-columns: 1fr; } }
	.pm-kpi {
		background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		padding: 14px 16px;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 8px 22px rgba(15, 23, 42, 0.06);
	}
	.pm-kpi__label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px; }
	.pm-kpi__value { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.15; letter-spacing: -0.02em; }
	.pm-kpi__hint { font-size: 11px; color: #94a3b8; margin-top: 6px; line-height: 1.35; }
	.pm-kpi--accent { border-color: #c7d2fe; background: linear-gradient(135deg, #eef2ff 0%, #ffffff 55%); }
	.pm-insights {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 10px;
		margin-bottom: 16px;
	}
	@media (max-width: 991px) { .pm-insights { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
	@media (max-width: 575px) { .pm-insights { grid-template-columns: 1fr; } }
	.pm-insight {
		background: #fff;
		border: 1px solid #e2e8f0;
		border-radius: 10px;
		padding: 10px 12px;
		font-size: 12px;
		color: #475569;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
	}
	.pm-insight__label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 4px; }
	.pm-insight__val { font-weight: 700; color: #0f172a; line-height: 1.35; word-break: break-word; }
	.pm-tabs-wrap { margin-bottom: 4px; }
	.pm-tabs.nav-tabs { border-bottom: 2px solid #e2e8f0; margin-bottom: 0; }
	.pm-tabs.nav-tabs > li > a {
		border: 1px solid transparent;
		border-radius: 10px 10px 0 0;
		padding: 11px 16px;
		font-weight: 700;
		color: #64748b;
		margin-right: 4px;
		background: transparent;
	}
	.pm-tabs.nav-tabs > li > a:hover { background: #f8fafc; color: #334155; border-color: #e2e8f0 #e2e8f0 transparent; }
	.pm-tabs.nav-tabs > li.active > a,
	.pm-tabs.nav-tabs > li.active > a:focus,
	.pm-tabs.nav-tabs > li.active > a:hover {
		color: #0f172a;
		font-weight: 800;
		background: #fff;
		border: 1px solid #e2e8f0;
		border-bottom-color: #fff;
		margin-bottom: -2px;
		padding-bottom: 12px;
		box-shadow: 0 -2px 0 #2563eb inset;
	}
	.pm-table-wrap { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
	.pm-table-wrap table { margin-bottom: 0; }
	.pm-table td, .pm-table th { vertical-align: middle !important; }
	.pm-table thead th {
		background: #f8fafc !important;
		font-size: 11px;
		text-transform: uppercase;
		letter-spacing: 0.03em;
		color: #64748b;
		font-weight: 800;
		border-bottom: 2px solid #e2e8f0 !important;
	}
	.pm-table tbody tr { transition: background 0.12s ease; }
	.pm-table tbody tr:hover { background: #f8fafc !important; }
	.pm-table tbody tr.pm-row--pos td { background: rgba(34, 197, 94, 0.05); }
	.pm-table tbody tr.pm-row--neg td { background: rgba(239, 68, 68, 0.05); }
	.pm-table tbody tr.pm-row--pos:hover td,
	.pm-table tbody tr.pm-row--neg:hover td { filter: brightness(0.98); }
	.pm-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
	.pm-actions { white-space: nowrap; }
	.pm-actions .btn { min-width: 72px; }
	/* Timeline */
	.pm-timeline { position: relative; }
	.pm-tg { margin-bottom: 28px; }
	.pm-tg:last-child { margin-bottom: 0; }
	.pm-tg__header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 14px;
		flex-wrap: wrap;
	}
	.pm-tg__date-badge {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		font-size: 13px;
		font-weight: 800;
		color: #0f172a;
		background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
		padding: 8px 14px;
		border-radius: 10px;
		border: 1px solid #e2e8f0;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
	}
	.pm-tg__count { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
	.pm-tg__list {
		position: relative;
		padding-left: 22px;
		margin-left: 6px;
		border-left: 3px solid #cbd5e1;
	}
	.pm-tl-card {
		position: relative;
		margin-bottom: 12px;
		padding: 14px 16px;
		background: #fff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
		cursor: pointer;
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
	}
	.pm-tl-card:last-child { margin-bottom: 0; }
	.pm-tl-card:hover {
		border-color: #cbd5e1;
		box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
	}
	.pm-tl-card::before {
		content: "";
		position: absolute;
		left: -28px;
		top: 20px;
		width: 12px;
		height: 12px;
		background: #fff;
		border: 2px solid #64748b;
		border-radius: 50%;
		box-shadow: 0 0 0 4px #f8fafc;
	}
	.pm-tl-card:hover::before { border-color: #2563eb; }
	.pm-item-name { font-weight: 800; font-size: 14px; color: #0f172a; line-height: 1.35; }
	.pm-item-meta { font-size: 12px; color: #64748b; margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
	.pm-item-desc { line-height: 1.45; font-size: 12px; color: #64748b; margin-top: 8px; }
	.pm-link { color: #2563eb; cursor: pointer; font-weight: 800; }
	.pm-link:hover { text-decoration: underline; }
	.pm-ext-link { color: #94a3b8; text-decoration: none; margin-left: 6px; }
	.pm-ext-link:hover { color: #475569; text-decoration: none; }
	.pm-modal-kv { display: flex; gap: 10px; margin-bottom: 8px; }
	.pm-modal-kv .pm-k { width: 64px; color: #334155; }
	.pm-modal-kv .pm-v { flex: 1; color: #0f172a; }
	.pm-section-hint { font-size: 12px; color: #94a3b8; font-weight: 600; margin: -6px 0 12px 0; }
</style>

<div class="mk">
<div class="mk-page pm-page" id="PlanManagerRoot" data-plan-id="{if $RECORD_ID}{$RECORD_ID}{else}{$RECORD->getId()}{/if}">
	<div class="mk-header">
		<div>
			<div class="mk-title">Plan overview</div>
			<div class="mk-subtitle">
				{$RECORD->get('planname')|escape:'html'}{if $RECORD->get('plan_code')} • {$RECORD->get('plan_code')|escape:'html'}{/if}
			</div>
		</div>
		<div>
			<button type="button" class="btn btn-primary btn-sm mk-btn-primary" id="pmAddCampaignBtn">Add Campaign</button>
		</div>
	</div>

	<div class="pm-kpi-row" id="pmKpiRow" aria-live="polite">
		<div class="pm-kpi">
			<div class="pm-kpi__label">Total campaigns</div>
			<div class="pm-kpi__value" id="pmKpiTotal">—</div>
			<div class="pm-kpi__hint">In this plan</div>
		</div>
		<div class="pm-kpi">
			<div class="pm-kpi__label">Completed</div>
			<div class="pm-kpi__value" id="pmKpiCompleted">—</div>
			<div class="pm-kpi__hint">Status indicates complete</div>
		</div>
		<div class="pm-kpi pm-kpi--accent">
			<div class="pm-kpi__label">Completion rate</div>
			<div class="pm-kpi__value" id="pmKpiRate">—</div>
			<div class="pm-kpi__hint">Share of completed</div>
		</div>
		<div class="pm-kpi">
			<div class="pm-kpi__label">Total planned cost</div>
			<div class="pm-kpi__value" id="pmKpiCost">—</div>
			<div class="pm-kpi__hint" id="pmKpiCostHint">From campaign records</div>
		</div>
	</div>

	<div class="pm-insights" id="pmInsightStrip" aria-live="polite">
		<div class="pm-insight">
			<div class="pm-insight__label">Earliest start</div>
			<div class="pm-insight__val" id="pmInEarliest">—</div>
		</div>
		<div class="pm-insight">
			<div class="pm-insight__label">Latest end</div>
			<div class="pm-insight__val" id="pmInLatest">—</div>
		</div>
		<div class="pm-insight">
			<div class="pm-insight__label">Longest run</div>
			<div class="pm-insight__val" id="pmInLongest">—</div>
		</div>
		<div class="pm-insight">
			<div class="pm-insight__label">Mix</div>
			<div class="pm-insight__val" id="pmInMix">—</div>
		</div>
	</div>

	<div class="pm-tabs-wrap">
		<ul class="nav nav-tabs pm-tabs" role="tablist">
			<li role="presentation" class="active">
				<a href="#pmCampaignTab" aria-controls="pmCampaignTab" role="tab" data-toggle="tab">Campaign Management</a>
			</li>
			<li role="presentation">
				<a href="#pmScheduleTab" aria-controls="pmScheduleTab" role="tab" data-toggle="tab">Plan Schedule</a>
			</li>
		</ul>
	</div>

	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="pmCampaignTab">
			<div class="mk-panel">
				<div class="mk-toolbar">
					<div>
						<div class="mk-section-title" style="margin:0;">Campaigns in this plan</div>
						<div class="pm-section-hint">Add or remove campaigns without a full page reload. Open a row for details.</div>
					</div>
				</div>
				<div class="table-responsive pm-table-wrap">
					<table class="table table-condensed pm-table">
						<thead>
							<tr>
								<th>Campaign</th>
								<th>Start</th>
								<th>End</th>
								<th>Status</th>
								<th class="pm-num">Cost</th>
								<th class="pm-num">Revenue</th>
								<th class="pm-num">ROI %</th>
								<th class="pm-actions" style="width:100px;">Actions</th>
							</tr>
						</thead>
						<tbody id="pmCampaignTableBody">
							<tr><td colspan="8" class="mk-empty">Loading…</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div role="tabpanel" class="tab-pane" id="pmScheduleTab">
			<div class="mk-panel">
				<div class="mk-section-title">Schedule timeline</div>
				<p class="pm-section-hint">Grouped by start date. Click a card for campaign details.</p>
				<div id="pmSchedule" class="pm-timeline"></div>
			</div>
		</div>
	</div>
</div>
</div>

<script type="application/json" id="PlanCampaignData">
{$CAMPAIGNS_JSON nofilter}
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Plans/resources/PlanManager.js')}"></script>
{/strip}
