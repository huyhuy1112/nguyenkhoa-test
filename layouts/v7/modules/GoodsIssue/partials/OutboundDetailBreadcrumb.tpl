{strip}
<nav class="mk-go-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-go-detail-breadcrumb__list">
		<li class="mk-go-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
		</li>
		<li class="mk-go-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-go-detail-breadcrumb__item">
			<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Outbound</a>
		</li>
		<li class="mk-go-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-go-detail-breadcrumb__item mk-go-detail-breadcrumb__item--current">
			<span class="mk-go-detail-breadcrumb__text textOverflowEllipsis" title="{decode_html($RECORD_DATA.subject)|escape:'html'}">{decode_html($RECORD_DATA.subject)|escape:'html'}</span>
		</li>
	</ol>
</nav>
{/strip}
