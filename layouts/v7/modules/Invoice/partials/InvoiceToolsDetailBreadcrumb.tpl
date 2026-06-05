{strip}
{assign var=MK_APP value=$SELECTED_MENU_CATEGORY|default:'TOOLS'}
<nav class="mk-inv-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-inv-detail-breadcrumb__list">
		<li class="mk-inv-detail-breadcrumb__item">
			<a href="index.php?module=Invoice&amp;view=List&amp;app={$MK_APP}">{vtranslate($MODULE, $MODULE)}</a>
		</li>
		<li class="mk-inv-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-inv-detail-breadcrumb__item">
			<a href="index.php?module=Invoice&amp;view=List&amp;app={$MK_APP}">{vtranslate('LBL_ALL', $MODULE)}</a>
		</li>
		<li class="mk-inv-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-inv-detail-breadcrumb__item mk-inv-detail-breadcrumb__item--current">
			<span class="mk-inv-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
