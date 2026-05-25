{strip}
<header class="mk-hd-detail-hero" role="region" aria-label="Ticket">
	<div class="mk-hd-detail-hero__row">
		<div class="mk-hd-detail-hero__brand">
			<span class="mk-hd-detail-hero__icon" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='TICKET'}</span>
			<div class="mk-hd-detail-hero__text">
				<div class="mk-hd-detail-hero__title-row">
					<h1 class="mk-hd-detail-hero__title">{$TICKET.subject|escape:'html'}</h1>
				</div>
				<p class="mk-hd-detail-hero__meta">
					<span class="mk-hd-detail-hero__code-pill">{$TICKET.ticket_code|escape:'html'}</span>
					<span class="mk-hd-detail-hero__created">Created at {$CREATED_AT_FORMATTED|escape:'html'}</span>
				</p>
				<div class="mk-hd-detail-hero__badges">
					<span class="mk-hd-badge mk-hd-badge--status mk-hd-badge--status-{$TICKET.status|replace:' ':'_'|lower}">{$TICKET.status}</span>
					<span class="mk-hd-badge mk-hd-badge--label">Priority</span>
					<span class="mk-hd-badge mk-hd-badge--priority mk-hd-badge--priority-{$TICKET.priority|lower}">{$TICKET.priority}</span>
					<span class="mk-hd-badge mk-hd-badge--label">Status</span>
				</div>
			</div>
		</div>
		<div class="mk-hd-detail-hero__actions">
			<a class="mk-hd-btn mk-hd-btn--ghost" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='IMPORT'}</span>
				<span class="mk-hd-btn__txt">Import</span>
			</a>
			<button type="button" class="mk-hd-btn mk-hd-btn--ghost mk-hd-btn--disabled" disabled>
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CUSTOMIZE'}</span>
				<span class="mk-hd-btn__txt">Customize</span>
			</button>
			<a class="mk-hd-btn mk-hd-btn--primary" href="index.php?module=HelpDesk&amp;view=Edit&amp;record={$TICKET.id}&amp;app=SUPPORT">
				<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-hd-btn__txt">Add Ticket</span>
			</a>
		</div>
	</div>
</header>
{/strip}
