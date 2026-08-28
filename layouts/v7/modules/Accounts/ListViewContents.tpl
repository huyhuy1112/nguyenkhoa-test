{* Accounts/Organizations ListViewContents: MARKETING theme | SALES Figma list card shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING' || $SELECTED_MENU_CATEGORY eq 'SUPPORT')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING' || $smarty.get.app eq 'SUPPORT'))}
	<div class="mk-so-page mk-so-list-sales-root mk-org-page">
		{include file="AccountsOrgListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-org-table-card">
			{capture name=mk_acc_sales_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_acc_sales_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}

