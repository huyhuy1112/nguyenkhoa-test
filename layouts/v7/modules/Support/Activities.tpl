{strip}
{assign var=DATA value=$SUPPORT_ACTIVITIES_DATA}
{assign var=ALL value=$DATA.all}
{assign var=COUNT value=$DATA.counts}

<div class="listViewPageDiv support-activities-page">
	<div class="contentsDiv">
		<div class="detailViewContainer">
			<div class="listViewPageHeader row-fluid">
				<div class="span8">
					<h3 class="module-title">Activities Dashboard</h3>
					<p class="muted" style="margin-top:6px;">
						Overview of support activities in read-only mode.
					</p>
				</div>
			</div>

			<div class="row-fluid" style="margin:12px 0 16px 0;">
				<div class="span3">
					<div class="well well-small" style="text-align:center;">
						<div class="muted">Total Activities</div>
						<div style="font-size:22px;font-weight:600;line-height:1.2;">{$COUNT.all}</div>
					</div>
				</div>
				<div class="span3">
					<div class="well well-small" style="text-align:center;">
						<div class="muted">Tasks</div>
						<div style="font-size:22px;font-weight:600;line-height:1.2;">{$COUNT.tasks}</div>
					</div>
				</div>
				<div class="span3">
					<div class="well well-small" style="text-align:center;">
						<div class="muted">Events</div>
						<div style="font-size:22px;font-weight:600;line-height:1.2;">{$COUNT.events}</div>
					</div>
				</div>
				<div class="span3">
					<div class="well well-small" style="text-align:center;">
						<div class="muted">Anniversaries</div>
						<div style="font-size:22px;font-weight:600;line-height:1.2;">{$COUNT.anniversaries}</div>
					</div>
				</div>
			</div>

			<div class="table-container">
				<table class="table table-bordered table-hover table-condensed listViewEntriesTable" id="SupportActivitiesTable">
					<thead>
						<tr>
							<th>Date</th>
							<th>Subject</th>
							<th>Type</th>
							<th>Assigned To</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						{if $ALL|@count gt 0}
							{foreach from=$ALL item=ROW}
								<tr data-activity-type="{$ROW.activitytype|strtolower|escape}" data-detail-url="{$ROW.detail_url|escape}">
									<td>{$ROW.date_start|escape}</td>
									<td>
										<a href="{$ROW.detail_url|escape}" target="_blank">
											{$ROW.subject|escape}
										</a>
									</td>
									<td>
										<span class="sa-tag {$ROW.tagClass|escape}">
											{if $ROW.activitytype|strtolower eq 'task'}
												✔
											{elseif $ROW.activitytype|strtolower eq 'meeting'}
												📅
											{elseif $ROW.activitytype|strtolower eq 'call'}
												📞
											{elseif $ROW.activitytype|strtolower eq 'anniversary'}
												🎂
											{/if}
											{$ROW.activitytype|escape}
										</span>
									</td>
									<td>{$ROW.assigned_to|escape}</td>
									<td>{$ROW.status|default:'—'|escape}</td>
								</tr>
							{/foreach}
						{else}
							<tr>
								<td colspan="5" class="text-muted text-center">No activities found.</td>
							</tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
{/strip}

