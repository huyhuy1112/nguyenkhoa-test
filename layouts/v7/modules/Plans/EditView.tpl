{strip}
{assign var=MK_IS_MARKETING value=($smarty.get.app eq 'MARKETING')}
{if $MK_IS_MARKETING && !empty($MK_PLANS_MODERN_CREATE)}
	{include file="partials/PlanMkCreate.tpl"|vtemplate_path:$MODULE}
{elseif $MK_IS_MARKETING}
	<div class="mk-planx mk-planx--legacy-edit" data-mk-planx="1">
		{include file=vtemplate_path('EditView.tpl','Vtiger')}
	</div>
{else}
	{include file=vtemplate_path('EditView.tpl','Vtiger')}
{/if}
{/strip}

