{* GoodsReceipt Inbound list — modern Inventory UI (Outbound style) *}
{strip}
{assign var=MK_GR_IS_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_GR_IS_INV value=true}
{/if}
{if $MK_GR_IS_INV}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card">
		<div class="mk-wh-page-head mk-gr-page-head">
			{include file="partials/InboundListHeader.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-inv-flow-bar">
			<nav class="mk-gi-topnav mk-gi-topnav--pills" aria-label="Inventory modules">
				<a class="is-active" href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY" aria-current="page">Inbound</a>
				<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Storage</a>
				<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Outbound</a>
			</nav>
		</div>
		<div class="mk-gi-dark-panel mk-wh-filter-panel">
		<form method="get" action="index.php" class="mk-gi-filter-bar">
			<input type="hidden" name="module" value="GoodsReceipt" />
			<input type="hidden" name="view" value="List" />
			<input type="hidden" name="app" value="INVENTORY" />
			<div class="mk-gi-filter-bar__fields">
				<label class="mk-gi-field">
					<span class="mk-gi-field__label">Subject or source</span>
					<input type="text" name="search" value="{$SEARCH|escape:'html'}" class="mk-gi-input" placeholder="Search subject/source" />
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
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FILTER'}</span>
					<span class="mk-gi-btn__txt">Filters</span>
				</button>
				<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY" class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost">
					<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RESET'}</span>
					<span class="mk-gi-btn__txt">Reset</span>
				</a>
			</div>
		</form>
		</div>

		<div class="mk-gi-table-panel">
		<div class="mk-gi-table-toolbar">
			<p class="mk-gi-table-toolbar__count" id="mkGrInboundCount">
				{assign var=MK_GR_TOTAL value=$ROWS|@count}
				Showing <strong>1</strong> to <strong>{if $MK_GR_TOTAL gt 0}{$MK_GR_TOTAL}{else}0{/if}</strong> of <strong>{$MK_GR_TOTAL}</strong> inbound{if $MK_GR_TOTAL ne 1}s{/if}
			</p>
		</div>

		<div class="mk-gi-table-wrap">
			<table class="mk-gi-table mk-gr-table" id="mkGrInboundTable">
				<thead>
					<tr>
						<th scope="col">Code</th>
						<th scope="col">Subject</th>
						<th scope="col">Source</th>
						<th scope="col">Received date</th>
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
								<a href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$R.receiptid}&amp;app=INVENTORY">{$R.subject|escape:'html'}</a>
							</td>
							<td>{if $R.source_name}{$R.source_name|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td>{if $R.received_date}{$R.received_date|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td>{if $R.storage_location}{$R.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</td>
							<td class="mk-gi-table__num mk-gi-table__qty">{$R.total_qty_display|escape:'html'}</td>
							<td class="mk-gi-table__num mk-gi-table__qty">{$R.total_value_display|escape:'html'}</td>
							<td class="mk-gi-table__actions">
								<div class="mk-gi-row-actions">
									<a class="mk-gi-icon-btn" href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$R.receiptid}&amp;app=INVENTORY" title="View" aria-label="View">
										{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='VIEW'}
									</a>
									<a class="mk-gi-icon-btn" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;record={$R.receiptid}&amp;app=INVENTORY" title="Edit" aria-label="Edit">
										{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}
									</a>
									<a class="mk-gi-icon-btn mk-gi-icon-btn--danger" href="index.php?module=GoodsReceipt&amp;action=Delete&amp;record={$R.receiptid}&amp;app=INVENTORY" title="Delete" aria-label="Delete" onclick="return confirm('Delete this inbound receipt? Stock will be reversed.');">
										{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}
									</a>
								</div>
							</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="8" class="mk-gi-table__empty">No inbound receipts found.</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
{else}
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
{/if}
{/strip}
