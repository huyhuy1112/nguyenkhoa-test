{strip}
<section class="mk-act-detail-top-card">
	{include file="partials/ActivityDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
	{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
</section>

<div class="mk-act-detail-tabs-wrap">
	<nav class="mk-act-detail-tabs" role="tablist" aria-label="Activity sections">
		<button type="button" class="mk-act-detail-tabs__btn is-active" role="tab" aria-selected="true" data-mk-act-tab="summary">Summary</button>
		<button type="button" class="mk-act-detail-tabs__btn" role="tab" aria-selected="false" data-mk-act-tab="details">Details</button>
	</nav>
	<div class="mk-act-detail-tabs__actions">
		<button type="button" class="mk-act-detail-tabs__btn mk-act-detail-tabs__btn--outline">
			{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='STAR'}<span>Follow</span>
		</button>
		<a class="mk-act-detail-tabs__btn mk-act-detail-tabs__btn--outline" href="index.php?module=Activities&amp;view=Edit&amp;record={$RECORD_ID}&amp;app=SUPPORT">
			{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}<span>Edit</span>
		</a>
		<div class="mk-act-detail-more">
			<button type="button" class="mk-act-detail-tabs__btn mk-act-detail-tabs__btn--outline mk-act-detail-more__toggle" aria-expanded="false" aria-haspopup="true">
				{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='MORE'}<span>More</span>
			</button>
			<div class="mk-act-detail-more__menu" role="menu" hidden>
				<a role="menuitem" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">Back to list</a>
				{if $RECORD_DATA.ticketid|default:0 gt 0}
					<a role="menuitem" href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$RECORD_DATA.ticketid}&amp;app=SUPPORT">View ticket</a>
				{/if}
			</div>
		</div>
	</div>
</div>

<div class="mk-act-detail-panels">
	<div class="mk-act-detail-panel is-active" id="mk-act-tab-summary" role="tabpanel">
		{include file="DetailViewSummary.tpl"|vtemplate_path:$MODULE}
	</div>
	<div class="mk-act-detail-panel" id="mk-act-tab-details" role="tabpanel" hidden>
		<section class="mk-act-detail-card">
			<header class="mk-act-detail-card__head">
				<h2 class="mk-act-detail-card__title">Details</h2>
			</header>
			<div class="mk-act-detail-card__body">
				<p class="mk-act-detail-muted">Additional activity metadata is shown on the Summary tab. Use Edit to change this record.</p>
			</div>
		</section>
	</div>
</div>
{/strip}