{strip}
<div class="sidebar-menu calendar-sidebar-mycalendars mk-cal-activitytypes-inner">
	<div class="module-filters" id="module-filters">
		<div class="sidebar-container lists-menu-container">
			{foreach item=SIDEBARWIDGET from=$QUICK_LINKS['SIDEBARWIDGET']}
				{if $SIDEBARWIDGET->get('linklabel') eq 'LBL_ACTIVITY_TYPES' || $SIDEBARWIDGET->get('linklabel') eq 'LBL_ADDED_CALENDARS'}
					<div class="calendar-sidebar-tabs sidebar-widget" id="{$SIDEBARWIDGET->get('linklabel')}-accordion" role="tablist" data-widget-name="{$SIDEBARWIDGET->get('linklabel')}">
						<div class="calendar-sidebar-tab">
							<div class="sidebar-widget-header" role="tab" data-url="{$SIDEBARWIDGET->getUrl()}">
								<div class="sidebar-header clearfix">
									<h5 class="pull-left">{vtranslate($SIDEBARWIDGET->get('linklabel'),$MODULE)}</h5>
									<button class="btn btn-default pull-right sidebar-btn add-calendar-feed" type="button">
										<div class="fa fa-plus" aria-hidden="true"></div>
									</button>
								</div>
							</div>
							<div class="list-menu-content">
								<div id="{$SIDEBARWIDGET->get('linklabel')}" class="sidebar-widget-body activitytypes">
									<div style="text-align:center;"><img src="layouts/v7/skins/images/loading.gif" alt="loading"></div>
								</div>
							</div>
						</div>
					</div>
				{/if}
			{/foreach}
		</div>
	</div>
</div>
{/strip}

