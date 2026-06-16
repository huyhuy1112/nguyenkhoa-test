{* Project Detail breadcrumb — MANAGEMENT *}
{strip}
<nav class="mk-project-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-project-detail-breadcrumb__list">
		<li class="mk-project-detail-breadcrumb__item">
			<a href="index.php?module=Project&amp;view=List&amp;app=MANAGEMENT">{vtranslate($MODULE, $MODULE)}</a>
		</li>
		<li class="mk-project-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-project-detail-breadcrumb__item">
			<a href="index.php?module=Project&amp;view=List&amp;app=MANAGEMENT">All</a>
		</li>
		<li class="mk-project-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-project-detail-breadcrumb__item mk-project-detail-breadcrumb__item--current">
			<span class="mk-project-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
