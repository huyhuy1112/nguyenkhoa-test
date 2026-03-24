{strip}
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<h3>Storage</h3>
			<p class="text-muted">Aggregated stock quantities from inbound receipts.</p>
			<form method="get" action="index.php" class="form-inline" style="margin:10px 0;">
				<input type="hidden" name="module" value="Warehouse" />
				<input type="hidden" name="view" value="List" />
				<input type="hidden" name="app" value="INVENTORY" />
				<input type="text" name="search" class="form-control" value="{$SEARCH|escape:'html'}" placeholder="Search product" />
				<button class="btn btn-default" type="submit">Search</button>
			</form>
			<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<thead><tr><th>Product</th><th class="text-right">Quantity</th><th class="text-right">Last Price</th><th>Updated Time</th></tr></thead>
					<tbody>
					{foreach from=$ROWS item=R}
						<tr>
							<td>{$R.product_name|escape:'html'}</td>
							<td class="text-right">{$R.quantity|escape:'html'}</td>
							<td class="text-right">{$R.last_price|escape:'html'}</td>
							<td>{$R.updatedtime|escape:'html'}</td>
						</tr>
					{foreachelse}
						<tr><td colspan="4" class="text-center text-muted">No stock records.</td></tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
{/strip}
