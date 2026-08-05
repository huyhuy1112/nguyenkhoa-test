{*+**********************************************************************************
 * Contacts List (Sales app): reuse SALES dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-contacts-ui-ready', 'mk-contacts-list-sales');</script>
<script type="text/javascript">window.__MK_CONTACTS_UI_BUILD__ = "20260716_class_detail1";</script>
<script type="text/javascript">window.MK_CONTACTS_API_READY = true;</script>
{if isset($MK_CONTACTS_ASSIGNABLE_USERS)}
<script type="text/javascript">window.MK_CONTACTS_ASSIGNABLE_USERS = {Zend_Json::encode($MK_CONTACTS_ASSIGNABLE_USERS)};</script>
{/if}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkLovableListShell.css')}&mk_v=20260709_lovable_shell4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260711_segments_ui2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsMkListShell.css')}&mk_v=20260713_loai_khach1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.css')}&mk_v=20260711_segments_ui2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkListLovable.css')}&mk_v=20260725_tags_grid2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkTagPalette.css')}&mk_v=20260725_cred_tags1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsMkList.css')}&mk_v=20260725_tags_grid2" />
<style type="text/css">
html.mk-contacts-ui-ready body[data-module="Contacts"][data-view="List"] .main-container .content-area,
html.mk-contacts-ui-ready body[data-module="Contacts"][data-view="List"] #listViewContent,
html.mk-contacts-ui-ready body:not([data-module="Calendar"]):not([data-module="Teams"])[data-module="Contacts"][data-view="List"] .main-container #sidebar-essentials.sidebar-essentials.hide + #listViewContent.listViewPageDiv.content-area {
	padding-left: 0 !important;
	margin-left: 0 !important;
}
html.mk-contacts-ui-ready body[data-module="Contacts"][data-view="List"] #mk-dash-main.mk-contacts-list-main {
	padding: 24px !important;
}
</style>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkIcons.js')}&mk_v=20260711_segments_ui2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsLovableRef.js')}&mk_v=20260725_cred_tags1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsLocalStore.js')}&mk_v=20260730_list_ux1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkLastTouchCall.js')}?mk_v=20260805_lt1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsMkList.js')}&mk_v=20260805_lt1"></script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260805_lt_sm1" />
<script type="text/javascript">
window.__mkSalesPosInlineConfig = {
	module: 'Contacts',
	tableSelector: '#mk-contacts-table',
	rowSelector: 'tr.mk-leads-row',
	colspan: 15,
	enabledSelector: '[data-mk-contacts-list]',
	loadingText: 'Đang tải chi tiết khách hàng...',
	errorText: 'Không tải được chi tiết khách hàng.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260805_lt1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-contacts-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-contacts-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-contacts-list-page">
			<div id="modnavigator" class="module-nav mk-contacts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-contacts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div class="listViewPageDiv content-area full-width mk-contacts-list-content" id="listViewContent">
{/strip}
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsList.css')}?mk_v=20260624_contacts_cols1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}?mk_v=20260606_pagingflash1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListTable.css')}?mk_v=20260617_mkt_align1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.js')}?mk_v=20260603_mkt_std1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/List.js')}?mk_v=20260624_contacts_cols1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-contacts-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-contacts-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-contacts-list-page">
			<div id="modnavigator" class="module-nav mk-contacts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-contacts-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div class="listViewPageDiv content-area full-width mk-contacts-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
