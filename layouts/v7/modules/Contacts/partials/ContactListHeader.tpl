{strip}
<div class="mk-contact-header">
	<nav class="mk-contact-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-contact-breadcrumb__list">
			<li class="mk-contact-breadcrumb__item">
				<a href="index.php?module=Contacts&amp;view=List&amp;app=SALES">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
			</li>
			<li class="mk-contact-breadcrumb__sep" aria-hidden="true">/</li>
			<li class="mk-contact-breadcrumb__item mk-contact-breadcrumb__item--current">
				<span>{vtranslate('LBL_ALL', $MODULE_NAME)} {vtranslate($MODULE_NAME, $MODULE_NAME)}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-contact-action-header" role="region" aria-label="{vtranslate('Contacts', 'Contacts')}">
		<div class="mk-contact-action-header__text">
			<h1 class="mk-contact-action-header__title">All Contacts</h1>
			<p class="mk-contact-action-header__subtitle">Manage company staffs&rsquo; and partners&rsquo; contacts</p>
		</div>
		<div class="mk-contact-action-header__actions">
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
				<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($IMPORT_ACTION->getLabel())}" class="mk-contact-btn mk-contact-btn--outline"
						{if stripos($IMPORT_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$IMPORT_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick="Vtiger_Import_Js.triggerImportAction('{$IMPORT_ACTION->getUrl()}')"
						{/if}>
					<span class="mk-contact-btn__txt">{vtranslate($IMPORT_ACTION->getLabel(), $MODULE)}</span>
				</button>
			{/if}
			{if $MODULE_SETTING_ACTIONS|@count gt 0}
				<div class="mk-contact-settings-wrap">
					<button type="button" class="mk-contact-btn mk-contact-btn--outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						{vtranslate('LBL_CUSTOMIZE', 'Reports')} <span class="caret"></span>
					</button>
					<ul class="dropdown-menu detailViewSetting mk-contact-settings-menu dropdown-menu-right">
						{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
							<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}"><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
			{if $ADD_ACTION}
				<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($ADD_ACTION->getLabel())}" class="mk-contact-btn mk-contact-btn--primary"
						{if stripos($ADD_ACTION->getUrl(), 'javascript:')===0}
					onclick='{$ADD_ACTION->getUrl()|substr:strlen("javascript:")};'
						{else}
					onclick='window.location.href = "{$ADD_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
						{/if}>
					<span class="mk-contact-btn__txt">{vtranslate('LBL_ADD_RECORD', $MODULE)}</span>
				</button>
			{/if}
		</div>
	</header>
</div>
{/strip}
