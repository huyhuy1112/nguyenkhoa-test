{* Settings sub-pages — breadcrumb + title (Index-style) *}
{strip}
{assign var=_settingsQual value=$QUALIFIED_MODULE|default:'Settings:Vtiger'}
{assign var=_settingsPageTitle value=''}
{if isset($PAGETITLE) && $PAGETITLE neq '' && $PAGETITLE neq 'Vtiger'}
	{assign var=_settingsPageTitle value={vtranslate($PAGETITLE, $_settingsQual)}}
{elseif $MODULE neq 'Vtiger'}
	{assign var=_settingsPageTitle value={vtranslate($MODULE, $MODULE)}}
{else}
	{assign var=_settingsPageTitle value={vtranslate('LBL_SETTINGS', 'Vtiger')}}
{/if}

<div class="mk-settings-subpage-shell">
	<nav class="mk-settings-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-settings-breadcrumb__list">
			<li class="mk-settings-breadcrumb__item">
				<a href="index.php?module=Vtiger&amp;parent=Settings&amp;view=Index">{vtranslate('LBL_SETTINGS', 'Vtiger')}</a>
			</li>
			{if isset($ACTIVE_BLOCK.block) && $ACTIVE_BLOCK.block neq ''}
				<li class="mk-settings-breadcrumb__sep" aria-hidden="true">&gt;</li>
				<li class="mk-settings-breadcrumb__item">
					<span>{vtranslate($ACTIVE_BLOCK.block, $_settingsQual)}</span>
				</li>
			{/if}
			<li class="mk-settings-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-settings-breadcrumb__item mk-settings-breadcrumb__item--current">
				<span>{$_settingsPageTitle|escape}</span>
			</li>
		</ol>
	</nav>

	<header class="mk-settings-subpage-header">
		<div class="mk-settings-subpage-header__text">
			<h1 class="mk-settings-subpage-header__title">{$_settingsPageTitle|escape}</h1>
		</div>
		<div class="mk-settings-subpage-header__actions" id="mk-settings-subpage-actions-slot">
			{include file="partials/SettingsSubpageActions.tpl"|@vtemplate_path:'Settings:Vtiger'}
		</div>
	</header>
</div>
{/strip}
