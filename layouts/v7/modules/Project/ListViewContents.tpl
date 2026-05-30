{* Project ListViewContents: MANAGEMENT layout inside dashboard shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="mk-so-page mk-project-list-mgmt-root mk-project-page">
		{include file="partials/ProjectListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-project-table-card">
			{capture name=mk_project_mgmt_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_project_mgmt_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
