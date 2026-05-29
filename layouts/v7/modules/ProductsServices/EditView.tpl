{strip}
{if !empty($MK_MODERN_PRODUCTSSERVICES_CREATE)}
	{include file="partials/ProductsServicesMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProductsServices/resources/Edit.js')}"></script>
{/if}
{/strip}
