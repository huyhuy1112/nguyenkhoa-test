{* Plans ListViewContents: MARKETING Figma list card shell (Campaigns pattern) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-so-page mk-so-list-sales-root mk-plan-page">
		{include file="partials/PlansListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-plan-table-card">
			{capture name=mk_plan_marketing_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_plan_marketing_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
