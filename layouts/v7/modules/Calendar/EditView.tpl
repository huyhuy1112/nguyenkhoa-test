{strip}
{if !empty($MK_MODERN_ACTIVITY_CREATE)}
	{include file="partials/CalendarMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
