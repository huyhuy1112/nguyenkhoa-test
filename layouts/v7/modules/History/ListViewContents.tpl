{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

<div class="history-dashboard">
	<div class="history-header">
		<div class="history-header-inner">
			<div class="history-title-block">
				<div class="history-eyebrow">System Activity</div>
				<h2 class="history-title">System Activity Audit Log</h2>
				<div class="history-subtitle">Audit trail for user control across internal modules</div>
			</div>
			<div class="history-summary">
				{assign var=totalLogs value=$HISTORY_ROWS|@count}
				{assign var=createdCount value=0}
				{assign var=editedCount value=0}
				{assign var=statusChangedCount value=0}
				{assign var=approvedCount value=0}
				{assign var=rejectedCount value=0}
				{assign var=deletedCount value=0}
				{assign var=restoredCount value=0}
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
					{elseif $ROW.action eq 'Restored'}
						{assign var=restoredCount value=$restoredCount+1}
					{/if}
				{/foreach}
				<div class="history-chip-row" aria-label="History summary">
					<span class="history-chip history-chip--total" title="Total logs">Total: {$totalLogs}</span>
					<span class="history-chip history-chip--created" title="Created">Created: {$createdCount}</span>
					<span class="history-chip history-chip--edited" title="Edited">Edited: {$editedCount}</span>
					<span class="history-chip history-chip--approved" title="Approved">Approved: {$approvedCount}</span>
					<span class="history-chip history-chip--rejected" title="Rejected">Rejected: {$rejectedCount}</span>
					<span class="history-chip history-chip--rejected" title="Deleted">Deleted: {$deletedCount}</span>
				</div>
			</div>
		</div>
	</div>

	<div class="history-controls">
		<div class="history-controls-inner">
			<div class="history-control">
				<label for="historySearch" class="history-control-label">Search</label>
				<input id="historySearch" type="text" class="form-control history-input" placeholder="User, module, action, record, fields..." autocomplete="off" />
			</div>
			<div class="history-control">
				<label for="historyUser" class="history-control-label">User</label>
				<select id="historyUser" class="form-control history-input">
					<option value="All">All</option>
					{foreach from=$HISTORY_USERS key=USER_ID item=USER_NAME}
						<option value="{$USER_ID}">{$USER_NAME|escape:'html'}</option>
					{/foreach}
				</select>
			</div>
			<div class="history-control">
				<label for="historyModule" class="history-control-label">Module</label>
				<select id="historyModule" class="form-control history-input">
					<option value="All">All</option>
					{foreach from=$HISTORY_MODULES item=MOD_NAME}
						<option value="{$MOD_NAME}">{$MOD_NAME|escape:'html'}</option>
					{/foreach}
				</select>
			</div>
			<div class="history-control">
				<label for="historyAction" class="history-control-label">Action</label>
				<select id="historyAction" class="form-control history-input">
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
			<div class="history-control history-control--date">
				<label class="history-control-label">Date</label>
				<div class="history-date-row">
					<input id="historyDateFrom" type="date" class="form-control history-input" />
					<span class="history-date-sep">to</span>
					<input id="historyDateTo" type="date" class="form-control history-input" />
				</div>
			</div>
		</div>
	</div>

	<div class="history-table-wrap">
		<div class="history-table-inner">
			{if $HISTORY_ROWS|@count gt 0}
				<div class="table-responsive">
					<table class="history-audit-table">
						<thead>
							<tr>
								<th style="width: 190px;">Time</th>
								<th style="width: 170px;">User</th>
								<th style="width: 160px;">Module</th>
								<th style="width: 140px;">Action</th>
								<th style="width: 260px;">Record</th>
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
									<td class="history-cell history-cell--time">{$ROW.time}</td>
									<td class="history-cell">{$ROW.user|escape:'html'}</td>
									<td class="history-cell">{$ROW.module|escape:'html'}</td>
									<td class="history-cell">
										{if $ROW.action eq 'Created'}
											<span class="history-badge history-badge--created">Created</span>
										{elseif $ROW.action eq 'Approved'}
											<span class="history-badge history-badge--approved">Approved</span>
										{elseif $ROW.action eq 'Rejected'}
											<span class="history-badge history-badge--rejected">Rejected</span>
										{elseif $ROW.action eq 'Deleted'}
											<span class="history-badge history-badge--deleted">Deleted</span>
										{elseif $ROW.action eq 'Restored'}
											<span class="history-badge history-badge--restored">Restored</span>
										{elseif $ROW.action eq 'Status changed'}
											<span class="history-badge history-badge--status-changed">Status changed</span>
										{else}
											<span class="history-badge history-badge--edited">Edited</span>
										{/if}
									</td>
									<td class="history-cell">
										<a class="history-order-link" href="{$ROW.detailUrl}" target="_blank" rel="noopener noreferrer">
											{$ROW.recordLabel|escape:'html'}
										</a>
									</td>
									<td class="history-cell">
										<div class="history-changes" title="{$ROW.details|escape:'html'}">{$ROW.details|escape:'html'}</div>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
				<script type="text/javascript">
					(function() {
						var tableBody = document.getElementById('historyTableBody');
						if (!tableBody) return;

						var searchEl = document.getElementById('historySearch');
						var userEl = document.getElementById('historyUser');
						var moduleEl = document.getElementById('historyModule');
						var actionEl = document.getElementById('historyAction');
						var fromEl = document.getElementById('historyDateFrom');
						var toEl = document.getElementById('historyDateTo');

						var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.history-row'));

						function parseDate(s) {
							if (!s) return null;
							var d = new Date(s + 'T00:00:00');
							return isNaN(d.getTime()) ? null : d;
						}

						function parseRowTime(t) {
							if (!t) return null;
							var normalized = String(t).replace(' ', 'T');
							var d = new Date(normalized);
							return isNaN(d.getTime()) ? null : d;
						}

						function applyFilters() {
							var search = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase().trim();
							var userId = (userEl ? userEl.value : 'All');
							var module = (moduleEl ? moduleEl.value : 'All');
							var action = (actionEl ? actionEl.value : 'All');
							var fromD = parseDate(fromEl ? fromEl.value : '');
							var toD = parseDate(toEl ? toEl.value : '');

							rows.forEach(function(row) {
								var actionV = row.getAttribute('data-action') || '';
								var userIdV = row.getAttribute('data-userid') || '';
								var userNameV = row.getAttribute('data-user') || '';
								var moduleV = row.getAttribute('data-module') || '';
								var recordV = row.getAttribute('data-record') || '';
								var detailsV = row.getAttribute('data-details') || '';
								var timeV = row.getAttribute('data-time') || '';

								var haystack = (actionV + ' ' + userNameV + ' ' + userIdV + ' ' + moduleV + ' ' + recordV + ' ' + detailsV).toLowerCase();

								var ok = true;
								if (search) ok = ok && haystack.indexOf(search) !== -1;
								if (action && action !== 'All') ok = ok && actionV === action;
								if (userId && userId !== 'All') ok = ok && String(userIdV) === String(userId);
								if (module && module !== 'All') ok = ok && moduleV === module;

								var rowD = parseRowTime(timeV);
								if (fromD) ok = ok && rowD && rowD >= fromD;
								if (toD) {
									var end = new Date(toD.getTime());
									end.setHours(23, 59, 59, 999);
									ok = ok && rowD && rowD <= end;
								}

								row.style.display = ok ? '' : 'none';
							});
						}

						[searchEl, userEl, moduleEl, actionEl, fromEl, toEl].forEach(function(el) {
							if (!el) return;
							el.addEventListener('input', applyFilters);
							el.addEventListener('change', applyFilters);
						});
					})();
				</script>
			{else}
				<div class="alert alert-info history-empty">
					No system activity history found for this Tools context.
				</div>
			{/if}
        </div>
    </div>
</div>