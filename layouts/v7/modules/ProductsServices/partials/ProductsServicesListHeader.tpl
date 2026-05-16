{strip}
<div class="mk-ps-header">
	<nav class="mk-ps-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-ps-breadcrumb__list">
			<li class="mk-ps-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">Sales</a>
			</li>
			<li class="mk-ps-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-ps-breadcrumb__item mk-ps-breadcrumb__item--current">
				<span>Products &amp; Services</span>
			</li>
		</ol>
	</nav>
	<header class="mk-ps-action-header" role="region" aria-label="{vtranslate('ProductsServices', 'ProductsServices')}">
		<div class="mk-ps-action-header__text">
			<h1 class="mk-ps-action-header__title">All Products &amp; Services</h1>
			<p class="mk-ps-action-header__subtitle">Manage products and services catalog</p>
		</div>
		<div class="mk-ps-action-header__actions">
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
				<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($IMPORT_ACTION->getLabel())}" class="mk-ps-btn mk-ps-btn--outline"
						{if stripos($IMPORT_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$IMPORT_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick="Vtiger_Import_Js.triggerImportAction('{$IMPORT_ACTION->getUrl()}')"
						{/if}>
					<span class="mk-ps-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
					<span class="mk-ps-btn__txt">{vtranslate($IMPORT_ACTION->getLabel(), $MODULE)}</span>
				</button>
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-ps-settings-wrap">
					<button type="button" class="mk-ps-btn mk-ps-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_CUSTOMIZE', 'Reports')}">
						<span class="mk-ps-btn__ic" aria-hidden="true"><span class="fa fa-wrench"></span></span>
						<span class="mk-ps-btn__txt">{vtranslate('LBL_CUSTOMIZE', 'Reports')}</span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-ps-settings-menu dropdown-menu-right">
						{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
							<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}"><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
			{if $ADD_ACTION}
				<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($ADD_ACTION->getLabel())}" class="mk-ps-btn mk-ps-btn--primary"
						{if stripos($ADD_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$ADD_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick='window.location.href = "{$ADD_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
						{/if}>
					<span class="mk-ps-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
					<span class="mk-ps-btn__txt">{vtranslate($ADD_ACTION->getLabel(), $MODULE)}</span>
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
