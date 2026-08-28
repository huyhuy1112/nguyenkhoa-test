{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	{include file='partials/RelatedActivitiesSALES.tpl'|@vtemplate_path:'ProductsServices'}
{else}
	{include file='RelatedActivities.tpl'|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
