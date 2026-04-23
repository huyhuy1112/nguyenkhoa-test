{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{if $smarty.get.view eq 'Calendar' OR $smarty.get.view eq 'SharedCalendar' OR $smarty.get.view eq 'Year'}
{* Giao diện Google Calendar: + Create, minimap (mini calendar), My calendars (Activity Types). Không có Lists/Extensions. *}
<div class="calendar-sidebar-google">
	{* BA: remove the left blue Create section for both app contexts *}
	{if $SHOW_MINI_CALENDAR_LEAVE}
	<div class="calendar-mini-wrap" id="calendar-mini-wrap" title="{vtranslate('LBL_MINI_CALENDAR','Calendar')}">
		<div class="calendar-mini-label">{vtranslate('LBL_MINI_CALENDAR','Calendar')}</div>
		<div id="calendar-mini"></div>
	</div>
	{/if}

	{* Year view: premium sidebar blocks (static, no Calendar.js dependency) *}
	{if $smarty.get.view eq 'Year'}
	<div class="cyv-sidebar-block">
		<div class="cyv-sidebar-title">Legend</div>
		<div class="cyv-legend">
			<div class="cyv-legend-row" data-cyv-filter="events">
				<span class="cyv-legend-dot is-event"></span>
				<span class="cyv-legend-text">Events</span>
				<span class="cyv-legend-chip">Blue</span>
			</div>
			<div class="cyv-legend-row" data-cyv-filter="tasks">
				<span class="cyv-legend-dot is-task"></span>
				<span class="cyv-legend-text">Tasks</span>
				<span class="cyv-legend-chip">Green</span>
			</div>
		</div>
	</div>

	<div class="cyv-sidebar-block">
		<div class="cyv-sidebar-title">My Calendars</div>
		<div class="cyv-menu">
			<a class="cyv-menu-item is-active" href="javascript:void(0)" data-cyv-view="all">
				<span class="cyv-menu-ico fa fa-th-large"></span>
				<span class="cyv-menu-label">All</span>
				<span class="cyv-menu-badge">12</span>
			</a>
			<a class="cyv-menu-item" href="javascript:void(0)" data-cyv-view="events">
				<span class="cyv-menu-ico fa fa-calendar"></span>
				<span class="cyv-menu-label">Events</span>
			</a>
			<a class="cyv-menu-item" href="javascript:void(0)" data-cyv-view="tasks">
				<span class="cyv-menu-ico fa fa-check-square-o"></span>
				<span class="cyv-menu-label">Tasks</span>
			</a>
		</div>
	</div>

	<div class="cyv-sidebar-block">
		<div class="cyv-sidebar-title">Other Calendars</div>
		<div class="cyv-menu">
			<a class="cyv-menu-item" href="index.php?module=Calendar&view=SharedCalendar{if $smarty.get.app neq ''}&app={$smarty.get.app}{/if}">
				<span class="cyv-menu-ico fa fa-users"></span>
				<span class="cyv-menu-label">Shared</span>
			</a>
			<a class="cyv-menu-item" href="index.php?module=Calendar&view=Calendar{if $smarty.get.app neq ''}&app={$smarty.get.app}{/if}">
				<span class="cyv-menu-ico fa fa-calendar-o"></span>
				<span class="cyv-menu-label">Month / Week</span>
			</a>
		</div>
	</div>
	{/if}
</div>
<div class="sidebar-menu calendar-sidebar-mycalendars">
	<div class="module-filters" id="module-filters">
		<div class="sidebar-container lists-menu-container">
			{foreach item=SIDEBARWIDGET from=$QUICK_LINKS['SIDEBARWIDGET']}
			{if $SIDEBARWIDGET->get('linklabel') eq 'LBL_ACTIVITY_TYPES' || $SIDEBARWIDGET->get('linklabel') eq 'LBL_ADDED_CALENDARS'}
			<div class="calendar-sidebar-tabs sidebar-widget" id="{$SIDEBARWIDGET->get('linklabel')}-accordion" role="tablist" data-widget-name="{$SIDEBARWIDGET->get('linklabel')}">
				<div class="calendar-sidebar-tab">
					<div class="sidebar-widget-header" role="tab" data-url="{$SIDEBARWIDGET->getUrl()}">
						<div class="sidebar-header clearfix">
							<h5 class="pull-left">{vtranslate($SIDEBARWIDGET->get('linklabel'),$MODULE)}</h5>
							<button class="btn btn-default pull-right sidebar-btn add-calendar-feed">
								<div class="fa fa-plus" aria-hidden="true"></div>
							</button>
						</div>
					</div>
					<div class="list-menu-content">
						<div id="{$SIDEBARWIDGET->get('linklabel')}" class="sidebar-widget-body activitytypes">
							<div style="text-align:center;"><img src="layouts/v7/skins/images/loading.gif"></div>
						</div>
					</div>
				</div>
			</div>
			{/if}
			{/foreach}
		</div>
	</div>
</div>
{else}
	{include file="partials/SidebarEssentials.tpl"|vtemplate_path:'Vtiger'}
{/if}
