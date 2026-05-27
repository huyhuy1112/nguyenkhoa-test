{strip}
{if !empty($MK_MODERN_ORG_CREATE)}
	{include file="partials/OrganizationMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}

