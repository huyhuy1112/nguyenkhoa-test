{strip}
<div class="mk-cal-sidebar" id="mk-cal-sidebar">
	<div class="mk-cal-sidebar-actions">
		<button type="button" class="mk-cal-action-btn" data-mk-cal-action="event" title="{vtranslate('LBL_ADD_EVENT', $MODULE)}">
			<span class="mk-cal-action-btn__icon" aria-hidden="true">
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 1V11M1 6H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
			</span>
			<span class="mk-cal-action-btn__label">{vtranslate('LBL_ADD_EVENT', $MODULE)}</span>
		</button>
		<button type="button" class="mk-cal-action-btn" data-mk-cal-action="task" title="{vtranslate('LBL_ADD_TASK', $MODULE)}">
			<span class="mk-cal-action-btn__icon" aria-hidden="true">
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 1V11M1 6H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
			</span>
			<span class="mk-cal-action-btn__label">{vtranslate('LBL_ADD_TASK', $MODULE)}</span>
		</button>
		<button type="button" class="mk-cal-action-btn" id="mk-cal-choose-year" title="{vtranslate('LBL_CHOOSE_YEAR', $MODULE)}">
			<span class="mk-cal-action-btn__icon" aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="1.75" y="2.5" width="10.5" height="9" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 1.5V4M9.5 1.5V4M1.75 6.5H12.25M4.5 9.5H9.5" stroke="currentColor" stroke-width="1.2"/></svg>
			</span>
			<span class="mk-cal-action-btn__label">{vtranslate('LBL_CHOOSE_YEAR', $MODULE)}</span>
		</button>
		<button type="button" class="mk-cal-action-btn" data-mk-cal-action="settings" title="{vtranslate('LBL_CALENDAR_SETTINGS', $MODULE)}">
			<span class="mk-cal-action-btn__icon" aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 1.5L8.2 2.8L10.5 0.5M12.5 2.5L10.2 4.8L11.5 6.1L7 10.1L2.5 5.6L3.8 4.3L6.1 6.6L3.8 8.9L2.5 10.2L7 14.2Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
			</span>
			<span class="mk-cal-action-btn__label">{vtranslate('LBL_CALENDAR_SETTINGS', $MODULE)}</span>
		</button>
	</div>

	<div class="mk-cal-mini-wrap">
		<div class="mk-cal-mini-head">
			<button type="button" class="mk-cal-mini-nav" id="mk-cal-mini-prev" aria-label="Previous month">&lsaquo;</button>
			<h2 class="mk-cal-mini-title" id="mk-cal-mini-title">Tháng 5 năm 2026</h2>
			<button type="button" class="mk-cal-mini-nav" id="mk-cal-mini-next" aria-label="Next month">&rsaquo;</button>
		</div>
		<div class="mk-cal-mini-grid-host" id="mk-cal-mini-host">
			<div class="calendar-mini-wrap" id="calendar-mini-wrap" title="{vtranslate('LBL_MINI_CALENDAR','Calendar')}">
				<div class="calendar-mini-label">{vtranslate('LBL_MINI_CALENDAR','Calendar')}</div>
				<div id="calendar-mini"></div>
			</div>
		</div>
	</div>

	<div class="mk-cal-activitytypes-card" id="mk-cal-activitytypes-card">
		{include file="partials/CalendarMkActivityTypes.tpl"|vtemplate_path:$MODULE}
	</div>

	<div class="mk-cal-activities-card">
		<h3 class="mk-cal-activities-title">{vtranslate('LBL_ACTIVITIES', 'Vtiger')}</h3>
		<ul class="mk-cal-activities-list" id="mk-cal-activities-list">
			<li class="mk-cal-activity mk-cal-activity--green">
				<span class="mk-cal-activity__bar"></span>
				<span class="mk-cal-activity__body">
					<span class="mk-cal-activity__title">Team Stand-up</span>
					<span class="mk-cal-activity__time">09:00 AM</span>
				</span>
			</li>
			<li class="mk-cal-activity mk-cal-activity--orange">
				<span class="mk-cal-activity__bar"></span>
				<span class="mk-cal-activity__body">
					<span class="mk-cal-activity__title">Project Review: Turbine Simulation</span>
					<span class="mk-cal-activity__time">10:00 AM - 11:30 AM</span>
				</span>
			</li>
			<li class="mk-cal-activity mk-cal-activity--blue">
				<span class="mk-cal-activity__bar"></span>
				<span class="mk-cal-activity__body">
					<span class="mk-cal-activity__title">Maintenance Window</span>
					<span class="mk-cal-activity__time">{vtranslate('LBL_ALL_DAY', 'Calendar')}</span>
				</span>
			</li>
		</ul>
	</div>
</div>
{/strip}