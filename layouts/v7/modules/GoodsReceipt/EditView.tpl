{strip}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260325" />
<div class="main-container clearfix">
	<div class="editViewPageDiv viewContent inv-modern-page">
		<div class="col-sm-12 col-xs-12 content-area full-width" style="margin-left:0;">
			<form method="post" action="index.php" class="container-fluid inv-modern-card" enctype="multipart/form-data">
				<div class="inv-topnav">
					<a class="active" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
					<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
					<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
				</div>
				<input type="hidden" name="module" value="GoodsReceipt" />
				<input type="hidden" name="action" value="Save" />
				<input type="hidden" name="app" value="INVENTORY" />
				<input type="hidden" name="record" value="{$RECORD.receiptid|default:0}" />
				<div class="row">
					<div class="col-lg-12">
						<div class="inv-suite-head">
							<div>
								<h3>{if $MODE eq 'edit'}Edit Inbound{else}New Inbound{/if}</h3>
								<p class="text-muted">Inbound goods receipt workflow.</p>
								{if $RECORD.code}
									<div style="margin-top:6px;"><span class="inv-chip">{$RECORD.code|escape:'html'}</span></div>
								{/if}
							</div>
							<div class="inv-suite-actions">
								<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Back to list</a>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-default inv-panel">
					<div class="panel-heading"><strong>Inbound info</strong></div>
					<div class="panel-body">
						<div class="row">
							<div class="col-sm-6"><div class="form-group"><label>Receipt Subject</label><input type="text" name="subject" class="form-control" value="{$RECORD.subject|escape:'html'}" required /></div></div>
							<div class="col-sm-6"><div class="form-group"><label>Inbound Code</label><input type="text" class="form-control" value="{if $RECORD.code}{$RECORD.code|escape:'html'}{else}Auto on save{/if}" readonly="readonly" /></div></div>
						</div>
						<div class="row">
							<div class="col-sm-6"><div class="form-group"><label>Supplier/Source</label><input type="text" name="source_name" class="form-control" value="{$RECORD.source_name|escape:'html'}" /></div></div>
							<div class="col-sm-3"><div class="form-group"><label>Received Date</label><input type="date" name="received_date" class="form-control" value="{$RECORD.received_date|escape:'html'}" /></div></div>
							<div class="col-sm-3"><div class="form-group"><label>Storage Location</label><input type="text" name="storage_location" class="form-control" value="{$RECORD.storage_location|escape:'html'}" /></div></div>
						</div>
						<div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="4">{$RECORD.note|escape:'html'}</textarea></div>
					</div>
				</div>

				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Line items</strong></div>
					<div class="panel-body">
						<p class="text-muted">Enter product name and line details manually. Inbound does not use storage-based autocomplete (that is for Outbound only).</p>
						<table class="table table-bordered inv-modern-table" id="inboundItemsTable">
							<thead><tr><th>Product Name</th><th>Type</th><th>Serial Number</th><th>Qty</th><th>Price</th><th>Description</th><th>Line Note</th><th></th></tr></thead>
							<tbody>
								{foreach from=$ITEMS item=IT}
								<tr>
									<td>
										<input type="hidden" name="item_productid[]" value="{$IT.productid|escape:'html'}" />
										<input type="text" name="item_product_name[]" class="form-control" value="{$IT.product_name|escape:'html'}" placeholder="Product name" autocomplete="off" required />
									</td>
									<td>
										<select name="item_product_type[]" class="form-control js-product-type">
											{assign var=ITYPE value=$IT.product_type|default:''}
											<option value="Hardware" {if $ITYPE eq 'Hardware'}selected="selected"{/if}>Hardware</option>
											<option value="Software" {if $ITYPE eq 'Software'}selected="selected"{/if}>Software</option>
											<option value="Service" {if $ITYPE eq 'Service'}selected="selected"{/if}>Service</option>
											<option value="Other" {if $ITYPE eq 'Other' || $ITYPE eq ''}selected="selected"{/if}>Other</option>
										</select>
									</td>
									<td><input type="text" name="item_serial[]" class="form-control" value="{$IT.serial_number|escape:'html'}" /></td>
									<td><input type="number" step="0.0001" min="0" name="item_quantity[]" class="form-control" value="{$IT.quantity|escape:'html'}" required /></td>
									<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" class="form-control" value="{$IT.unit_price|escape:'html'}" /></td>
									<td><input type="text" name="description[]" class="form-control" value="{$IT.description|escape:'html'}" /></td>
									<td><input type="text" name="item_line_note[]" class="form-control" value="{$IT.line_note|escape:'html'}" /></td>
									<td><button type="button" class="btn btn-xs btn-danger js-remove-row">x</button></td>
								</tr>
								{/foreach}
							</tbody>
						</table>
						<button type="button" class="btn btn-default" id="addInboundRow">+ Add Line</button>
					</div>
				</div>

				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Attachments</strong></div>
					<div class="panel-body">
						<input type="file" name="attachments[]" class="form-control" multiple
							accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt" />
						<p class="text-muted" style="margin-top:8px;">Allowed: jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx, csv, txt</p>
						{if $ATTACHMENTS|@count gt 0}
							<table class="table table-bordered table-condensed inv-modern-table" style="margin-top:8px;">
								<thead><tr><th>File</th><th>Type</th><th>Size</th><th>Action</th></tr></thead>
								<tbody>
								{foreach from=$ATTACHMENTS item=A}
									<tr>
										<td>{$A.filename|escape:'html'}</td>
										<td>{$A.filetype|escape:'html'}</td>
										<td>{$A.filesize|escape:'html'}</td>
										<td>
											<a class="btn btn-xs btn-default" target="_blank"
											   href="index.php?module=GoodsReceipt&action=DownloadAttachment&attachmentid={$A.attachmentid}&record={$RECORD.receiptid}&app=INVENTORY">Open/Download</a>
											<a class="btn btn-xs btn-danger"
											   href="index.php?module=GoodsReceipt&action=DeleteAttachment&attachmentid={$A.attachmentid}&record={$RECORD.receiptid}&app=INVENTORY"
											   onclick="return confirm('Delete this attachment?');">Delete</a>
										</td>
									</tr>
								{/foreach}
								</tbody>
							</table>
						{/if}
					</div>
				</div>
				<div class="row inv-form-actions">
					<div class="col-lg-12">
						<button type="submit" class="btn btn-success">Save Inbound</button>
						<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Cancel</a>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
{literal}
<script>
(function(){
  var tbody = document.querySelector('#inboundItemsTable tbody');
  if (!tbody) return;
  var addBtn = document.getElementById('addInboundRow');
  if (addBtn) {
    addBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML = '<td><input type="hidden" name="item_productid[]" value="" /><input type="text" name="item_product_name[]" class="form-control" placeholder="Product name" autocomplete="off" required /></td>' +
        '<td><select name="item_product_type[]" class="form-control"><option value="Hardware">Hardware</option><option value="Software">Software</option><option value="Service">Service</option><option value="Other" selected="selected">Other</option></select></td>' +
        '<td><input type="text" name="item_serial[]" class="form-control" /></td>' +
        '<td><input type="number" step="0.0001" min="0" name="item_quantity[]" class="form-control" value="1" required /></td>' +
        '<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" class="form-control" value="0" /></td>' +
        '<td><input type="text" name="description[]" class="form-control" /></td>' +
        '<td><input type="text" name="item_line_note[]" class="form-control" /></td>' +
        '<td><button type="button" class="btn btn-xs btn-danger js-remove-row">x</button></td>';
      tbody.appendChild(tr);
    });
  }
  tbody.addEventListener('click', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('js-remove-row')) {
      var tr = e.target.closest('tr');
      if (tr) tr.remove();
    }
  });
})();
</script>
{/literal}
{/strip}
