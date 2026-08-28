{* Settings shortcut tile — icon slot ready for designer SVG *}
{strip}
{assign var=_shortcutName value=$SETTINGS_SHORTCUT->get('name')}
<div id="shortcut_{$SETTINGS_SHORTCUT->getId()}" data-actionurl="{$SETTINGS_SHORTCUT->getPinUnpinActionUrl()}" class="mk-settings-shortcut-card nk-set-card moduleBlock cursorPointer" data-url="{$SETTINGS_SHORTCUT->getUrl()}" role="button" tabindex="0" style="--nk-set-delay: {$smarty.foreach.shortcuts.iteration * 40}ms">
	<span class="mk-settings-shortcut-card__icon" aria-hidden="true">
		{include file="partials/SettingsShortcutSvgIcon.tpl"|@vtemplate_path:'Settings:Vtiger' ICON=$_shortcutName}
	</span>
	<span class="mk-settings-shortcut-card__title">{vtranslate($_shortcutName, $MODULE)}</span>
	{if $SETTINGS_SHORTCUT->get('description') && $SETTINGS_SHORTCUT->get('description') neq 'NULL'}
		<span class="mk-settings-shortcut-card__desc">{vtranslate($SETTINGS_SHORTCUT->get('description'), $MODULE)}</span>
	{/if}
	<button data-id="{$SETTINGS_SHORTCUT->getId()}" title="{vtranslate('LBL_REMOVE', $MODULE)}" type="button" class="mk-settings-shortcut-unpin unpin hiden" aria-label="{vtranslate('LBL_REMOVE', $MODULE)}">
		<i class="fa fa-close" aria-hidden="true"></i>
	</button>
</div>
{/strip}
