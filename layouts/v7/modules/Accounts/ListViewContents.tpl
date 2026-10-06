{* Accounts/Tuibao tiềm năng: SALES = Leads-like shell + Google Sheet; SUPPORT giữ POS *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable mk-acc-page mk-acc-page--lovable" data-mk-acc-list="1">
		{include file="partials/AccountsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-acc-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-acc-pos-search" type="search" placeholder="{vtranslate('LBL_MK_ACCOUNTS_SEARCH_PLACEHOLDER', $MODULE)}" autocomplete="off" />
					<button type="button" class="mk-leads-search__clear" id="mk-acc-pos-search-clear" aria-label="Xóa" hidden>×</button>
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-pos-trigger-columns" title="Cột hiển thị">
					{vtranslate('LBL_COLUMNS', 'Vtiger')|default:'Cột'}
				</button>
			</div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-acc-table-card" role="region" aria-label="{vtranslate('LBL_RECORDS_LIST', $MODULE)}">
			{capture name=mk_acc_sales_lv}{include file="partials/MkSalesPosListContents.tpl"|vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_acc_sales_lv}
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT')}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-org-page">
		<div class="mk-so-pos-layout" id="mk-acc-pos-layout">
			<div class="mk-so-pos-main">
				{assign var=MK_POS_SEARCH_ID value='mk-acc-pos-search'}
				{assign var=MK_POS_SEARCH_CLEAR_ID value='mk-acc-pos-search-clear'}
				{assign var=MK_POS_SEARCH_PLACEHOLDER value='Theo tên Tuibao'}
				{assign var=MK_POS_TITLE value='Khách hàng nhượng quyền tiềm năng'}
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
