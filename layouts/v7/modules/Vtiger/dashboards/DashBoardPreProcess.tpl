{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License.
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Vtiger/views/DashBoard.php — split shell: sidebar | app shell (topbar + subnav + main). Header was historically pulled in via Topbar.tpl. *}

{strip}
{include file="modules/Vtiger/Header.tpl"}
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:$MODULE}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div class="container-fluid app-nav app-nav-{$SELECTED_MENU_CATEGORY} mk-app-shell-subnav">
			<div class="row">
				{include file="modules/Vtiger/partials/SidebarHeader.tpl"}
				{include file="ModuleHeader.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content" id="mk-dash-main" role="main">
{/strip}
