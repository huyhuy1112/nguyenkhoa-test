{* Campaigns Edit/Create (MARKETING) — enterprise content wrapper (content area only). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Campaigns&view=List&app=MARKETING'}
<div class="mk-campx" id="mkCampEnterpriseRoot" data-mk-campx="1">
	<div class="mk-campx-hero" data-mk-campx-hero>
		<div class="mk-campx-hero__left">
			<div class="mk-campx-hero__titleRow">
				<div class="mk-campx-hero__titleWrap">
					<h1 class="mk-campx-hero__title" data-mk-campx-name>Campaign</h1>
					<div class="mk-campx-hero__meta">
						<span class="mk-campx-pill" data-mk-campx-type>Type</span>
						<span class="mk-campx-pill mk-campx-pill--muted" data-mk-campx-owner>Assigned</span>
					</div>
				</div>
			</div>
		</div>
		<div class="mk-campx-hero__right">
			<span class="mk-campx-badge" data-mk-campx-status>Planning</span>
			<div class="mk-campx-hero__actions">
				<button type="button" class="mk-campx-btn mk-campx-btn--primary" id="mkCampXSave">Save</button>
				<a class="mk-campx-btn mk-campx-btn--ghost" href="{$MK_LIST_URL}">Cancel</a>
			</div>
		</div>
	</div>

	<section class="mk-campx-kpis" aria-label="Campaign performance overview">
		<div class="mk-campx-kpi" data-kpi="budgetcost">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-credit-card"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Budget Cost</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>—</div>
			</div>
		</div>
		<div class="mk-campx-kpi" data-kpi="expectedrevenue">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-line-chart"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Expected Revenue</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>—</div>
			</div>
		</div>
		<div class="mk-campx-kpi" data-kpi="expectedroi">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-pie-chart"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Expected ROI</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>—</div>
			</div>
		</div>
		<div class="mk-campx-kpi" data-kpi="targetsize">
			<div class="mk-campx-kpi__ic" aria-hidden="true"><i class="fa fa-users"></i></div>
			<div class="mk-campx-kpi__body">
				<div class="mk-campx-kpi__label">Target Size</div>
				<div class="mk-campx-kpi__value" data-kpi-value>—</div>
				<div class="mk-campx-kpi__trend" data-kpi-trend>—</div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card" data-section="info">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><i class="fa fa-info-circle" aria-hidden="true"></i> Campaign information</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-formGrid" id="mkCampXInfoGrid"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid mk-campx-grid--two">
		<div class="mk-campx-card" data-section="expected">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><i class="fa fa-bullseye" aria-hidden="true"></i> Expected metrics</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-miniStats" id="mkCampXExpectedStats"></div>
			</div>
		</div>

		<div class="mk-campx-card" data-section="actual">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><i class="fa fa-bar-chart" aria-hidden="true"></i> Actual metrics</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-miniStats" id="mkCampXActualStats"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card" data-section="collab">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><i class="fa fa-comments-o" aria-hidden="true"></i> Notes & attachments</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-collab" id="mkCampXCollab"></div>
			</div>
		</div>
	</section>

	<section class="mk-campx-grid">
		<div class="mk-campx-card" data-section="phases">
			<header class="mk-campx-card__head">
				<div class="mk-campx-card__title"><i class="fa fa-tasks" aria-hidden="true"></i> Campaign phases</div>
				<div class="mk-campx-card__actions">
					<button type="button" class="mk-campx-miniBtn js-campaign-add-phase">Add phase</button>
					<button type="button" class="mk-campx-miniBtn js-campaign-remove-phase">Remove last</button>
					<span class="mk-campx-miniHint js-campaign-phase-hint"></span>
				</div>
			</header>
			<div class="mk-campx-card__body">
				<div class="mk-campx-accordion" id="mkCampXPhases"></div>
			</div>
		</div>
	</section>

	{* Keep native form in DOM for backend; JS will move field inputs into the enterprise sections. *}
	<div class="mk-campx-native" id="mkCampXNativeHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}

