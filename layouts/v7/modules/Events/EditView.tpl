{strip}
{if !empty($MK_MODERN_ACTIVITY_CREATE) || !empty($MK_MODERN_EVENT_CREATE)}
	{include file="partials/EventMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
