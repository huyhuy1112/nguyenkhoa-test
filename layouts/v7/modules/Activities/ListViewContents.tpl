{* Activities list — card feed UI *}
{strip}
{assign var=MK_ACT_IS_SUPPORT value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
	{assign var=MK_ACT_IS_SUPPORT value=true}
{/if}

{if $MK_ACT_IS_SUPPORT}
<div class="mk-act-v2">
	<div class="mk-act-v2__shell">
		<div class="mk-act-v2__top">
			<div class="mk-act-v2__intro">
				<nav class="mk-act-v2__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Support</a>
					<span aria-hidden="true">/</span>
					<span>Activities</span>
				</nav>
				<h1 class="mk-act-v2__title">Activities Dashboard</h1>
				<p class="mk-act-v2__desc">Overview of support activities in read-only mode.</p>
			</div>
			<div class="mk-act-v2__top-actions">
				<a class="mk-act-v2__btn mk-act-v2__btn--primary" href="index.php?module=Activities&amp;view=Edit&amp;app=SUPPORT">+ Add Record</a>
				<a class="mk-act-v2__btn mk-act-v2__btn--outline" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Tickets</a>
			</div>
		</div>

		<div class="mk-act-v2__metrics" role="group" aria-label="Activity summary">
			<div class="mk-act-v2__metric">
				<span class="mk-act-v2__metric-label">Total</span>
				<strong class="mk-act-v2__metric-value">{$ACTIVITY_COUNTS.all|default:0}</strong>
			</div>
			<div class="mk-act-v2__metric">
				<span class="mk-act-v2__metric-label">Tasks</span>
				<strong class="mk-act-v2__metric-value">{$ACTIVITY_COUNTS.tasks|default:0}</strong>
			</div>
			<div class="mk-act-v2__metric">
				<span class="mk-act-v2__metric-label">Events</span>
				<strong class="mk-act-v2__metric-value">{$ACTIVITY_COUNTS.events|default:0}</strong>
			</div>
			<div class="mk-act-v2__metric">
				<span class="mk-act-v2__metric-label">Anniversaries</span>
				<strong class="mk-act-v2__metric-value">{$ACTIVITY_COUNTS.anniversaries|default:0}</strong>
			</div>
		</div>

		<form method="get" action="index.php" class="mk-act-v2__filters" id="ActivitiesFilterForm">
			<input type="hidden" name="module" value="Activities" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="SUPPORT" />
			<input type="search" name="q" class="mk-act-v2__search" placeholder="Search subject…" value="{$FILTER_KEYWORD|escape:'html'}" />
			<select name="status" class="mk-act-v2__select" aria-label="Status">
				<option value="">All status</option>
				{foreach from=$STATUS_OPTIONS item=OPT}
					<option value="{$OPT|escape:'html'}" {if $FILTER_STATUS eq $OPT}selected="selected"{/if}>{$OPT|escape:'html'}</option>
				{/foreach}
			</select>
			<select name="activity_type" class="mk-act-v2__select" aria-label="Type">
				<option value="">All types</option>
				{foreach from=$TYPE_OPTIONS item=OPT}
					<option value="{$OPT|escape:'html'}" {if $FILTER_TYPE eq $OPT}selected="selected"{/if}>{$OPT|escape:'html'}</option>
				{/foreach}
			</select>
			<select name="assigned_user_id" class="mk-act-v2__select" aria-label="Staff">
				<option value="">All staff</option>
				{foreach from=$USERS item=U}
					<option value="{$U.id}" {if $FILTER_STAFF eq $U.id}selected="selected"{/if}>{$U.first_name|escape:'html'} {$U.last_name|escape:'html'}</option>
				{/foreach}
			</select>
			<input type="date" name="activity_date" class="mk-act-v2__date" value="{$FILTER_DATE|escape:'html'}" aria-label="Date" />
			<select name="sort" class="mk-act-v2__select" aria-label="Sort">
				<option value="latest" {if $FILTER_SORT ne 'oldest'}selected="selected"{/if}>Latest</option>
				<option value="oldest" {if $FILTER_SORT eq 'oldest'}selected="selected"{/if}>Oldest</option>
			</select>
			<button type="submit" class="mk-act-v2__btn mk-act-v2__btn--filter">Apply</button>
			<a href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT" class="mk-act-v2__btn mk-act-v2__btn--ghost">Clear</a>
		</form>

		<div class="mk-act-v2__feed-head">
			<p class="mk-act-v2__feed-count" id="mkActActivityCount">
				Showing <strong>{$SHOW_FROM}</strong>–<strong>{$SHOW_TO}</strong> of <strong>{$TOTAL_COUNT}</strong>
			</p>
		</div>

		<div class="mk-act-v2__feed" id="mkActActivityFeed">
			{foreach from=$ACTIVITIES item=ROW}
				<article class="mk-act-v2__card {$ROW.tagClass|escape:'html'}">
					<div class="mk-act-v2__card-date">
						<span class="mk-act-v2__card-date-label">Date</span>
						<time datetime="{$ROW.date_start|escape:'html'}">{$ROW.date_start|escape:'html'|default:'—'}</time>
					</div>
					<div class="mk-act-v2__card-body">
						<a class="mk-act-v2__card-subject" href="{$ROW.detail_url|escape:'html'}">{$ROW.subject|escape:'html'|default:'—'}</a>
						<div class="mk-act-v2__card-meta">
							<span class="mk-act-v2__tag {$ROW.tagClass|escape:'html'}">
								<span aria-hidden="true">{$ROW.type_icon|escape:'html'}</span>
								{$ROW.activitytype|escape:'html'|default:'—'}
							</span>
							<span class="mk-act-v2__meta-item">Assigned: {$ROW.assigned_display|escape:'html'|default:'—'}</span>
							{if $ROW.status|default:'' neq ''}
								<span class="mk-act-v2__status mk-act-v2__status--{$ROW.status|replace:' ':'_'|lower}">{$ROW.status|escape:'html'}</span>
							{/if}
						</div>
					</div>
					<a class="mk-act-v2__card-link" href="{$ROW.detail_url|escape:'html'}" aria-label="View activity">View</a>
				</article>
			{foreachelse}
				<div class="mk-act-v2__empty">No activities found.</div>
			{/foreach}
		</div>

		{if $PAGE_COUNT gt 1}
			<nav class="mk-act-v2__pagination" aria-label="Activities pagination">
				{if $CURRENT_PAGE gt 1}
					<a class="mk-act-v2__page" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT&amp;page={$CURRENT_PAGE-1}&amp;status={$FILTER_STATUS|escape:'url'}&amp;activity_type={$FILTER_TYPE|escape:'url'}&amp;assigned_user_id={$FILTER_STAFF|escape:'url'}&amp;activity_date={$FILTER_DATE|escape:'url'}&amp;sort={$FILTER_SORT|escape:'url'}&amp;q={$FILTER_KEYWORD|escape:'url'}">&lsaquo; Prev</a>
				{/if}
				<span class="mk-act-v2__page mk-act-v2__page--current">Page {$CURRENT_PAGE} / {$PAGE_COUNT}</span>
				{if $CURRENT_PAGE lt $PAGE_COUNT}
					<a class="mk-act-v2__page" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT&amp;page={$CURRENT_PAGE+1}&amp;status={$FILTER_STATUS|escape:'url'}&amp;activity_type={$FILTER_TYPE|escape:'url'}&amp;assigned_user_id={$FILTER_STAFF|escape:'url'}&amp;activity_date={$FILTER_DATE|escape:'url'}&amp;sort={$FILTER_SORT|escape:'url'}&amp;q={$FILTER_KEYWORD|escape:'url'}">Next &rsaquo;</a>
				{/if}
			</nav>
		{/if}
	</div>
</div>
{else}
<div class="container-fluid">
	<p class="text-muted">Use SUPPORT app for the modern activities UI.</p>
</div>
{/if}
{/strip}
