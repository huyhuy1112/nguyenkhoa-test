{* SupportFAQ Detail (SUPPORT): split shell + topbar *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-sf-faq-detail-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SupportFAQ/resources/SupportFAQDetail.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/SupportFAQ/resources/Detail.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-supportfaq-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-sf-faq-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-sf-faq-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-sf-faq-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-sf-faq-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-sf-faq-detail-inner">
				<div class="detailview-content mk-sf-faq-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD_ID}" />
					<div class="mk-sf-faq-detail-page" id="mkSfFaqDetailPage">
{/strip}
