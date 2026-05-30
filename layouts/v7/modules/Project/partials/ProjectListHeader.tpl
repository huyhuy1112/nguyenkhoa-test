{* MANAGEMENT Project list — action header (Figma / ProjectTask pattern) *}
{strip}
<div class="mk-project-header">
	<div class="mk-project-breadcrumb" aria-label="Breadcrumb">
		<span>{vtranslate($MODULE, $MODULE)}</span>
		<span class="mk-project-breadcrumb__sep">&gt;</span>
		<span>All</span>
	</div>
	<header class="mk-project-action-header" role="region" aria-label="{vtranslate($MODULE, $MODULE)}">
		<div class="mk-project-action-header__text">
			<h1 class="mk-project-action-header__title">All Projects</h1>
		</div>
		<div class="mk-project-action-header__actions">
			{if $MODULE_BASIC_ACTIONS|@count gt 0}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
						<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-project-btn mk-project-btn--primary"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick='window.location.href = "{$BASIC_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
								{/if}>
							<span class="mk-project-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
							<span class="mk-project-btn__txt">Add Project</span>
						</button>
					{/if}
				{/foreach}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
						<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-project-btn mk-project-btn--outline"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick="Vtiger_Import_Js.triggerImportAction('{$BASIC_ACTION->getUrl()}')"
								{/if}>
							<span class="mk-project-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
							<span class="mk-project-btn__txt">{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}</span>
						</button>
					{/if}
				{/foreach}
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-project-settings-wrap">
					<button type="button" class="mk-project-btn mk-project-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
						<span class="mk-project-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
						<span class="mk-project-btn__txt">Customize</span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-project-settings-menu dropdown-menu-right">
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
