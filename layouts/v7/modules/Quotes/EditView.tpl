{strip}
{if !empty($MK_MODERN_QUOTE_CREATE)}
	{include file="partials/QuoteMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Inventory'}
{/if}
{/strip}
