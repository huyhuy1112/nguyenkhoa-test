{strip}
<div class="mk-act-edit-header">
	<nav class="mk-act-edit-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-act-edit-breadcrumb__list">
			<li class="mk-act-edit-breadcrumb__item">
				<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Support</a>
			</li>
			<li class="mk-act-edit-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-act-edit-breadcrumb__item">
				<a href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">Activities</a>
			</li>
			<li class="mk-act-edit-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-act-edit-breadcrumb__item mk-act-edit-breadcrumb__item--current">
				<span>{if $MODE eq 'edit'}Edit{else}New{/if}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-act-edit-action-header" role="region" aria-label="Activity form">
		<div class="mk-act-edit-action-header__brand">
			<span class="mk-act-edit-action-header__icon" aria-hidden="true">{include file="partials/ActivityListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ACTIVITY'}</span>
			<div class="mk-act-edit-action-header__text">
				<h1 class="mk-act-edit-action-header__title">{if $MODE eq 'edit'}Edit Activity{else}Create Activity{/if}</h1>
				<p class="mk-act-edit-action-header__subtitle">Schedule follow-ups, meetings, gifts, and other support touchpoints.</p>
				{if $RECORD.activityid}
					<div class="mk-act-edit-header__code"><span class="mk-act-edit-chip">#{$RECORD.activityid|escape:'html'}</span></div>
				{/if}
			</div>
		</div>
		<div class="mk-act-edit-action-header__actions">
			{if $FROM_TICKET gt 0 && $RECORD.ticketid gt 0}
				<a class="mk-act-edit-btn mk-act-edit-btn--ghost" href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$RECORD.ticketid}&amp;app=SUPPORT">
					<span class="mk-act-edit-btn__txt">Back to ticket</span>
				</a>
			{else}
				<a class="mk-act-edit-btn mk-act-edit-btn--ghost" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">
					<span class="mk-act-edit-btn__txt">Back to list</span>
				</a>
			{/if}
		</div>
	</header>
</div>
{/strip}
