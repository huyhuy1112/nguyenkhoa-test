{* Breadcrumb strip for Sales Organizations Detail only (included from Accounts DetailViewPreProcess). *}
{strip}
<nav class="mk-acc-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-acc-detail-breadcrumb__list">
		<li class="mk-acc-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-acc-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-acc-detail-breadcrumb__item">
			<a href="index.php?module=Accounts&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		<li class="mk-acc-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-acc-detail-breadcrumb__item mk-acc-detail-breadcrumb__item--current">
			<span class="mk-acc-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
