{* Tickets list — breadcrumb + hero title + primary actions *}
{strip}
<div class="mk-hd-header">
	<nav class="mk-hd-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-hd-breadcrumb__list">
			<li class="mk-hd-breadcrumb__item">
				<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Tickets</a>
			</li>
			<li class="mk-hd-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-hd-breadcrumb__item mk-hd-breadcrumb__item--current">
				<span>All Tickets</span>
			</li>
		</ol>
	</nav>
	<header class="mk-hd-action-header" role="region" aria-label="Tickets">
		<div class="mk-hd-action-header__brand">
			<span class="mk-hd-action-header__icon" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='TICKET'}</span>
			<div class="mk-hd-action-header__text">
				<h1 class="mk-hd-action-header__title">All Tickets</h1>
				<p class="mk-hd-action-header__subtitle">Track support requests, SLA deadlines, and assignments in one place.</p>
			</div>
		</div>
		<div class="mk-hd-action-header__actions">
			<a class="mk-hd-btn mk-hd-btn--ghost" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT" title="Import">
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='IMPORT'}</span>
				<span class="mk-hd-btn__txt">Import</span>
			</a>
			<button type="button" class="mk-hd-btn mk-hd-btn--ghost mk-hd-btn--disabled" disabled title="Customize">
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CUSTOMIZE'}</span>
				<span class="mk-hd-btn__txt">Customize</span>
			</button>
			<a class="mk-hd-btn mk-hd-btn--primary" href="index.php?module=HelpDesk&amp;view=Edit&amp;app=SUPPORT">
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-hd-btn__txt">Add Ticket</span>
			</a>
		</div>
	</header>
</div>
{/strip}
