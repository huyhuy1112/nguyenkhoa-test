{* SalesOrder Create (SALES): native SO dashboard shell (not Quote create shell). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesOrderEditAntiFouc.tpl"|@vtemplate_path:'SalesOrder'}
<script type="text/javascript">{literal}
document.documentElement.classList.add('mk-inv-odoo-active', 'mk-so-create-guard');
(function () {
	var b = document.body;
	if (b && !b.getAttribute('data-app')) {
		b.setAttribute('data-app', 'SALES');
	}
})();
{/literal}</script>
<script type="text/javascript">window.MK_PRODUCT_CATALOG = {$MK_PRODUCT_CATALOG_JSON|default:'[]' nofilter};</script>
<script type="text/javascript">window.MK_PRICE_CHANNEL = "{$MK_PRICE_CHANNEL|default:'retail'|escape:'javascript'}";</script>
<style type="text/css">
	/* Hide noisy field pairs early (label + value). Do not hide whole <tr> — Vtiger packs 2 fields/row. */
	html.mk-so-create-guard #mkSoFormHost .fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"],
	html.mk-so-create-guard #mkSoFormHost .fieldBlockContainer[data-block="LBL_TERMS_INFORMATION"],
	html.mk-so-create-guard #mkSoFormHost .fieldBlockContainer[data-block="Recurring Invoice Information"],
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="carrier"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="carrier"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="shipping"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="shipping"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="salescommission"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="salescommission"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="leadsource"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="leadsource"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="team_group"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="team_group"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="subject"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="subject"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="potential_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="potential_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="potential_id_display"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="potential_id_display"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="purchaseorder"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="purchaseorder"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="customerno"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="customerno"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="pending"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="pending"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="exciseduty"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="exciseduty"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="purpose"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="purpose"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="lead_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="lead_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="leadid"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="leadid"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="lead_id_display"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="lead_id_display"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="internal_cost"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="internal_cost"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="needed_time"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="needed_time"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="internal_order_status"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="internal_order_status"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="created_user_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="created_user_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="approved_by"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="approved_by"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="approval_note"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="approval_note"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="account_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="account_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="contact_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="contact_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="currency_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="currency_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="conversion_rate"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="conversion_rate"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="hdnTaxType"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="hdnTaxType"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="taxtype"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="taxtype"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="duedate"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="duedate"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="assigned_user_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="assigned_user_id"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="assigned_user_id1"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="assigned_user_id1"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="enable_recurring"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="enable_recurring"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="bill_pobox"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="bill_pobox"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="bill_city"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="bill_city"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="bill_state"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="bill_state"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="bill_code"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="bill_code"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="bill_country"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="bill_country"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="ship_pobox"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="ship_pobox"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="ship_city"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="ship_city"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="ship_state"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="ship_state"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="ship_code"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="ship_code"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldLabel:has(+ td.fieldValue [name="ship_country"]),
	html.mk-so-create-guard #mkSoFormHost td.fieldValue:has([name="ship_country"]),
	html.mk-so-create-guard #mkSoFormHost .addressBlock > tbody > tr:first-child {
		display: none !important;
	}

	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="SalesOrder"][data-view="Edit"] #lineItemTab tr.lineItemRow,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) #lineItemTab > tbody > tr:first-child,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) .lineitemTableContainer .well {
		visibility: hidden !important;
		opacity: 0 !important;
	}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css')}&mk_v=20260826_disc_amt1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkEdit.css')}&mk_v=20260805_so_perf1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.css')}&mk_v=20260805_so_perf1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.js')}&mk_v=20260826_disc_amt1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkBa.js')}&mk_v=20260728_quote_addr_merge1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.js')}&mk_v=20260805_so_perf1"></script>
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
