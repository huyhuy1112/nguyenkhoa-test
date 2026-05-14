{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/DashBoard.php *}

{strip}
{assign var=MK_DASH_HEADER_TITLE value='My Dashboard'}
{foreach from=$DASHBOARD_TABS item=TAB_DATA}
	{if $TAB_DATA["id"] eq $SELECTED_TAB}
		{assign var=MK_DASH_HEADER_TITLE value=$TAB_DATA["tabname"]}
	{/if}
{/foreach}
<div class="dashboard-page-root">
	<div class="mk-dashboard-shell">
		<div class="dashBoardContainer clearfix mk-dash-board-inner">
			<div class="mk-dashboard-top-stack">
				<header class="mk-dashboard-page-header mk-dashboard-page-header--figma clearfix" role="banner">
					<div class="mk-dashboard-figma-header-row">
						<div class="mk-dashboard-title-block">
							<div class="mk-dashboard-kicker">TDB SOLUTION</div>
							<h1 class="mk-dashboard-title">{$MK_DASH_HEADER_TITLE|escape:'html'}</h1>
						</div>
						<div class="mk-dashboard-figma-header-actions">
							<div class="mk-dashboard-tab-toolbar mk-dashboard-tab-toolbar--figma clearfix">
								<div class="mk-dashboard-tab-toolbar-actions mk-dashboard-tab-toolbar-actions--figma">
									<div class="dropdown dashBoardDropDown mk-dash-modify-dropdown">
										<button class="btn btn-default dropdown-toggle mk-dash-figma-btn" type="button" data-toggle="dropdown">Modify Dashboard
											&nbsp;&nbsp;<span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right moreDashBoards">
											<li id="newDashBoardLi"{if php7_count($DASHBOARD_TABS) eq $DASHBOARD_TABS_LIMIT} class="disabled"{/if}><a class="addNewDashBoard" href="#">{vtranslate('LBL_ADD_NEW_DASHBOARD',$MODULE)}</a></li>
											<li><a class="reArrangeTabs" href="#">{vtranslate('LBL_REARRANGE_DASHBOARD_TABS',$MODULE)}</a></li>
										</ul>
									</div>
									<div class="mk-dash-toolbar-add-wrap">
										{include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME DASHBOARDHEADER_TITLE=vtranslate($MODULE, $MODULE)}
									</div>
									<button class="btn-success updateSequence hide mk-dash-save-tab-order mk-dash-figma-btn mk-dash-figma-btn--secondary">{vtranslate('LBL_SAVE_ORDER',$MODULE)}</button>
								</div>
							</div>
						</div>
					</div>
				</header>
				<div class="tabContainer mk-dashboard-tab-container">
					<ul class="nav nav-tabs tabs sortable container-fluid mk-dashboard-tabs mk-dashboard-tabs--figma" role="tablist" aria-label="{vtranslate('LBL_DASHBOARD',$MODULE)} tabs">
						{foreach key=index item=TAB_DATA from=$DASHBOARD_TABS}
							<li class="{if $TAB_DATA["id"] eq $SELECTED_TAB}active{/if} dashboardTab" data-tabid="{$TAB_DATA["id"]}" data-tabname="{$TAB_DATA["tabname"]}">
								<a data-toggle="tab" href="#tab_{$TAB_DATA["id"]}">
									<div>
										<span class="name textOverflowEllipsis" value="{$TAB_DATA["tabname"]}" style="width:10%">
											<strong>{$TAB_DATA["tabname"]}</strong>
										</span>
										<span class="editTabName hide">
											<input type="text" name="tabName"/>
										</span>
										{if $TAB_DATA["isdefault"] eq 0}
											<i class="fa fa-close deleteTab"></i>
										{/if}
										<i class="fa fa-bars moveTab hide"></i>
									</div>
								</a>
							</li>
						{/foreach}
						<li class="mk-dashboard-tab-insert-anchor hide" aria-hidden="true"></li>
					</ul>
					<div class="tab-content">
						{foreach key=index item=TAB_DATA from=$DASHBOARD_TABS}
							<div id="tab_{$TAB_DATA["id"]}" data-tabid="{$TAB_DATA["id"]}" data-tabname="{$TAB_DATA["tabname"]}" class="tab-pane fade {if $TAB_DATA["id"] eq $SELECTED_TAB}in active{/if}">
								{if $TAB_DATA["id"] eq $SELECTED_TAB}
									{include file="dashboards/DashBoardTabContents.tpl"|vtemplate_path:$MODULE TABID=$TABID}
								{/if}
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</main>
</div>
</div>
{/strip}
