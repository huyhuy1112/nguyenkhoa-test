{strip}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260325" />
<div class="container-fluid detailview-content inv-modern-page">
	<div class="inv-modern-card">
	<div class="inv-topnav">
		<a class="active" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
		<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
		<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<div class="row inv-page-header" style="margin-bottom:12px;">
				<div class="col-sm-8">
					<h3 style="margin-top:0;">{$RECORD_DATA.subject|escape:'html'}</h3>
					<div class="text-muted">
						Inbound receipt details and supporting documents
					</div>
					<div class="inv-header-badges" style="margin-top:8px;">
						{if $RECORD_DATA.code}<span class="inv-chip">{$RECORD_DATA.code|escape:'html'}</span>{/if}
					</div>
				</div>
				<div class="col-sm-4 text-right inv-header-actions">
					<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=Edit&record={$RECORD_DATA.receiptid}&app=INVENTORY">Edit</a>
					<a class="btn btn-danger" href="index.php?module=GoodsReceipt&action=Delete&record={$RECORD_DATA.receiptid}&app=INVENTORY" onclick="return confirm('Delete this inbound receipt?');">Delete</a>
					<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Back to list</a>
				</div>
			</div>

			<div class="panel panel-default">
				<div class="panel-heading"><strong>Inbound Info</strong></div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-3"><strong>Code</strong><div>{if $RECORD_DATA.code}<span class="inv-chip">{$RECORD_DATA.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</div></div>
						<div class="col-sm-3"><strong>Subject</strong><div>{$RECORD_DATA.subject|escape:'html'}</div></div>
						<div class="col-sm-3"><strong>Supplier/Source</strong><div>{$RECORD_DATA.source_name|escape:'html'}</div></div>
						<div class="col-sm-3"><strong>Received Date</strong><div>{$RECORD_DATA.received_date|escape:'html'}</div></div>
					</div>
					<div class="row" style="margin-top:10px;">
						<div class="col-sm-12"><strong>Storage Location</strong><div>{$RECORD_DATA.storage_location|escape:'html'}</div></div>
					</div>
					<div class="row" style="margin-top:10px;">
						<div class="col-sm-12"><strong>Note</strong><div class="well well-sm" style="margin-top:6px;">{$RECORD_DATA.note|escape:'html'|nl2br}</div></div>
					</div>
				</div>
			</div>

			<div class="panel panel-default" style="margin-top:12px;">
				<div class="panel-heading"><strong>Line Items</strong></div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover">
							<thead><tr><th>Product</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Line Total</th><th>Note</th></tr></thead>
							<tbody>
							{foreach from=$ITEMS item=IT}
								<tr>
									<td>{$IT.product_name_display|escape:'html'}</td>
									<td>{$IT.product_type|escape:'html'}</td>
									<td class="text-right metric-strong">{$IT.quantity_display|escape:'html'}</td>
									<td class="text-right">{$IT.unit_price_display|escape:'html'}</td>
									<td class="text-right metric-strong">{$IT.line_total_display|escape:'html'}</td>
									<td>{$IT.line_note|escape:'html'}</td>
								</tr>
							{foreachelse}
								<tr><td colspan="6" class="text-center text-muted">No line items.</td></tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="panel panel-default" style="margin-top:12px;">
				<div class="panel-heading"><strong>Attachments</strong></div>
				<div class="panel-body">
					{if $ATTACHMENTS|@count gt 0}
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead><tr><th>Filename</th><th>Type</th><th>Size (bytes)</th><th>Created Time</th><th>Action</th></tr></thead>
								<tbody>
								{foreach from=$ATTACHMENTS item=A}
									<tr>
										<td>{$A.filename|escape:'html'}</td>
										<td>{$A.filetype|escape:'html'}</td>
										<td>{$A.filesize|escape:'html'}</td>
										<td>{$A.createdtime|escape:'html'}</td>
										<td>
											<a class="btn btn-xs btn-default" target="_blank"
											   href="index.php?module=GoodsReceipt&action=DownloadAttachment&attachmentid={$A.attachmentid}&record={$RECORD_DATA.receiptid}&app=INVENTORY">Open/Download</a>
										</td>
									</tr>
								{/foreach}
								</tbody>
							</table>
						</div>
					{else}
						<div class="text-muted">No attachments uploaded.</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
</div>
{/strip}
