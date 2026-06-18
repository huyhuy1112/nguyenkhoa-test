{* Campaigns Edit/Create (MARKETING) — luxury enterprise workspace *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Campaigns&view=List&app=MARKETING'}
{assign var=MK_IS_CREATE value=!isset($RECORD_ID) || empty($RECORD_ID)}

<div class="mk-campx{if $MK_IS_CREATE} mk-campx--create{/if}" id="mkCampEnterpriseRoot" data-mk-campx="1" data-mk-upload-limit-bytes="52428800"{if $MK_IS_CREATE} data-mk-campx-create="1"{/if}>
	<nav class="mk-campx-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-campx-breadcrumb__list">
			<li><a href="{$MK_LIST_URL}">Campaigns</a></li>
			<li aria-hidden="true" class="mk-campx-breadcrumb__sep">/</li>
			<li class="mk-campx-breadcrumb__current">{if $MK_IS_CREATE}New campaign{else}Edit{/if}</li>
		</ol>
	</nav>

	<div class="mk-campx-hero" data-mk-campx-hero>
		<div class="mk-campx-hero__glow" aria-hidden="true"></div>
		<div class="mk-campx-hero__left">
			<div class="mk-campx-hero__brand">
				<span class="mk-campx-hero__icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
				<div class="mk-campx-hero__titleWrap">
					<p class="mk-campx-hero__eyebrow">{if $MK_IS_CREATE}New campaign{else}Campaign workspace{/if}</p>
					<h1 class="mk-campx-hero__title" data-mk-campx-name>{if $MK_IS_CREATE}Create Campaign{else}Campaign{/if}</h1>
					<div class="mk-campx-hero__meta">
						<span class="mk-campx-pill mk-campx-pill--type" data-mk-campx-type>Type</span>
						<span class="mk-campx-pill mk-campx-pill--muted" data-mk-campx-owner>Assigned</span>
					</div>
				</div>
			</div>
		</div>
		<div class="mk-campx-hero__right">
			<span class="mk-campx-badge mk-campx-badge--blue" data-mk-campx-status>Planning</span>
			<div class="mk-campx-hero__actions">
				<a class="mk-campx-btn mk-campx-btn--ghost" href="{$MK_LIST_URL}">Cancel</a>
				<button type="button" class="mk-campx-btn mk-campx-btn--primary" id="mkCampXSave"><i class="fa fa-check" aria-hidden="true"></i> Save</button>
			</div>
		</div>
	</div>

	<section class="mk-campx-kpis" aria-label="Campaign performance overview">
		<div class="mk-campx-kpi mk-campx-kpi--budget" data-kpi="budgetcost" tabindex="0" role="button" title="Focus budget cost">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-credit-card"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Budget Cost</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>Enter value below</div>
			</div>
		</div>
		<div class="mk-campx-kpi mk-campx-kpi--revenue" data-kpi="expectedrevenue" tabindex="0" role="button" title="Focus expected revenue">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-line-chart"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Expected Revenue</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>Enter value below</div>
			</div>
		</div>
		<div class="mk-campx-kpi mk-campx-kpi--roi" data-kpi="expectedroi" tabindex="0" role="button" title="Focus expected ROI">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-pie-chart"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Expected ROI</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>Enter value below</div>
			</div>
		</div>
		<div class="mk-campx-kpi mk-campx-kpi--audience" data-kpi="targetsize" tabindex="0" role="button" title="Focus target size">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-users"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Target Size</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>Enter value below</div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card mk-campx-card--info" data-section="info">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><span class="mk-campx-card__dot" aria-hidden="true"></span> Campaign information</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-formGrid" id="mkCampXInfoGrid"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid mk-campx-grid--two">
		<div class="mk-campx-card mk-campx-card--expected" data-section="expected">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><span class="mk-campx-card__dot" aria-hidden="true"></span> Expected metrics</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-miniStats" id="mkCampXExpectedStats"></div>
			</div>
		</div>

		<div class="mk-campx-card mk-campx-card--actual" data-section="actual">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><span class="mk-campx-card__dot" aria-hidden="true"></span> Actual metrics</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-miniStats" id="mkCampXActualStats"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card mk-campx-card--collab" data-section="collab">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><span class="mk-campx-card__dot" aria-hidden="true"></span> Notes & attachments</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-collab" id="mkCampXCollab"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card mk-campx-card--phases" data-section="phases">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><span class="mk-campx-card__dot" aria-hidden="true"></span> Campaign phases</div>
				<div class="mk-campx-card__actions">
					<button type="button" class="mk-campx-miniBtn js-campaign-add-phase"><i class="fa fa-plus"></i> Add phase</button>
					<button type="button" class="mk-campx-miniBtn mk-campx-miniBtn--muted js-campaign-remove-phase">Remove last</button>
					<span class="mk-campx-miniHint js-campaign-phase-hint"></span>
				</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-accordion" id="mkCampXPhases"></div>
			</div>
		</div>
	</section>

	<div class="mk-campx-native" id="mkCampXNativeHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}
