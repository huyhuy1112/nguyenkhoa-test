{* Accounts/Tuibao ListViewContents: SALES/SUPPORT POS shell like Quotes *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'SUPPORT')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'SUPPORT'))}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-org-page">
		<div class="mk-so-pos-layout" id="mk-acc-pos-layout">
			<div class="mk-so-pos-main">
				{assign var=MK_POS_SEARCH_ID value='mk-acc-pos-search'}
				{assign var=MK_POS_SEARCH_CLEAR_ID value='mk-acc-pos-search-clear'}
				{assign var=MK_POS_SEARCH_PLACEHOLDER value='Theo tên Tuibao'}
				{assign var=MK_POS_TITLE value='Tuibao'}
				{include file="partials/MkSalesPosListHeader.tpl"|vtemplate_path:'Vtiger'}
				<div class="mk-so-table-card mk-org-table-card">
					{capture name=mk_acc_sales_lv}{include file="partials/MkSalesPosListContents.tpl"|vtemplate_path:'Vtiger'}{/capture}
					{$smarty.capture.mk_acc_sales_lv}
				</div>
			</div>
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-so-page mk-so-list-sales-root mk-org-page">
		{include file="AccountsOrgListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-org-table-card">
			{capture name=mk_acc_mkt_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_acc_mkt_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
