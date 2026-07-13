{*+**********************************************************************************
 * Warehouse Management (Inventory app): dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">
	document.documentElement.classList.add('mk-wh-mgmt-ready');
	{if $VIEW eq 'WhDetail'}
	document.documentElement.classList.add('mk-wh-proto-ready');
	{/if}
</script>
{if $MK_WH_DB_STATE_JSON}
<script type="text/javascript">
	window.MK_WH_DB_STATE = {$MK_WH_DB_STATE_JSON};
</script>
{/if}
{if $MK_WH_PRODUCT_CATALOG_JSON}
<script type="text/javascript">
	window.MK_WH_PRODUCT_CATALOG = {$MK_WH_PRODUCT_CATALOG_JSON};
</script>
{/if}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryListShared.css')}?mk_v=20260612_wh_mgmt7" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehousePrototype.css')}?mk_v=20260702_wh_issue_flow2" />
{if $VIEW eq 'WhDetail'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseWhDetailOverrides.css')}?mk_v=20260713_wh_cancel1" />
{else}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseMgmt.css')}?mk_v=20260702_wh_db14" />
{/if}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseLocalStore.js')}?mk_v=20260713_wh_cancel1"></script>
{if $VIEW eq 'WhDetail'}
	<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseWhDetailPrototype.js')}?mk_v=20260713_wh_tab1"></script>
{else}
	<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Warehouse/resources/WarehouseMgmt.js')}?mk_v=20260711_wh_roles2"></script>
{/if}
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-warehouse-mgmt="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		{*
			overlayPageContent is a legacy vtiger overlay container.
			Warehouse pages don't use it, and it can block topbar clicks due to stacking/positioning.
		*}
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true" style="display:none !important; pointer-events:none !important;">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content{if $VIEW eq 'WhDetail'} mk-wh-proto-main{else} mk-wh-mgmt-main{/if}" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE}{if $VIEW eq 'WhDetail'} mk-wh-proto-page{else} mk-wh-mgmt-page{/if}">
			<div class="viewContent mk-wh-mgmt-inner" id="mkWhMgmtRoot" data-mk-wh-view="{$VIEW|escape:'html'}">
{/strip}
{else}
{include file="IndexViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
