{strip}
<div class="container-fluid activities-page activities-list-wrapper">
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default activities-card">
				<div class="panel-heading">
					<h4 class="panel-title">Activities</h4>
				</div>
				<div class="panel-body">
					<form method="get" action="index.php" class="form-inline activities-filter-form" style="margin-bottom:16px;">
						<input type="hidden" name="module" value="Activities" />
						<input type="hidden" name="view" value="List" />
						<input type="hidden" name="app" value="SUPPORT" />
						<div class="form-group">
							<label class="control-label">Status</label>
							<select name="status" class="form-control input-sm">
								<option value="">-- All --</option>
								{foreach from=$STATUS_OPTIONS item=opt}
									<option value="{$opt|escape}" {if $STATUS_FILTER eq $opt}selected{/if}>{$opt|escape}</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group">
							<label class="control-label">Type</label>
							<select name="activity_type" class="form-control input-sm">
								<option value="">-- All --</option>
								{foreach from=$TYPE_OPTIONS item=opt}
									<option value="{$opt|escape}" {if $TYPE_FILTER eq $opt}selected{/if}>{$opt|escape}</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group">
							<label class="control-label">Staff</label>
							<select name="assigned_user_id" class="form-control input-sm">
								<option value="">-- All --</option>
								{foreach from=$USERS item=u}
									<option value="{$u.id}" {if $STAFF_FILTER eq $u.id}selected{/if}>
										{$u.first_name|escape} {$u.last_name|escape}
									</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group">
							<label class="control-label">Date</label>
							<input type="date" name="activity_date" class="form-control input-sm" value="{$DATE_FILTER|escape}" />
						</div>
						<div class="form-group">
							<label class="control-label">Sort</label>
							<select name="sort" class="form-control input-sm">
								<option value="latest" {if $SORT_FILTER ne 'oldest'}selected{/if}>Latest first</option>
								<option value="oldest" {if $SORT_FILTER eq 'oldest'}selected{/if}>Oldest first</option>
							</select>
						</div>
						<div class="form-group">
							<input type="text" name="q" class="form-control input-sm" placeholder="Search..." value="{$KEYWORD|escape}" />
						</div>
						<button type="submit" class="btn btn-default btn-sm">Filter</button>
						<a href="index.php?module=Activities&view=List&app=SUPPORT" class="btn btn-link btn-sm">Clear Filters</a>
						<a href="index.php?module=Activities&view=Edit&app=SUPPORT" class="btn btn-primary btn-sm pull-right">+ Add Activity</a>
					</form>

					<table class="table table-bordered table-striped activities-table">
						<thead>
							<tr>
								<th>Content</th>
								<th>Type</th>
								<th>Organization</th>
								<th>Ticket</th>
								<th>Assigned To</th>
								<th>Date</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							{if $ACTIVITIES|@count eq 0}
								<tr><td colspan="8" class="text-center text-muted">No activities found.</td></tr>
							{else}
								{foreach from=$ACTIVITIES item=row}
									<tr>
										<td><a href="index.php?module=Activities&view=Detail&record={$row.activityid}&app=SUPPORT">{$row.content|escape|default:'-'}</a></td>
										<td>{$row.activity_type|escape|default:'-'}</td>
										<td>{$row.org_name|escape|default:'-'}</td>
										<td>{if $row.ticketid|default:'' neq ''}{$row.ticketid}{else}-{/if}</td>
										<td>{$row.first_name|escape} {$row.last_name|escape}</td>
										<td>{$row.activity_date|escape|default:'-'}</td>
										<td>
											<span class="activities-status activities-status-{$row.status|lower|replace:' ':'_'|escape}">
												{$row.status|escape|default:'-'}
											</span>
										</td>
										<td>
											<a href="index.php?module=Activities&view=Detail&record={$row.activityid}&app=SUPPORT" class="btn btn-xs btn-default">View</a>
											<a href="index.php?module=Activities&view=Edit&record={$row.activityid}&app=SUPPORT" class="btn btn-xs btn-default">Edit</a>
										</td>
									</tr>
								{/foreach}
							{/if}
						</tbody>
					</table>

					{if $MAX_PAGE > 1}
						<nav>
							<ul class="pagination pagination-sm">
								{if $PAGE > 1}
									<li><a href="index.php?module=Activities&view=List&page={$PAGE-1}&status={$STATUS_FILTER|escape}&activity_type={$TYPE_FILTER|escape}&assigned_user_id={$STAFF_FILTER|escape}&activity_date={$DATE_FILTER|escape}&sort={$SORT_FILTER|escape}&q={$KEYWORD|escape}&app=SUPPORT">&laquo;</a></li>
								{/if}
								<li class="disabled"><span>Page {$PAGE} of {$MAX_PAGE} ({$TOTAL} total)</span></li>
								{if $PAGE < $MAX_PAGE}
									<li><a href="index.php?module=Activities&view=List&page={$PAGE+1}&status={$STATUS_FILTER|escape}&activity_type={$TYPE_FILTER|escape}&assigned_user_id={$STAFF_FILTER|escape}&activity_date={$DATE_FILTER|escape}&sort={$SORT_FILTER|escape}&q={$KEYWORD|escape}&app=SUPPORT">&raquo;</a></li>
								{/if}
							</ul>
						</nav>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}
