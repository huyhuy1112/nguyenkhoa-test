{* Figma action header — Import / Customize / Add with dashboard SVG icons *}
{strip}
<div class="mk-opportunity-header">
	<div class="mk-opportunity-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php">{vtranslate('LBL_HOME', 'Vtiger')}</a>
		<span class="mk-opportunity-breadcrumb__sep">&gt;</span>
		<span>{vtranslate('SINGLE_Potentials', 'Potentials')}</span>
	</div>
	<header class="mk-opportunity-action-header" role="region" aria-label="{vtranslate('Potentials', 'Potentials')}">
		<div class="mk-opportunity-action-header__text">
			<h1 class="mk-opportunity-action-header__title">All Opportunities</h1>
			<p class="mk-opportunity-action-header__subtitle">Manage opportunities from our customers</p>
		</div>
		<div class="mk-opportunity-action-header__actions">
			{if $MODULE_BASIC_ACTIONS|@count gt 0}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
						<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-opportunity-btn mk-opportunity-btn--outline"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick="Vtiger_Import_Js.triggerImportAction('{$BASIC_ACTION->getUrl()}')"
								{/if}>
							<span class="mk-opportunity-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
							<span class="mk-opportunity-btn__txt">{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}</span>
						</button>
					{elseif $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
						<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-opportunity-btn mk-opportunity-btn--primary"
								{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
							onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
								{else}
							onclick='window.location.href = "{$BASIC_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
								{/if}>
							<span class="mk-opportunity-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
							<span class="mk-opportunity-btn__txt">Add Opportunities</span>
						</button>
					{/if}
				{/foreach}
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-opportunity-settings-wrap">
					<button type="button" class="mk-opportunity-btn mk-opportunity-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
						<span class="mk-opportunity-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
						<span class="mk-opportunity-btn__txt">Customize</span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-opportunity-settings-menu dropdown-menu-right">
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
