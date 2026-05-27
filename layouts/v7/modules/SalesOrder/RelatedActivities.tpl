{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{include file='partials/RelatedActivitiesSALES.tpl'|@vtemplate_path:'SalesOrder'}
{else}
	{include file='RelatedActivities.tpl'|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
