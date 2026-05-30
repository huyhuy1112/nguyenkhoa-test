{* Calendar main view — chrome Figma + FullCalendar *}
{strip}
<input type="hidden" id="currentView" value="{$REQ->get('view')}" />
<input type="hidden" id="start_day" value="{$CURRENT_USER->get('dayoftheweek')}" />
<input type="hidden" id="activity_view" value="{$CURRENT_USER->get('activity_view')}" />
<input type="hidden" id="time_format" value="{$CURRENT_USER->get('hour_format')}" />
<input type="hidden" id="start_hour" value="{$CURRENT_USER->get('start_hour')}" />
<input type="hidden" id="date_format" value="{$CURRENT_USER->get('date_format')}" />
<input type="hidden" id="hideCompletedEventTodo" value="{$CURRENT_USER->get('hidecompletedevents')}">
<input type="hidden" id="show_allhours" value="{$CURRENT_USER->get('showallhours')}">
<input type="hidden" name="is_record_creation_allowed" id="is_record_creation_allowed" value="{$IS_CREATE_PERMITTED}">

{include file="partials/CalendarMkHeaderBar.tpl"|vtemplate_path:$MODULE}

<div class="mk-cal-layout">
	<aside class="mk-cal-layout__aside" id="mk-cal-layout-aside">
		{include file="partials/CalendarMkSidebar.tpl"|vtemplate_path:$MODULE}
	</aside>
	<div class="mk-cal-layout__main">
		{include file="partials/CalendarMkToolbar.tpl"|vtemplate_path:$MODULE}
		<div class="mk-cal-layout__grid-wrap">
			<div class="mk-cal-grid-surface">
				<div id="mycalendar" class="calendarview mk-cal-fc-host">
					{assign var=LEFTPANELHIDE value=$CURRENT_USER_MODEL->get('leftpanelhide')}
					<div class="essentials-toggle mk-cal-hide-legacy" title="{vtranslate('LBL_LEFT_PANEL_SHOW_HIDE', 'Vtiger')}">
						<span class="essentials-toggle-marker fa {if $LEFTPANELHIDE eq '1'}fa-chevron-right{else}fa-chevron-left{/if} cursorPointer"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}