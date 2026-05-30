{* Activities list — breadcrumb + title + actions (light theme) *}
{strip}
<div class="mk-act-header">
	<nav class="mk-act-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-act-breadcrumb__list">
			<li class="mk-act-breadcrumb__item">
				<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Support</a>
			</li>
			<li class="mk-act-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-act-breadcrumb__item mk-act-breadcrumb__item--current">
				<span>Activities</span>
			</li>
		</ol>
	</nav>
	<header class="mk-act-action-header" role="region" aria-label="Activities">
		<div class="mk-act-action-header__brand">
			<span class="mk-act-action-header__icon" aria-hidden="true">{include file="partials/ActivityListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ACTIVITY'}</span>
			<div class="mk-act-action-header__text">
				<h1 class="mk-act-action-header__title">Activities</h1>
				<p class="mk-act-action-header__subtitle">Overview of support activities — tasks, events, and anniversaries.</p>
			</div>
		</div>
		<div class="mk-act-action-header__actions">
			<a class="mk-act-btn mk-act-btn--primary" href="index.php?module=Activities&amp;view=Edit&amp;app=SUPPORT" title="Add record">
				<span class="mk-act-btn__ic" aria-hidden="true">{include file="partials/ActivityListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-act-btn__txt">Add Record</span>
			</a>
		</div>
	</header>
</div>
{/strip}
