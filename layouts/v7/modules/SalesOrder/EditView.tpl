{strip}
{if !empty($MK_MODERN_SALES_ORDER_CREATE)}
	{include file="partials/SalesOrderMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Inventory'}
{/if}
{/strip}
