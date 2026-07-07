{*+**********************************************************************************
 * ProductsServices List (Kho / INVENTORY app): dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-ps-list-sales');</script>
<style type="text/css">
html.mk-ps-list-sales:not(.mk-ps-list-ready) #listViewContent { visibility: hidden; }
html.mk-ps-list-sales.mk-ps-list-ready #listViewContent { visibility: visible; }
html.mk-ps-list-sales #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-ps-list-sales #listViewContent .bottom-fixed-scroll {
	display: none !important;
	height: 0 !important;
	margin: 0 !important;
	padding: 0 !important;
	border: none !important;
	overflow: hidden !important;
	position: absolute !important;
	left: -9999px !important;
	width: 0 !important;
	pointer-events: none !important;
}
html.mk-ps-list-sales #listview-table {
	table-layout: fixed;
	width: 100%;
	border-collapse: collapse;
}
html.mk-ps-list-sales #listview-table tr th:first-child,
html.mk-ps-list-sales #listview-table tr td:first-child {
	width: 152px;
	min-width: 152px;
	max-width: 152px;
	box-sizing: border-box;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260607_sales_footer1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260703_global_search3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProductsServices/resources/List.js')}?mk_v=20260707_ps_inv2"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-ps-list="1">
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesList.css')}?mk_v=20260707_ps_inv2" />
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesInventoryTheme.css')}?mk_v=20260707_ps_inv2" />
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
		<div class="main-container main-container-{$MODULE} mk-ps-list-page{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')} mk-ps-inventory-page{/if}">
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
