{* Documents History — Premium activity log (MANAGEMENT content area) *}
{strip}
{if !isset($SELECTED_MENU_CATEGORY)}
	{assign var=SELECTED_MENU_CATEGORY value=""}
{/if}
{assign var=APP_PARAM value=""}
{if $SELECTED_MENU_CATEGORY}
	{assign var=APP_PARAM value="&app={$SELECTED_MENU_CATEGORY}"}
{/if}
{assign var=HISTORY_COUNT value=$HISTORY_ROWS|@count}

<div class="mk-doc-history" id="mkDocHistory">
	<header class="mk-doc-history-hero">
		<nav class="mk-doc-history-hero__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Documents&amp;view=List&amp;app=MANAGEMENT">{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</a>
			<span class="mk-doc-history-hero__sep" aria-hidden="true">/</span>
			<a href="index.php?module=Documents&amp;view=List{$APP_PARAM}">{vtranslate('Documents', 'Documents')}</a>
			<span class="mk-doc-history-hero__sep" aria-hidden="true">/</span>
			<span class="mk-doc-history-hero__current">Activity</span>
		</nav>
		<div class="mk-doc-history-hero__row">
			<div class="mk-doc-history-hero__copy">
				<h1 class="mk-doc-history-hero__title">
					<span class="mk-doc-history-hero__icon" aria-hidden="true">
						<svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M14 6v8l5 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="14" cy="14" r="11" stroke="currentColor" stroke-width="2"/></svg>
					</span>
					Activity history
				</h1>
				<p class="mk-doc-history-hero__sub">Audit trail of uploads, edits, moves, and deletions across all documents.</p>
			</div>
			<div class="mk-doc-history-hero__actions">
				<a href="index.php?module=Documents&amp;view=List{$APP_PARAM}" class="mk-doc-hbtn mk-doc-hbtn--ghost">
					<i class="fa fa-arrow-left" aria-hidden="true"></i>
					<span>Back to documents</span>
				</a>
			</div>
		</div>
	</header>

	<div class="mk-doc-history-stats">
		<div class="mk-doc-history-stat">
			<span class="mk-doc-history-stat__value">{$HISTORY_COUNT}</span>
			<span class="mk-doc-history-stat__label">Events logged</span>
		</div>
		<div class="mk-doc-history-stat mk-doc-history-stat--muted">
			<span class="mk-doc-history-stat__value">100</span>
			<span class="mk-doc-history-stat__label">Max shown</span>
		</div>
	</div>

	<div class="mk-doc-history-toolbar">
		<div class="mk-doc-history-search">
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4"/><path d="M10.5 10.5 14 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
			<input type="search" id="mkDocHistorySearch" class="mk-doc-history-search__input" placeholder="Search file, user, or action…" autocomplete="off" />
		</div>
		<div class="mk-doc-history-filters" role="group" aria-label="Filter by action">
			<button type="button" class="mk-doc-history-filter is-active" data-filter="all">All</button>
			<button type="button" class="mk-doc-history-filter" data-filter="created">Created</button>
			<button type="button" class="mk-doc-history-filter" data-filter="updated">Updated</button>
			<button type="button" class="mk-doc-history-filter" data-filter="deleted">Deleted</button>
		</div>
	</div>

	<div class="mk-doc-history-card">
		{if $HISTORY_COUNT gt 0}
			<div class="mk-doc-history-table-wrap" role="region" aria-label="Activity log">
				<table class="mk-doc-history-table" id="mkDocHistoryTable">
					<thead>
						<tr>
							<th scope="col">When</th>
							<th scope="col">Document</th>
							<th scope="col">Action</th>
							<th scope="col">User</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$HISTORY_ROWS item=ROW}
							<tr class="mk-doc-history-row" data-action="{$ROW.actionSlug|escape:'html'}" data-search="{$ROW.label|escape:'html'} {$ROW.user|escape:'html'} {$ROW.action|escape:'html'}">
								<td class="mk-doc-history-row__time">
									<time datetime="{$ROW.changedon}" class="mk-doc-history-time" data-timestamp="{$ROW.changedon}">{$ROW.changedon}</time>
								</td>
								<td class="mk-doc-history-row__file">
									<a href="{$ROW.detailUrl}" class="mk-doc-history-file-link" target="_blank" rel="noopener noreferrer">
										<span class="mk-doc-history-file-icon" aria-hidden="true">
											<svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M5 3h5l4 4v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.3"/><path d="M10 3v4h4" stroke="currentColor" stroke-width="1.3"/></svg>
										</span>
										<span class="mk-doc-history-file-name">{$ROW.label|escape:'html'}</span>
									</a>
								</td>
								<td class="mk-doc-history-row__action">
									<span class="mk-doc-history-badge mk-doc-history-badge--{$ROW.actionSlug|escape:'html'}">{$ROW.action|escape:'html'}</span>
								</td>
								<td class="mk-doc-history-row__user">
									<span class="mk-doc-history-user">
										<span class="mk-doc-history-avatar" aria-hidden="true">{$ROW.userInitial|escape:'html'}</span>
										<span class="mk-doc-history-user-name">{$ROW.user|escape:'html'}</span>
									</span>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<p class="mk-doc-history-no-match" id="mkDocHistoryNoMatch" hidden>No entries match your search or filter.</p>
		{else}
			<div class="mk-doc-history-empty">
				<div class="mk-doc-history-empty__icon" aria-hidden="true">
					<svg width="56" height="56" viewBox="0 0 56 56" fill="none"><circle cx="28" cy="28" r="22" stroke="currentColor" stroke-width="2"/><path d="M28 16v14l8 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</div>
				<h2 class="mk-doc-history-empty__title">No activity yet</h2>
				<p class="mk-doc-history-empty__text">When documents are created or updated, events will appear here.</p>
				<a href="index.php?module=Documents&amp;view=List{$APP_PARAM}" class="mk-doc-hbtn mk-doc-hbtn--primary">Go to documents</a>
			</div>
		{/if}
	</div>
</div>
{/strip}
