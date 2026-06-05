{* DocumentTemplate list header (TOOLS) *}
{strip}
<div class="mk-dt-header">
	<nav class="mk-dt-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php?module=Home&amp;view=DashBoard&amp;app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
		<span class="mk-dt-breadcrumb__sep">&gt;</span>
		<span>Templates</span>
	</nav>
	<header class="mk-dt-action-header" role="region" aria-label="Document Templates">
		<div class="mk-dt-action-header__text">
			<div class="mk-dt-eyebrow">Document Template</div>
			<h1 class="mk-dt-action-header__title">Templates</h1>
			<p class="mk-dt-action-header__subtitle">Features (Invoice / Quote / Contract)</p>
		</div>
		<div class="mk-dt-action-header__actions">
			<a href="index.php?module=DocumentTemplate&amp;view=Edit&amp;app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}TOOLS{/if}" class="mk-dt-create-btn" title="Create template">
				<i class="fa fa-plus" aria-hidden="true"></i>
				<span>Tạo mẫu</span>
			</a>
		</div>
	</header>
	{if $FEATURES|@count gt 0}
		<div class="mk-dt-feature-tabs" role="tablist" aria-label="Filter by feature">
			<a class="mk-dt-feature-tab{if $FILTER_FEATURE eq ''} is-active{/if}"
			   role="tab"
			   aria-selected="{if $FILTER_FEATURE eq ''}true{else}false{/if}"
			   href="index.php?module=DocumentTemplate&amp;view=List&amp;app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}TOOLS{/if}{if $FILTER_SEARCH}&amp;search={$FILTER_SEARCH|escape:'url'}{/if}">
				All Features
			</a>
			{foreach from=$FEATURES item=F}
				{if $F ne 'Other'}
					<a class="mk-dt-feature-tab{if $FILTER_FEATURE eq $F} is-active{/if}"
					   role="tab"
					   aria-selected="{if $FILTER_FEATURE eq $F}true{else}false{/if}"
					   href="index.php?module=DocumentTemplate&amp;view=List&amp;feature={$F}&amp;app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}TOOLS{/if}{if $FILTER_SEARCH}&amp;search={$FILTER_SEARCH|escape:'url'}{/if}">
						{$F}
					</a>
				{/if}
			{/foreach}
		</div>
	{/if}
</div>
{/strip}
