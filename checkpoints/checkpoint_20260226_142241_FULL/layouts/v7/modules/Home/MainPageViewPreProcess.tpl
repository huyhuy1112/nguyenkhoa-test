{* Main Page: cùng cấu trúc với Schedule (Calendar) để có main-container + modnavigator, menu bật được *}
{include file="modules/Vtiger/partials/Topbar.tpl"}
{strip}
<div class="container-fluid app-nav app-nav-mainpage">
	<div class="row">
		{include file="partials/SidebarHeader.tpl"|vtemplate_path:$MODULE}
		<div class="col-sm-11 col-xs-10 padding0 module-action-bar clearfix coloredBorderTop">
			<div class="module-action-content clearfix Home-module-action-content">
				<div class="col-lg-7 col-md-6 col-sm-5 col-xs-11 padding0 module-breadcrumb module-breadcrumb-MainPage">
					<h4 class="module-title pull-left text-uppercase"><i class="fa fa-dashboard"></i> Main Page</h4>
				</div>
			</div>
		</div>
	</div>
</div>
</nav>
<div id='overlayPageContent' class='fade modal overlayPageContent content-area overlay-container-60' tabindex='-1' role='dialog' aria-hidden='true'>
	<div class="data">
	</div>
	<div class="modal-dialog">
	</div>
</div>
<div class="main-container main-container-Home">
	{assign var=LEFTPANELHIDE value=$CURRENT_USER_MODEL->get('leftpanelhide')}
	<div id="modnavigator" class="module-nav calendar-navigator clearfix">
		<div class="mod-switcher-container">
			{include file="modules/Vtiger/partials/Menubar.tpl"}
		</div>
	</div>
	<div id="sidebar-essentials" class="sidebar-essentials {if $LEFTPANELHIDE eq '1'} hide {/if}">
		{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
	</div>
	<div class="listViewPageDiv content-area {if $LEFTPANELHIDE eq '1'} full-width {/if}" id="listViewContent" data-view="MainPage">
