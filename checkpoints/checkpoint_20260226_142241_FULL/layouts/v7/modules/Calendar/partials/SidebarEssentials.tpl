{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{if $smarty.get.view eq 'Calendar' OR $smarty.get.view eq 'SharedCalendar'}
{* Giao diện Google Calendar: + Create, minimap (mini calendar), My calendars (Activity Types). Không có Lists/Extensions. *}
<div class="calendar-sidebar-google">
	{if $IS_CREATE_PERMITTED}
	<div class="calendar-google-create">
		<div class="dropdown">
			<button type="button" class="btn calendar-create-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				<span class="fa fa-plus"></span> {vtranslate('LBL_CREATE','Calendar')} <span class="fa fa-chevron-down"></span>
			</button>
			<ul class="dropdown-menu dropdown-menu-right">
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showCreateEventModal();"><span class="fa fa-calendar-plus-o"></span> {vtranslate('LBL_ADD_EVENT', $MODULE)}</a></li>
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showCreateTaskModal();"><span class="fa fa-tasks"></span> {vtranslate('LBL_ADD_TASK', $MODULE)}</a></li>
				{if $SHOW_LEAVE_REQUEST}
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showLeaveRequestCreateModal();"><span class="fa fa-calendar-minus-o"></span> {vtranslate('LBL_LEAVE_REQUEST', $MODULE)}</a></li>
				{/if}
			</ul>
		</div>
	</div>
	{/if}
	{if $SHOW_MINI_CALENDAR_LEAVE}
	<div class="calendar-mini-wrap" id="calendar-mini-wrap" title="{vtranslate('LBL_MINI_CALENDAR','Calendar')}">
		<div class="calendar-mini-label">{vtranslate('LBL_MINI_CALENDAR_LEAVE','Calendar')}</div>
		<div id="calendar-mini"></div>
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
