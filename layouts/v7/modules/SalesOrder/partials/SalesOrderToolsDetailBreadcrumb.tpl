{* Tools app — SalesOrder detail breadcrumb *}
{strip}
<nav class="mk-so-tools-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-so-tools-breadcrumb__list">
		<li class="mk-so-tools-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
		</li>
		<li class="mk-so-tools-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-so-tools-breadcrumb__item">
			<a href="index.php?module=SalesOrder&amp;view=List&amp;app=TOOLS">{vtranslate('SalesOrder', 'SalesOrder')}</a>
		</li>
		<li class="mk-so-tools-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-so-tools-breadcrumb__item mk-so-tools-breadcrumb__item--current">
			<span class="mk-so-tools-breadcrumb__text" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()|escape:'html'}</span>
		</li>
	</ol>
</nav>
{/strip}
