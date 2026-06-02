{* Create Quote — dashboard shell + stock Inventory #EditView (all fields + line items). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Quotes&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-qt-create{if $MK_IS_EDIT} mk-qt-create--edit{/if}" id="mkQtCreateWorkspace" data-mk-quote-create="1">
	<header class="mk-qt-sticky-head" id="mkQtStickyHead">
		<div class="mk-qt-sticky-head__inner">
			<div class="mk-qt-sticky-head__left">
				<nav class="mk-qt-sticky-head__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
					<span aria-hidden="true">/</span>
					<a href="{$MK_LIST_URL}">{vtranslate('Quotes', $MODULE)}</a>
					<span aria-hidden="true">/</span>
					{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
				</nav>
				<div class="mk-qt-sticky-head__title-row">
					{if $MK_IS_EDIT}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Quotes', $MODULE)}</h1>
					{else}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Quotes', $MODULE)}</h1>
					{/if}
					<span class="mk-qt-badge mk-qt-badge--stage" id="mkQtHeadStageBadge">Draft</span>
				</div>
				<div class="mk-qt-autosave" id="mkQtAutosave" aria-live="polite">
					<span class="mk-qt-autosave__dot" aria-hidden="true"></span>
					<span class="mk-qt-autosave__text">Ready to save</span>
				</div>
			</div>
			<div class="mk-qt-sticky-head__actions">
				<a class="mk-qt-btn mk-qt-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-qt-btn mk-qt-btn--secondary" id="mkQtSaveSendTop" title="Save the quote first to send by email">
					Save &amp; Send
				</button>
				<button type="button" class="mk-qt-btn mk-qt-btn--primary" id="mkQtSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-qt-create__grid">
		<div class="mk-qt-create__main">
			<div class="mk-qt-form-host" id="mkQtFormHost">
				{include file="partials/QuoteMkInventoryForm.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>

		<aside class="mk-qt-rail" id="mkQtQuoteRail" aria-label="Quote summary">
			<div class="mk-qt-rail-card mk-qt-rail-card--summary">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-file-text-o"></i></span>
					<h2 class="mk-qt-rail-card__title">Quote Summary</h2>
				</div>
				<dl class="mk-qt-summary-list">
					<div class="mk-qt-summary-list__row">
						<dt>Pipeline stage</dt>
						<dd id="mkQtRailStage">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Valid until</dt>
						<dd id="mkQtRailValidUntil">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Organization</dt>
						<dd id="mkQtRailOrganization">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Opportunity</dt>
						<dd id="mkQtRailOpportunity">—</dd>
					</div>
					<div class="mk-qt-summary-list__row mk-qt-summary-list__row--total">
						<dt>Grand total</dt>
						<dd id="mkQtRailTotal">—</dd>
					</div>
				</dl>
			</div>

			<div class="mk-qt-rail-card">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-user"></i></span>
					<h2 class="mk-qt-rail-card__title">Assigned To</h2>
				</div>
				<p class="mk-qt-rail-meta" id="mkQtRailOwner">{$MK_QUOTE_OWNER_NAME|escape}</p>
			</div>

			<div class="mk-qt-rail-card mk-qt-rail-card--muted">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-clock-o"></i></span>
					<h2 class="mk-qt-rail-card__title">Activity</h2>
				</div>
				<p class="mk-qt-rail-placeholder">Timeline appears after the quote is saved.</p>
			</div>

			<div class="mk-qt-rail-card mk-qt-rail-card--ai">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-magic"></i></span>
					<h2 class="mk-qt-rail-card__title">Suggestions</h2>
				</div>
				<ul class="mk-qt-ai-list">
					<li>Add products to improve quote completeness</li>
					<li>Set a valid-until date before sending to customer</li>
					<li>Link an opportunity for pipeline tracking</li>
				</ul>
				<p class="mk-qt-rail-note">Visual guidance only — no automated changes.</p>
			</div>
		</aside>
	</div>
</div>
{/strip}
