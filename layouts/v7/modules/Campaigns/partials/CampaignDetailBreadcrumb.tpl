{* Breadcrumb — Campaigns Detail (Marketing app) *}
{strip}
<nav class="mk-camp-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-camp-detail-breadcrumb__list">
		<li class="mk-camp-detail-breadcrumb__item">
			<a href="index.php?module=Campaigns&amp;view=List&amp;app=MARKETING">{vtranslate('Campaigns', 'Campaigns')}</a>
		</li>
		<li class="mk-camp-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-camp-detail-breadcrumb__item">
			<span>{vtranslate('LBL_VIEW_CAMPAIGN', 'Campaigns')}</span>
		</li>
		<li class="mk-camp-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-camp-detail-breadcrumb__item mk-camp-detail-breadcrumb__item--current">
			<span class="mk-camp-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
