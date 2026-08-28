{* Invoice List (TOOLS / SUPPORT): dashboard split shell + topbar — same as SALES Opportunities *}
{assign var=MK_INV_MK_LIST value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_INV_MK_LIST value=true}
{/if}
{if $MK_INV_MK_LIST}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-invoice-list-support', 'mk-opportunity-list-sales');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Invoice/resources/InvoiceListContent.css')}?mk_v=20260605_inv_search1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Invoice/resources/InvoiceSupportList.css')}?mk_v=20260605_inv_search1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260607_sales_footer1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_search2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260607_sales_footer1"></script>
{assign var=mk_inv_mod value=Vtiger_Module_Model::getInstance($MODULE)}
{assign var=mk_inv_status_fm value=Vtiger_Field_Model::getInstance('invoicestatus', $mk_inv_mod)}
{if !$mk_inv_status_fm}
	{assign var=mk_inv_status_fm value=Vtiger_Field_Model::getInstance('status', $mk_inv_mod)}
{/if}
{assign var=mk_inv_status_field_name value=''}
{assign var=mk_inv_status_label value=vtranslate('Status', $MODULE)}
{if $mk_inv_status_fm}
	{assign var=mk_inv_status_field_name value=$mk_inv_status_fm->getName()}
	{assign var=mk_inv_status_label value=vtranslate($mk_inv_status_fm->get('label'), $MODULE)}
{/if}
<script type="text/javascript">
window.__mkInvSupportListConfig = window.__mkInvSupportListConfig || {};
window.__mkInvSupportListConfig.statusFieldCandidates = ['invoicestatus', 'status', 'sostatus'];
window.__mkInvSupportListConfig.preferredStatusField = {if $mk_inv_status_field_name ne ''}{Zend_Json::encode($mk_inv_status_field_name)}{else}null{/if};
window.__mkInvSupportListConfig.statusHeaderLabel = {Zend_Json::encode($mk_inv_status_label)};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Invoice/resources/ListSupportBoot.js')}?mk_v=20260605_inv_search1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-invoice-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-opportunity-list-main mk-inv-support-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-opportunity-list-page mk-inv-support-list-page">
			<div id="modnavigator" class="module-nav mk-opportunity-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-opportunity-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-opportunity-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
