{* Contacts DetailViewSummaryContents: SALES scoped summary form wrapper. *}
{strip}
{if $smarty.get.app eq 'MARKETING'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	<form id="detailView" class="clearfix" method="POST" style="position: relative">
		<div class="col-lg-12 resizable-summary-view mk mk-page">
			{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
		</div>
	</form>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<form id="detailView" class="clearfix mk-contact-detail-summary-form" method="POST" style="position: relative">
		<div class="col-lg-12 resizable-summary-view mk-contact-detail-summary-col">
			{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
		</div>
	</form>
{else}
	<form id="detailView" class="clearfix" method="POST" style="position: relative">
		<div class="col-lg-12 resizable-summary-view">
			{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
		</div>
	</form>
{/if}
{/strip}
