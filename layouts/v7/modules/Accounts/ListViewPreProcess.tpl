{*+**********************************************************************************
 * Accounts List (Sales + Support): dashboard split shell + modern topbar; legacy list nav hidden in CSS.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'SUPPORT')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'SUPPORT'))}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-accounts-list-modern');</script>
{include file="partials/MkSalesListAntiFouc.tpl"|@vtemplate_path:'Vtiger'}
<style type="text/css">
html.mk-sales-list-guard:not(.mk-sales-list-ready) #modnavigator,
html.mk-sales-list-guard:not(.mk-sales-list-ready) #sidebar-essentials,
html.mk-sales-list-guard:not(.mk-sales-list-ready) .essentials-toggle {
	display: none !important;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsList.css')}?mk_v=20260710_pos2" />
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT')}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsMkSalesListSupport.css')}?mk_v=20260603_support1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSupportListTable.css')}?mk_v=20260625_support_search_v1" />
{/if}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260624_sales_antifouc1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.css')}?mk_v=20260710_pos2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260805_care_2r_sc" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260703_global_search3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript">
window.__mkSalesPosListConfig = {
	searchInput: '#mk-acc-pos-search',
	searchClear: '#mk-acc-pos-search-clear',
	columnsTrigger: '.mk-pos-trigger-columns',
	searchFields: ['tb_contract_no', 'accountname', 'phone', 'tb_store_address', 'assigned_user_id']
};
window.__mkSalesPosInlineConfig = {
	module: 'Accounts',
	loadingText: 'Đang tải chi tiết Tuibao...',
	errorText: 'Không tải được chi tiết Tuibao.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.js')}?mk_v=20260710_pos2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260805_care_2r_sc"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsList.js')}?mk_v=20260723_tb_list1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-accounts-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-accounts-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-accounts-list-page">
			<div id="modnavigator" class="module-nav mk-accounts-list-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-accounts-list-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-accounts-list-content" id="listViewContent">
{/strip}

{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{* MARKETING branch unchanged — uses MkMarketingListShared *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-accounts-list-modern');</script>
<style type="text/css">
html.mk-accounts-list-modern:not(.mk-accounts-list-ready) #listViewContent { visibility: hidden; }
html.mk-accounts-list-modern.mk-accounts-list-ready #listViewContent { visibility: visible; }
html.mk-accounts-list-modern:not(.mk-accounts-list-ready) #modnavigator,
html.mk-accounts-list-modern:not(.mk-accounts-list-ready) #sidebar-essentials,
html.mk-accounts-list-modern:not(.mk-accounts-list-ready) .essentials-toggle {
	display: none !important;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsList.css')}?mk_v=20260701_org_quotes_ui1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}?mk_v=20260606_pagingflash1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListTable.css')}?mk_v=20260617_mkt_align1" onload="document.documentElement.classList.add('mk-accounts-list-ready')" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.js')}?mk_v=20260701_org_ui_fix1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsList.js')}?mk_v=20260701_org_flicker_fix1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-accounts-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-accounts-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-accounts-list-page">
			<div id="modnavigator" class="module-nav mk-accounts-list-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-accounts-list-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-accounts-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
