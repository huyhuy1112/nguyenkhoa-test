{* Main Page (MANAGEMENT): split shell + topbar — đồng bộ Calendar / Leads *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-mainpage-ui-ready');</script>
<script type="text/javascript">window.__MK_MAINPAGE_UI_BUILD__ = "20260520_2400";</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Home/resources/MainPageMkView.css')}&mk_v=20260529_ann1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Home/resources/MainPageMkView.js')}&mk_v=20260520_2400"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-mainpage-ui="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-mainpage-main" id="mk-dash-main" role="main">
			<div class="main-container main-container-Home mk-mainpage-page">
				<div class="listViewPageDiv content-area full-width mk-mainpage-content" id="listViewContent" data-view="MainPage">
{/strip}
