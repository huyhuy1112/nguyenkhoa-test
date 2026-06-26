{* SalesOrder Create (SALES): dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesOrderEditAntiFouc.tpl"|@vtemplate_path:'SalesOrder'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.css')}?mk_v=20260624_so_recurring2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.js')}?mk_v=20260625_so_commerce_refresh1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-sales-order-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-so-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-so-edit-page">
			<div class="editViewPageDiv viewContent mk-so-edit-inner">
{/strip}
