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
	{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' sale orders'}
	{include file="partials/OpportunityListViewActions.tpl"|vtemplate_path:'Potentials'}
{elseif $MK_SO_IS_SALES}
	{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' đơn hàng'}
	{include file="partials/MkSalesListViewActions.tpl"|vtemplate_path:'Vtiger'}
{else}
	{include file="ListViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
