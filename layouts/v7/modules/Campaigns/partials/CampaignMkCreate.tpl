{* Campaigns Create (MARKETING) — suite card + stock vtiger EditView form *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Campaigns&view=List&app=MARKETING'}
<div class="mk-camp-page mk-camp-create" id="mkCampCreateWorkspace" data-mk-camp-create="1">
	<div class="mk-camp-suite-card">
		<header class="mk-camp-page-head">
			<nav class="mk-camp-breadcrumb" aria-label="Breadcrumb">
				<ol class="mk-camp-breadcrumb__list">
					<li class="mk-camp-breadcrumb__item">
						<a href="{$MK_LIST_URL}">Campaigns</a>
					</li>
					<li class="mk-camp-breadcrumb__sep" aria-hidden="true">&gt;</li>
					<li class="mk-camp-breadcrumb__item mk-camp-breadcrumb__item--current">
						<span>New</span>
					</li>
				</ol>
			</nav>
			<div class="mk-camp-action-header" role="region" aria-label="Campaign form">
				<div class="mk-camp-action-header__brand">
					<span class="mk-camp-action-header__icon" aria-hidden="true">
						<span class="mk-camp-icon-chip"><i class="fa fa-bullhorn"></i></span>
					</span>
					<div class="mk-camp-action-header__text">
						<h1 class="mk-camp-action-header__title">Create Campaign</h1>
						<p class="mk-camp-action-header__subtitle">Set up campaign details, target audience, and ROI expectations.</p>
					</div>
				</div>
				<div class="mk-camp-action-header__actions">
					<a class="mk-camp-btn mk-camp-btn--ghost" href="{$MK_LIST_URL}">Cancel</a>
					<button type="button" class="mk-camp-btn mk-camp-btn--primary" id="mkCampSaveTop" data-action="save">Save</button>
				</div>
			</div>
		</header>

		<div class="mk-camp-edit-content">
			<div class="mk-camp-form-host" id="mkCampFormHost">
				{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
			</div>
		</div>
	</div>
</div>
{/strip}

