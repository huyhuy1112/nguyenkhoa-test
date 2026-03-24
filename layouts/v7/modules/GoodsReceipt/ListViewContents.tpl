{strip}
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<h3>Inbound</h3>
			<p class="text-muted">Warehouse receiving receipts and batch history.</p>
			<form method="get" action="index.php" class="form-inline" style="margin:10px 0 14px;">
				<input type="hidden" name="module" value="GoodsReceipt" />
				<input type="hidden" name="view" value="List" />
				<input type="hidden" name="app" value="INVENTORY" />
				<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="form-control" placeholder="Search subject/source" />
				<input type="date" name="date_from" value="{$DATE_FROM|escape:'html'}" class="form-control" />
				<input type="date" name="date_to" value="{$DATE_TO|escape:'html'}" class="form-control" />
				<input type="text" name="storage_location" value="{$STORAGE_LOCATION|escape:'html'}" class="form-control" placeholder="Storage location" />
				<button type="submit" class="btn btn-default">Filter</button>
				<a href="index.php?module=GoodsReceipt&view=Edit&app=INVENTORY" class="btn btn-success pull-right">+ New Inbound</a>
			</form>
			<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th>Subject</th><th>Source</th><th>Received Date</th><th>Storage</th>
							<th class="text-right">Total Qty</th><th class="text-right">Total Value</th><th>Actions</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$ROWS item=R}
							<tr>
								<td><a href="index.php?module=GoodsReceipt&view=Detail&record={$R.receiptid}&app=INVENTORY">{$R.subject|escape:'html'}</a></td>
								<td>{$R.source_name|escape:'html'}</td>
								<td>{$R.received_date|escape:'html'}</td>
								<td>{$R.storage_location|escape:'html'}</td>
								<td class="text-right">{$R.total_qty|escape:'html'}</td>
								<td class="text-right">{$R.total_value|escape:'html'}</td>
								<td>
									<a class="btn btn-xs btn-default" href="index.php?module=GoodsReceipt&view=Edit&record={$R.receiptid}&app=INVENTORY">Edit</a>
									<a class="btn btn-xs btn-danger" href="index.php?module=GoodsReceipt&action=Delete&record={$R.receiptid}&app=INVENTORY" onclick="return confirm('Delete this inbound receipt? Stock will be reversed.');">Delete</a>
								</td>
							</tr>
						{foreachelse}
							<tr><td colspan="7" class="text-center text-muted">No inbound receipts found.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
{/strip}
