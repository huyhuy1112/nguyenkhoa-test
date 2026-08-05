{* Quotes Create (SALES): dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
<script type="text/javascript">{literal}
document.documentElement.classList.add('mk-quote-create-ready', 'mk-inv-odoo-active');
(function(){var b=document.body;if(b&&!b.getAttribute('data-app')){b.setAttribute('data-app','SALES');}})();
setTimeout(function(){document.documentElement.classList.add('mk-inv-ui-ready','mk-quote-create-enhanced');}, 1800);
{/literal}</script>
<style type="text/css">
	/* Anti-FOUC: hide legacy vtiger form chrome before JS enhances */
	html.mk-quote-create-ready body[data-module="Quotes"][data-view="Edit"] #mk-dash-split-root,
	html.mk-quote-create-ready body[data-module="Quotes"][data-view="Edit"] .editViewPageDiv,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="Quotes"][data-view="Edit"] #mk-dash-split-root,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="Quotes"][data-view="Edit"] .editViewPageDiv {
		opacity: 0 !important;
		visibility: hidden !important;
		pointer-events: none !important;
	}
	html.mk-inv-ui-ready body[data-module="Quotes"][data-view="Edit"] #mk-dash-split-root,
	html.mk-inv-ui-ready body[data-module="Quotes"][data-view="Edit"] .editViewPageDiv,
	html.mk-quote-create-enhanced.mk-inv-ui-ready body[data-module="Quotes"][data-view="Edit"] #mk-dash-split-root,
	html.mk-quote-create-enhanced.mk-inv-ui-ready body[data-module="Quotes"][data-view="Edit"] .editViewPageDiv {
		opacity: 1 !important;
		visibility: visible !important;
		pointer-events: auto !important;
		transition: none !important;
	}

	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="Quotes"][data-view="Edit"] #lineItemTab tr.lineItemRow,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) body[data-module="SalesOrder"][data-view="Edit"] #lineItemTab tr.lineItemRow,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) #lineItemTab > tbody > tr:first-child,
	html.mk-inv-odoo-active:not(.mk-inv-ui-ready) .lineitemTableContainer .well {
		visibility: hidden !important;
		opacity: 0 !important;
	}

	html.mk-quote-create-ready #mkQtFormHost #modnavigator,
	html.mk-quote-create-ready #mkQtFormHost .editViewModNavigator,
	html.mk-quote-create-ready #mkQtFormHost .module-nav,
	html.mk-quote-create-ready #mkQtFormHost .editViewHeader,
	html.mk-quote-create-ready #mkQtFormHost .modal-overlay-footer,
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="carrier"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="shipping"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="inventorymanager"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="assigned_user_id1"]),
	html.mk-quote-create-ready #mkQtFormHost .fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"],
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="bill_pobox"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="bill_city"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="bill_state"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="bill_code"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="bill_country"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="ship_pobox"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="ship_city"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="ship_state"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="ship_code"]),
	html.mk-quote-create-ready #mkQtFormHost tr:has([name="ship_country"]),
	html.mk-quote-create-ready #mkQtFormHost .addressBlock > tbody > tr:first-child,
	html.mk-quote-create-ready #mkQtFormHost td.fieldLabel:has(+ td.fieldValue [name="quotestage"]),
	html.mk-quote-create-ready #mkQtFormHost td.fieldValue:has([name="quotestage"]) {
		display: none !important;
	}
	html.mk-quote-create-ready:not(.mk-inv-ui-ready) #mkQtFormHost,
	html.mk-quote-create-ready:not(.mk-inv-ui-ready) #mk-dash-split-root,
	html.mk-quote-create-ready:not(.mk-inv-ui-ready) .editViewPageDiv {
		opacity: 0 !important;
		visibility: hidden !important;
		pointer-events: none !important;
	}
	html.mk-inv-ui-ready #mkQtFormHost,
	html.mk-inv-ui-ready #mk-dash-split-root,
	html.mk-inv-ui-ready .editViewPageDiv {
		visibility: visible !important;
		opacity: 1 !important;
		pointer-events: auto !important;
		transition: none !important;
	}
</style>
{if !empty($MK_QUOTE_BA_CONFIG_JSON)}
<script type="text/javascript">window.__MK_QUOTE_BA_CONFIG = {$MK_QUOTE_BA_CONFIG_JSON nofilter};</script>
{/if}
{if !empty($MK_QUOTE_NEXT_NO)}
<script type="text/javascript">window.__MK_QUOTE_NEXT_NO = "{$MK_QUOTE_NEXT_NO|escape:'javascript'}";</script>
{/if}
<script type="text/javascript">window.MK_PRODUCT_CATALOG = {$MK_PRODUCT_CATALOG_JSON|default:'[]' nofilter};</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css')}&mk_v=20260805_line_align1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkEdit.css')}&mk_v=20260728_quote_tier_align1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.js')}&mk_v=20260728_quote_commerce6"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkBa.js')}&mk_v=20260728_quote_addr_merge1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteMkEdit.js')}&mk_v=20260730_quote_save_err"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-quote-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-quote-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-quote-edit-page">
			<div class="editViewPageDiv viewContent mk-quote-edit-inner">
{/strip}
