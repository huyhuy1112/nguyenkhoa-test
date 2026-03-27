{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260325" />
	<div class="listViewPageDiv content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a class="active" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
				<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="inv-suite-head">
						<div>
							<h3>Inbound</h3>
							<p class="text-muted">Warehouse receiving receipts and batch history.</p>
						</div>
						<div class="inv-suite-actions">
							<a href="index.php?module=GoodsReceipt&view=Edit&app=INVENTORY" class="btn btn-success">+ New Inbound</a>
						</div>
					</div>
					<form method="get" action="index.php" class="form-inline inv-filter-bar">
						<input type="hidden" name="module" value="GoodsReceipt" />
						<input type="hidden" name="view" value="List" />
						<input type="hidden" name="app" value="INVENTORY" />
						<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="form-control" placeholder="Search subject/source" />
						<input type="date" name="date_from" value="{$DATE_FROM|escape:'html'}" class="form-control" />
						<input type="date" name="date_to" value="{$DATE_TO|escape:'html'}" class="form-control" />
						<input type="text" name="storage_location" value="{$STORAGE_LOCATION|escape:'html'}" class="form-control" placeholder="Storage location" />
						<button type="submit" class="btn btn-default">Filter</button>
						<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY" class="btn btn-link">Reset</a>
					</form>
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Code</th><th>Subject</th><th>Source</th><th>Received Date</th><th>Storage</th>
									<th class="text-right">Total Qty</th><th class="text-right">Total Value</th><th>Actions</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$ROWS item=R}
									<tr>
										<td>{if $R.code}<span class="inv-chip">{$R.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td><a href="index.php?module=GoodsReceipt&view=Detail&record={$R.receiptid}&app=INVENTORY">{$R.subject|escape:'html'}</a></td>
										<td>{$R.source_name|escape:'html'}</td>
										<td>{$R.received_date|escape:'html'}</td>
										<td>{$R.storage_location|escape:'html'}</td>
										<td class="text-right metric-strong">{$R.total_qty_display|escape:'html'}</td>
										<td class="text-right metric-strong">{$R.total_value_display|escape:'html'}</td>
										<td>
											<a class="btn btn-xs btn-default" href="index.php?module=GoodsReceipt&view=Edit&record={$R.receiptid}&app=INVENTORY">Edit</a>
											<a class="btn btn-xs btn-danger" href="index.php?module=GoodsReceipt&action=Delete&record={$R.receiptid}&app=INVENTORY" onclick="return confirm('Delete this inbound receipt? Stock will be reversed.');">Delete</a>
										</td>
									</tr>
								{foreachelse}
									<tr><td colspan="8" class="inv-empty">No inbound receipts found.</td></tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>
{/strip}
