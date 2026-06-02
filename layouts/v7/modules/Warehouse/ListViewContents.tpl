{* Warehouse Storage list — modern Inventory UI *}
{strip}
{assign var=MK_WH_IS_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_WH_IS_INV value=true}
{/if}
{if $MK_WH_IS_INV}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card mk-wh-suite-card">
		<div class="mk-wh-page-head">
			{include file="partials/StorageListHeader.tpl"|vtemplate_path:$MODULE}
		</div>

		{* Prototype KPI strip (UI-only) *}
		<section class="mk-inv-kpis" aria-label="Storage KPIs">
			<article class="mk-inv-kpi">
				<div class="mk-inv-kpi__label"><span class="mk-inv-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.9"/></svg></span>Phiếu chờ QC</div>
				<div class="mk-inv-kpi__value">1</div>
			</article>
			<article class="mk-inv-kpi">
				<div class="mk-inv-kpi__label"><span class="mk-inv-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 3v12" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span>Phiếu xuất chờ duyệt</div>
				<div class="mk-inv-kpi__value">1</div>
			</article>
			<article class="mk-inv-kpi">
				<div class="mk-inv-kpi__label"><span class="mk-inv-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.7"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>SKU đang lưu kho</div>
				<div class="mk-inv-kpi__value">4</div>
			</article>
			<article class="mk-inv-kpi mk-inv-kpi--danger">
				<div class="mk-inv-kpi__label"><span class="mk-inv-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M12 16.6h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/><path d="M10.3 4.2 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>Lô sắp hết hạn (&lt;90 ngày)</div>
				<div class="mk-inv-kpi__value">2</div>
			</article>
		</section>

		<div class="mk-inv-flow-bar">
			<nav class="mk-gi-topnav mk-gi-topnav--pills" aria-label="Inventory modules">
				<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 3v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>Inbound</a>
				<a class="is-active" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY" aria-current="page"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.6"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>Storage</a>
				<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 14l-5-5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>Outbound</a>
				<a href="javascript:void(0)" role="button" data-mk-wh-tab="qc"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8"/></svg></span>QC</a>
			</nav>
		</div>

		<div id="mkWhStoragePane">
		<section class="mk-wh-kpi-section" aria-label="Storage overview">
			{include file="partials/StorageListKpi.tpl"|vtemplate_path:$MODULE}
			{if !empty($SHOW_DELETED)}
				<div class="mk-gi-alert mk-gi-alert--success" role="status">Stock row deleted.</div>
			{/if}
			{if !empty($SHOW_DELETE_ERROR)}
				<div class="mk-gi-alert mk-gi-alert--danger" role="alert">Unable to delete: row was not found or is not empty.</div>
			{/if}
			{if !empty($FILTER_LOW_STOCK)}
				<div class="mk-gi-alert mk-gi-alert--warn" role="status">Showing low stock only (available &lt; {$LOW_STOCK_THRESHOLD|escape:'html'}). <a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Clear filter</a></div>
			{/if}
		</section>

		<div class="mk-gi-dark-panel mk-wh-filter-panel">
		<form method="get" action="index.php" class="mk-gi-filter-bar mk-wh-filter-bar">
			<input type="hidden" name="module" value="Warehouse" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="INVENTORY" />
			<div class="mk-gi-filter-bar__fields">
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Product name or key</span>
					<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="mk-gi-input" placeholder="Product name or key" />
				</label>
				<label class="mk-gi-field mk-gi-field--narrow">
					<span class="mk-gi-field__label">Min qty</span>
					<input type="text" name="qty_min" value="{$QTY_MIN|escape:'html'}" class="mk-gi-input" placeholder="Min qty" />
				</label>
				<label class="mk-gi-field mk-gi-field--narrow">
					<span class="mk-gi-field__label">Max qty</span>
					<input type="text" name="qty_max" value="{$QTY_MAX|escape:'html'}" class="mk-gi-input" placeholder="Max qty" />
				</label>
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Location contains</span>
					<input type="text" name="storage_location" value="{$FILTER_LOCATION|escape:'html'}" class="mk-gi-input" placeholder="Location contains" />
				</label>
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Type</span>
					<select name="item_type" class="mk-gi-input mk-gi-select">
						<option value="">All types</option>
						<option value="hardware" {if $FILTER_ITEM_TYPE eq 'hardware'}selected="selected"{/if}>Hardware</option>
						<option value="software" {if $FILTER_ITEM_TYPE eq 'software'}selected="selected"{/if}>Software</option>
						<option value="service" {if $FILTER_ITEM_TYPE eq 'service'}selected="selected"{/if}>Service</option>
						<option value="other" {if $FILTER_ITEM_TYPE eq 'other'}selected="selected"{/if}>Other</option>
						<option value="__empty__" {if $FILTER_ITEM_TYPE eq '__empty__'}selected="selected"{/if}>No type</option>
					</select>
				</label>
				<label class="mk-gi-field mk-gi-field--check">
					<span class="mk-gi-field__label">Legacy only</span>
					<span class="mk-wh-check">
						<input type="checkbox" name="legacy_only" value="1" {if !empty($FILTER_LEGACY_ONLY)}checked="checked"{/if} />
						<span>Legacy only</span>
					</span>
				</label>
			</div>
			<div class="mk-gi-filter-bar__actions">
				<button type="submit" class="mk-gi-btn mk-gi-btn--filter">
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FILTER'}</span>
					<span class="mk-gi-btn__txt">Filters</span>
				</button>
				<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY" class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost">
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RESET'}</span>
					<span class="mk-gi-btn__txt">Reset</span>
				</a>
			</div>
		</form>
		</div>

		<div class="mk-gi-table-panel">
		<div class="mk-gi-table-toolbar">
			<p class="mk-gi-table-toolbar__count" id="mkWhStorageCount">
				{assign var=MK_WH_TOTAL value=$ROWS|@count}
				Showing <strong>1</strong> to <strong>{if $MK_WH_TOTAL gt 0}{$MK_WH_TOTAL}{else}0{/if}</strong> of <strong>{$MK_WH_TOTAL}</strong> storage{if $MK_WH_TOTAL ne 1}s{/if}
			</p>
		</div>

		<div class="mk-gi-table-wrap mk-wh-table-wrap">
			<table class="mk-gi-table mk-wh-table" id="mkWhStorageTable">
				<thead>
					<tr>
						<th scope="col">Code</th>
						<th scope="col">Product</th>
						<th scope="col">Serial</th>
						<th scope="col">Identity</th>
						<th scope="col">Type</th>
						<th scope="col" class="mk-gi-table__num">Qty</th>
						<th scope="col">Expired date</th>
						<th scope="col" class="mk-gi-table__num">Available</th>
						<th scope="col" class="mk-gi-table__num">Last price</th>
						<th scope="col">Location</th>
						<th scope="col">Updated</th>
						<th scope="col" class="mk-gi-table__actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$ROWS item=R}
						<tr class="{if $R.is_low_stock}mk-wh-row--low{/if}{if $R.is_expiring_soon || $R.is_expired} mk-wh-row--expiring{/if}">
							<td>
								{if $R.code}<span class="mk-gi-chip">{$R.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}
							</td>
							<td class="mk-gi-table__product">
								<a href="index.php?module=Warehouse&amp;view=Detail&amp;record={$R.stockid}&amp;app=INVENTORY">{$R.product_name_display|escape:'html'}</a>
								{if $R.is_expiring_soon}<span class="mk-wh-exp-dot" title="Sắp hết hạn (<= 3 tháng)"></span>{/if}
								{if $R.is_expired}<span class="mk-wh-exp-dot mk-wh-exp-dot--danger" title="Đã hết hạn"></span>{/if}
							</td>
							<td class="mk-gi-table__serial">
								{if $R.serial_full ne ''}<span title="{$R.serial_full|escape:'html'}">{$R.serial_display|escape:'html'}</span>{else}{$R.serial_display|escape:'html'}{/if}
							</td>
							<td>
								{if $R.is_legacy_identity}
									<span class="mk-wh-badge mk-wh-badge--legacy">Legacy</span>
								{else}
									<span class="mk-wh-badge mk-wh-badge--catalog">Catalog</span>
								{/if}
							</td>
							<td><span class="mk-gi-chip mk-gi-chip--type">{$R.type_label|escape:'html'}</span></td>
							<td class="mk-gi-table__num mk-gi-table__qty">{$R.quantity_display|escape:'html'}</td>
							<td class="mk-wh-exp-cell">
								{if $R.expired_date_display ne '—'}
									<div class="mk-wh-exp-cell__group">
										{if $R.is_expired}
											<span class="mk-wh-expired-date mk-wh-expired-date--danger">{$R.expired_date_display|escape:'html'}</span>
										{elseif $R.is_expiring_soon}
											<a class="mk-wh-expired-date mk-wh-expired-date--warn" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY&amp;expiring=1" title="Xem danh sách sắp hết hạn">{$R.expired_date_display|escape:'html'}</a>
										{else}
											<span class="mk-wh-expired-date">{$R.expired_date_display|escape:'html'}</span>
										{/if}
										{if $R.is_expired}<span class="mk-wh-badge mk-wh-badge--expired" title="Đã hết hạn sử dụng">Expired</span>
										{elseif $R.is_expiring_soon}<span class="mk-wh-badge mk-wh-badge--dangerous" title="Sắp hết hạn (trong 3 tháng)">Dangerous</span>{/if}
									</div>
								{else}
									<span class="mk-gi-muted">—</span>
								{/if}
							</td>
							<td class="mk-gi-table__num mk-gi-table__qty">
								{$R.available_display|escape:'html'}
								{if $R.is_low_stock}<span class="mk-wh-badge mk-wh-badge--low">Low</span>{/if}
							</td>
							<td class="mk-gi-table__num">{$R.last_price_display|escape:'html'}</td>
							<td>{if $R.storage_location}{$R.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td class="mk-wh-updated">{$R.updatedtime_display|escape:'html'}</td>
							<td class="mk-gi-table__actions">
								<div class="mk-gi-row-actions">
									<a class="mk-gi-icon-btn" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$R.stockid}&amp;app=INVENTORY" title="View" aria-label="View">
										{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='VIEW'}
									</a>
									<a class="mk-gi-icon-btn" href="index.php?module=Warehouse&amp;view=Edit&amp;record={$R.stockid}&amp;app=INVENTORY" title="Edit" aria-label="Edit">
										{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}
									</a>
								</div>
							</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="12" class="mk-gi-table__empty">No stock rows match your filters.</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</div>
	</div>

	<div class="mk-inv-qc-pane hide" id="mkWhQcPane" aria-label="Hàng đợi QC">
		<div class="mk-inv-qc-card">
			<div class="mk-inv-qc-card__head">
				<h3 class="mk-inv-qc-card__title">Hàng đợi QC</h3>
				<a class="mk-inv-qc-card__btn" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">Tạo phiếu nhập</a>
			</div>
			<div class="mk-inv-qc-table-wrap">
				<table class="mk-inv-qc-table" role="table">
					<thead>
						<tr>
							<th>Mã phiếu</th>
							<th>Nhà cung cấp</th>
							<th>PO</th>
							<th>Số dòng</th>
							<th>Ngày tạo</th>
							<th>Trạng thái</th>
							<th class="mk-inv-qc-td-right">Thao tác</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><span class="mk-gi-chip">GRN-0002</span></td>
							<td>Vinamilk Logistics</td>
							<td>PO-2026-0151</td>
							<td>2</td>
							<td>16:09 26/5/26</td>
							<td><span class="mk-inv-qc-pill">Chờ QC</span></td>
							<td class="mk-inv-qc-td-right"><a class="mk-gi-btn mk-gi-btn--mini" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">QC</a></td>
						</tr>
						<tr>
							<td><span class="mk-gi-chip">GRN-0004</span></td>
							<td>CTY Dược Hậu Giang</td>
							<td>PO-2026-0182</td>
							<td>1</td>
							<td>09:41 01/6/26</td>
							<td><span class="mk-inv-qc-pill">Chờ QC</span></td>
							<td class="mk-inv-qc-td-right"><a class="mk-gi-btn mk-gi-btn--mini" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">QC</a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
{else}
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
									<th>SERIAL</th>
									<th>Identity</th>
									<th>Type</th>
									<th class="text-right">Qty</th>
									<th>Expired date</th>
									<th class="text-right">Available</th>
									<th class="text-right">Last price</th>
									<th>Location</th>
									<th>Updated</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$ROWS item=R}
									<tr class="{if $R.is_low_stock}inv-row-low{/if}{if $R.is_expiring_soon || $R.is_expired} inv-row-expiring{/if}">
										<td>{if $R.code}<span class="inv-chip">{$R.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td>
											<a href="index.php?module=Warehouse&view=Detail&record={$R.stockid}&app=INVENTORY">{$R.product_name_display|escape:'html'}</a>
											{if $R.is_expiring_soon}<span class="inv-status-dot" title="Sắp hết hạn (<= 3 tháng)" style="background:#f59e0b;"></span>{/if}
											{if $R.is_expired}<span class="inv-status-dot" title="Đã hết hạn" style="background:#dc2626;"></span>{/if}
										</td>
										<td class="serial-cell" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
											{if $R.serial_full ne ''}<span title="{$R.serial_full|escape:'html'}">{$R.serial_display|escape:'html'}</span>{else}{$R.serial_display|escape:'html'}{/if}
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
										<td class="mk-wh-exp-cell">
											{if $R.expired_date_display ne '—'}
												<div class="mk-wh-exp-cell__group">
													{if $R.is_expired}
														<span class="inv-badge inv-badge-low">{$R.expired_date_display|escape:'html'}</span>
													{elseif $R.is_expiring_soon}
														<a href="index.php?module=Warehouse&view=List&app=INVENTORY&expiring=1" class="inv-badge inv-badge-low" title="Xem danh sách sắp hết hạn">{$R.expired_date_display|escape:'html'}</a>
													{else}
														<span class="text-muted">{$R.expired_date_display|escape:'html'}</span>
													{/if}
													{if $R.is_expired}<span class="inv-badge inv-badge-expired" title="Đã hết hạn sử dụng">Expired</span>{elseif $R.is_expiring_soon}<span class="inv-badge inv-badge-dangerous" title="Sắp hết hạn (trong 3 tháng)">Dangerous</span>{/if}
												</div>
											{else}
												<span class="text-muted">—</span>
											{/if}
										</td>
										<td class="text-right metric-strong">{$R.available_display|escape:'html'}{if $R.is_low_stock}<span class="inv-badge inv-badge-low" title="Available below threshold">Low</span>{/if}</td>
										<td class="text-right">{$R.last_price_display|escape:'html'}</td>
										<td>{if $R.storage_location}{$R.storage_location|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td>{$R.updatedtime_display|escape:'html'}</td>
										<td class="text-nowrap">
											<a class="btn btn-xs btn-default" href="index.php?module=Warehouse&view=Detail&record={$R.stockid}&app=INVENTORY">View</a>
											<a class="btn btn-xs btn-primary" href="index.php?module=Warehouse&view=Edit&record={$R.stockid}&app=INVENTORY">Edit</a>
										</td>
									</tr>
								{foreachelse}
									<tr><td colspan="12" class="inv-empty">No stock rows match your filters.</td></tr>
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
{/if}
{/strip}
