{* Campaigns Detail: restore Phase Progress dashboard + standard detail blocks *}
{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
	.cpd-wrap { padding: 12px 16px; }
	.cpd-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px; }
	.cpd-title { font-weight:600; font-size:13px; margin-bottom:10px; }
	.cpd-progress { height:16px; border-radius:999px; overflow:hidden; background:#f1f5f9; }
	.cpd-progress .bar { height:100%; line-height:16px; font-size:11px; font-weight:600; color:#0f172a; text-align:center; }
	.cpd-phase-grid { display:grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap:10px; }
	.cpd-card { border:1px solid #e5e7eb; border-radius:12px; padding:10px; cursor:pointer; background:#fff; }
	.cpd-card:hover { background:#f8fafc; border-color:#e2e8f0; }
	.cpd-card h5 { margin:0 0 8px 0; font-size:12px; font-weight:700; color:#0f172a; }
	.cpd-meta { font-size:11px; color:#64748b; display:flex; justify-content:space-between; gap:8px; }
	@media (max-width: 1100px) { .cpd-grid { grid-template-columns: 1fr; } .cpd-phase-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="mk">
<div class="cpd-wrap mk-page" id="CampaignPhaseDashboard"
	 data-start-date="{$RECORD->get('start_date')|escape:'html'}"
	 data-closing-date="{$RECORD->get('closingdate')|escape:'html'}">

	<div class="cpd-grid">
		<div class="mk-panel">
			<div class="mk-section-title">Time Progress</div>
			<div class="cpd-progress">
				<div class="bar js-time-progress" style="width:0%; background:rgba(59,130,246,0.35); border:1px solid rgba(59,130,246,0.9);" aria-valuenow="0">0%</div>
			</div>
			<div class="cpd-meta" style="margin-top:8px;">
				<div>Start: {$RECORD->get('start_date')|escape:'html'}</div>
				<div>End: {$RECORD->get('closingdate')|escape:'html'}</div>
			</div>
		</div>
		<div class="mk-panel">
			<div class="mk-section-title">Result Progress</div>
			<div class="cpd-progress">
				<div class="bar js-result-progress" style="width:0%; background:rgba(34,197,94,0.35); border:1px solid rgba(34,197,94,0.9);" aria-valuenow="0">0%</div>
			</div>
			<div class="cpd-meta" style="margin-top:8px;">
				<div>Total Expected: <span class="text-muted">sum(phase expected)</span></div>
				<div>Total Actual: <span class="text-muted">sum(phase actual)</span></div>
			</div>
		</div>
	</div>

	<div class="mk-panel" style="margin-bottom:12px;">
		<div class="mk-section-title">Campaign Phases</div>
		<div class="cpd-phase-grid">
			{for $i=1 to 5}
				{assign var=expField value="phase`$i`_expected"}
				{assign var=actField value="phase`$i`_actual"}
				{assign var=comField value="phase`$i`_comment"}
				{assign var=expVal value=$RECORD->get($expField)}
				{assign var=actVal value=$RECORD->get($actField)}
				{assign var=comVal value=$RECORD->get($comField)}
				<div class="cpd-card js-phase-card"
					 data-expected="{$expVal|escape:'html'}"
					 data-actual="{$actVal|escape:'html'}">
					<h5>Phase {$i}</h5>
					<div class="cpd-progress" style="margin-bottom:8px;">
						<div class="bar js-phase-progress" style="width:0%; background:rgba(148,163,184,0.35); border:1px solid rgba(148,163,184,0.9);" aria-valuenow="0">0%</div>
					</div>
					<div class="cpd-meta">
						<div>Exp: {$expVal|default:'0'|escape:'html'}</div>
						<div>Act: {$actVal|default:'0'|escape:'html'}</div>
					</div>
					{if $comVal}
						<div class="text-muted" style="font-size:11px; margin-top:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
							{$comVal|escape:'html'}
						</div>
					{/if}
				</div>
			{/for}
		</div>
	</div>

	<form id="detailView" method="POST">
		{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
	</form>
</div>
</div>

<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/PhaseProgress.js')}"></script>
{/strip}

