{*+**********************************************************************************
 * SalesOrder List (Sales app): SALES dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderList.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}"></script>
{assign var=mk_so_mod value=Vtiger_Module_Model::getInstance($MODULE)}
{assign var=mk_so_status_fm value=Vtiger_Field_Model::getInstance('sostatus', $mk_so_mod)}
{if !$mk_so_status_fm}
	{assign var=mk_so_status_fm value=Vtiger_Field_Model::getInstance('salesorder_status', $mk_so_mod)}
{/if}
{if !$mk_so_status_fm}
	{assign var=mk_so_status_fm value=Vtiger_Field_Model::getInstance('invoicestatus', $mk_so_mod)}
{/if}
{if !$mk_so_status_fm}
	{assign var=mk_so_status_fm value=Vtiger_Field_Model::getInstance('status', $mk_so_mod)}
{/if}
{assign var=mk_so_status_field_name value=''}
{assign var=mk_so_status_label value=vtranslate('Status', $MODULE)}
{if $mk_so_status_fm}
	{assign var=mk_so_status_field_name value=$mk_so_status_fm->getName()}
	{assign var=mk_so_status_label value=vtranslate($mk_so_status_fm->get('label'), $MODULE)}
{/if}
<script type="text/javascript">
window.__mkSoSalesListConfig = window.__mkSoSalesListConfig || {};
window.__mkSoSalesListConfig.statusFieldCandidates = ['sostatus', 'salesorder_status', 'invoicestatus', 'status'];
window.__mkSoSalesListConfig.preferredStatusField = {if $mk_so_status_field_name ne ''}{Zend_Json::encode($mk_so_status_field_name)}{else}null{/if};
window.__mkSoSalesListConfig.statusHeaderLabel = {Zend_Json::encode($mk_so_status_label)};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/List.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-sales-order-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-so-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-so-list-page">
			<div id="modnavigator" class="module-nav mk-so-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-so-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-so-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
