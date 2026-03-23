{strip}
<form id="detailView" method="POST">
	<div class="block">
		<div><h4>Order Info</h4></div><hr>
		<table class="table detailview-table no-border">
			<tr>
				<td class="fieldLabel"><span class="muted">Order Name</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('subject')}</span></td>
				<td class="fieldLabel"><span class="muted">Team Group</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('team_group')}</span></td>
			</tr>
			<tr>
				<td class="fieldLabel"><span class="muted">Purpose</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('purpose')}</span></td>
				<td class="fieldLabel"><span class="muted">Cost</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('internal_cost')}</span></td>
			</tr>
			<tr>
				<td class="fieldLabel"><span class="muted">Needed Time</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('needed_time')}</span></td>
				<td class="fieldLabel"></td>
				<td class="fieldValue"></td>
			</tr>
		</table>
	</div>
	<br>

	<div class="block">
		<div><h4>Approval Info</h4></div><hr>
		<table class="table detailview-table no-border">
			<tr>
				<td class="fieldLabel"><span class="muted">Status</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('internal_order_status')}</span></td>
				<td class="fieldLabel"><span class="muted">Approved By</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('approved_by')}</span></td>
			</tr>
			<tr>
				<td class="fieldLabel"><span class="muted">Approval Note</span></td>
				<td class="fieldValue" colspan="3"><span class="value">{$RECORD->getDisplayValue('approval_note')}</span></td>
			</tr>
		</table>
	</div>
	<br>

	<div class="block">
		<div><h4>System</h4></div><hr>
		<table class="table detailview-table no-border">
			<tr>
				<td class="fieldLabel"><span class="muted">Ordered By</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('created_user_id')}</span></td>
				<td class="fieldLabel"><span class="muted">Created Time</span></td>
				<td class="fieldValue"><span class="value">{$RECORD->getDisplayValue('createdtime')}</span></td>
			</tr>
		</table>
	</div>
</form>
{/strip}
