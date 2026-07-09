{*+**********************************************************************************
 * Contacts Detail (Sales app): reuse SALES dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="partials/ContactModernUiInit.tpl"|vtemplate_path:$MODULE}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesDetailInlineEdit.css')}&mk_v=20260611_mk_inline_v3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsDetail.css')}&mk_v=20260709_contact_tags_ui4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.css')}&mk_v=20260605_mk_rel_v2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.js')}&mk_v=20260605_mk_rel_v2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsDetailTags.js')}&mk_v=20260709_contact_tags_ui4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsDetail.js')}&mk_v=20260709_contact_tags_ui4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactDetailCommerce.js')}&mk_v=20260709_contact_tags_ui4"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-contact-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-contact-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-contact-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-contact-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-contact-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-contact-detail-inner">
				{include file="partials/ContactDetailBreadcrumb.tpl"|@vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-contact-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-contact-detail-details-row">
{/strip}
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="partials/ContactModernUiInit.tpl"|vtemplate_path:$MODULE}
{include file="modules/Vtiger/Header.tpl"}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsDetail.css')}&mk_v=20260709_contact_tags_ui4" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsDetailTags.js')}&mk_v=20260709_contact_tags_ui4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsDetail.js')}&mk_v=20260709_contact_tags_ui4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactDetailCommerce.js')}&mk_v=20260709_contact_tags_ui4"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-contact-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-contact-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-contact-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-contact-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-contact-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-contact-detail-inner">
				{include file="partials/ContactDetailBreadcrumb.tpl"|@vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-contact-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-contact-detail-details-row">
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
