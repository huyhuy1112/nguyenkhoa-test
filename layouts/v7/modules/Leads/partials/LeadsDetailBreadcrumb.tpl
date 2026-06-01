{* Breadcrumb — Leads Detail (Sales + Marketing) *}
{strip}
{assign var=MK_CRUMB_APP value=$SELECTED_MENU_CATEGORY|default:$smarty.get.app|default:'SALES'}
<nav class="mk-leads-breadcrumb mk-leads-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-leads-detail-breadcrumb__list">
		{if $MK_CRUMB_APP eq 'MARKETING'}
		<li class="mk-leads-detail-breadcrumb__item">
			<a href="index.php?module=Leads&amp;view=List&amp;app=MARKETING">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		{else}
		<li class="mk-leads-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-leads-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-leads-detail-breadcrumb__item">
			<a href="index.php?module=Leads&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		{/if}
		<li class="mk-leads-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-leads-detail-breadcrumb__item mk-leads-detail-breadcrumb__item--current">
			<span class="mk-leads-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
