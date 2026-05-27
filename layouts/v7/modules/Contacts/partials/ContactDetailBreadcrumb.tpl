{* Breadcrumb — Contacts Detail (Sales + Marketing) *}
{strip}
{assign var=MK_CRUMB_APP value=$SELECTED_MENU_CATEGORY|default:$smarty.get.app|default:'SALES'}
<nav class="mk-contact-breadcrumb mk-contact-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-contact-detail-breadcrumb__list">
		{if $MK_CRUMB_APP eq 'MARKETING'}
		<li class="mk-contact-detail-breadcrumb__item">
			<a href="index.php?module=Contacts&amp;view=List&amp;app=MARKETING">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		{else}
		<li class="mk-contact-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-contact-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-contact-detail-breadcrumb__item">
			<a href="index.php?module=Contacts&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		{/if}
		<li class="mk-contact-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-contact-detail-breadcrumb__item mk-contact-detail-breadcrumb__item--current">
			<span class="mk-contact-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
