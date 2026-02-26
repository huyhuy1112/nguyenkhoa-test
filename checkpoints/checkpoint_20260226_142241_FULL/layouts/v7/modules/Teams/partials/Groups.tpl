{strip}
<div class="table-responsive">
	<table class="table listViewEntriesTable">
		<thead>
			<tr>
				<th>Group Name</th>
				<th>Members</th>
				<th>Assign Type</th>
				<th class="actions">{vtranslate('LBL_ACTIONS','Vtiger')}</th>
			</tr>
		</thead>
		<tbody>
			{foreach item=G from=$GROUPS}
				<tr>
					<td>
						<strong>{$G.group_name|decode_html}</strong>
					</td>
					<td>{if $G.member_count}{$G.member_count}{else}0{/if}</td>
					<td>{$G.assign_type|decode_html}</td>
					<td>
						<div class="btn-group">
							<button class="btn btn-default btn-sm dropdown-toggle teams-action-btn" data-toggle="dropdown" title="{vtranslate('LBL_ACTIONS','Vtiger')}">
								<i class="fa fa-ellipsis-v"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-right">
								<li><a href="index.php?module=Teams&view=EditGroup&record={$G.groupid|escape}&app=Management" class="dropdown-item" style="padding: 3px 20px; display: block; color: #333; text-decoration: none;">{vtranslate('LBL_EDIT','Vtiger')}</a></li>
								<li><a href="index.php?module=Teams&action=DeleteGroup&record={$G.groupid|escape}&app=Management" class="dropdown-item" style="padding: 3px 20px; display: block; color: #333; text-decoration: none;" onclick="return confirm('Delete this group?');">{vtranslate('LBL_DELETE','Vtiger')}</a></li>
							</ul>
						</div>
					</td>
				</tr>
			{foreachelse}
				<tr><td colspan="4" class="text-center text-muted">No groups found.</td></tr>
			{/foreach}
		</tbody>
	</table>
</div>
{/strip}
