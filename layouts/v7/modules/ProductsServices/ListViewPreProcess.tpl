{*+**********************************************************************************
 * ProductsServices List (Kho / INVENTORY): shell + early v2 CSS (no FOUC).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">
document.documentElement.classList.add('mk-ps-list-sales','mk-ps-list-v2');
if (document.body) { document.body.classList.add('mk-ps-list-v2'); }
else { document.addEventListener('DOMContentLoaded', function () { document.body.classList.add('mk-ps-list-v2'); }); }
/* Fallback: never keep list hidden if List.js fails after crash */
setTimeout(function () {
	document.documentElement.classList.add('mk-ps-list-ready', 'mk-ps-ui-ready');
	if (document.body) {
		document.body.classList.add('mk-ps-ui-ready', 'mk-ps-list-v2');
	}
}, 2200);
</script>
{* Critical CSS first — hide until ready; lock column grid so old CSS cannot flash *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesListTable.css')}?mk_v=20260806_ps_row_sel_only1" />
<style type="text/css">
html.mk-ps-list-v2:not(.mk-ps-list-ready) #listViewContent,
html.mk-ps-list-sales:not(.mk-ps-list-ready) #listViewContent {
	visibility: hidden !important;
}
html.mk-ps-list-v2.mk-ps-list-ready #listViewContent,
html.mk-ps-list-sales.mk-ps-list-ready #listViewContent {
	visibility: visible !important;
}
html.mk-ps-list-v2 #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-ps-list-v2 #listViewContent .bottom-fixed-scroll,
html.mk-ps-list-sales #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-ps-list-sales #listViewContent .bottom-fixed-scroll {
	display: none !important;
	height: 0 !important;
	pointer-events: none !important;
}
html.mk-ps-list-v2 #listview-table {
	table-layout: fixed !important;
	width: 100% !important;
	border-collapse: separate !important;
}
html.mk-ps-list-v2 #listview-table > thead {
	display: table-header-group !important;
	width: 100% !important;
}
html.mk-ps-list-v2 #listview-table > thead > tr.listViewContentHeader {
	display: table-row !important;
	width: 100% !important;
}
html.mk-ps-list-v2 #listview-table > thead > tr.listViewContentHeader > th,
html.mk-ps-list-v2 #listview-table > tbody > tr.listViewEntries > td {
	display: table-cell !important;
	box-sizing: border-box !important;
}
/* Inline grid lock — prevents misalignment before full CSS */
html.mk-ps-list-v2 #listview-table > thead > tr.listViewContentHeader > th:first-child,
html.mk-ps-list-v2 #listview-table > tbody > tr.listViewEntries > td.listViewRecordActions {
	display: table-cell !important;
	width: 52px !important;
	min-width: 52px !important;
	max-width: 56px !important;
	box-sizing: border-box !important;
}
html.mk-ps-list-v2 #listview-table tr.searchRow {
	display: none !important;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260808_pager_v2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesList.css')}?mk_v=20260808_pager_v2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesInventoryTheme.css')}?mk_v=20260808_pager_v2" />
{* Re-assert v2 table last so it beats MkSalesListTable / List.css *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesListTable.css')}?mk_v=20260806_ps_row_sel_only1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260806_ps_row_sel_only1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260703_global_search3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript">
window.__mkSalesPosInlineConfig = {
	module: 'ProductsServices',
	loadingText: 'Đang tải chi tiết hàng hoá...',
	errorText: 'Không tải được chi tiết hàng hoá.',
	enabledSelector: '[data-mk-ps-list]',
	tableSelector: '#listViewContent #listview-table',
	rowSelector: 'tr.listViewEntries'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260806_ps_row_sel_only1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProductsServices/resources/List.js')}?mk_v=20260808_pager_v2"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-ps-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-productsservices-list-main mk-ps-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-ps-list-page mk-ps-list-v2{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')} mk-ps-inventory-page{/if}">
			<div id="modnavigator" class="module-nav mk-ps-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-ps-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-ps-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
