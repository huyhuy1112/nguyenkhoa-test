{* Recycle Bin header (TOOLS) + module switcher *}
{strip}
<div class="mk-rb-header">
	<nav class="mk-rb-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php?module=Home&amp;view=DashBoard&amp;app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
		<span class="mk-rb-breadcrumb__sep">&gt;</span>
		<span>{vtranslate('RecycleBin', 'RecycleBin')}</span>
		{if $SOURCE_MODULE}
			<span class="mk-rb-breadcrumb__sep">&gt;</span>
			<span>{vtranslate($SOURCE_MODULE, $SOURCE_MODULE)}</span>
		{/if}
	</nav>
	<header class="mk-rb-action-header" role="region" aria-label="{vtranslate('RecycleBin', 'RecycleBin')}">
		<div class="mk-rb-action-header__text">
			<div class="mk-rb-eyebrow">{vtranslate('RecycleBin', 'RecycleBin')}</div>
			<h1 class="mk-rb-action-header__title">{vtranslate('RecycleBin', 'RecycleBin')}</h1>
			<p class="mk-rb-action-header__subtitle">
				{vtranslate('LBL_RESTORE', 'RecycleBin')} {vtranslate('LBL_OR', 'Vtiger')}
				{vtranslate('LBL_DELETE', 'RecycleBin')} bản ghi đã xóa
				{if $SOURCE_MODULE} — {vtranslate($SOURCE_MODULE, $SOURCE_MODULE)}{/if}
			</p>
		</div>
	</header>
	{if $MODULE_LIST|@count gt 0}
		<div class="mk-rb-module-tabs" role="tablist" aria-label="Deleted records by module">
			{foreach item=MODULEMODEL from=$MODULE_LIST}
				<a class="mk-rb-module-tab{if $MODULEMODEL->getName() eq $SOURCE_MODULE} is-active{/if}"
				   role="tab"
				   aria-selected="{if $MODULEMODEL->getName() eq $SOURCE_MODULE}true{else}false{/if}"
				   href="index.php?module=RecycleBin&amp;view=List&amp;sourceModule={$MODULEMODEL->getName()}&amp;app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}TOOLS{/if}">
					{vtranslate($MODULEMODEL->getName(), $MODULEMODEL->getName())}
				</a>
			{/foreach}
		</div>
	{/if}
</div>
{/strip}
