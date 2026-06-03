{strip}
<nav class="mk-gr-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-gr-detail-breadcrumb__list">
		<li class="mk-gr-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
		</li>
		<li class="mk-gr-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-gr-detail-breadcrumb__item">
			<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
		</li>
		<li class="mk-gr-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-gr-detail-breadcrumb__item mk-gr-detail-breadcrumb__item--current">
			<span class="mk-gr-detail-breadcrumb__text textOverflowEllipsis" title="{decode_html($RECORD_DATA.subject)|escape:'html'}">{decode_html($RECORD_DATA.subject)|escape:'html'}</span>
		</li>
	</ol>
</nav>
{/strip}
