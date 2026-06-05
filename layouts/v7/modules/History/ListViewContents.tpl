{* History — System Activity Audit Log (TOOLS) *}
{strip}
<div class="mk-history-page">
	<div class="mk-history-page-header">
		<nav class="mk-history-breadcrumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span class="mk-history-breadcrumb__sep">&gt;</span>
			<span>{vtranslate('History', 'History')}</span>
		</nav>
		<div class="mk-history-eyebrow">System Activity</div>
		<h1 class="mk-history-title">{if $LISTVIEW_MODULE_TITLE}{$LISTVIEW_MODULE_TITLE}{else}System Activity Audit Log{/if}</h1>
		<p class="mk-history-subtitle">Audit trail for user actions across internal modules — filter by user, module, action, or date range.</p>
	</div>

	<div class="mk-history-card">
		{assign var=totalLogs value=$HISTORY_ROWS|@count}
		{assign var=createdCount value=0}
		{assign var=editedCount value=0}
		{assign var=statusChangedCount value=0}
		{assign var=approvedCount value=0}
		{assign var=rejectedCount value=0}
		{assign var=deletedCount value=0}
		{foreach from=$HISTORY_ROWS item=ROW}
			{if $ROW.action eq 'Created'}
				{assign var=createdCount value=$createdCount+1}
			{elseif $ROW.action eq 'Edited'}
				{assign var=editedCount value=$editedCount+1}
			{elseif $ROW.action eq 'Status changed'}
				{assign var=statusChangedCount value=$statusChangedCount+1}
			{elseif $ROW.action eq 'Approved'}
				{assign var=approvedCount value=$approvedCount+1}
			{elseif $ROW.action eq 'Rejected'}
				{assign var=rejectedCount value=$rejectedCount+1}
			{elseif $ROW.action eq 'Deleted'}
				{assign var=deletedCount value=$deletedCount+1}
			{/if}
		{/foreach}

		<div class="mk-history-summary-bar">
			<div class="mk-history-summary-label">
				Showing <strong id="historyVisibleCount">{$totalLogs}</strong> of <strong>{$totalLogs}</strong> entries
			</div>
			<div class="mk-history-chip-row" aria-label="History summary">
				<span class="mk-history-chip mk-history-chip--total" title="Total">Total: {$totalLogs}</span>
				<span class="mk-history-chip mk-history-chip--created" title="Created">Created: {$createdCount}</span>
				<span class="mk-history-chip mk-history-chip--edited" title="Edited">Edited: {$editedCount}</span>
				{if $statusChangedCount gt 0}
					<span class="mk-history-chip mk-history-chip--status" title="Status changed">Status: {$statusChangedCount}</span>
				{/if}
				<span class="mk-history-chip mk-history-chip--approved" title="Approved">Approved: {$approvedCount}</span>
				<span class="mk-history-chip mk-history-chip--rejected" title="Rejected">Rejected: {$rejectedCount}</span>
				<span class="mk-history-chip mk-history-chip--deleted" title="Deleted">Deleted: {$deletedCount}</span>
			</div>
		</div>

		<div class="mk-history-filter-bar">
			<div class="mk-history-filter-grid">
				<div class="mk-history-control">
					<label for="historySearch" class="mk-history-control-label">Search</label>
					<input id="historySearch" type="text" class="form-control mk-history-input" placeholder="User, module, action, record, fields..." autocomplete="off" />
				</div>
				<div class="mk-history-control">
					<label for="historyUser" class="mk-history-control-label">User</label>
					<select id="historyUser" class="form-control mk-history-input">
						<option value="All">All</option>
						{foreach from=$HISTORY_USERS key=USER_ID item=USER_NAME}
							<option value="{$USER_ID}">{$USER_NAME|escape:'html'}</option>
						{/foreach}
					</select>
				</div>
				<div class="mk-history-control">
					<label for="historyModule" class="mk-history-control-label">Module</label>
					<select id="historyModule" class="form-control mk-history-input">
						<option value="All">All</option>
						{foreach from=$HISTORY_MODULES item=MOD_NAME}
							<option value="{$MOD_NAME}">{$MOD_NAME|escape:'html'}</option>
						{/foreach}
					</select>
				</div>
				<div class="mk-history-control">
					<label for="historyAction" class="mk-history-control-label">Action</label>
					<select id="historyAction" class="form-control mk-history-input">
						<option value="All">All</option>
						<option value="Created">Created</option>
						<option value="Edited">Edited</option>
						<option value="Approved">Approved</option>
						<option value="Rejected">Rejected</option>
						<option value="Status changed">Status changed</option>
						<option value="Deleted">Deleted</option>
						<option value="Restored">Restored</option>
					</select>
				</div>
				<div class="mk-history-control">
					<label class="mk-history-control-label">Date range</label>
					<div class="mk-history-date-row">
						<input id="historyDateFrom" type="date" class="form-control mk-history-input" aria-label="From date" />
						<span class="mk-history-date-sep">to</span>
						<input id="historyDateTo" type="date" class="form-control mk-history-input" aria-label="To date" />
					</div>
				</div>
			</div>
		</div>

		<div class="mk-history-table-area">
			{if $HISTORY_ROWS|@count gt 0}
				<table class="mk-history-audit-table">
					<thead>
						<tr>
							<th style="width: 168px;">Time</th>
							<th style="width: 148px;">User</th>
							<th style="width: 128px;">Module</th>
							<th style="width: 132px;">Action</th>
							<th style="width: 200px;">Record</th>
							<th>Changed Fields</th>
						</tr>
					</thead>
					<tbody id="historyTableBody">
						{foreach from=$HISTORY_ROWS item=ROW}
							<tr class="history-row"
								data-action="{$ROW.action|escape:'html'}"
								data-userid="{$ROW.userId|escape:'html'}"
								data-user="{$ROW.user|escape:'html'}"
								data-module="{$ROW.module|escape:'html'}"
								data-record="{$ROW.recordLabel|escape:'html'}"
								data-time="{$ROW.time|escape:'html'}"
								data-details="{$ROW.details|escape:'html'}">
								<td class="mk-history-cell--time">{$ROW.time}</td>
								<td class="mk-history-cell--user">{$ROW.user|escape:'html'}</td>
								<td class="mk-history-cell--module">{$ROW.module|escape:'html'}</td>
								<td>
									{if $ROW.action eq 'Created'}
										<span class="mk-history-badge mk-history-badge--created">Created</span>
									{elseif $ROW.action eq 'Approved'}
										<span class="mk-history-badge mk-history-badge--approved">Approved</span>
									{elseif $ROW.action eq 'Rejected'}
										<span class="mk-history-badge mk-history-badge--rejected">Rejected</span>
									{elseif $ROW.action eq 'Deleted'}
										<span class="mk-history-badge mk-history-badge--deleted">Deleted</span>
									{elseif $ROW.action eq 'Restored'}
										<span class="mk-history-badge mk-history-badge--restored">Restored</span>
									{elseif $ROW.action eq 'Status changed'}
										<span class="mk-history-badge mk-history-badge--status-changed">Status changed</span>
									{else}
										<span class="mk-history-badge mk-history-badge--edited">Edited</span>
									{/if}
								</td>
								<td>
									<a class="mk-history-record-link" href="{$ROW.detailUrl}" target="_blank" rel="noopener noreferrer">
										{$ROW.recordLabel|escape:'html'}
									</a>
								</td>
								<td>
									<div class="mk-history-changes" title="{$ROW.details|escape:'html'}">{$ROW.details|escape:'html'}</div>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			{else}
				<div class="mk-history-empty">
					<span class="mk-history-empty__title">No activity found</span>
					No system activity history found for this Tools context.
				</div>
			{/if}
		</div>
	</div>
</div>
{/strip}
