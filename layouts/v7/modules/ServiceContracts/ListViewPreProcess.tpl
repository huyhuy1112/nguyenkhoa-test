{*+**********************************************************************************
 * ServiceContracts List (Sales): Leads-like Lovable shell + affiliate.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-sc-ui-ready', 'mk-sc-list-sales');</script>
<script type="text/javascript">window.__MK_SC_UI_BUILD__ = "20260729_sc_no_fanpage1";</script>
<script type="text/javascript">window.MK_SC_API_READY = true;</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkLovableListShell.css')}&mk_v=20260709_lovable_shell4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260711_segments_ui2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.css')}&mk_v=20260711_segments_ui2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkListLovable.css')}&mk_v=20260805_tag_hscroll1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkTagPalette.css')}&mk_v=20260715_tag_color_v1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsMkList.css')}?mk_v=20260806_sc_quote1" />
<style type="text/css">
html.mk-sc-ui-ready body[data-module="ServiceContracts"][data-view="List"] .main-container .content-area,
html.mk-sc-ui-ready body[data-module="ServiceContracts"][data-view="List"] #listViewContent,
html.mk-sc-ui-ready body:not([data-module="Calendar"]):not([data-module="Teams"])[data-module="ServiceContracts"][data-view="List"] .main-container #sidebar-essentials.sidebar-essentials.hide + #listViewContent.listViewPageDiv.content-area {
	padding-left: 0 !important;
	margin-left: 0 !important;
}
html.mk-sc-ui-ready body[data-module="ServiceContracts"][data-view="List"] #mk-dash-main.mk-sc-list-main {
	padding: 24px !important;
}
</style>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkIcons.js')}&mk_v=20260711_segments_ui2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsLovableRef.js')}&mk_v=20260725_sc_list1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsLocalStore.js')}&mk_v=20260725_sc_list1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsMkList.js')}&mk_v=20260808_sc_recent_touch1"></script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260806_sc_quote_btn1" />
<script type="text/javascript">
window.__mkSalesPosInlineConfig = {
	module: 'ServiceContracts',
	tableSelector: '#mk-sc-table',
	rowSelector: 'tr.mk-leads-row',
	colspan: 13,
	enabledSelector: '[data-mk-sc-list]',
	loadingText: 'Đang tải chi tiết khách chuyển nhượng...',
	errorText: 'Không tải được chi tiết khách chuyển nhượng.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260806_sc_quote_btn1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-sc-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-sc-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-sc-list-page">
			<div id="modnavigator" class="module-nav mk-sc-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-sc-list-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div class="listViewPageDiv content-area full-width mk-sc-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
