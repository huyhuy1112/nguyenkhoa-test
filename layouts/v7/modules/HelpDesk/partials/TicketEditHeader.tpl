{strip}
<div class="mk-hd-header mk-hd-edit-header">
	<nav class="mk-hd-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-hd-breadcrumb__list">
			<li class="mk-hd-breadcrumb__item">
				<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Tickets</a>
			</li>
			<li class="mk-hd-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-hd-breadcrumb__item mk-hd-breadcrumb__item--current">
				<span>{if $MODE eq 'edit'}Edit{else}New{/if}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-hd-action-header mk-hd-action-header--light" role="region" aria-label="Ticket form">
		<div class="mk-hd-action-header__brand">
			<span class="mk-hd-action-header__icon" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='TICKET'}</span>
			<div class="mk-hd-action-header__text">
				<h1 class="mk-hd-action-header__title">{if $MODE eq 'edit'}Edit Ticket{else}Create Ticket{/if}</h1>
				<p class="mk-hd-action-header__subtitle">Log a support request with customer, priority, assignment, and attachments.</p>
				{if $TICKET && $TICKET.ticket_code}
					<div class="mk-hd-edit-header__code"><span class="mk-hd-chip">{$TICKET.ticket_code|escape:'html'}</span></div>
				{/if}
			</div>
		</div>
		<div class="mk-hd-action-header__actions">
			<a class="mk-hd-btn mk-hd-btn--ghost" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">
				<span class="mk-hd-btn__txt">Back to list</span>
			</a>
		</div>
	</header>
</div>
{/strip}
