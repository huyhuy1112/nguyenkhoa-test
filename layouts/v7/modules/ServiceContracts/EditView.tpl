{strip}
{if !empty($MK_MODERN_SERVICE_CONTRACT_CREATE)}
	{include file="partials/ServiceContractMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
