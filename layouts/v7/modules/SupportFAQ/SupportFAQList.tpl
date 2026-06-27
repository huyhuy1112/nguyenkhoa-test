{* Support FAQ list – mockup (All FAQ) *}
<div class="mk-sf-faq-page" data-mk-sf-faq="1">
	<nav class="mk-sf-faq-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php?module=SupportFAQ&amp;view=List&amp;app=SUPPORT" class="mk-sf-faq-breadcrumb__link">Support FAQ</a>
		<span class="mk-sf-faq-breadcrumb__sep" aria-hidden="true">{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-right'}</span>
		<span class="mk-sf-faq-breadcrumb__current">All FAQ</span>
	</nav>

	<div class="mk-sf-faq-hero">
		<div class="mk-sf-faq-shared">
			<div class="mk-sf-faq-shared__label">SHARED LIST</div>
			<div class="mk-sf-faq-shared__select-wrap">
				<select class="mk-sf-faq-shared__select" name="shared_list" aria-label="Shared list">
					<option value="all" selected>All</option>
				</select>
				<span class="mk-sf-faq-shared__chevron" aria-hidden="true">{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-down'}</span>
			</div>
		</div>

		<div class="mk-sf-faq-hero__center">
			<h1 class="mk-sf-faq-hero__title">All FAQ</h1>
			<div class="mk-sf-faq-tags">
				<span class="mk-sf-faq-tags__label">Tag clouds</span>
				<span class="mk-sf-faq-tag mk-sf-faq-tag--1">Tag 1</span>
				<span class="mk-sf-faq-tag mk-sf-faq-tag--2">Tag 2</span>
				<span class="mk-sf-faq-tag mk-sf-faq-tag--3">Tag 3</span>
				<span class="mk-sf-faq-tag mk-sf-faq-tag--4">Tag 4</span>
			</div>
		</div>

		<div class="mk-sf-faq-hero__actions">
			<a href="#" class="mk-sf-faq-btn mk-sf-faq-btn--outline" title="Import">
				<span class="mk-sf-faq-btn__icon">{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='import'}</span>
				<span>IMPORT</span>
			</a>
			<a href="#" class="mk-sf-faq-btn mk-sf-faq-btn--outline" title="Customize">
				<span class="mk-sf-faq-btn__icon">{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='customize'}</span>
				<span>CUSTOMIZE</span>
			</a>
			<a href="index.php?module=SupportFAQ&amp;view=Edit&amp;app=SUPPORT" class="mk-sf-faq-btn mk-sf-faq-btn--primary">
				<span class="mk-sf-faq-btn__icon">{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='plus'}</span>
				<span>+ ADD RECORD</span>
			</a>
		</div>
	</div>

	<div class="mk-sf-faq-card">
		<div class="mk-sf-faq-card__toolbar">
			<div class="mk-sf-faq-card__toolbar-left">
				<div class="mk-sf-faq-view-toggle" role="group" aria-label="View mode">
					<button type="button" class="mk-sf-faq-view-toggle__btn" data-view-mode="grid" title="Grid view" aria-pressed="false">
						{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='grid'}
					</button>
					<button type="button" class="mk-sf-faq-view-toggle__btn" data-view-mode="list" title="List view" aria-pressed="false">
						{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='list'}
					</button>
				</div>
				<p class="mk-sf-faq-card__summary">
					Showing <strong>{$FAQ_SHOW_FROM|default:0}</strong> to <strong>{$FAQ_SHOW_TO|default:0}</strong> of <strong>{$FAQ_TOTAL|default:0}</strong> FAQs
				</p>
			</div>
			<div class="mk-sf-faq-card__toolbar-right">
				<button type="button" class="mk-sf-faq-icon-btn" title="Filter" aria-label="Filter">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='filter'}
				</button>
				<button type="button" class="mk-sf-faq-icon-btn" title="Sort" aria-label="Sort">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='sort'}
				</button>
			</div>
		</div>

		<div class="mk-sf-faq-card__body">
		<div class="mk-sf-faq-list-view">
		<div class="mk-sf-faq-table-wrap">
			<table class="mk-sf-faq-table" role="grid">
				<colgroup>
					<col class="mk-sf-faq-col mk-sf-faq-col--check" />
					<col class="mk-sf-faq-col mk-sf-faq-col--actions" />
					<col class="mk-sf-faq-col mk-sf-faq-col--question" />
					<col class="mk-sf-faq-col mk-sf-faq-col--triggered" />
					<col class="mk-sf-faq-col mk-sf-faq-col--ticket" />
					<col class="mk-sf-faq-col mk-sf-faq-col--creator" />
				</colgroup>
				<thead>
					<tr>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--check">
							<input type="checkbox" class="mk-sf-faq-check-all" aria-label="Select all" />
						</th>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--actions">Actions</th>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--question">QUESTION</th>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--triggered">TRIGGERED</th>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--ticket">RELATED TICKET</th>
						<th scope="col" class="mk-sf-faq-th mk-sf-faq-th--creator">CREATED BY</th>
					</tr>
				</thead>
				<tbody>
					{if $FAQ_RECORDS|@count gt 0}
						{foreach from=$FAQ_RECORDS item=ROW}
							<tr class="mk-sf-faq-row">
								<td class="mk-sf-faq-td mk-sf-faq-td--check">
									<input type="checkbox" class="mk-sf-faq-row-check" value="{$ROW.supportfaqid}" aria-label="Select row" />
								</td>
								<td class="mk-sf-faq-td mk-sf-faq-td--actions">
									<div class="mk-sf-faq-row-actions">
										<button type="button" class="mk-sf-faq-row-action" title="Favorite" aria-label="Favorite">
											{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='star'}
										</button>
										<a href="index.php?module=SupportFAQ&amp;view=Detail&amp;record={$ROW.supportfaqid}&amp;app=SUPPORT" class="mk-sf-faq-row-action" title="View" aria-label="View">
											{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='eye'}
										</a>
										<button type="button" class="mk-sf-faq-row-action" title="More" aria-label="More actions">
											{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='more'}
										</button>
									</div>
								</td>
								<td class="mk-sf-faq-td mk-sf-faq-td--question">
									<a href="index.php?module=SupportFAQ&amp;view=Detail&amp;record={$ROW.supportfaqid}&amp;app=SUPPORT" class="mk-sf-faq-question-link" title="{decode_html($ROW.question)|escape:'html'}">
										{decode_html($ROW.question)|escape:'html'}
									</a>
								</td>
								<td class="mk-sf-faq-td mk-sf-faq-td--triggered">{$ROW.occurrence_count|default:0}</td>
								<td class="mk-sf-faq-td mk-sf-faq-td--ticket">{$ROW.related_ticket_id|default:0}</td>
								<td class="mk-sf-faq-td mk-sf-faq-td--creator">
									<div class="mk-sf-faq-creator">
										<span class="mk-sf-faq-creator__avatar" aria-hidden="true">{$ROW.created_by_initials|escape}</span>
										<span class="mk-sf-faq-creator__name">{$ROW.created_by_name|escape}</span>
									</div>
								</td>
							</tr>
						{/foreach}
					{else}
						<tr>
							<td colspan="6" class="mk-sf-faq-empty">No FAQs found.</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>
		</div>

		<div class="mk-sf-faq-grid-view" hidden>
			{if $FAQ_RECORDS|@count gt 0}
			<div class="mk-sf-faq-grid" role="list">
				{foreach from=$FAQ_RECORDS item=ROW}
				<article class="mk-sf-faq-grid-card" role="listitem">
					<div class="mk-sf-faq-grid-card__head">
						<input type="checkbox" class="mk-sf-faq-row-check" value="{$ROW.supportfaqid}" aria-label="Select FAQ" />
						<div class="mk-sf-faq-row-actions">
							<button type="button" class="mk-sf-faq-row-action" title="Favorite" aria-label="Favorite">
								{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='star'}
							</button>
							<a href="index.php?module=SupportFAQ&amp;view=Detail&amp;record={$ROW.supportfaqid}&amp;app=SUPPORT" class="mk-sf-faq-row-action" title="View" aria-label="View">
								{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='eye'}
							</a>
							<button type="button" class="mk-sf-faq-row-action" title="More" aria-label="More actions">
								{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='more'}
							</button>
						</div>
					</div>
					<a href="index.php?module=SupportFAQ&amp;view=Detail&amp;record={$ROW.supportfaqid}&amp;app=SUPPORT" class="mk-sf-faq-grid-card__question" title="{decode_html($ROW.question)|escape:'html'}">
						{decode_html($ROW.question)|escape:'html'}
					</a>
					<dl class="mk-sf-faq-grid-card__meta">
						<div class="mk-sf-faq-grid-card__meta-item">
							<dt>Triggered</dt>
							<dd>{$ROW.occurrence_count|default:0}</dd>
						</div>
						<div class="mk-sf-faq-grid-card__meta-item">
							<dt>Related ticket</dt>
							<dd>{$ROW.related_ticket_id|default:0}</dd>
						</div>
					</dl>
					<div class="mk-sf-faq-creator mk-sf-faq-grid-card__creator">
						<span class="mk-sf-faq-creator__avatar" aria-hidden="true">{$ROW.created_by_initials|escape}</span>
						<span class="mk-sf-faq-creator__name">{$ROW.created_by_name|escape}</span>
					</div>
				</article>
				{/foreach}
			</div>
			{else}
			<p class="mk-sf-faq-grid-empty">No FAQs found.</p>
			{/if}
		</div>
		</div>

		{if $FAQ_PAGES|default:1 gt 1}
		<nav class="mk-sf-faq-pagination" aria-label="Pagination">
			{if $FAQ_PAGE gt 1}
				<a class="mk-sf-faq-pagination__arrow" href="index.php?module=SupportFAQ&amp;view=List&amp;app=SUPPORT&amp;page={$FAQ_PAGE-1}{$FAQ_SEARCH_QUERY|default:''}" aria-label="Previous page">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-left'}
				</a>
			{else}
				<span class="mk-sf-faq-pagination__arrow is-disabled" aria-hidden="true">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-left'}
				</span>
			{/if}

			<div class="mk-sf-faq-pagination__pages">
				{foreach from=$FAQ_PAGINATION item=P}
					{if $P eq '…'}
						<span class="mk-sf-faq-pagination__ellipsis">…</span>
					{elseif $P eq $FAQ_PAGE}
						<span class="mk-sf-faq-pagination__page is-active" aria-current="page">{$P}</span>
					{else}
						<a class="mk-sf-faq-pagination__page" href="index.php?module=SupportFAQ&amp;view=List&amp;app=SUPPORT&amp;page={$P}{$FAQ_SEARCH_QUERY|default:''}">{$P}</a>
					{/if}
				{/foreach}
			</div>

			{if $FAQ_PAGE lt $FAQ_PAGES}
				<a class="mk-sf-faq-pagination__arrow" href="index.php?module=SupportFAQ&amp;view=List&amp;app=SUPPORT&amp;page={$FAQ_PAGE+1}{$FAQ_SEARCH_QUERY|default:''}" aria-label="Next page">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-right'}
				</a>
			{else}
				<span class="mk-sf-faq-pagination__arrow is-disabled" aria-hidden="true">
					{include file='partials/SupportFAQListSvgIcon.tpl'|vtemplate_path:'SupportFAQ' ICON='chevron-right'}
				</span>
			{/if}
		</nav>
		{/if}
	</div>
</div>
