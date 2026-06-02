{* Leads Detail summary — Sales widget grid (same as Potentials). *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<form id="detailView" class="clearfix mk-lead-detail-summary-form" method="POST" style="position: relative">
		<div class="col-lg-12 resizable-summary-view mk-lead-detail-summary-col">
			{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
		</div>
	</form>
{else}
	<form id="detailView" method="POST">
		{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
	</form>
{/if}
{/strip}
