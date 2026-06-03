{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="editViewPageDiv viewContent content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
				<a class="active" href="index.php?module=Warehouse&view=List&app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
				<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
			</div>
			<div class="inv-suite-head">
				<div>
					<h3 style="margin-top:0;">Edit warehouse fields</h3>
					<p class="text-muted">{$STOCK.product_name_display|escape:'html'} — only shrinkage, storage location, and warehouse notes can be changed here.</p>
				</div>
				<div class="inv-suite-actions">
					<a class="btn btn-default" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$STOCK.stockid}&amp;app=INVENTORY">Back to detail</a>
				</div>
			</div>
			<div class="inv-header-badges" style="margin-bottom:14px;">
				{if $STOCK.code}
					<span class="inv-chip">{$STOCK.code|escape:'html'}</span>
				{/if}
				{if $IS_LEGACY_IDENTITY}
					<span class="inv-badge inv-badge-legacy">Legacy name-based</span>
				{else}
					<span class="inv-badge inv-badge-catalog">Catalog linked</span>
				{/if}
				{if !$IS_LEGACY_IDENTITY && $CATALOG_PRODUCT_ID > 0}
					<span class="inv-badge inv-badge-muted">ID {$CATALOG_PRODUCT_ID|escape:'html'}</span>
				{/if}
				<span class="inv-chip">{$TYPE_LABEL|escape:'html'}</span>
			</div>

			<form id="WarehouseEditForm" method="post" action="index.php" class="form-horizontal">
				<input type="hidden" name="module" value="Warehouse" />
				<input type="hidden" name="action" value="Save" />
				<input type="hidden" name="app" value="INVENTORY" />
				<input type="hidden" name="record" value="{$STOCK.stockid}" />

				<div class="row inv-metric-strip" style="margin-bottom:16px;">
					<div class="col-sm-3 col-xs-6"><div class="inv-metric inv-metric-readonly"><div class="inv-metric-label">On hand (read-only)</div><div class="inv-metric-value">{$STOCK.quantity_display|escape:'html'}</div></div></div>
					<div class="col-sm-3 col-xs-6"><div class="inv-metric inv-metric-readonly"><div class="inv-metric-label">Available</div><div class="inv-metric-value inv-metric-accent">{$STOCK.available_display|escape:'html'}</div></div></div>
					<div class="col-sm-3 col-xs-6"><div class="inv-metric inv-metric-readonly"><div class="inv-metric-label">Last price (read-only)</div><div class="inv-metric-value">{$STOCK.last_price_display|escape:'html'}</div></div></div>
					<div class="col-sm-3 col-xs-6"><div class="inv-metric inv-metric-readonly"><div class="inv-metric-label">Current shrinkage</div><div class="inv-metric-value">{$STOCK.shrinkage_display|escape:'html'}</div></div></div>
				</div>

				<details class="inv-tech-details" style="margin-bottom:16px;">
					<summary>Technical reference</summary>
					<div class="text-muted small" style="margin-top:8px;">Aggregation key <code>{$PRODUCT_KEY_DISPLAY|escape:'html'}</code></div>
				</details>

				<div class="panel panel-default inv-panel">
					<div class="panel-heading"><strong>Warehouse fields</strong></div>
					<div class="panel-body">
						<div class="form-group">
							<label class="col-sm-3 control-label">Storage code</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" value="{if $STOCK.code}{$STOCK.code|escape:'html'}{else}Auto generated{/if}" readonly="readonly" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Storage location</label>
							<div class="col-sm-9">
								<input type="text" name="storage_location" class="form-control" value="{$STOCK.storage_location|escape:'html'}" />
								<p class="help-block">Override allowed. The next Inbound save with a non-empty receipt location overwrites this (latest inbound wins).</p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Shrinkage / loss</label>
							<div class="col-sm-9">
								<input type="text" name="shrinkage_qty" class="form-control" value="{$STOCK.shrinkage_qty|escape:'html'}" />
								<p class="help-block">0 … on-hand quantity. Does not change Inbound totals; only reduces available.</p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Warehouse notes</label>
							<div class="col-sm-9">
								<textarea name="warehouse_note" class="form-control" rows="4">{$STOCK.warehouse_note|escape:'html'}</textarea>
								<p class="help-block">Internal warehouse notes only. Inbound receipt notes appear separately on the detail page.</p>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group inv-form-actions">
					<div class="col-sm-offset-3 col-sm-9">
						<button type="submit" class="btn btn-success">Save</button>
						<a class="btn btn-default" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$STOCK.stockid}&amp;app=INVENTORY">Cancel</a>
					</div>
				</div>
			</form>
			{literal}
			<script type="text/javascript">
				(function() {
					var form = document.getElementById('WarehouseEditForm');
					if (form) {
						form.addEventListener('submit', function() {
							if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
								var existing = form.querySelector('input[name="' + csrfMagicName + '"]');
								if (!existing) {
									var hidden = document.createElement('input');
									hidden.type = 'hidden';
									hidden.name = csrfMagicName;
									hidden.value = csrfMagicToken;
									form.appendChild(hidden);
								}
							}
						});
					}
				})();
			</script>
			{/literal}
		</div>
	</div>
	</div>
</div>
{/strip}
