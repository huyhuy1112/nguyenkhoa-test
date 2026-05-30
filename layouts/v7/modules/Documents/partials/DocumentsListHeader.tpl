{* Documents list header — real data only (shell unchanged) *}
{strip}
<header class="mk-doc-hero mk-doc-hero--lite">
	<nav class="mk-doc-hero__crumb" aria-label="Breadcrumb">
		<a href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</a>
		<span class="mk-doc-hero__crumb-sep" aria-hidden="true">/</span>
		<span>{vtranslate($MODULE, $MODULE)}</span>
		{if isset($FOLDER_VALUE) && $FOLDER_VALUE !== ''}
			<span class="mk-doc-hero__crumb-sep" aria-hidden="true">/</span>
			<span class="mk-doc-hero__crumb-current">{$FOLDER_VALUE|escape:'html'}</span>
		{/if}
	</nav>
	<div class="mk-doc-hero__row">
		<div class="mk-doc-hero__copy">
			<h1 class="mk-doc-hero__title">{vtranslate($MODULE, $MODULE)}</h1>
			<p class="mk-doc-hero__meta" id="mkDocHeroMeta">
				<span id="mkDocStatTotal">—</span> {vtranslate('SINGLE_Documents', $MODULE)|default:'documents'}
				· {if isset($FOLDER_COUNT)}{$FOLDER_COUNT}{else}0{/if} {vtranslate('LBL_FOLDERS', $MODULE)|lower}
			</p>
		</div>
		<div class="mk-doc-hero__actions">
			{if $IS_CREATE_PERMITTED && $CREATE_DOCUMENT_URL}
				<a href="{$CREATE_DOCUMENT_URL}" class="mk-doc-btn mk-doc-btn--primary doc-btn-new-document" data-mk-doc-action="new">
					<span>{vtranslate('LBL_NEW_DOCUMENT', $MODULE)}</span>
				</a>
			{/if}
			<a href="index.php?module=Documents&amp;view=History{if $SELECTED_MENU_CATEGORY}&amp;app={$SELECTED_MENU_CATEGORY}{/if}" class="mk-doc-btn mk-doc-btn--ghost doc-btn-history">
				<span>{vtranslate('LBL_HISTORY', $MODULE)|default:'History'}</span>
			</a>
		</div>
	</div>
</header>

<div class="mk-doc-action-bar mk-doc-action-bar--lite" role="toolbar" aria-label="Document actions">
	<div class="mk-doc-action-bar__search">
		<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.4"/><path d="M12.5 12.5 16 16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
		<input type="search" id="mkDocGlobalSearch" class="mk-doc-action-bar__search-input" placeholder="{vtranslate('LBL_SEARCH', $MODULE)|default:'Search in list…'}" autocomplete="off" />
	</div>
	<div class="mk-doc-action-bar__actions">
		<div class="mk-doc-view-toggle" role="group" aria-label="View mode">
			<button type="button" class="mk-doc-view-toggle__btn is-active" data-mk-doc-view="table" title="Table view">
				<i class="fa fa-list" aria-hidden="true"></i>
			</button>
			<button type="button" class="mk-doc-view-toggle__btn" data-mk-doc-view="grid" title="Grid view">
				<i class="fa fa-th-large" aria-hidden="true"></i>
			</button>
		</div>
	</div>
</div>
{/strip}
