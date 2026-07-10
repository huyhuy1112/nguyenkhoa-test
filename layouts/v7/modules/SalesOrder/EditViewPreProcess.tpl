{* SalesOrder Create (SALES): same dashboard shell as Quote create (grid + right rail). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
{include file="partials/MkSalesOrderEditAntiFouc.tpl"|@vtemplate_path:'SalesOrder'}
<script type="text/javascript">{literal}
document.documentElement.classList.add('mk-quote-create-ready', 'mk-inv-odoo-active', 'mk-so-create-guard');
(function(){var b=document.body;if(b&&!b.getAttribute('data-app')){b.setAttribute('data-app','SALES');}})();
setTimeout(function(){document.documentElement.classList.add('mk-inv-ui-ready','mk-quote-create-enhanced','mk-so-create-styled');}, 1800);
{/literal}</script>
<style type="text/css">
	/* Anti-FOUC: hide until Inventory/Quote shell CSS+JS are ready (same as Quotes) */
	html.mk-quote-create-ready body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
	html.mk-quote-create-ready body[data-module="SalesOrder"][data-view="Edit"] .editViewPageDiv,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="SalesOrder"][data-view="Edit"] .editViewPageDiv {
		opacity: 0 !important;
		visibility: hidden !important;
		pointer-events: none !important;
	}
	html.mk-inv-ui-ready body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
	html.mk-inv-ui-ready body[data-module="SalesOrder"][data-view="Edit"] .editViewPageDiv,
	html.mk-quote-create-enhanced.mk-inv-ui-ready body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
	html.mk-quote-create-enhanced.mk-inv-ui-ready body[data-module="SalesOrder"][data-view="Edit"] .editViewPageDiv {
		opacity: 1 !important;
		visibility: visible !important;
		pointer-events: auto !important;
		transition: none !important;
	}

	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="SalesOrder"][data-view="Edit"] #lineItemTab tr.lineItemRow,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) #lineItemTab > tbody > tr:first-child,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) .lineitemTableContainer .well {
		visibility: hidden !important;
		opacity: 0 !important;
	}

	html.mk-quote-create-ready #mkSoFormHost #modnavigator,
	html.mk-quote-create-ready #mkSoFormHost .editViewModNavigator,
	html.mk-quote-create-ready #mkSoFormHost .module-nav,
	html.mk-quote-create-ready #mkSoFormHost .editViewHeader,
	html.mk-quote-create-ready #mkSoFormHost .modal-overlay-footer,
	html.mk-quote-create-ready #mkSoFormHost .fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"],
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="carrier"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="shipping"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="salescommission"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="leadsource"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="team_group"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="quote_id"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="purchaseorder"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="currency_id"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="conversion_rate"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="hdnTaxType"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="taxtype"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="bill_pobox"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="bill_city"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="bill_state"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="bill_code"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="bill_country"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="ship_pobox"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="ship_city"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="ship_state"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="ship_code"]),
	html.mk-quote-create-ready #mkSoFormHost tr:has([name="ship_country"]),
	html.mk-quote-create-ready #mkSoFormHost .addressBlock > tbody > tr:first-child {
		display: none !important;
	}

	/* Quote-style create: form fills the main column (not capped at 1120) */
	body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] .mk-qt-create .mk-so-form-host form#EditView,
	body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] .mk-qt-create .mk-so-form-host .editViewContents,
	body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] .mk-qt-create .mk-so-form-host [name="editContent"] {
		max-width: 100% !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
	}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css')}&mk_v=20260708_so_qt_shell2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkEdit.css')}&mk_v=20260708_so_qt_shell2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.js')}&mk_v=20260708_so_qt_shell2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkEdit.js')}&mk_v=20260708_so_qt_shell2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderMkEdit.js')}&mk_v=20260708_so_qt_shell2"></script>
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
		<main class="mk-dash-main mk-content mk-so-edit-main mk-quote-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-so-edit-page mk-quote-edit-page">
			<div class="editViewPageDiv viewContent mk-so-edit-inner mk-quote-edit-inner">
{/strip}
