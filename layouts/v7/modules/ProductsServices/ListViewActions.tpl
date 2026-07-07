{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
{strip}
{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' mặt hàng'}
{include file="partials/MkSalesListViewActions.tpl"|vtemplate_path:'Vtiger'}
{/strip}
{else}
{include file="ListViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
