{strip}
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<h3>{$RECORD_DATA.subject|escape:'html'}</h3>
			<p class="text-muted">
				Source: {$RECORD_DATA.source_name|escape:'html'} |
				Received Date: {$RECORD_DATA.received_date|escape:'html'} |
				Storage: {$RECORD_DATA.storage_location|escape:'html'}
			</p>
			<div style="margin:10px 0;">
				<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=Edit&record={$RECORD_DATA.receiptid}&app=INVENTORY">Edit</a>
				<a class="btn btn-danger" href="index.php?module=GoodsReceipt&action=Delete&record={$RECORD_DATA.receiptid}&app=INVENTORY" onclick="return confirm('Delete this inbound receipt?');">Delete</a>
			</div>
			<div class="well">{$RECORD_DATA.note|escape:'html'|nl2br}</div>
			<table class="table table-bordered">
				<thead><tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Line Total</th><th>Note</th></tr></thead>
				<tbody>
				{foreach from=$ITEMS item=IT}
					<tr>
						<td>{$IT.product_name|escape:'html'}</td>
						<td class="text-right">{$IT.quantity|escape:'html'}</td>
						<td class="text-right">{$IT.unit_price|escape:'html'}</td>
						<td class="text-right">{$IT.line_total}</td>
						<td>{$IT.line_note|escape:'html'}</td>
					</tr>
				{foreachelse}
					<tr><td colspan="5" class="text-center text-muted">No line items.</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
{/strip}
