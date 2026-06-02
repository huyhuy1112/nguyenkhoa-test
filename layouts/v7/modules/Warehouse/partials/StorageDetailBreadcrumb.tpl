{strip}
<nav class="mk-wh-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-wh-detail-breadcrumb__list">
		<li class="mk-wh-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
		</li>
		<li class="mk-wh-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-wh-detail-breadcrumb__item">
			<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Storage</a>
		</li>
		<li class="mk-wh-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-wh-detail-breadcrumb__item mk-wh-detail-breadcrumb__item--current">
			<span class="mk-wh-detail-breadcrumb__text textOverflowEllipsis" title="{decode_html($STOCK.product_name_display)|escape:'html'}">{decode_html($STOCK.product_name_display)|escape:'html'}</span>
		</li>
	</ol>
</nav>
{/strip}
