{strip}
{if !empty($MK_MODERN_SUPPORTFAQ_CREATE)}
	{include file="partials/FaqMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file=vtemplate_path('EditView.tpl','Vtiger')}
{/if}
{/strip}
