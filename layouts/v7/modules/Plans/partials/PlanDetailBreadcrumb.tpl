{* Breadcrumb — Plans Detail (Marketing app) *}
{strip}
<nav class="mk-plan-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-plan-detail-breadcrumb__list">
		<li class="mk-plan-detail-breadcrumb__item">
			<a href="index.php?module=Plans&amp;view=List&amp;app=MARKETING">{vtranslate('Plans', 'Plans')}</a>
		</li>
		<li class="mk-plan-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-plan-detail-breadcrumb__item">
			<span>{vtranslate('LBL_RECORDS_LIST', 'Plans')|default:'All Plans'}</span>
		</li>
		<li class="mk-plan-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-plan-detail-breadcrumb__item mk-plan-detail-breadcrumb__item--current">
			<span class="mk-plan-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
