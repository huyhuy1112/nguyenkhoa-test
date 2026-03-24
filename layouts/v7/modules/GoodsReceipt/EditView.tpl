{strip}
<form method="post" action="index.php" class="container-fluid">
	<input type="hidden" name="module" value="GoodsReceipt" />
	<input type="hidden" name="action" value="Save" />
	<input type="hidden" name="app" value="INVENTORY" />
	<input type="hidden" name="record" value="{$RECORD.receiptid|default:0}" />
	<div class="row">
		<div class="col-lg-12">
			<h3>{if $MODE eq 'edit'}Edit Inbound{else}New Inbound{/if}</h3>
			<p class="text-muted">Inbound goods receipt workflow.</p>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-4">
			<div class="form-group"><label>Receipt Subject</label><input type="text" name="subject" class="form-control" value="{$RECORD.subject|escape:'html'}" required /></div>
			<div class="form-group"><label>Supplier/Source</label><input type="text" name="source_name" class="form-control" value="{$RECORD.source_name|escape:'html'}" /></div>
			<div class="form-group"><label>Received Date</label><input type="date" name="received_date" class="form-control" value="{$RECORD.received_date|escape:'html'}" /></div>
			<div class="form-group"><label>Storage Location</label><input type="text" name="storage_location" class="form-control" value="{$RECORD.storage_location|escape:'html'}" /></div>
			<div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="4">{$RECORD.note|escape:'html'}</textarea></div>
		</div>
		<div class="col-lg-8">
			<h4>Line Items</h4>
			<table class="table table-bordered" id="inboundItemsTable">
				<thead><tr><th>Product Name</th><th>Qty</th><th>Price</th><th>Line Note</th><th></th></tr></thead>
				<tbody>
					{foreach from=$ITEMS item=IT}
					<tr>
						<td>
							<input type="hidden" name="item_productid[]" value="{$IT.productid|escape:'html'}" />
							<input type="text" name="item_product_name[]" class="form-control" value="{$IT.product_name|escape:'html'}" required />
						</td>
						<td><input type="number" step="0.0001" min="0" name="item_quantity[]" class="form-control" value="{$IT.quantity|escape:'html'}" required /></td>
						<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" class="form-control" value="{$IT.unit_price|escape:'html'}" /></td>
						<td><input type="text" name="item_line_note[]" class="form-control" value="{$IT.line_note|escape:'html'}" /></td>
						<td><button type="button" class="btn btn-xs btn-danger js-remove-row">x</button></td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			<button type="button" class="btn btn-default" id="addInboundRow">+ Add Line</button>
		</div>
	</div>
	<div class="row" style="margin-top:10px;">
		<div class="col-lg-12">
			<button type="submit" class="btn btn-success">Save Inbound</button>
			<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Cancel</a>
		</div>
	</div>
</form>
{literal}
<script>
(function(){
  var tbody=document.querySelector('#inboundItemsTable tbody');
  document.getElementById('addInboundRow').addEventListener('click', function(){
    var tr=document.createElement('tr');
    tr.innerHTML='<td><input type="hidden" name="item_productid[]" value="" /><input type="text" name="item_product_name[]" class="form-control" required /></td><td><input type="number" step="0.0001" min="0" name="item_quantity[]" class="form-control" value="1" required /></td><td><input type="number" step="0.0001" min="0" name="item_unit_price[]" class="form-control" value="0" /></td><td><input type="text" name="item_line_note[]" class="form-control" /></td><td><button type="button" class="btn btn-xs btn-danger js-remove-row">x</button></td>';
    tbody.appendChild(tr);
  });
  tbody.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('js-remove-row')){ e.target.closest('tr').remove(); }
  });
})();
</script>
{/literal}
{/strip}
