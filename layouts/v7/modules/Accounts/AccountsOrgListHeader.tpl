{* Figma action header — single set of IDs (same as ModuleHeader) for Import / Add / Customize *}
{strip}
<header class="mk-org-action-header" role="region" aria-label="{vtranslate('Accounts', 'Accounts')}">
	<div class="mk-org-action-header__text">
		<h1 class="mk-org-action-header__title">{vtranslate('LBL_ORG_LIST_PAGE_TITLE', 'Accounts')}</h1>
		<p class="mk-org-action-header__subtitle">{vtranslate('LBL_ORG_LIST_PAGE_SUBTITLE', 'Accounts')}</p>
	</div>
	<div class="mk-org-action-header__actions">
		{if $MODULE_BASIC_ACTIONS|@count gt 0}
			{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
				{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
					<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-org-btn mk-org-btn--outline"
							{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
						onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
							{else}
						onclick="Vtiger_Import_Js.triggerImportAction('{$BASIC_ACTION->getUrl()}')"
							{/if}>
						<span class="mk-org-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
						<span class="mk-org-btn__txt">{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}</span>
					</button>
				{elseif $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
					<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-org-btn mk-org-btn--primary"
							{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
						onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
							{else}
						onclick='window.location.href = "{$BASIC_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
							{/if}>
						<span class="mk-org-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
						<span class="mk-org-btn__txt">{if $BASIC_ACTION->getLabel() eq 'LBL_ADD_RECORD' && isset($LISTVIEW_ADD_RECORD_LABEL) && $LISTVIEW_ADD_RECORD_LABEL neq ''}{$LISTVIEW_ADD_RECORD_LABEL}{else}{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}{/if}</span>
					</button>
				{else}
					<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" class="mk-org-btn mk-org-btn--outline"
							{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
						onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
							{else}
						onclick='window.location.href = "{$BASIC_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
							{/if}>
						<span class="mk-org-btn__txt">{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}</span>
					</button>
				{/if}
			{/foreach}
		{/if}
		{if $MODULE_SETTING_ACTIONS|@count gt 0}
			<div class="mk-org-settings-wrap">
				<button type="button" class="mk-org-btn mk-org-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
					<span class="mk-org-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
					<span class="mk-org-btn__txt">{vtranslate('LBL_CUSTOMIZE', 'Reports')}</span>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu detailViewSetting mk-org-settings-menu dropdown-menu-right">
					{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
						<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}"><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
					{/foreach}
				</ul>
			</div>
		{/if}
	</div>
</header>
{if $FIELDS_INFO neq null}
	<script type="text/javascript">
		var uimeta = (function () {
			var fieldInfo = {$FIELDS_INFO};
			return {
				field: {
					get: function (name, property) {
						if (name && property === undefined) {
							return fieldInfo[name];
						}
						if (name && property) {
							return fieldInfo[name][property]
						}
					},
					isMandatory: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].mandatory;
						}
						return false;
					},
					getType: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].type
						}
						return false;
					}
				},
			};
		})();
	</script>
{/if}
{/strip}
