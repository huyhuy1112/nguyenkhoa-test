{* GoodsIssue Outbound list — modern Inventory UI (custom data table) *}
{strip}
{assign var=MK_GI_IS_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_GI_IS_INV value=true}
{/if}
{if $MK_GI_IS_INV}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card">
		<div class="mk-wh-page-head mk-go-page-head">
			{include file="partials/OutboundListHeader.tpl"|vtemplate_path:$MODULE}
		</div>

		{* Prototype KPI strip (UI-only) *}
		<section class="mk-inv-kpis" aria-label="Outbound KPIs">
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
				<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.6"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>Storage</a>
				<a class="is-active" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY" aria-current="page"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 14l-5-5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>Outbound</a>
				<a href="javascript:void(0)" role="button" data-mk-gi-tab="qc"><span class="mk-inv-tab-ic" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8"/></svg></span>QC</a>
			</nav>
		</div>
		<div id="mkGiOutboundPane">
		<div class="mk-gi-dark-panel mk-wh-filter-panel">
		{if !empty($SHOW_DELETED)}
			<div class="mk-gi-alert mk-gi-alert--success" role="status">Outbound issue deleted and stock restored.</div>
		{/if}
		{if !empty($SHOW_DELETE_ERROR)}
			<div class="mk-gi-alert mk-gi-alert--danger" role="alert">Unable to delete outbound issue.</div>
		{/if}

		<form method="get" action="index.php" class="mk-gi-filter-bar">
			<input type="hidden" name="module" value="GoodsIssue" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="INVENTORY" />
			<div class="mk-gi-filter-bar__fields">
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Subject or destination</span>
					<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="mk-gi-input" placeholder="Search subject/destination" />
				</label>
				<label class="mk-gi-field mk-gi-field--date">
					<span class="mk-gi-field__label">From</span>
					<input type="date" name="date_from" value="{$DATE_FROM|escape:'html'}" class="mk-gi-input" />
				</label>
				<label class="mk-gi-field mk-gi-field--date">
					<span class="mk-gi-field__label">To</span>
					<input type="date" name="date_to" value="{$DATE_TO|escape:'html'}" class="mk-gi-input" />
				</label>
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Storage location</span>
					<input type="text" name="storage_location" value="{$STORAGE_LOCATION|escape:'html'}" class="mk-gi-input" placeholder="Storage location" />
				</label>
			</div>
			<div class="mk-gi-filter-bar__actions">
				<button type="submit" class="mk-gi-btn mk-gi-btn--filter">
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FILTER'}</span>
					<span class="mk-gi-btn__txt">Filters</span>
				</button>
				<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY" class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost">
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RESET'}</span>
					<span class="mk-gi-btn__txt">Reset</span>
				</a>
			</div>
		</form>
		</div>

		<div class="mk-gi-table-panel">
		<div class="mk-gi-table-toolbar">
			<p class="mk-gi-table-toolbar__count" id="mkGiOutboundCount">
				{assign var=MK_GI_TOTAL value=$ROWS|@count}
				Showing <strong>1</strong> to <strong>{if $MK_GI_TOTAL gt 0}{$MK_GI_TOTAL}{else}0{/if}</strong> of <strong>{$MK_GI_TOTAL}</strong> outbound{if $MK_GI_TOTAL ne 1}s{/if}
			</p>
		</div>

		<div class="mk-gi-table-wrap">
			<table class="mk-gi-table mk-go-table" id="mkGiOutboundTable">
				<thead>
					<tr>
						<th scope="col">Code</th>
						<th scope="col">Subject</th>
						<th scope="col">Destination</th>
						<th scope="col">Issued date</th>
						<th scope="col">Storage</th>
						<th scope="col" class="mk-gi-table__num">Total qty</th>
						<th scope="col" class="mk-gi-table__num">Total value</th>
						<th scope="col" class="mk-gi-table__actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$ROWS item=R}
						<tr>
							<td>
								{if $R.code}<span class="mk-gi-chip">{$R.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}
							</td>
							<td class="mk-gi-table__subject">
								<a href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$R.issueid}&amp;app=INVENTORY">{$R.subject|escape:'html'}</a>
							</td>
							<td>{if $R.destination}{$R.destination|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td>{if $R.issued_date}{$R.issued_date|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td>{if $R.storage_location}{$R.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td class="mk-gi-table__num mk-gi-table__qty">{$R.total_qty_display|escape:'html'}</td>
							<td class="mk-gi-table__num mk-gi-table__qty">{$R.total_value_display|escape:'html'}</td>
							<td class="mk-gi-table__actions">
								<div class="mk-gi-row-actions">
									<a class="mk-gi-icon-btn" href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$R.issueid}&amp;app=INVENTORY" title="View" aria-label="View">
										{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='VIEW'}
									</a>
									<a class="mk-gi-icon-btn" href="index.php?module=GoodsIssue&amp;view=Edit&amp;record={$R.issueid}&amp;app=INVENTORY" title="Edit" aria-label="Edit">
										{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}
									</a>
									<a class="mk-gi-icon-btn mk-gi-icon-btn--danger" href="index.php?module=GoodsIssue&amp;action=Delete&amp;record={$R.issueid}&amp;app=INVENTORY" title="Delete" aria-label="Delete" onclick="return confirm('Delete this outbound issue and restore stock?');">
										{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}
									</a>
								</div>
							</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="8" class="mk-gi-table__empty">No outbound issues yet.</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</div>
	</div>

	<div class="mk-inv-qc-pane hide" id="mkGiQcPane" aria-label="Hàng đợi QC">
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
				<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a class="active" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
			</div>
			<div class="row">
				<div class="col-lg-12">
					{if !empty($SHOW_DELETED)}
						<div class="alert alert-success inv-alert">Outbound issue deleted and stock restored.</div>
					{/if}
					{if !empty($SHOW_DELETE_ERROR)}
						<div class="alert alert-danger inv-alert">Unable to delete outbound issue.</div>
					{/if}
					<div class="inv-suite-head">
						<div>
							<h3>Outbound</h3>
							<p class="text-muted">Outbound issues deduct from Storage. Editing applies deltas safely; deleting restores stock.</p>
						</div>
						<div class="inv-suite-actions">
							<a class="btn btn-success" href="index.php?module=GoodsIssue&view=Edit&app=INVENTORY">+ New Outbound</a>
						</div>
					</div>
					<form method="get" action="index.php" class="form-inline inv-filter-bar">
						<input type="hidden" name="module" value="GoodsIssue" />
						<input type="hidden" name="view" value="List" />
						<input type="hidden" name="app" value="INVENTORY" />
						<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="form-control" placeholder="Subject or destination" style="min-width:220px;" />
						<input type="text" name="destination" value="{$DESTINATION|escape:'html'}" class="form-control" placeholder="Destination contains" style="min-width:200px;" />
						<input type="date" name="date_from" value="{$DATE_FROM|escape:'html'}" class="form-control" />
						<input type="date" name="date_to" value="{$DATE_TO|escape:'html'}" class="form-control" />
						<button type="submit" class="btn btn-default">Filter</button>
						<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY" class="btn btn-link">Reset</a>
					</form>
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Subject</th>
									<th>Code</th>
									<th>Destination</th>
									<th>Date</th>
									<th class="text-right">Total qty</th>
									<th class="text-right">Updated</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$ROWS item=R}
									<tr>
										<td><a href="index.php?module=GoodsIssue&view=Detail&record={$R.issueid}&app=INVENTORY">{$R.subject|escape:'html'}</a></td>
										<td>{if $R.code}<span class="inv-chip">{$R.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</td>
										<td>{if $R.destination}{$R.destination|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td>{if $R.issued_date}{$R.issued_date|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td class="text-right metric-strong">{number_format($R.total_qty,2,'.',',')}</td>
										<td class="text-right">{if $R.updatedtime}{$R.updatedtime|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
										<td class="text-nowrap">
											<a class="btn btn-xs btn-default" href="index.php?module=GoodsIssue&view=Detail&record={$R.issueid}&app=INVENTORY">View</a>
											<a class="btn btn-xs btn-primary" href="index.php?module=GoodsIssue&view=Edit&record={$R.issueid}&app=INVENTORY">Edit</a>
											<a class="btn btn-xs btn-danger" href="index.php?module=GoodsIssue&action=Delete&record={$R.issueid}&app=INVENTORY" onclick="return confirm('Delete this outbound issue and restore stock?');">Delete</a>
										</td>
									</tr>
								{foreachelse}
									<tr><td colspan="7" class="inv-empty">No outbound issues yet.</td></tr>
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
