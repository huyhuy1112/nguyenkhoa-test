{strip}
{if !empty($MK_MODERN_CONTACT_CREATE)}
	{include file="partials/ContactMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
