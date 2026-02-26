{* Documents History - lịch sử thao tác file *}
{strip}
<div class="doc-management-view">
	<div class="doc-management-main" style="margin: 15px;">
		<h3 style="margin-bottom: 15px;">
			<i class="fa fa-history"></i>
			&nbsp;History
		</h3>

		{if $HISTORY_ROWS|@count gt 0}
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th style="width: 160px;">Time</th>
						<th>File</th>
						<th style="width: 120px;">Action</th>
						<th style="width: 180px;">User</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$HISTORY_ROWS item=ROW}
						<tr>
							<td>{$ROW.changedon}</td>
							<td>
								<a href="{$ROW.detailUrl}" target="_blank" rel="noopener noreferrer">
									{$ROW.label|escape:'html'}
								</a>
							</td>
							<td>{$ROW.action}</td>
							<td>{$ROW.user|escape:'html'}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="alert alert-info">
				No history entries found for Documents yet.
			</div>
		{/if}
	</div>
</div>
{/strip}

