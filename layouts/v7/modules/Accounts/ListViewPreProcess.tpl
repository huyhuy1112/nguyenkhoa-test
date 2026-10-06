{*+**********************************************************************************
 * Accounts List (Sales): Leads-like shell + Google Sheet + tags MkList.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-accounts-list-modern', 'mk-acc-list-sales', 'mk-acc-ui-ready');</script>
<script type="text/javascript">window.MK_ACC_API_READY = true;</script>
<script type="text/javascript">window.MK_LEADS_API_READY = true;</script>
{include file="partials/MkSalesListAntiFouc.tpl"|@vtemplate_path:'Vtiger'}
<style type="text/css">
html.mk-sales-list-guard:not(.mk-sales-list-ready) #modnavigator,
html.mk-sales-list-guard:not(.mk-sales-list-ready) #sidebar-essentials,
html.mk-sales-list-guard:not(.mk-sales-list-ready) .essentials-toggle {
	display: none !important;
}
html.mk-acc-list-sales body[data-module="Accounts"][data-view="List"] #mk-dash-main.mk-accounts-list-main {
	padding: 24px !important;
}
html.mk-acc-list-sales body[data-module="Accounts"][data-view="List"] .main-container .content-area,
html.mk-acc-list-sales body[data-module="Accounts"][data-view="List"] #listViewContent {
	padding-left: 0 !important;
	margin-left: 0 !important;
}
html.mk-acc-ui-ready body[data-module="Accounts"][data-view="List"] #modnavigator,
html.mk-acc-ui-ready body[data-module="Accounts"][data-view="List"] #sidebar-essentials {
	display: none !important;
}
.mk-acc-page .mk-leads-tags-add {
	display: inline-block;
	margin-left: 4px;
	opacity: 0.55;
	font-weight: 700;
}
.mk-acc-page .mk-leads-tags-edit {
	border: 0;
	background: transparent;
	padding: 0;
	text-align: left;
	cursor: pointer;
	max-width: 280px;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsList.css')}?mk_v=20261006_leads2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkLovableListShell.css')}&mk_v=20260709_lovable_shell4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260711_segments_ui2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.css')}&mk_v=20261006_acc_list2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkListLovable.css')}&mk_v=20260916_touchfix2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkTagPalette.css')}&mk_v=20260715_tag_color_v1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsMkList.css')}?mk_v=20261006_no_ellipsis1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260820_sheet1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkIcons.js')}&mk_v=20260711_segments_ui2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsLovableRef.js')}&mk_v=20261006_acc_list2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsLocalStore.js')}&mk_v=20261006_acc_sc1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkQuickImport.js')}&mk_v=20261006_quick1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsMkList.js')}&mk_v=20261006_acc_sc1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLocalStore.js')}&mk_v=20261006_acc_sheet2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.js')}&mk_v=20261006_acc_sheet2"></script>
<script type="text/javascript">
window.__mkSalesPosInlineConfig = {
	module: 'Accounts',
	drawer: true,
	tableSelector: '#mk-acc-table',
	rowSelector: 'tr.mk-leads-row',
	colspan: 13,
	enabledSelector: '[data-mk-acc-list]',
	loadingText: 'Đang tải chi tiết chủ quán...',
	errorText: 'Không tải được chi tiết.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260820_sheet1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-accounts-list="1" data-mk-acc-list="1">
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
			<div id="modnavigator" class="module-nav mk-accounts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-accounts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div class="listViewPageDiv content-area full-width mk-accounts-list-content" id="listViewContent">
{/strip}

{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT')}
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
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsMkSalesListSupport.css')}?mk_v=20260603_support1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSupportListTable.css')}?mk_v=20260625_support_search_v1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260810_list_pager_leads1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.css')}?mk_v=20260710_pos2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260820_sheet1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260810_list_pager_leads1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript">
window.__mkSalesPosListConfig = {
	searchInput: '#mk-acc-pos-search',
	searchClear: '#mk-acc-pos-search-clear',
	columnsTrigger: '.mk-pos-trigger-columns',
	searchFields: ['account_no', 'accountname', 'phone', 'tb_store_address', 'assigned_user_id', 'createdtime']
};
window.__mkSalesPosInlineConfig = {
	module: 'Accounts',
	drawer: true,
	loadingText: 'Đang tải chi tiết Tuibao...',
	errorText: 'Không tải được chi tiết Tuibao.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.js')}?mk_v=20260710_pos2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260820_sheet1"></script>
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
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-accounts-list-modern');</script>
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
