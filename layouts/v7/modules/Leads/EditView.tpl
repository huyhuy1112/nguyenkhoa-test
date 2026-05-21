{strip}
{if !empty($MK_MODERN_LEADS_CREATE)}
	{include file="partials/LeadsMkEdit.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
