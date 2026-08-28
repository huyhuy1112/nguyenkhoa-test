{* Settings Index — Figma-aligned summary + shortcuts *}
{strip}
<div class="mk-settings-index">
	<nav class="mk-settings-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-settings-breadcrumb__list">
			<li class="mk-settings-breadcrumb__item">
				<a href="index.php?module=Vtiger&amp;parent=Settings&amp;view=Index">{vtranslate('LBL_SETTINGS', 'Vtiger')}</a>
			</li>
			<li class="mk-settings-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-settings-breadcrumb__item mk-settings-breadcrumb__item--current">
				<span>{vtranslate('LBL_SUMMARY', $MODULE)}</span>
			</li>
		</ol>
	</nav>

	<header class="mk-settings-index-header">
		<h1 class="mk-settings-index-header__title">{vtranslate('LBL_SETTINGS', 'Vtiger')}</h1>
	</header>

	<section class="mk-settings-summary" aria-labelledby="mk-settings-summary-title">
		<h2 id="mk-settings-summary-title" class="mk-settings-section-title">{vtranslate('LBL_SUMMARY', $MODULE)}</h2>
		<div class="mk-settings-summary-grid">
			<a class="mk-settings-stat-card" href="index.php?module=Users&amp;parent=Settings&amp;view=List">
				<div class="mk-settings-stat-card__value">{$USERS_COUNT}</div>
				<div class="mk-settings-stat-card__label">{vtranslate('LBL_ACTIVE_USERS', $MODULE)}</div>
			</a>
			<a class="mk-settings-stat-card" href="index.php?module=Workflows&amp;parent=Settings&amp;view=List&amp;parentblock=LBL_AUTOMATION">
				<div class="mk-settings-stat-card__value">{$ACTIVE_WORKFLOWS}</div>
				<div class="mk-settings-stat-card__label">{vtranslate('LBL_WORKFLOWS_ACTIVE', $MODULE)}</div>
			</a>
			<a class="mk-settings-stat-card" href="index.php?module=ModuleManager&amp;parent=Settings&amp;view=List">
				<div class="mk-settings-stat-card__value">{$ACTIVE_MODULES}</div>
				<div class="mk-settings-stat-card__label">{vtranslate('LBL_MODULES', $MODULE)}</div>
			</a>
		</div>
	</section>

	<section class="mk-settings-shortcuts" aria-labelledby="mk-settings-shortcuts-title">
		<h2 id="mk-settings-shortcuts-title" class="mk-settings-section-title">{vtranslate('LBL_SETTINGS_SHORTCUTS', $MODULE)}</h2>
		<div id="settingsShortCutsContainer" class="mk-settings-shortcuts-grid">
			{foreach item=SETTINGS_SHORTCUT from=$SETTINGS_SHORTCUTS name=shortcuts}
				{include file='SettingsShortCut.tpl'|@vtemplate_path:$MODULE}
			{/foreach}
		</div>
	</section>
</div>
{/strip}
