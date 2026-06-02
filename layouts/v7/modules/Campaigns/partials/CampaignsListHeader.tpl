{* Figma header — Active Campaigns (Marketing app) *}
{strip}
<div class="mk-camp-header">
	<nav class="mk-camp-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-camp-breadcrumb__list">
			<li class="mk-camp-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=MARKETING">{vtranslate('LBL_MARKETING', 'Vtiger')}</a>
			</li>
			<li class="mk-camp-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-camp-breadcrumb__item mk-camp-breadcrumb__item--current">
				<span>{vtranslate('Campaigns', 'Campaigns')}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-camp-action-header" role="region" aria-label="{vtranslate('Campaigns', 'Campaigns')}">
		<div class="mk-camp-action-header__text">
			<h1 class="mk-camp-action-header__title">Active Campaigns</h1>
			<p class="mk-camp-action-header__subtitle">Track and manage your marketing campaigns in one place</p>
		</div>
		<div class="mk-camp-action-header__actions">
			{assign var=IMPORT_ACTION value=false}
			{assign var=ADD_ACTION value=false}
			{if $MODULE_BASIC_ACTIONS|@count gt 0}
				{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
					{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
						{assign var=IMPORT_ACTION value=$BASIC_ACTION}
					{elseif $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
						{assign var=ADD_ACTION value=$BASIC_ACTION}
					{/if}
				{/foreach}
			{/if}
			{if $IMPORT_ACTION}
				<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($IMPORT_ACTION->getLabel())}" class="mk-camp-btn mk-camp-btn--outline"
						{if stripos($IMPORT_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$IMPORT_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick="Vtiger_Import_Js.triggerImportAction('{$IMPORT_ACTION->getUrl()}')"
						{/if}>
					<span class="mk-camp-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
					<span class="mk-camp-btn__txt">{vtranslate($IMPORT_ACTION->getLabel(), $MODULE)}</span>
				</button>
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-camp-settings-wrap">
					<button type="button" class="mk-camp-btn mk-camp-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
						<span class="mk-camp-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
						<span class="mk-camp-btn__txt">{vtranslate('LBL_CUSTOMIZE', 'Reports')}</span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-camp-settings-menu dropdown-menu-right">
						{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
							<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}"><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
			{if $ADD_ACTION}
				<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($ADD_ACTION->getLabel())}" class="mk-camp-btn mk-camp-btn--primary"
						{if stripos($ADD_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$ADD_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick='window.location.href = "{$ADD_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
						{/if}>
					<span class="mk-camp-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
					<span class="mk-camp-btn__txt">Add Campaign</span>
				</button>
			{/if}
		</div>
	</header>
</div>
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
