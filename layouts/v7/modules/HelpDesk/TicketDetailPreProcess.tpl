{* HelpDesk TicketDetail — split shell + topbar (same as Inventory detail pages) *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-hd-detail-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/HelpDesk/resources/HelpDeskDetail.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/HelpDesk/resources/TicketDetail.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-helpdesk-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-go-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-go-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-go-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-go-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-go-detail-inner">
				<div class="detailview-content mk-go-detailview-content">
					<input id="recordId" type="hidden" value="{$TICKET.id}" />
					<div class="mk-hd-detail-page mk-go-detail-body" id="mkHdTicketDetail" data-ticket-id="{$smarty.get.record|default:''}">
{/strip}
