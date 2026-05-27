{strip}
{if !empty($MK_MODERN_OPPORTUNITY_CREATE)}
	{include file="partials/OpportunityMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
