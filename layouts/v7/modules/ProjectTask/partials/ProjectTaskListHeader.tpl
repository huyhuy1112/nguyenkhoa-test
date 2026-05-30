{* MANAGEMENT ProjectTask list — action header (Project list pattern) *}
{strip}
<div class="mk-projecttask-header">
	<div class="mk-projecttask-breadcrumb" aria-label="Breadcrumb">
		<span>{vtranslate($MODULE, $MODULE)}</span>
		<span class="mk-projecttask-breadcrumb__sep">&gt;</span>
		<span>All Tasks</span>
	</div>
	<header class="mk-projecttask-action-header" role="region" aria-label="{vtranslate($MODULE, $MODULE)}">
		<div class="mk-projecttask-action-header__text">
			<h1 class="mk-projecttask-action-header__title">All Tasks</h1>
		</div>
		<div class="mk-projecttask-action-header__actions">
			{if $MODULE_BASIC_ACTIONS|@count gt 0}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
						<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-projecttask-btn mk-projecttask-btn--primary"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick='window.location.href = "{$BASIC_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
								{/if}>
							<span class="mk-projecttask-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
							<span class="mk-projecttask-btn__txt">Add Project Task</span>
						</button>
					{/if}
				{/foreach}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
						<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-projecttask-btn mk-projecttask-btn--outline"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick="Vtiger_Import_Js.triggerImportAction('{$BASIC_ACTION->getUrl()}')"
								{/if}>
							<span class="mk-projecttask-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
							<span class="mk-projecttask-btn__txt">{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}</span>
						</button>
					{/if}
				{/foreach}
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-projecttask-settings-wrap">
					<button type="button" class="mk-projecttask-btn mk-projecttask-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
						<span class="mk-projecttask-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
						<span class="mk-projecttask-btn__txt">Customize</span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-projecttask-settings-menu dropdown-menu-right">
						{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
							<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}"><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
		</div>
	</header>
</div>
{/strip}
