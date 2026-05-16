{strip}
<nav class="mk-qt-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-qt-detail-breadcrumb__list">
		<li class="mk-qt-detail-breadcrumb__item">
			<a href="index.php?module=Quotes&amp;view=List&amp;app=SALES">{vtranslate($MODULE, $MODULE)}</a>
		</li>
		<li class="mk-qt-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-qt-detail-breadcrumb__item">
			<a href="index.php?module=Quotes&amp;view=List&amp;app=SALES">View Quote</a>
		</li>
		<li class="mk-qt-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-qt-detail-breadcrumb__item mk-qt-detail-breadcrumb__item--current">
			<span class="mk-qt-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
