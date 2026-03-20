{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
	.pm-wrap { padding: 0; }
	.pm-tabs { margin: 10px 0 12px 0; }
	.pm-table td, .pm-table th { vertical-align: middle !important; }
	.pm-day { padding: 10px; border-bottom: 1px solid #f1f5f9; }
	.pm-day:last-child { border-bottom: none; }
	.pm-day-title { font-weight: 900; font-size: 12px; color:#334155; margin-bottom: 8px; display:flex; justify-content:space-between; }
	.pm-schedule { position: relative; }
	.pm-schedule::before { content:""; position:absolute; left: 14px; top: 12px; bottom: 12px; width: 2px; background: #e5e7eb; border-radius: 999px; }
	.pm-item { padding: 10px 12px; border: 1px solid #eef2f7; border-radius: 12px; margin-bottom: 10px; cursor: pointer; background: #fff; }
	.pm-item:hover { background:#f8fafc; border-color:#e2e8f0; }
	.pm-item-name { font-weight: 900; font-size: 13px; color:#0f172a; }
	.pm-item-meta { font-size: 12px; color:#64748b; margin-top: 4px; display:flex; gap:8px; flex-wrap:wrap; }
	.pm-item-desc { line-height: 1.35; }
	.pm-item { position: relative; padding-left: 18px; }
	.pm-item::before { content:""; position:absolute; left: -2px; top: 14px; width: 10px; height: 10px; background: #fff; border: 2px solid #cbd5e1; border-radius: 999px; }
	.pm-item:hover::before { border-color: #94a3b8; }
	.pm-link { color:#2563eb; cursor:pointer; font-weight: 900; }
	.pm-link:hover { text-decoration: underline; }
	.pm-ext-link { color:#94a3b8; text-decoration:none; margin-left:6px; }
	.pm-ext-link:hover { color:#475569; text-decoration:none; }
	/* modal kv layout */
	.pm-modal-kv { display:flex; gap:10px; margin-bottom:8px; }
	.pm-modal-kv .pm-k { width: 64px; color:#334155; }
	.pm-modal-kv .pm-v { flex:1; color:#0f172a; }
</style>

<div class="mk">
<div class="mk-page" id="PlanManagerRoot" data-plan-id="{if $RECORD_ID}{$RECORD_ID}{else}{$RECORD->getId()}{/if}">
	<div class="mk-header">
		<div>
			<div class="mk-title">Plan Campaign Manager</div>
			<div class="mk-subtitle">
				{$RECORD->get('planname')|escape:'html'}{if $RECORD->get('plan_code')} • {$RECORD->get('plan_code')|escape:'html'}{/if}
			</div>
		</div>
		<div>
			<button type="button" class="btn btn-primary btn-sm mk-btn-primary" id="pmAddCampaignBtn">Add Campaign</button>
		</div>
	</div>

	<ul class="nav nav-tabs pm-tabs mk-tabs" role="tablist">
		<li role="presentation" class="active">
			<a href="#pmCampaignTab" aria-controls="pmCampaignTab" role="tab" data-toggle="tab">Campaign Management</a>
		</li>
		<li role="presentation">
			<a href="#pmScheduleTab" aria-controls="pmScheduleTab" role="tab" data-toggle="tab">Plan Schedule</a>
		</li>
	</ul>

	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="pmCampaignTab">
			<div class="mk-panel">
				<div class="mk-toolbar">
					<div class="mk-section-title" style="margin:0;">Campaigns in this Plan</div>
					<div class="text-muted" style="font-size:12px;">Add/remove campaigns without reloading</div>
				</div>
				<div class="table-responsive">
					<table class="table table-bordered table-striped table-condensed pm-table">
						<thead>
							<tr>
								<th>Campaign Name</th>
								<th>Start Date</th>
								<th>End Date</th>
								<th>Status</th>
								<th style="width:90px;">Actions</th>
							</tr>
						</thead>
						<tbody id="pmCampaignTableBody">
							<tr><td colspan="5" class="mk-empty">Loading...</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div role="tabpanel" class="tab-pane" id="pmScheduleTab">
			<div class="mk-panel">
				<div class="mk-section-title">Schedule</div>
				<div id="pmSchedule" class="pm-schedule"></div>
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

