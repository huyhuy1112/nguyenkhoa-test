{strip}
<nav class="mk-so-breadcrumb mk-so-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-so-detail-breadcrumb__list">
		<li class="mk-so-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">Sales</a>
		</li>
		<li class="mk-so-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-so-detail-breadcrumb__item">
			<a href="index.php?module=SalesOrder&amp;view=List&amp;app=SALES">View Sale Order</a>
		</li>
		<li class="mk-so-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-so-detail-breadcrumb__item mk-so-detail-breadcrumb__item--current">
			<span class="mk-so-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
