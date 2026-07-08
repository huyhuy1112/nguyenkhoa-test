{* SalesOrder Create (SALES): dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesOrderEditAntiFouc.tpl"|@vtemplate_path:'SalesOrder'}
<style type="text/css">
	html.mk-so-create-guard #mkSoFormHost .fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"],
	/* Keep the form minimal: only show core fields like the BA screenshot */
	html.mk-so-create-guard #mkSoFormHost tr:has([name="subject"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="assigned_user_id"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="salescommission"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="carrier"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="leadsource"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="team_group"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="sostatus"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="salesorder_status"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="invoicestatus"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="quote_id"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="potential_id"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="contact_id"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="purchaseorder"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="currency_id"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="conversion_rate"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="hdnTaxType"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="taxtype"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="bill_pobox"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="bill_city"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="bill_state"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="bill_code"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="bill_country"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="ship_pobox"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="ship_city"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="ship_state"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="ship_code"]),
	html.mk-so-create-guard #mkSoFormHost tr:has([name="ship_country"]),
	html.mk-so-create-guard #mkSoFormHost .addressBlock > tbody > tr:first-child {
		display: none !important;
	}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css')}&mk_v=20260708_price_cols_v21" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.css')}&mk_v=20260707_so_pos17" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.js')}&mk_v=20260708_price_cols_v21"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.js')}&mk_v=20260707_so_pos22"></script>
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
