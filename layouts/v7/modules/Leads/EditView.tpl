{strip}
{if !empty($MK_MODERN_LEADS_CREATE)}
	{if !empty($RECORD_ID) && empty($IS_DUPLICATE)}
		{include file="partials/LeadsMkEditStock.tpl"|vtemplate_path:$MODULE}
	{else}
		{include file="partials/LeadsMkEdit.tpl"|vtemplate_path:$MODULE}
	{/if}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
