{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="detailViewPageDiv content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
				<a class="active" href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
			</div>
			{if !empty($SHOW_SAVED)}
				<div class="alert alert-success inv-alert">Warehouse fields saved.</div>
			{/if}
			{if !empty($SHOW_DELETE_BLOCKED)}
				<div class="alert alert-warning inv-alert">Delete blocked: on-hand quantity and shrinkage must both be zero before removing this storage row.</div>
			{/if}
			{if !empty($SHOW_LINK_SUCCESS)}
				<div class="alert alert-success inv-alert">Product identity updated and inbound lines linked to the catalog record.</div>
			{/if}
			{if $LINK_ERROR eq 'not_legacy'}
				<div class="alert alert-warning inv-alert">Link skipped: this row already uses catalog identity (not legacy name-based).</div>
			{elseif $LINK_ERROR eq 'invalid_product'}
				<div class="alert alert-danger inv-alert">Link failed: selected catalog product was not found.</div>
			{elseif $LINK_ERROR ne ''}
				<div class="alert alert-danger inv-alert">Unable to complete product link. Try again or contact an admin.</div>
			{/if}

			<div class="row inv-page-header" style="margin-bottom:16px;">
				<div class="col-sm-8">
					<h3 style="margin-top:0;">{$STOCK.product_name_display|escape:'html'}</h3>
					<p class="text-muted" style="margin-bottom:8px;">Inventory storage (aggregated from Inbound). Quantities and prices are derived; adjust only warehouse fields on Edit.</p>
					<div class="inv-header-badges">
						{if $STOCK.code}<span class="inv-chip">{$STOCK.code|escape:'html'}</span>{/if}
						{if $IS_LEGACY_IDENTITY}
							<span class="inv-badge inv-badge-legacy inv-badge-strong">Legacy name-based identity</span>
						{else}
							<span class="inv-badge inv-badge-catalog inv-badge-strong">Catalog linked</span>
						{/if}
						{if !$IS_LEGACY_IDENTITY && $CATALOG_PRODUCT_ID > 0}
							<span class="inv-badge inv-badge-muted">ID {$CATALOG_PRODUCT_ID|escape:'html'}</span>
						{/if}
						{if $TYPE_LABEL eq 'Hardware'}
							<span class="inv-badge inv-badge-hardware inv-badge-strong">Hardware</span>
						{elseif $TYPE_LABEL eq 'Software'}
							<span class="inv-badge inv-badge-software inv-badge-strong">Software</span>
						{elseif $TYPE_LABEL eq 'Service'}
							<span class="inv-badge inv-badge-service inv-badge-strong">Service</span>
						{else}
							<span class="inv-badge inv-badge-other inv-badge-strong">{$TYPE_LABEL|escape:'html'}</span>
						{/if}
					</div>
				</div>
				<div class="col-sm-4 text-right inv-header-actions">
					<a class="btn btn-primary" href="index.php?module=Warehouse&amp;view=Edit&amp;record={$STOCK.stockid}&amp;app=INVENTORY">Edit warehouse fields</a>
					<a class="btn btn-default" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Back to list</a>
					{if $CAN_DELETE}
						<a class="btn btn-danger" href="index.php?module=Warehouse&amp;action=Delete&amp;record={$STOCK.stockid}&amp;app=INVENTORY" onclick="return confirm('Delete this empty storage row?');">Delete</a>
					{/if}
				</div>
			</div>

			<div class="row inv-metric-strip">
				<div class="col-sm-3 col-xs-6"><div class="inv-metric"><div class="inv-metric-label">On hand</div><div class="inv-metric-value">{$STOCK.quantity_display|escape:'html'}</div></div></div>
				<div class="col-sm-3 col-xs-6"><div class="inv-metric"><div class="inv-metric-label">Available</div><div class="inv-metric-value inv-metric-accent">{$STOCK.available_display|escape:'html'}</div></div></div>
				<div class="col-sm-3 col-xs-6"><div class="inv-metric"><div class="inv-metric-label">Shrinkage</div><div class="inv-metric-value">{$STOCK.shrinkage_display|escape:'html'}</div></div></div>
				<div class="col-sm-3 col-xs-6"><div class="inv-metric"><div class="inv-metric-label">Last price</div><div class="inv-metric-value">{$STOCK.last_price_display|escape:'html'}</div></div></div>
			</div>

			<div class="panel panel-default inv-panel">
				<div class="panel-heading"><strong>Stock info</strong></div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-4"><strong>Storage code</strong><div>{if $STOCK.code}<span class="inv-chip">{$STOCK.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</div></div>
						<div class="col-sm-4"><strong>Product type</strong><div>{if $TYPE_LABEL}<span class="inv-chip">{$TYPE_LABEL|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</div></div>
						<div class="col-sm-4"><strong>Last updated</strong><div>{$STOCK.updatedtime_display|escape:'html'}</div></div>
					</div>
					<details class="inv-tech-details" style="margin-top:12px;">
						<summary>Technical reference</summary>
						<div class="text-muted small" style="margin-top:8px;">Internal key <code>{$PRODUCT_KEY_DISPLAY|escape:'html'}</code> — used for aggregation; prefer catalog-linked rows for stable inventory.</div>
					</details>
				</div>
			</div>

			{if $IS_LEGACY_IDENTITY}
				<div class="panel panel-default inv-panel inv-panel-warn">
					<div class="panel-heading"><strong>Stabilize product identity</strong></div>
					<div class="panel-body">
						<p class="text-muted">This row was created from free-text product names. Link it to a Products &amp; Services record to use a stable key (e.g. <code>P:123</code>) and avoid duplicates.</p>
						<form id="WarehouseLinkProductForm" method="post" action="index.php" class="form-inline">
							<input type="hidden" name="module" value="Warehouse" />
							<input type="hidden" name="action" value="LinkProduct" />
							<input type="hidden" name="app" value="INVENTORY" />
							<input type="hidden" name="record" value="{$STOCK.stockid}" />
							<select name="link_productid" class="form-control" required="required" style="min-width:260px;">
								<option value="">Select catalog product…</option>
								{foreach from=$LINK_PRODUCT_OPTIONS item=LP}
									<option value="{$LP.id|escape:'html'}">{$LP.name|escape:'html'}</option>
								{/foreach}
							</select>
							<button type="submit" class="btn btn-warning">Link &amp; migrate lines</button>
						</form>
						<p class="text-muted small" style="margin-top:10px;">Updates matching inbound lines that share the exact legacy product name. If a catalog row already exists for that product, quantities are merged safely.</p>
						{literal}
						<script type="text/javascript">
							(function() {
								var f = document.getElementById('WarehouseLinkProductForm');
								if (f) {
									f.addEventListener('submit', function() {
										if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
											var ex = f.querySelector('input[name="' + csrfMagicName + '"]');
											if (!ex) {
												var h = document.createElement('input');
												h.type = 'hidden';
												h.name = csrfMagicName;
												h.value = csrfMagicToken;
												f.appendChild(h);
											}
										}
									});
								}
							})();
						</script>
						{/literal}
					</div>
				</div>
			{/if}

			<div class="panel panel-default inv-panel" style="margin-top:12px;">
				<div class="panel-heading"><strong>Warehouse info</strong></div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-6">
							<strong>Storage location</strong>
							<div class="inv-field-block">{if $STOCK.storage_location}{$STOCK.storage_location|escape:'html'|nl2br}{else}<span class="text-muted">—</span>{/if}</div>
						</div>
						<div class="col-sm-6">
							<strong>Warehouse notes</strong> <span class="text-muted small">(editable in Storage only)</span>
							<div class="inv-field-block inv-well">{if $STOCK.warehouse_note}{$STOCK.warehouse_note|escape:'html'|nl2br}{else}<span class="text-muted">None.</span>{/if}</div>
						</div>
					</div>
					<div class="row" style="margin-top:12px;">
						<div class="col-sm-12">
							<strong>Latest inbound note</strong> <span class="text-muted small">(mapped from Inbound receipt header)</span>
							<div class="inv-field-block inv-well">{if $STOCK.inbound_note}{$STOCK.inbound_note|escape:'html'|nl2br}{else}<span class="text-muted">None mapped yet.</span>{/if}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="panel panel-default inv-panel" style="margin-top:12px;">
				<div class="panel-heading"><strong>Stock movement chart</strong></div>
				<div class="panel-body">
					<div class="inv-header-badges" style="margin-bottom:8px;">
						<span class="inv-badge inv-badge-hardware">Inbound qty</span>
						<span class="inv-badge inv-badge-legacy">Outbound qty</span>
					</div>
					<div id="WarehouseMovementChart" style="width:100%; min-height:220px; border:1px solid rgba(102,129,176,.25); border-radius:10px; padding:8px; background:rgba(19,28,48,.25);"></div>
				</div>
			</div>

			<div class="panel panel-default inv-panel" style="margin-top:12px;">
				<div class="panel-heading"><strong>Inbound history</strong></div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Code</th>
									<th>Receipt</th>
									<th>Date</th>
									<th>Type</th>
									<th>Location</th>
									<th class="text-right">Qty</th>
									<th class="text-right">Unit price</th>
									<th>Line product</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$INBOUND_HISTORY item=H}
									<tr>
										<td>{if $H.code}<span class="inv-chip">{$H.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td><a href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$H.receiptid}&amp;app=INVENTORY">{$H.subject|escape:'html'}</a></td>
										<td>{$H.received_date_display|escape:'html'}</td>
										<td><span class="inv-chip">{$H.product_type|escape:'html'}</span></td>
										<td>{if $H.storage_location}{$H.storage_location|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td class="text-right metric-strong">{$H.quantity_display|escape:'html'}</td>
										<td class="text-right">{$H.unit_price_display|escape:'html'}</td>
										<td>{$H.product_name_display|escape:'html'}</td>
									</tr>
								{foreachelse}
									<tr><td colspan="8" class="text-muted text-center">No inbound lines matched this stock identity yet.</td></tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="panel panel-default inv-panel" style="margin-top:12px;">
				<div class="panel-heading"><strong>Outbound history</strong></div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Code</th>
									<th>Outbound</th>
									<th>Date</th>
									<th>Destination</th>
									<th>Location</th>
									<th>Type</th>
									<th class="text-right">Qty</th>
									<th class="text-right">Unit price</th>
									<th>Line product</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$OUTBOUND_HISTORY item=O}
									<tr>
										<td>{if $O.code}<span class="inv-chip">{$O.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td><a href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$O.issueid}&amp;app=INVENTORY">{$O.subject|escape:'html'}</a></td>
										<td>{$O.issued_date_display|escape:'html'}</td>
										<td>{if $O.destination}{$O.destination|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td>{if $O.storage_location}{$O.storage_location|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td><span class="inv-chip">{$O.product_type|escape:'html'}</span></td>
										<td class="text-right metric-strong">{$O.quantity_display|escape:'html'}</td>
										<td class="text-right">{$O.unit_price_display|escape:'html'}</td>
										<td>{$O.product_name_display|escape:'html'}</td>
									</tr>
								{foreachelse}
									<tr><td colspan="9" class="text-muted text-center">No outbound movements matched this stock identity yet.</td></tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
			{literal}
			<script type="text/javascript">
				(function() {
					var el = document.getElementById('WarehouseMovementChart');
					if (!el) return;
					var raw = {/literal}{$MOVEMENT_SERIES_JSON nofilter}{literal};
					var points = [];
					try {
						if (typeof raw === 'string') {
							points = raw ? JSON.parse(raw) : [];
						} else {
							points = raw || [];
						}
					} catch (e) { points = []; }
					if (!points || !points.length) {
						el.innerHTML = '<div class="text-muted" style="padding:18px;">No movement data yet for this stock identity.</div>';
						return;
					}

					var width = Math.max(el.clientWidth - 16, 320);
					var height = 220;
					var padL = 46, padR = 16, padT = 16, padB = 36;
					var chartW = width - padL - padR;
					var chartH = height - padT - padB;
					var maxY = 0;
					points.forEach(function(p) {
						var inn = parseFloat(p.inbound || 0);
						var out = parseFloat(p.outbound || 0);
						if (inn > maxY) maxY = inn;
						if (out > maxY) maxY = out;
					});
					if (maxY <= 0) maxY = 1;
					var stepX = points.length > 1 ? (chartW / (points.length - 1)) : 0;
					function y(v){ return padT + chartH - (v / maxY) * chartH; }
					function x(i){ return padL + i * stepX; }

					function buildPath(key) {
						var d = '';
					points.forEach(function(p, i){
							var xv = x(i);
							var yv = y(parseFloat(p[key] || 0));
							d += (i === 0 ? 'M ' : ' L ') + xv + ' ' + yv;
						});
						return d;
					}

					var labels = '';
					points.forEach(function(p, i){
						if (i % Math.ceil(points.length / 6) !== 0 && i !== points.length - 1) return;
						var xv = x(i);
						var rawLabel = (p.event_time || '').toString();
						var lbl = rawLabel.length >= 16 ? rawLabel.substring(0, 16) : rawLabel;
						labels += '<text x="' + xv + '" y="' + (height - 10) + '" text-anchor="middle" fill="#9fb2cc" font-size="11">' + lbl + '</text>';
					});
					var yTicks = '';
					for (var t = 0; t <= 4; t++) {
						var val = (maxY * t / 4);
						var yy = y(val);
						yTicks += '<line x1="' + padL + '" y1="' + yy + '" x2="' + (width - padR) + '" y2="' + yy + '" stroke="rgba(159,178,204,.18)" />';
						yTicks += '<text x="' + (padL - 6) + '" y="' + (yy + 4) + '" text-anchor="end" fill="#9fb2cc" font-size="11">' + val.toFixed(0) + '</text>';
					}

					var svg = '' +
						'<svg width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">' +
						yTicks +
						'<line x1="' + padL + '" y1="' + (padT + chartH) + '" x2="' + (width - padR) + '" y2="' + (padT + chartH) + '" stroke="rgba(159,178,204,.35)" />' +
						'<path d="' + buildPath('inbound') + '" fill="none" stroke="#2eb85c" stroke-width="2.5" />' +
						'<path d="' + buildPath('outbound') + '" fill="none" stroke="#f0ad4e" stroke-width="2.5" />' +
						labels +
						'</svg>';
					el.innerHTML = svg;
				})();
			</script>
			{/literal}
		</div>
	</div>
	</div>
</div>
{/strip}
