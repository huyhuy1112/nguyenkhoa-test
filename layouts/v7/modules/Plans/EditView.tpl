{strip}
{assign var=MK_IS_MARKETING value=($smarty.get.app eq 'MARKETING')}
{assign var=MK_IS_CREATE value=empty($RECORD_ID)}
{if $MK_IS_MARKETING && $MK_IS_CREATE}
	{include file="partials/PlanMkCreate.tpl"|vtemplate_path:$MODULE}
{else}
	{include file=vtemplate_path('EditView.tpl','Vtiger')}
{/if}
{/strip}

