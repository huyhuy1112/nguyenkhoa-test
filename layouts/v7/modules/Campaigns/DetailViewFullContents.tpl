{* Campaigns Detail — Figma layout (progress KPIs + blocks + phases) *}
{strip}
<div class="cpd-wrap campaign-detail-modern" id="CampaignPhaseDashboard"
	data-start-date="{$RECORD->get('start_date')|escape:'html'}"
	data-closing-date="{$RECORD->get('closingdate')|escape:'html'}">

	<div class="cpd-grid mk-camp-kpi-grid">
		<article class="mk-camp-kpi-card mk-camp-kpi-card--time">
			<header class="mk-camp-kpi-card__head">
				<span class="mk-camp-kpi-card__eyebrow mk-camp-kpi-card__eyebrow--blue">{vtranslate('LBL_CAMPAIGN_TIMELINE', $MODULE_NAME)}</span>
				<span class="mk-camp-kpi-card__label">{vtranslate('LBL_TIME_PROGRESS', $MODULE_NAME)}</span>
			</header>
			<div class="mk-camp-kpi-card__pct mk-camp-kpi-card__pct--blue js-time-progress-value">0%</div>
			<div class="cpd-progress mk-camp-kpi-card__bar">
				<div class="bar js-time-progress" style="width:0%;" aria-valuenow="0"></div>
			</div>
			<div class="mk-camp-kpi-meta mk-camp-kpi-meta--time">
				<div class="mk-camp-kpi-meta__box">
					<span class="mk-camp-kpi-meta__label">{vtranslate('LBL_START_DATE', $MODULE_NAME)}</span>
					<span class="mk-camp-kpi-meta__value">{$RECORD->getDisplayValue('start_date')|default:$RECORD->get('start_date')}</span>
				</div>
				<div class="mk-camp-kpi-meta__box">
					<span class="mk-camp-kpi-meta__label">{vtranslate('LBL_END_DATE', $MODULE_NAME)}</span>
					<span class="mk-camp-kpi-meta__value">{$RECORD->getDisplayValue('closingdate')|default:$RECORD->get('closingdate')}</span>
				</div>
			</div>
		</article>

		<article class="mk-camp-kpi-card mk-camp-kpi-card--result">
			<header class="mk-camp-kpi-card__head">
				<span class="mk-camp-kpi-card__eyebrow mk-camp-kpi-card__eyebrow--green">{vtranslate('LBL_PERFORMANCE_METRIC', $MODULE_NAME)}</span>
				<span class="mk-camp-kpi-card__label">{vtranslate('LBL_RESULT_PROGRESS', $MODULE_NAME)}</span>
			</header>
			<div class="mk-camp-kpi-card__pct mk-camp-kpi-card__pct--green js-result-progress-value">0%</div>
			<div class="cpd-progress mk-camp-kpi-card__bar">
				<div class="bar js-result-progress" style="width:0%;" aria-valuenow="0"></div>
			</div>
			<div class="mk-camp-kpi-meta mk-camp-kpi-meta--result">
				<div class="mk-camp-kpi-meta__box">
					<span class="mk-camp-kpi-meta__label">{vtranslate('LBL_EXPECTED', $MODULE_NAME)|default:'EXPECTED'}</span>
					<span class="mk-camp-kpi-meta__value js-campaign-phase-sum-expected">{$CAMPAIGN_PHASE_SUMS.expected_fmt|escape:'html'}</span>
				</div>
				<div class="mk-camp-kpi-meta__box">
					<span class="mk-camp-kpi-meta__label">{vtranslate('LBL_ACTUAL', $MODULE_NAME)}</span>
					<span class="mk-camp-kpi-meta__value js-campaign-phase-sum-actual">{$CAMPAIGN_PHASE_SUMS.actual_fmt|escape:'html'}</span>
				</div>
			</div>
		</article>
	</div>

	<form id="detailView" method="POST">
		<div class="mk-campaign-detail-blocks">
			{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
		</div>

		{if isset($CAMPAIGN_PHASE_INDICES) && $CAMPAIGN_PHASE_INDICES|@count gt 0}
		<section class="mk-camp-phases-panel">
			<h3 class="mk-camp-phases-panel__title">{vtranslate('LBL_CAMPAIGN_PHASES', $MODULE_NAME)}</h3>
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
					<article class="cpd-card js-phase-card"
						data-phase="{$i|escape:'html'}"
						data-expected="{$expVal|escape:'html'}"
						data-actual="{$actVal|escape:'html'}">
						<h4 class="cpd-card__title">Phase {$i}</h4>
						<div class="cpd-card__pct js-phase-progress-pct">0%</div>
						<div class="cpd-progress">
							<div class="bar js-phase-progress" style="width:0%;" aria-valuenow="0"></div>
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
							<div class="cpd-meta cpd-meta--dates">
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
							<div class="cpd-comment">{$comVal|html_entity_decode|escape:'html'}</div>
						{/if}
					</article>
				{/foreach}
			</div>
		</section>
		{/if}

		{if isset($CAMPAIGN_RENDER_FILES_FALLBACK) && $CAMPAIGN_RENDER_FILES_FALLBACK}
			{include file='CampaignResultFilesBlock.tpl'|@vtemplate_path:$MODULE_NAME MODULE_NAME=$MODULE_NAME RECORD=$RECORD CAMPAIGN_FILES=$CAMPAIGN_FILES CAMPAIGN_DETAIL_DESC_FILES=$CAMPAIGN_DETAIL_DESC_FILES}
		{/if}
	</form>
</div>

<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/PhaseProgress.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignFilesDetail.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/DetailRoiInfo.js')}"></script>
{/strip}
