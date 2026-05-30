{strip}
{if !empty($MK_MODERN_PROJECTTASK_CREATE)}
	{include file="partials/ProjectTaskMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
