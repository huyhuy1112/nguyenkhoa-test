{* Settings sub-page toolbar actions (list + module basic actions) *}
{strip}
<ul class="mk-settings-subpage-actions">
	{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS|default:[]}
		<li>
			{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
				<button id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}" type="button" class="mk-settings-btn mk-settings-btn--outline module-buttons"
					{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
						onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
					{else}
						onclick="Vtiger_Import_Js.triggerImportAction('{$BASIC_ACTION->getUrl()}')"
					{/if}>
					{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}
				</button>
			{else}
				<button type="button" class="mk-settings-btn mk-settings-btn--outline module-buttons"
					id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($BASIC_ACTION->getLabel())}"
					{if stripos($BASIC_ACTION->getUrl(), 'javascript:')===0}
						onclick='{$BASIC_ACTION->getUrl()|substr:strlen("javascript:")};'
					{else}
						onclick='window.location.href="{$BASIC_ACTION->getUrl()}"'
					{/if}>
					{vtranslate($BASIC_ACTION->getLabel(), $MODULE)}
				</button>
			{/if}
		</li>
	{/foreach}
	{if !empty($LISTVIEW_LINKS['LISTVIEWSETTING']) && ($LISTVIEW_LINKS['LISTVIEWSETTING']|@count gt 0)}
		{assign var=_settingsQualMod value=$QUALIFIEDMODULE|default:$MODULE}
		<li class="mk-settings-subpage-actions__dropdown">
			<button type="button" class="mk-settings-btn mk-settings-btn--outline dropdown-toggle module-buttons" data-toggle="dropdown" aria-expanded="false">
				{vtranslate('LBL_SETTINGS', $MODULE)} <span class="caret"></span>
			</button>
			<ul class="dropdown-menu dropdown-menu-right">
				{foreach item=SETTING from=$LISTVIEW_LINKS['LISTVIEWSETTING']}
					<li id="{$MODULE}_setings_lisview_advancedAction_{$SETTING->getLabel()}">
						<a {if stripos($SETTING->getUrl(), 'javascript:') === 0}
								onclick='{$SETTING->getUrl()|substr:strlen("javascript:")};'
							{else}
								href="{$SETTING->getUrl()}"
							{/if}>
							{vtranslate($SETTING->getLabel(), $_settingsQualMod)}
						</a>
					</li>
				{/foreach}
			</ul>
		</li>
	{/if}
	{assign var=RESTRICTED_MODULE_LIST value=['Users', 'EmailTemplates']}
	{if !empty($LISTVIEW_LINKS['LISTVIEWBASIC']) && ($LISTVIEW_LINKS['LISTVIEWBASIC']|@count gt 0) && (!in_array($MODULE, $RESTRICTED_MODULE_LIST))}
		{assign var=_settingsQualModule value=$QUALIFIED_MODULE|default:'Settings:'|cat:$MODULE}
		{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
			<li>
				<button class="mk-settings-btn mk-settings-btn--primary module-buttons"
					id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}"
					{if $MODULE eq 'Workflows'}
						onclick='Settings_Workflows_List_Js.triggerCreate("{$LISTVIEW_BASICACTION->getUrl()}&mode=V7Edit")'
					{elseif stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0}
						onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'
					{else}
						onclick='window.location.href = "{$LISTVIEW_BASICACTION->getUrl()}"'
					{/if}>
					{if $MODULE eq 'Tags'}
						{vtranslate('LBL_ADD_TAG', $_settingsQualModule)}
					{else}
						{vtranslate($LISTVIEW_BASICACTION->getLabel(), $_settingsQualModule)}
					{/if}
				</button>
			</li>
		{/foreach}
	{/if}
</ul>
{/strip}
