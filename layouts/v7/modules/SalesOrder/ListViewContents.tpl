{* SalesOrder ListViewContents: SALES Figma list card shell *}
{strip}
{assign var=MK_SO_IS_SALES value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES') || (isset($smarty.request.app) && $smarty.request.app eq 'SALES')}
	{assign var=MK_SO_IS_SALES value=true}
{/if}
{if $MK_SO_IS_SALES}
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
