{strip}
<nav class="mk-ps-breadcrumb mk-ps-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-ps-detail-breadcrumb__list">
		<li class="mk-ps-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">Sales</a>
		</li>
		<li class="mk-ps-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-ps-detail-breadcrumb__item">
			<a href="index.php?module=ProductsServices&amp;view=List&amp;app=SALES">Products &amp; Services</a>
		</li>
		<li class="mk-ps-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-ps-detail-breadcrumb__item mk-ps-detail-breadcrumb__item--current">
			<span class="mk-ps-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
