{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' products'}
{include file="partials/MkSalesListViewActions.tpl"|vtemplate_path:'Vtiger'}
{/strip}
{else}
{include file="ListViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
