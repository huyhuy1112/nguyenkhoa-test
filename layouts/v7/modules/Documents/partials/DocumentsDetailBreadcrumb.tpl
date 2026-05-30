{* Documents Detail breadcrumb — MANAGEMENT *}
{strip}
<nav class="mk-documents-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-documents-detail-breadcrumb__list">
		<li class="mk-documents-detail-breadcrumb__item">
			<a href="index.php?module=Documents&amp;view=List&amp;app=MANAGEMENT">{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</a>
		</li>
		<li class="mk-documents-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-documents-detail-breadcrumb__item">
			<a href="index.php?module=Documents&amp;view=List&amp;app=MANAGEMENT">{vtranslate($MODULE, $MODULE)}</a>
		</li>
		<li class="mk-documents-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-documents-detail-breadcrumb__item mk-documents-detail-breadcrumb__item--current">
			<span class="mk-documents-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
