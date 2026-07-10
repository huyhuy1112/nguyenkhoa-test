{* SalesOrder List: SALES or TOOLS — dashboard split shell + topbar (Invoice pattern for TOOLS) *}
{assign var=MK_SO_IS_SALES value=false}
{assign var=MK_SO_IS_TOOLS value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES') || (isset($smarty.request.app) && $smarty.request.app eq 'SALES')}
	{assign var=MK_SO_IS_SALES value=true}
{/if}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'TOOLS') || (isset($smarty.get.app) && $smarty.get.app eq 'TOOLS') || (isset($smarty.request.app) && $smarty.request.app eq 'TOOLS')}
	{assign var=MK_SO_IS_TOOLS value=true}
{/if}
{if $MK_SO_IS_SALES || $MK_SO_IS_TOOLS}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{if $MK_SO_IS_TOOLS}
<script type="text/javascript">document.documentElement.classList.add('mk-salesorder-list-tools', 'mk-opportunity-list-sales');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderToolsListContent.css')}?mk_v=20260605_so_tools2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderToolsList.css')}?mk_v=20260605_so_tools2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260607_sales_footer1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_search2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260703_global_search3"></script>
{else}
{include file="partials/MkSalesListAntiFouc.tpl"|@vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderList.css')}?mk_v=20260710_so_confirm3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260710_so_confirm3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.css')}?mk_v=20260710_pos4" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260707_so_pos17"></script>
{/if}
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
window.__mkSoSalesListConfig.globalSearchPlaceholder = 'Theo mã phiếu đặt';
window.__mkSoSalesListConfig.globalSearchFields = ['salesorder_no', 'customerno', 'subject'];
window.__mkSoSalesListConfig.paidField = {if isset($MK_SO_POS_PAID_FIELD) && $MK_SO_POS_PAID_FIELD ne ''}{Zend_Json::encode($MK_SO_POS_PAID_FIELD)}{else}'received'{/if};
window.__mkSoSalesListConfig.dueField = 'hdnGrandTotal';
window.__mkSoSalesListConfig.filterMeta = {if isset($MK_SO_POS_FILTER_META)}{Zend_Json::encode($MK_SO_POS_FILTER_META)}{else}{}{/if};
{if $MK_SO_IS_TOOLS}
window.__mkSoToolsListConfig = window.__mkSoToolsListConfig || {};
window.__mkSoToolsListConfig.statusFieldCandidates = window.__mkSoSalesListConfig.statusFieldCandidates;
window.__mkSoToolsListConfig.preferredStatusField = window.__mkSoSalesListConfig.preferredStatusField;
window.__mkSoToolsListConfig.statusHeaderLabel = window.__mkSoSalesListConfig.statusHeaderLabel;
{/if}
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
{if $MK_SO_IS_TOOLS}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/ListToolsBoot.js')}?mk_v=20260605_so_tools2"></script>
{else}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SalesOrder/resources/List.js')}?mk_v=20260710_so_whredir1"></script>
{/if}
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
		<main class="mk-dash-main mk-content {if $MK_SO_IS_TOOLS}mk-opportunity-list-main mk-so-tools-list-main{else}mk-so-list-main{/if}" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} {if $MK_SO_IS_TOOLS}mk-opportunity-list-page mk-so-tools-list-page{else}mk-so-list-page{/if}">
			<div id="modnavigator" class="module-nav {if $MK_SO_IS_TOOLS}mk-opportunity-hide-legacy{else}mk-so-hide-legacy{/if}">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide {if $MK_SO_IS_TOOLS}mk-opportunity-hide-legacy{else}mk-so-hide-legacy{/if}">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width {if $MK_SO_IS_TOOLS}mk-opportunity-list-content{else}mk-so-list-content{/if}" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
