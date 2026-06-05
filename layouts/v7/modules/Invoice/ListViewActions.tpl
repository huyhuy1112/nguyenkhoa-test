{strip}
{assign var=MK_INV_MK_LIST value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_INV_MK_LIST value=true}
{/if}
{if $MK_INV_MK_LIST}
	{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' hóa đơn'}
	{include file="partials/OpportunityListViewActions.tpl"|vtemplate_path:'Potentials'}
{else}
	{include file="ListViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
