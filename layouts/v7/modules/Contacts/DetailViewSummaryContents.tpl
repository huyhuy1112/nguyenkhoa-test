{* Contacts DetailViewSummaryContents: Sales + Marketing modern summary form. *}
{strip}
{if !empty($MK_CONTACT_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
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
