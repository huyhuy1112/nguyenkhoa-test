{* Contacts ListViewContents: MARKETING theme | SALES Figma list card shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
	<div class="mk-so-page mk-so-list-sales-root mk-contact-page">
		{include file="partials/ContactListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-contact-table-card">
			{capture name=mk_contact_sales_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_contact_sales_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
