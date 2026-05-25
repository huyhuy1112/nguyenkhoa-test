{strip}
<div class="mk-sf-faq-detail-hero__actions">
	<button type="button" class="mk-sf-faq-detail-hero__btn" disabled title="Coming soon">
		{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='star'}<span>Follow</span>
	</button>
	<a class="mk-sf-faq-detail-hero__btn" href="index.php?module=SupportFAQ&amp;view=Edit&amp;record={$RECORD_ID}&amp;app=SUPPORT">
		{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='edit'}<span>Edit</span>
	</a>
	<button type="button" class="mk-sf-faq-detail-hero__btn" id="mkSfFaqIncreaseOccurrence" data-increase-url="{$INCREASE_OCCURRENCE_URL|escape:'html'}">
		<span>Increase Occurrence</span>
	</button>
	<div class="mk-sf-faq-detail-more">
		<button type="button" class="mk-sf-faq-detail-hero__btn mk-sf-faq-detail-more__toggle" aria-expanded="false" aria-haspopup="true">
			{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='more'}<span>More</span>
		</button>
		<div class="mk-sf-faq-detail-more__menu" role="menu" hidden>
			<a role="menuitem" href="index.php?module=SupportFAQ&amp;view=List&amp;app=SUPPORT">Back to list</a>
			{if $RECORD_DATA.related_ticket_id gt 0}
				<a role="menuitem" href="{$RECORD_DATA.ticket_detail_url|escape:'html'}">View related ticket</a>
			{/if}
		</div>
	</div>
</div>
{/strip}
