{*+**********************************************************************************
 * Warehouse Management (Inventory app): dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-wh-mgmt-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryListShared.css')}?mk_v=20260612_wh_mgmt7" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehousePrototype.css')}?mk_v=20260612_wh_mgmt7" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseMgmt.css')}?mk_v=20260612_wh_mgmt7" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseLocalStore.js')}?mk_v=20260612_wh_mgmt4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseMgmt.js')}?mk_v=20260612_wh_mgmt4"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-warehouse-mgmt="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-wh-mgmt-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-wh-mgmt-page">
			<div class="viewContent mk-wh-mgmt-inner" id="mkWhMgmtRoot" data-mk-wh-view="{$VIEW|escape:'html'}">
{/strip}
{else}
{include file="IndexViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
