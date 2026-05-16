{* Breadcrumb for Sales Contact Detail. *}
{strip}
<nav class="mk-contact-breadcrumb mk-contact-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-contact-detail-breadcrumb__list">
		<li class="mk-contact-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-contact-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-contact-detail-breadcrumb__item">
			<a href="index.php?module=Contacts&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		<li class="mk-contact-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-contact-detail-breadcrumb__item mk-contact-detail-breadcrumb__item--current">
			<span class="mk-contact-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
