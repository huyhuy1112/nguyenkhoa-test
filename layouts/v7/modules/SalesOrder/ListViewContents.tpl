{* SalesOrder ListViewContents: SALES or TOOLS modern list shell *}
{strip}
{assign var=MK_SO_IS_SALES value=false}
{assign var=MK_SO_IS_TOOLS value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES') || (isset($smarty.request.app) && $smarty.request.app eq 'SALES')}
	{assign var=MK_SO_IS_SALES value=true}
{/if}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'TOOLS') || (isset($smarty.get.app) && $smarty.get.app eq 'TOOLS') || (isset($smarty.request.app) && $smarty.request.app eq 'TOOLS')}
	{assign var=MK_SO_IS_TOOLS value=true}
{/if}
{if $MK_SO_IS_TOOLS}
	<div class="mk-so-page mk-so-list-sales-root mk-opportunity-page">
		{include file="partials/SalesOrderToolsListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-opportunity-table-card">
			{capture name=mk_so_tools_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_so_tools_lv}
		</div>
	</div>
{elseif $MK_SO_IS_SALES}
	<div class="mk-so-page mk-so-list-sales-root">
		{include file="partials/SalesOrderListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card">
			{capture name=mk_so_sales_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_so_sales_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
