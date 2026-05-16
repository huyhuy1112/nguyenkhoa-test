{* Accounts/Organizations ListViewContents: MARKETING theme | SALES Figma list card shell *}
{strip}
{if $smarty.get.app eq 'MARKETING'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	<div class="mk">
		<div class="mk-page">
			{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
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

