{* All Leads — SALES app *}
{strip}
<div class="mk-leads-header">
	<nav class="mk-leads-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-leads-breadcrumb__list">
			<li class="mk-leads-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
			</li>
			<li class="mk-leads-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-leads-breadcrumb__item mk-leads-breadcrumb__item--current">
				<span>{vtranslate('Leads', 'Leads')}</span>
			</li>
		</ol>
	</nav>

	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('Leads', 'Leads')}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">All Leads</h1>
			<p class="mk-leads-action-header__subtitle"><span>Tag-driven segmentation · </span><span id="mk-leads-count">21 of 21</span></p>
		</div>
		<div class="mk-leads-action-header__actions">
			<div class="mk-leads-search">
				<span class="mk-leads-search__ic" aria-hidden="true">
					{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='SEARCH'}
				</span>
				<input class="mk-leads-search__input" id="mk-leads-search" type="search" placeholder="Search name or phone..." value="" />
			</div>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Leads&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
				<span class="mk-leads-btn__txt">Create Lead</span>
			</button>
		</div>
	</header>
</div>
{/strip}
