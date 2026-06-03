{* Warehouse Storage detail — Inventory modern UI *}
{strip}
{assign var=MK_WH_DETAIL_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_WH_DETAIL_INV value=true}
{/if}
{if $MK_WH_DETAIL_INV}
<div class="mk-wh-detail-page-content mk-gi-page">
	{include file="partials/InventoryDetailTopnav.tpl"|@vtemplate_path:'Vtiger'}

	{if !empty($SHOW_SAVED)}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--success" role="status">Warehouse fields saved.</div>
	{/if}
	{if !empty($SHOW_DELETE_BLOCKED)}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--warn" role="alert">Delete blocked: on-hand quantity and shrinkage must both be zero before removing this storage row.</div>
	{/if}
	{if !empty($SHOW_LINK_SUCCESS)}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--success" role="status">Product identity updated and inbound lines linked to the catalog record.</div>
	{/if}
	{if $LINK_ERROR eq 'not_legacy'}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--warn" role="alert">Link skipped: this row already uses catalog identity (not legacy name-based).</div>
	{elseif $LINK_ERROR eq 'invalid_product'}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--error" role="alert">Link failed: selected catalog product was not found.</div>
	{elseif $LINK_ERROR ne ''}
		<div class="mk-wh-detail-alert mk-wh-detail-alert--error" role="alert">Unable to complete product link. Try again or contact an admin.</div>
	{/if}

	<div class="mk-wh-detail-stats mk-wh-detail-stats--quad" role="group" aria-label="Stock levels">
		<div class="mk-wh-detail-stat">
			<span class="mk-wh-detail-stat__label">On hand</span>
			<strong class="mk-wh-detail-stat__value">{$STOCK.quantity_display|escape:'html'}</strong>
		</div>
		<div class="mk-wh-detail-stat">
			<span class="mk-wh-detail-stat__label">Available</span>
			<strong class="mk-wh-detail-stat__value mk-wh-detail-stat__value--accent">{$STOCK.available_display|escape:'html'}</strong>
		</div>
		<div class="mk-wh-detail-stat">
			<span class="mk-wh-detail-stat__label">Shrinkage</span>
			<strong class="mk-wh-detail-stat__value">{$STOCK.shrinkage_display|escape:'html'}</strong>
		</div>
		<div class="mk-wh-detail-stat mk-wh-detail-stat--accent">
			<span class="mk-wh-detail-stat__label">Last price</span>
			<strong class="mk-wh-detail-stat__value">{$STOCK.last_price_display|escape:'html'}</strong>
		</div>
	</div>

	<section class="mk-wh-detail-card mk-wh-detail-card--info" aria-labelledby="mkWhStockInfoTitle">
		<header class="mk-wh-detail-card__head">
			<h2 class="mk-wh-detail-card__title" id="mkWhStockInfoTitle">Stock info</h2>
		</header>
		<div class="mk-wh-detail-card__body">
			<div class="mk-wh-detail-fields">
				<div class="mk-wh-detail-field mk-wh-detail-field--wide">
					<span class="mk-wh-detail-field__label">Product name</span>
					<span class="mk-wh-detail-field__value mk-wh-detail-field__value--product">
						{if $STOCK.product_name_display ne ''}
							{if $CATALOG_PRODUCT_ID > 0}
								<a class="mk-wh-detail-product-link" href="index.php?module=ProductsServices&amp;view=Detail&amp;record={$CATALOG_PRODUCT_ID}&amp;app=SALES" title="View in Products &amp; Services">{decode_html($STOCK.product_name_display)|escape:'html'}</a>
							{else}
								<strong class="mk-wh-detail-product-name">{decode_html($STOCK.product_name_display)|escape:'html'}</strong>
							{/if}
						{else}
							<span class="mk-gi-muted">—</span>
						{/if}
					</span>
				</div>
				<div class="mk-wh-detail-field">
					<span class="mk-wh-detail-field__label">Storage code</span>
					<span class="mk-wh-detail-field__value">{if $STOCK.code}<span class="mk-gi-chip">{$STOCK.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-wh-detail-field">
					<span class="mk-wh-detail-field__label">Product type</span>
					<span class="mk-wh-detail-field__value"><span class="mk-gi-chip mk-gi-chip--type">{$TYPE_LABEL|escape:'html'}</span></span>
				</div>
				<div class="mk-wh-detail-field">
					<span class="mk-wh-detail-field__label">Last updated</span>
					<span class="mk-wh-detail-field__value">{$STOCK.updatedtime_display|escape:'html'}</span>
				</div>
				<div class="mk-wh-detail-field">
					<span class="mk-wh-detail-field__label">Expired date</span>
					<span class="mk-wh-detail-field__value">
						{if $STOCK.expired_date_display ne '—'}
							<div class="mk-wh-exp-cell__group">
								{if $STOCK.is_expired}
									<span class="mk-wh-expired-date mk-wh-expired-date--danger">{$STOCK.expired_date_display|escape:'html'}</span>
								{elseif $STOCK.is_expiring_soon}
									<span class="mk-wh-expired-date mk-wh-expired-date--warn">{$STOCK.expired_date_display|escape:'html'}</span>
								{else}
									<span class="mk-wh-expired-date">{$STOCK.expired_date_display|escape:'html'}</span>
								{/if}
								{if $STOCK.is_expired}<span class="mk-wh-badge mk-wh-badge--expired">Expired</span>
								{elseif $STOCK.is_expiring_soon}<span class="mk-wh-badge mk-wh-badge--dangerous">Dangerous</span>{/if}
							</div>
						{else}
							<span class="mk-gi-muted">—</span>
						{/if}
					</span>
				</div>
				<div class="mk-wh-detail-field mk-wh-detail-field--wide">
					<span class="mk-wh-detail-field__label">Serial numbers <span class="mk-wh-detail-field__hint">(from inbound receipts)</span></span>
					<span class="mk-wh-detail-field__value mk-wh-detail-field__value--mono">{if $STOCK.serial_full ne ''}<span title="{$STOCK.serial_full|escape:'html'}">{$STOCK.serial_display|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
			</div>
			<details class="mk-wh-detail-tech">
				<summary>Technical reference</summary>
				<p class="mk-wh-detail-tech__body">Internal key <code>{$PRODUCT_KEY_DISPLAY|escape:'html'}</code> — used for aggregation; prefer catalog-linked rows for stable inventory.</p>
			</details>
		</div>
	</section>

	{if $IS_LEGACY_IDENTITY}
		<section class="mk-wh-detail-card mk-wh-detail-card--link" aria-labelledby="mkWhLinkTitle">
			<header class="mk-wh-detail-card__head">
				<h2 class="mk-wh-detail-card__title" id="mkWhLinkTitle">Stabilize product identity</h2>
			</header>
			<div class="mk-wh-detail-card__body">
				<p class="mk-wh-detail-lead">This row was created from free-text product names. Link it to a Products &amp; Services record for a stable key (e.g. <code>P:123</code>) and fewer duplicates.</p>
				<form id="WarehouseLinkProductForm" method="post" action="index.php" class="mk-wh-detail-link-form">
					<input type="hidden" name="module" value="Warehouse" />
					<input type="hidden" name="action" value="LinkProduct" />
					<input type="hidden" name="app" value="INVENTORY" />
					<input type="hidden" name="record" value="{$STOCK.stockid}" />
					<select name="link_productid" class="mk-wh-detail-select" required="required">
						<option value="">Select catalog product…</option>
						{foreach from=$LINK_PRODUCT_OPTIONS item=LP}
							<option value="{$LP.id|escape:'html'}">{$LP.name|escape:'html'}</option>
						{/foreach}
					</select>
					<button type="submit" class="mk-wh-detail-btn mk-wh-detail-btn--warn">Link &amp; migrate lines</button>
				</form>
				<p class="mk-wh-detail-footnote">Updates matching inbound lines that share the exact legacy product name. If a catalog row already exists for that product, quantities are merged safely.</p>
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
		</section>
	{/if}

	<section class="mk-wh-detail-card" aria-labelledby="mkWhWarehouseInfoTitle">
		<header class="mk-wh-detail-card__head">
			<h2 class="mk-wh-detail-card__title" id="mkWhWarehouseInfoTitle">Warehouse info</h2>
		</header>
		<div class="mk-wh-detail-card__body">
			<div class="mk-wh-detail-fields">
				<div class="mk-wh-detail-field mk-wh-detail-field--wide">
					<span class="mk-wh-detail-field__label">Storage location</span>
					<span class="mk-wh-detail-field__value mk-wh-detail-field__value--note">{if $STOCK.storage_location}{$STOCK.storage_location|escape:'html'|nl2br}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-wh-detail-field mk-wh-detail-field--wide">
					<span class="mk-wh-detail-field__label">Warehouse notes <span class="mk-wh-detail-field__hint">(editable in Storage only)</span></span>
					<span class="mk-wh-detail-field__value mk-wh-detail-field__value--note">{if $STOCK.warehouse_note}{$STOCK.warehouse_note|escape:'html'|nl2br}{else}<span class="mk-gi-muted">None.</span>{/if}</span>
				</div>
				<div class="mk-wh-detail-field mk-wh-detail-field--wide">
					<span class="mk-wh-detail-field__label">Latest inbound note <span class="mk-wh-detail-field__hint">(from receipt header)</span></span>
					<span class="mk-wh-detail-field__value mk-wh-detail-field__value--note">{if $STOCK.inbound_note}{$STOCK.inbound_note|escape:'html'|nl2br}{else}<span class="mk-gi-muted">None mapped yet.</span>{/if}</span>
				</div>
			</div>
		</div>
	</section>

	<section class="mk-wh-detail-card mk-wh-detail-card--chart" aria-labelledby="mkWhChartTitle">
		<header class="mk-wh-detail-card__head">
			<h2 class="mk-wh-detail-card__title" id="mkWhChartTitle">Stock movement</h2>
			<div class="mk-wh-detail-chart-legend" aria-hidden="true">
				<span class="mk-wh-detail-chart-legend__item mk-wh-detail-chart-legend__item--in">Inbound qty</span>
				<span class="mk-wh-detail-chart-legend__item mk-wh-detail-chart-legend__item--out">Outbound qty</span>
			</div>
		</header>
		<div class="mk-wh-detail-card__body mk-wh-detail-card__body--flush">
			<script type="application/json" id="mk-wh-movement-json">{$MOVEMENT_SERIES_JSON nofilter}</script>
			<div id="WarehouseMovementChart" class="mk-wh-detail-chart" role="img" aria-label="Inbound and outbound quantities over time"></div>
		</div>
	</section>

	<section class="mk-wh-detail-card mk-wh-detail-card--lines" aria-labelledby="mkWhInboundHistTitle">
		<header class="mk-wh-detail-card__head">
			<h2 class="mk-wh-detail-card__title" id="mkWhInboundHistTitle">Inbound history</h2>
			<span class="mk-wh-detail-card__badge">{$INBOUND_HISTORY_COUNT|default:0} line{if $INBOUND_HISTORY_COUNT|default:0 ne 1}s{/if}</span>
		</header>
		<div class="mk-wh-detail-card__body mk-wh-detail-card__body--flush">
			<div class="mk-gi-table-wrap">
				<table class="mk-gi-table mk-wh-detail-table mk-wh-detail-table--inbound">
					<thead>
						<tr>
							<th scope="col">Code</th>
							<th scope="col">Receipt</th>
							<th scope="col">Date</th>
							<th scope="col">Type</th>
							<th scope="col">Location</th>
							<th scope="col">Qty</th>
							<th scope="col">Unit price</th>
							<th scope="col">Serial</th>
							<th scope="col">Description</th>
							<th scope="col">Line product</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$INBOUND_HISTORY item=H}
							<tr>
								<td><span class="mk-wh-detail-cell">{if $H.code}<span class="mk-gi-chip">{$H.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell"><a href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$H.receiptid}&amp;app=INVENTORY">{$H.subject|escape:'html'}</a></span></td>
								<td><span class="mk-wh-detail-cell">{$H.received_date_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell"><span class="mk-gi-chip mk-gi-chip--type">{$H.product_type|escape:'html'}</span></span></td>
								<td><span class="mk-wh-detail-cell">{if $H.storage_location}{$H.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{$H.quantity_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell">{$H.unit_price_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell">{if $H.serial_display ne ''}<span title="{$H.serial_display|escape:'html'}">{$H.serial_display|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{if $H.description ne ''}{$H.description|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{$H.product_name_display|escape:'html'}</span></td>
							</tr>
						{foreachelse}
							<tr><td colspan="10" class="mk-gi-table__empty">No inbound lines matched this stock identity yet.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="mk-wh-detail-card mk-wh-detail-card--lines" aria-labelledby="mkWhOutboundHistTitle">
		<header class="mk-wh-detail-card__head">
			<h2 class="mk-wh-detail-card__title" id="mkWhOutboundHistTitle">Outbound history</h2>
			<span class="mk-wh-detail-card__badge">{$OUTBOUND_HISTORY_COUNT|default:0} line{if $OUTBOUND_HISTORY_COUNT|default:0 ne 1}s{/if}</span>
		</header>
		<div class="mk-wh-detail-card__body mk-wh-detail-card__body--flush">
			<div class="mk-gi-table-wrap">
				<table class="mk-gi-table mk-wh-detail-table mk-wh-detail-table--outbound">
					<thead>
						<tr>
							<th scope="col">Code</th>
							<th scope="col">{vtranslate('GoodsIssue','GoodsIssue')}</th>
							<th scope="col">Date</th>
							<th scope="col">Destination</th>
							<th scope="col">Location</th>
							<th scope="col">Type</th>
							<th scope="col">Qty</th>
							<th scope="col">Unit price</th>
							<th scope="col">Serial</th>
							<th scope="col">Description</th>
							<th scope="col">Line product</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$OUTBOUND_HISTORY item=O}
							<tr>
								<td><span class="mk-wh-detail-cell">{if $O.code}<span class="mk-gi-chip">{$O.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell"><a href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$O.issueid}&amp;app=INVENTORY">{$O.subject|escape:'html'}</a></span></td>
								<td><span class="mk-wh-detail-cell">{$O.issued_date_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell">{if $O.destination}{$O.destination|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{if $O.storage_location}{$O.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell"><span class="mk-gi-chip mk-gi-chip--type">{$O.product_type|escape:'html'}</span></span></td>
								<td><span class="mk-wh-detail-cell">{$O.quantity_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell">{$O.unit_price_display|escape:'html'}</span></td>
								<td><span class="mk-wh-detail-cell">{if $O.serial_display ne ''}<span title="{$O.serial_full|escape:'html'}">{$O.serial_display|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{if $O.description ne ''}{$O.description|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-wh-detail-cell">{$O.product_name_display|escape:'html'}</span></td>
							</tr>
						{foreachelse}
							<tr><td colspan="11" class="mk-gi-table__empty">No outbound movements matched this stock identity yet.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</section>
</div>
{else}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
<div class="main-container clearfix">
	<div class="detailViewPageDiv content-area full-width" style="margin-left:0;padding:24px;">
		<p class="text-muted">Open with <code>app=INVENTORY</code> for the modern Storage detail view.</p>
		<a class="btn btn-default" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$STOCK.stockid}&amp;app=INVENTORY">Mở giao diện kho</a>
	</div>
</div>
{/if}
{/strip}
