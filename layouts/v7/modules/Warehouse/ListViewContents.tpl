{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="listViewPageDiv content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
				<a class="active" href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="inv-suite-head">
						<div>
							<h3>Storage</h3>
							<p class="text-muted">Authoritative current stock per catalog or legacy identity, derived from Inbound/Outbound.</p>
						</div>
						<div class="inv-suite-actions">
							<a href="index.php?module=GoodsReceipt&view=Edit&app=INVENTORY" class="btn btn-default">+ New Inbound</a>
							<a href="index.php?module=GoodsIssue&view=Edit&app=INVENTORY" class="btn btn-success">+ New Outbound</a>
						</div>
					</div>
					{if !empty($SHOW_DELETED)}
						<div class="alert alert-success inv-alert">Stock row deleted.</div>
					{/if}
					{if !empty($SHOW_DELETE_ERROR)}
						<div class="alert alert-danger inv-alert">Unable to delete: row was not found or is not empty.</div>
					{/if}
					<form method="get" action="index.php" class="form-inline inv-filter-bar">
						<input type="hidden" name="module" value="Warehouse" />
						<input type="hidden" name="view" value="List" />
						<input type="hidden" name="app" value="INVENTORY" />
						<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="form-control" placeholder="Product name or key" style="min-width:200px;" />
						<input type="text" name="qty_min" value="{$QTY_MIN|escape:'html'}" class="form-control" placeholder="Min qty" style="width:90px;" />
						<input type="text" name="qty_max" value="{$QTY_MAX|escape:'html'}" class="form-control" placeholder="Max qty" style="width:90px;" />
						<input type="text" name="storage_location" value="{$FILTER_LOCATION|escape:'html'}" class="form-control" placeholder="Location contains" style="min-width:160px;" />
						<select name="item_type" class="form-control">
							<option value="">All types</option>
							<option value="hardware" {if $FILTER_ITEM_TYPE eq 'hardware'}selected="selected"{/if}>Hardware</option>
							<option value="software" {if $FILTER_ITEM_TYPE eq 'software'}selected="selected"{/if}>Software</option>
							<option value="service" {if $FILTER_ITEM_TYPE eq 'service'}selected="selected"{/if}>Service</option>
							<option value="other" {if $FILTER_ITEM_TYPE eq 'other'}selected="selected"{/if}>Other</option>
							<option value="__empty__" {if $FILTER_ITEM_TYPE eq '__empty__'}selected="selected"{/if}>No type</option>
						</select>
						<label class="checkbox-inline" style="margin-left:8px;color:rgba(234,242,255,.9);">
							<input type="checkbox" name="legacy_only" value="1" {if !empty($FILTER_LEGACY_ONLY)}checked="checked"{/if} /> Legacy only
						</label>
						<button type="submit" class="btn btn-default">Filter</button>
						<a href="index.php?module=Warehouse&view=List&app=INVENTORY" class="btn btn-link">Reset</a>
					</form>
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Code</th>
									<th>Product</th>
									<th>Identity</th>
									<th>Type</th>
									<th class="text-right">Qty</th>
									<th class="text-right">Shrinkage</th>
									<th class="text-right">Available</th>
									<th class="text-right">Last price</th>
									<th>Location</th>
									<th>Updated</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$ROWS item=R}
									<tr class="{if $R.is_low_stock}inv-row-low{/if}">
										<td>{if $R.code}<span class="inv-chip">{$R.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td>
											<a href="index.php?module=Warehouse&view=Detail&amp;record={$R.stockid}&amp;app=INVENTORY">{$R.product_name_display|escape:'html'}</a>
											{if $R.has_shrinkage}<span class="inv-status-dot" title="Shrinkage recorded"></span>{/if}
										</td>
										<td>
											{if $R.is_legacy_identity}
												<span class="inv-badge inv-badge-legacy">Legacy</span>
											{else}
												<span class="inv-badge inv-badge-catalog">Catalog</span>
											{/if}
										</td>
										<td><span class="inv-chip">{$R.type_label|escape:'html'}</span></td>
										<td class="text-right metric-strong">{$R.quantity_display|escape:'html'}</td>
										<td class="text-right">{$R.shrinkage_display|escape:'html'}</td>
										<td class="text-right metric-strong">{$R.available_display|escape:'html'}{if $R.is_low_stock}<span class="inv-badge inv-badge-low" title="Available below threshold">Low</span>{/if}</td>
										<td class="text-right">{$R.last_price_display|escape:'html'}</td>
										<td>{if $R.storage_location}{$R.storage_location|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td>{$R.updatedtime_display|escape:'html'}</td>
										<td class="text-nowrap">
											<a class="btn btn-xs btn-default" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$R.stockid}&amp;app=INVENTORY">View</a>
											<a class="btn btn-xs btn-primary" href="index.php?module=Warehouse&amp;view=Edit&amp;record={$R.stockid}&amp;app=INVENTORY">Edit</a>
										</td>
									</tr>
								{foreachelse}
									<tr><td colspan="11" class="inv-empty">No stock rows match your filters.</td></tr>
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
