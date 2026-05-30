{* ProjectTask ListViewContents: MANAGEMENT Figma layout inside dashboard shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="mk-so-page mk-projecttask-list-mgmt-root mk-projecttask-page">
		{include file="partials/ProjectTaskListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-projecttask-table-card">
			{capture name=mk_projecttask_mgmt_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_projecttask_mgmt_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
