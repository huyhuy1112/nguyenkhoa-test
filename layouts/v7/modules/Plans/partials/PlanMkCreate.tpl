{* Modern Create Plan (MARKETING): enterprise card wrapper while keeping native form. *}
{strip}
{assign var=MK_LIST_URL value="index.php?module=Plans&view=List&app=MARKETING"}

<div class="mk-planx" data-mk-planx="1">
	<div class="mk-planx-hero">
		<nav class="mk-planx-bc" aria-label="Breadcrumb">
			<a class="mk-planx-bc__link" href="{$MK_LIST_URL}">Plans</a>
			<span class="mk-planx-bc__sep" aria-hidden="true">/</span>
			<span class="mk-planx-bc__current">Adding new</span>
		</nav>

		<div class="mk-planx-hero__row">
			<div class="mk-planx-hero__left">
				<h1 class="mk-planx-title">Create Plan</h1>
				<div class="mk-planx-sub">Set up timeline, milestones and planning information</div>
			</div>

			<div class="mk-planx-hero__right">
				<button type="button" class="mk-planx-btn mk-planx-btn--primary" id="mkPlanXSaveTop">Save</button>
				<a class="mk-planx-btn mk-planx-btn--ghost" href="{$MK_LIST_URL}">Cancel</a>
			</div>
		</div>
	</div>

	<section class="mk-planx-summary" aria-label="Plan summary">
		<div class="mk-planx-summary__chips">
			<div class="mk-planx-chip mk-planx-chip--status" data-mk-planx-summary-status>
				<span class="mk-planx-chip__dot" aria-hidden="true"></span>
				<span class="mk-planx-chip__label">Status</span>
			</div>
			<div class="mk-planx-chip" data-mk-planx-summary-duration>
				<span class="mk-planx-chip__ic" aria-hidden="true"><i class="fa fa-clock-o"></i></span>
				<span class="mk-planx-chip__label">Duration</span>
				<span class="mk-planx-chip__value">—</span>
			</div>
			<div class="mk-planx-chip" data-mk-planx-summary-start>
				<span class="mk-planx-chip__ic" aria-hidden="true"><i class="fa fa-calendar"></i></span>
				<span class="mk-planx-chip__label">Start Date</span>
				<span class="mk-planx-chip__value">—</span>
			</div>
			<div class="mk-planx-chip" data-mk-planx-summary-end>
				<span class="mk-planx-chip__ic" aria-hidden="true"><i class="fa fa-calendar"></i></span>
				<span class="mk-planx-chip__label">End Date</span>
				<span class="mk-planx-chip__value">—</span>
			</div>
		</div>
	</section>

	<section class="mk-planx-layout" aria-label="Plan information and timeline">
		<div class="mk-planx-layout__left">
			<div class="mk-planx-card">
				<div class="mk-planx-card__head">
					<div class="mk-planx-card__title"><i class="fa fa-info-circle" aria-hidden="true"></i> Plan Information</div>
				</div>
				<div class="mk-planx-card__body">
					<div class="mk-planx-infoGrid" id="mkPlanXInfoGrid" aria-label="Plan fields"></div>
					<div class="mk-planx-infoFull" id="mkPlanXInfoFull" aria-label="Plan long fields"></div>

					{* Keep native vtiger form in DOM for backend; JS will move inputs into our grid *}
					<div class="mk-planx-native" id="mkPlanXNativeHost" data-mk-planx-native>
						{include file=vtemplate_path('EditView.tpl','Vtiger')}
					</div>
				</div>
			</div>
		</div>

		<div class="mk-planx-layout__right">
			<div class="mk-planx-card mk-planx-card--tight">
				<div class="mk-planx-card__head">
					<div class="mk-planx-card__title"><i class="fa fa-road" aria-hidden="true"></i> Timeline Preview</div>
				</div>
				<div class="mk-planx-card__body">
					<div class="mk-planx-timeline" data-mk-planx-timeline>
						<div class="mk-planx-timeline__bar">
							<div class="mk-planx-timeline__fill" data-mk-planx-timeline-fill></div>
						</div>
						<div class="mk-planx-timeline__milestones">
							<div class="mk-planx-tm" data-mk-planx-timeline-dot="start"><span class="mk-planx-tm__dot"></span><span class="mk-planx-tm__lbl">Start</span></div>
							<div class="mk-planx-tm" data-mk-planx-timeline-dot="mid"><span class="mk-planx-tm__dot"></span><span class="mk-planx-tm__lbl">In Progress</span></div>
							<div class="mk-planx-tm" data-mk-planx-timeline-dot="end"><span class="mk-planx-tm__dot"></span><span class="mk-planx-tm__lbl">End</span></div>
						</div>
						<div class="mk-planx-timeline__kvs">
							<div class="mk-planx-timeline__kv"><div class="mk-planx-timeline__k">Start Date</div><div class="mk-planx-timeline__v" data-mk-planx-timeline-start-date>—</div></div>
							<div class="mk-planx-timeline__kv"><div class="mk-planx-timeline__k">Current Progress</div><div class="mk-planx-timeline__v" data-mk-planx-timeline-progress>—</div></div>
							<div class="mk-planx-timeline__kv"><div class="mk-planx-timeline__k">End Date</div><div class="mk-planx-timeline__v" data-mk-planx-timeline-end-date>—</div></div>
						</div>
					</div>

					<div class="mk-planx-status">
						<div class="mk-planx-status__title">Plan Status</div>
						<div class="mk-planx-status__badge" data-mk-planx-status-badge></div>
						<div class="mk-planx-statusBadges" data-mk-planx-status-badges aria-label="Status badges"></div>
					</div>
				</div>
			</div>

			<div class="mk-planx-card mk-planx-card--tight mk-planx-rightCard" aria-label="Recent updates">
				<div class="mk-planx-card__head">
					<div class="mk-planx-card__title"><i class="fa fa-bell" aria-hidden="true"></i> Recent Updates</div>
				</div>
				<div class="mk-planx-card__body">
					<div class="mk-planx-activity__list mk-planx-activity__list--mini" data-mk-planx-activity></div>
				</div>
			</div>
		</div>
	</section>
</div>
{/strip}

