{*+**********************************************************************************
 * GoodsReceipt Inbound Detail (Inventory app): split shell + topbar (Sale Order layout).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-gr-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryListShared.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/GoodsReceipt/resources/GoodsReceiptDetail.css')}?mk_v=20260528_inbound_detail_font1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/GoodsReceipt/resources/Detail.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-goodsreceipt-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-gr-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-gr-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-gr-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-gr-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-gr-detail-inner">
				{include file="partials/InboundDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-gr-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					<div class="mk-gr-detail-body">
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
