{* Breadcrumb for Sales Service Contracts Detail (Contacts pattern). *}
{strip}
<nav class="mk-sc-breadcrumb mk-sc-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-sc-detail-breadcrumb__list">
		<li class="mk-sc-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-sc-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-sc-detail-breadcrumb__item">
			<a href="index.php?module=ServiceContracts&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		<li class="mk-sc-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-sc-detail-breadcrumb__item mk-sc-detail-breadcrumb__item--current">
			<span class="mk-sc-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
