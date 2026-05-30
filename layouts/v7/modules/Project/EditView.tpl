{strip}
{if !empty($MK_MODERN_PROJECT_CREATE)}
	{include file="partials/ProjectMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
