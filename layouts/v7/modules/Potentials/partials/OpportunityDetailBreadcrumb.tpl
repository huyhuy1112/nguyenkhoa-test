{* Breadcrumb strip for Sales Opportunity Detail only. *}
{strip}
<nav class="mk-opportunity-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-opportunity-detail-breadcrumb__list">
		<li class="mk-opportunity-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-opportunity-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-opportunity-detail-breadcrumb__item">
			<a href="index.php?module=Potentials&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		<li class="mk-opportunity-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-opportunity-detail-breadcrumb__item mk-opportunity-detail-breadcrumb__item--current">
			<span class="mk-opportunity-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
