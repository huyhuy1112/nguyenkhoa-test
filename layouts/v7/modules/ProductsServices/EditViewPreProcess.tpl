{* ProductsServices Create: dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-ps-create-ready');</script>
{* Cache-bust: vresource_url already adds ?v=… so append &mk_v=… *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260715_ps_leads2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
{* Theme first (list/detail accents); MkEdit last so create compact green UI wins *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesInventoryTheme.css')}&mk_v=20260715_ps_leads2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.css')}&mk_v=20260715_ps_leads2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.js')}&mk_v=20260715_ps_leads2"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-ps-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-productsservices-edit-main mk-ps-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-ps-edit-page{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')} mk-ps-inventory-page{/if}">
			<div class="editViewPageDiv viewContent mk-ps-edit-inner">
{/strip}
