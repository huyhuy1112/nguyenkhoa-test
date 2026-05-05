{* Campaigns Detail: restore Phase Progress dashboard + standard detail blocks *}
{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
<style>
#CampaignPhaseDashboard .cpd-wrap { padding: 14px 18px; }
#CampaignPhaseDashboard .cpd-grid { display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:14px; }

/* Titles */
#CampaignPhaseDashboard .mk-section-title {
	font-size: 12px;
	font-weight: 800;
	letter-spacing: 0.02em;
	color: #0f172a;
	margin-bottom: 10px;
}

/* Premium panels (Time/Result) */
#CampaignPhaseDashboard .mk-panel {
	border: 1px solid rgba(226,232,240,0.95) !important;
	background: rgba(255,255,255,0.92) !important;
	border-radius: 16px !important;
	padding: 14px 14px !important;
	box-shadow: 0 12px 34px rgba(15,23,42,0.05) !important;
	transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease !important;
	position: relative;
}
#CampaignPhaseDashboard .mk-panel:hover {
	transform: translateY(-2px);
	box-shadow: 0 18px 48px rgba(15,23,42,0.08) !important;
	border-color: rgba(203,213,225,0.95) !important;
}

/* Progress bars */
#CampaignPhaseDashboard .cpd-progress {
	height: 18px;
	border-radius: 999px;
	overflow: hidden;
	background: linear-gradient(90deg, rgba(241,245,249,1), rgba(248,250,252,1));
	box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
}
#CampaignPhaseDashboard .cpd-progress .bar {
	height: 100%;
	line-height: 18px;
	font-size: 11px;
	font-weight: 800;
	color: rgba(15,23,42,0.92);
	text-align: center;
	transition: width 0.22s ease, filter 0.22s ease;
	white-space: nowrap;
	border-radius: 999px;
}

/* Gradient accents (JS sets only width/text) */
#CampaignPhaseDashboard .js-time-progress {
	background: linear-gradient(90deg, rgba(59,130,246,0.72), rgba(56,189,248,0.50));
}
#CampaignPhaseDashboard .js-result-progress {
	background: linear-gradient(90deg, rgba(34,197,94,0.70), rgba(16,185,129,0.50));
}
#CampaignPhaseDashboard .js-phase-progress {
	background: linear-gradient(90deg, rgba(148,163,184,0.60), rgba(100,116,139,0.52));
}

/* Subtle top glow strip */
#CampaignPhaseDashboard .mk-cp-glow {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 3px;
	border-radius: 16px 16px 0 0;
	background: rgba(59,130,246,0.45);
}
#CampaignPhaseDashboard .mk-cp-glow--time { background: rgba(56,189,248,0.55); }
#CampaignPhaseDashboard .mk-cp-glow--result { background: rgba(34,197,94,0.55); }
#CampaignPhaseDashboard .mk-cp-glow--phase { background: rgba(99,102,241,0.35); }

/* Phases */
#CampaignPhaseDashboard .cpd-phase-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	gap: 12px;
	align-items: stretch;
}
#CampaignPhaseDashboard .cpd-card {
	border: 1px solid rgba(226,232,240,0.95);
	border-radius: 16px;
	padding: 14px 12px;
	cursor: pointer;
	background: #ffffff;
	box-shadow: 0 10px 26px rgba(15,23,42,0.04);
	transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
	height: 100%;
	display: flex;
	flex-direction: column;
}
#CampaignPhaseDashboard .cpd-card:hover {
	transform: translateY(-3px);
	border-color: rgba(203,213,225,0.95);
	box-shadow: 0 18px 44px rgba(15,23,42,0.09);
}
#CampaignPhaseDashboard .cpd-card h5 {
	margin: 0 0 10px 0;
	font-size: 12px;
	font-weight: 900;
	color: #0f172a;
}
#CampaignPhaseDashboard .cpd-meta {
	font-size: 11px;
	color: #64748b;
	display: flex;
	justify-content: space-between;
	gap: 8px;
}
#CampaignPhaseDashboard .cp-meta-item{
	display: flex;
	flex-direction: column;
	gap: 2px;
}
#CampaignPhaseDashboard .cp-meta-label{
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 0.01em;
	color: rgba(100,116,139,0.92);
	text-transform: uppercase;
}
#CampaignPhaseDashboard .cp-meta-value{
	font-size: 12px;
	font-weight: 900;
	color: rgba(15,23,42,0.92);
}
#CampaignPhaseDashboard .cpd-comment {
	font-size: 11px;
	margin-top: 8px;
	white-space: normal;
	color: #64748b;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	word-break: break-word;
}

/* Campaign detail blocks: render as clean white cards */
#CampaignPhaseDashboard .mk-campaign-detail-blocks .block {
	margin-bottom: 12px;
	border-radius: 16px;
	border: 1px solid rgba(226,232,240,0.95);
	background: #fff;
	box-shadow: 0 12px 34px rgba(15,23,42,0.04);
	overflow: hidden;
}
#CampaignPhaseDashboard .mk-campaign-detail-blocks .block h4 {
	margin: 0;
	padding: 14px 16px;
	font-size: 13px;
	font-weight: 900;
	color: #0f172a;
	background: linear-gradient(90deg, rgba(99,102,241,0.08), rgba(59,130,246,0.05));
	border-bottom: 1px solid rgba(226,232,240,0.95);
	display: flex;
	align-items: center;
	gap: 8px;
}
#CampaignPhaseDashboard .mk-campaign-detail-blocks .block > hr {
	margin: 0;
	border: 0;
	border-top: 1px solid rgba(226,232,240,0.95);
}
#CampaignPhaseDashboard .mk-campaign-detail-blocks .blockData {
	padding: 14px 16px 16px 16px;
}
#CampaignPhaseDashboard .mk-campaign-detail-blocks table.detailview-table.no-border td {
	padding-top: 8px;
	padding-bottom: 8px;
}

.mk-campaign-description-files {
	border: 1px solid rgba(226,232,240,0.95);
	border-radius: 16px;
	background: #fff;
	padding: 14px 16px;
	margin-bottom: 12px;
	box-shadow: 0 12px 34px rgba(15,23,42,0.04);
}
.mk-campaign-description-files h4 {
	margin: 0 0 10px 0;
	font-size: 13px;
	font-weight: 900;
	color: #0f172a;
}

@media (max-width: 1100px) {
	#CampaignPhaseDashboard .cpd-grid { grid-template-columns: 1fr; }
	#CampaignPhaseDashboard .cpd-phase-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

<div class="mk">
<!-- CAMPAIGN_DETAIL_RENDER_PATH={$CAMPAIGN_DETAIL_RENDER_PATH|default:'unset'} -->
<!-- CAMPAIGN_FILES_COUNT={if isset($CAMPAIGN_FILES)}{$CAMPAIGN_FILES|@count}{else}0{/if} -->
<!-- CAMPAIGN_PHASE_INDICES={$CAMPAIGN_PHASE_INDICES_JSON|default:'[]'|escape:'html'} -->
<div class="cpd-wrap mk-page campaign-detail-modern" id="CampaignPhaseDashboard"
	 data-start-date="{$RECORD->get('start_date')|escape:'html'}"
	 data-closing-date="{$RECORD->get('closingdate')|escape:'html'}">

	<div class="cpd-grid">
		<div class="mk-panel">
			<div class="mk-cp-glow mk-cp-glow--time"></div>
			<div class="mk-section-title">Time Progress</div>
			<div class="cpd-progress">
				<div class="bar js-time-progress" style="width:0%;" aria-valuenow="0">0%</div>
			</div>
			<div class="cpd-meta" style="margin-top:10px;">
				<div class="cp-meta-item">
					<div class="cp-meta-label">Start</div>
					<div class="cp-meta-value">{$RECORD->get('start_date')|escape:'html'}</div>
				</div>
				<div class="cp-meta-item">
					<div class="cp-meta-label">End</div>
					<div class="cp-meta-value">{$RECORD->get('closingdate')|escape:'html'}</div>
				</div>
			</div>
		</div>
		<div class="mk-panel">
			<div class="mk-cp-glow mk-cp-glow--result"></div>
			<div class="mk-section-title">Result Progress</div>
			<div class="cpd-progress">
				<div class="bar js-result-progress" style="width:0%;" aria-valuenow="0">0%</div>
			</div>
			<div class="cpd-meta" style="margin-top:10px;">
				<div class="cp-meta-item">
					<div class="cp-meta-label">Total Expected</div>
					<div class="cp-meta-value"><span class="js-campaign-phase-sum-expected">{$CAMPAIGN_PHASE_SUMS.expected_fmt|escape:'html'}</span></div>
				</div>
				<div class="cp-meta-item">
					<div class="cp-meta-label">Total Actual</div>
					<div class="cp-meta-value"><span class="js-campaign-phase-sum-actual">{$CAMPAIGN_PHASE_SUMS.actual_fmt|escape:'html'}</span></div>
				</div>
			</div>
		</div>
	</div>

	<form id="detailView" method="POST">
		<div class="mk-campaign-detail-blocks">
			{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
		</div>

		{if isset($CAMPAIGN_PHASE_INDICES) && $CAMPAIGN_PHASE_INDICES|@count gt 0}
		<div class="mk-panel mk-panel--phases" style="margin-bottom:12px;">
			<div class="mk-cp-glow mk-cp-glow--phase"></div>
			<div class="mk-section-title mk-section-title--block">{vtranslate('LBL_CAMPAIGN_PHASES', $MODULE_NAME)}</div>
			<div class="cpd-phase-grid">
				{foreach from=$CAMPAIGN_PHASE_INDICES item=i}
					{assign var=expField value="phase`$i`_expected"}
					{assign var=actField value="phase`$i`_actual"}
					{assign var=comField value="phase`$i`_comment"}
					{assign var=sdField value="phase`$i`_start_date"}
					{assign var=edField value="phase`$i`_end_date"}
					{assign var=expVal value=$RECORD->get($expField)}
					{assign var=actVal value=$RECORD->get($actField)}
					{assign var=comVal value=$RECORD->get($comField)}
					{assign var=sdVal value=$RECORD->get($sdField)}
					{assign var=edVal value=$RECORD->get($edField)}
					<div class="cpd-card js-phase-card"
						 data-phase="{$i|escape:'html'}"
						 data-expected="{$expVal|escape:'html'}"
						 data-actual="{$actVal|escape:'html'}">
						<h5>Phase {$i}</h5>
						<div class="cpd-progress">
							<div class="bar js-phase-progress" style="width:0%;" aria-valuenow="0">0%</div>
						</div>
						<div class="cpd-meta">
							<div class="cp-meta-item">
								<div class="cp-meta-label">{vtranslate('LBL_PHASE_KPI_EXPECTED', $MODULE_NAME)}</div>
								<div class="cp-meta-value">{$expVal|default:'0'|escape:'html'}</div>
							</div>
							<div class="cp-meta-item">
								<div class="cp-meta-label">{vtranslate('LBL_PHASE_KPI_ACTUAL', $MODULE_NAME)}</div>
								<div class="cp-meta-value">{$actVal|default:'0'|escape:'html'}</div>
							</div>
						</div>
						{if $sdVal || $edVal}
							<div class="cpd-meta" style="margin-top:8px;">
								<div class="cp-meta-item">
									<div class="cp-meta-label">{vtranslate('LBL_PHASE_START', $MODULE_NAME)}</div>
									<div class="cp-meta-value">{$sdVal|escape:'html'}</div>
								</div>
								<div class="cp-meta-item">
									<div class="cp-meta-label">{vtranslate('LBL_PHASE_END', $MODULE_NAME)}</div>
									<div class="cp-meta-value">{$edVal|escape:'html'}</div>
								</div>
							</div>
						{/if}
						{if $comVal}
							<div class="cpd-comment">
								{$comVal|html_entity_decode|escape:'html'}
							</div>
						{/if}
					</div>
				{/foreach}
			</div>
		</div>
		{/if}

		{if isset($CAMPAIGN_RENDER_FILES_FALLBACK) && $CAMPAIGN_RENDER_FILES_FALLBACK}
			{include file='CampaignResultFilesBlock.tpl'|@vtemplate_path:$MODULE_NAME MODULE_NAME=$MODULE_NAME RECORD=$RECORD CAMPAIGN_FILES=$CAMPAIGN_FILES CAMPAIGN_DETAIL_DESC_FILES=$CAMPAIGN_DETAIL_DESC_FILES}
		{/if}
	</form>
</div>
</div>

<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/PhaseProgress.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignFilesDetail.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/DetailRoiInfo.js')}"></script>
{/strip}

