{* GoodsReceipt Inbound detail — summary cards (Inventory modern UI) *}
{strip}
{assign var=MK_GR_DETAIL_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_GR_DETAIL_INV value=true}
{/if}
{if $MK_GR_DETAIL_INV}
<div class="mk-gr-detail-page-content mk-gi-page">
	{include file="partials/InventoryDetailTopnav.tpl"|@vtemplate_path:'Vtiger'}

	<div class="mk-gr-detail-stats" role="group" aria-label="Receipt summary">
		<div class="mk-gr-detail-stat">
			<span class="mk-gr-detail-stat__label">Line items</span>
			<strong class="mk-gr-detail-stat__value">{$ITEM_COUNT|default:0}</strong>
		</div>
		<div class="mk-gr-detail-stat">
			<span class="mk-gr-detail-stat__label">Total quantity</span>
			<strong class="mk-gr-detail-stat__value">{$TOTAL_QTY_DISPLAY|default:'0.00'}</strong>
		</div>
		<div class="mk-gr-detail-stat mk-gr-detail-stat--accent">
			<span class="mk-gr-detail-stat__label">Receipt value</span>
			<strong class="mk-gr-detail-stat__value">{$TOTAL_VALUE_DISPLAY|default:'0'}</strong>
		</div>
	</div>

	<section class="mk-gr-detail-card mk-gr-detail-card--info" aria-labelledby="mkGrInboundInfoTitle">
		<header class="mk-gr-detail-card__head">
			<h2 class="mk-gr-detail-card__title" id="mkGrInboundInfoTitle">Inbound Info</h2>
		</header>
		<div class="mk-gr-detail-card__body">
			<div class="mk-gr-detail-fields">
				<div class="mk-gr-detail-field">
					<span class="mk-gr-detail-field__label">Code</span>
					<span class="mk-gr-detail-field__value">{if $RECORD_DATA.code}<span class="mk-gi-chip">{$RECORD_DATA.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-gr-detail-field">
					<span class="mk-gr-detail-field__label">Subject</span>
					<span class="mk-gr-detail-field__value">{$RECORD_DATA.subject|escape:'html'}</span>
				</div>
				<div class="mk-gr-detail-field">
					<span class="mk-gr-detail-field__label">Supplier / Source</span>
					<span class="mk-gr-detail-field__value">{if $RECORD_DATA.source_name}{$RECORD_DATA.source_name|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-gr-detail-field">
					<span class="mk-gr-detail-field__label">Received date</span>
					<span class="mk-gr-detail-field__value">{if $RECORD_DATA.received_date}{$RECORD_DATA.received_date|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-gr-detail-field mk-gr-detail-field--wide">
					<span class="mk-gr-detail-field__label">Storage location</span>
					<span class="mk-gr-detail-field__value">{if $RECORD_DATA.storage_location}{$RECORD_DATA.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-gr-detail-field mk-gr-detail-field--wide">
					<span class="mk-gr-detail-field__label">Note</span>
					<span class="mk-gr-detail-field__value mk-gr-detail-field__value--note">{if $RECORD_DATA.note}{$RECORD_DATA.note|escape:'html'|nl2br}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
			</div>
		</div>
	</section>

	<section class="mk-gr-detail-card mk-gr-detail-card--lines" aria-labelledby="mkGrLineItemsTitle">
		<header class="mk-gr-detail-card__head">
			<h2 class="mk-gr-detail-card__title" id="mkGrLineItemsTitle">Line Items</h2>
			<span class="mk-gr-detail-card__badge">{$ITEM_COUNT|default:0} {if $ITEM_COUNT|default:0 eq 1}item{else}items{/if}</span>
		</header>
		<div class="mk-gr-detail-card__body mk-gr-detail-card__body--flush">
			<div class="mk-gi-table-wrap">
				<table class="mk-gi-table mk-gr-detail-table">
					<thead>
						<tr>
							<th scope="col">Product</th>
							<th scope="col">Type</th>
							<th scope="col">Serial number</th>
							<th scope="col" class="mk-gi-table__num">Qty</th>
							<th scope="col" class="mk-gi-table__num">Price</th>
							<th scope="col" class="mk-gi-table__num">Line total</th>
							<th scope="col">Description</th>
							<th scope="col">Note</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$ITEMS item=IT}
							<tr>
								<td class="mk-gi-table__subject"><span class="mk-gr-detail-cell">{$IT.product_name_display|escape:'html'}</span></td>
								<td><span class="mk-gr-detail-cell"><span class="mk-gi-chip mk-gi-chip--type">{$IT.product_type|escape:'html'}</span></span></td>
								<td><span class="mk-gr-detail-cell">{if $IT.serial_number}{$IT.serial_number|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td class="mk-gi-table__num mk-gi-table__qty"><span class="mk-gr-detail-cell">{$IT.quantity_display|escape:'html'}</span></td>
								<td class="mk-gi-table__num"><span class="mk-gr-detail-cell">{$IT.unit_price_display|escape:'html'}</span></td>
								<td class="mk-gi-table__num mk-gi-table__qty"><span class="mk-gr-detail-cell">{$IT.line_total_display|escape:'html'}</span></td>
								<td><span class="mk-gr-detail-cell">{if $IT.description}{$IT.description|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-gr-detail-cell">{if $IT.line_note}{$IT.line_note|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
							</tr>
						{foreachelse}
							<tr><td colspan="8" class="mk-gi-table__empty">No line items.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="mk-gr-detail-card mk-gr-detail-card--attach" aria-labelledby="mkGrAttachmentsTitle">
		<header class="mk-gr-detail-card__head">
			<h2 class="mk-gr-detail-card__title" id="mkGrAttachmentsTitle">Attachments</h2>
			<span class="mk-gr-detail-card__badge">{$ATTACHMENTS|@count} file{if $ATTACHMENTS|@count ne 1}s{/if}</span>
		</header>
		{assign var=MK_GR_HAS_ATTACH value=($ATTACHMENTS|@count gt 0)}
		<div class="mk-gr-detail-card__body{if $MK_GR_HAS_ATTACH} mk-gr-detail-card__body--flush{/if}">
			{if $MK_GR_HAS_ATTACH}
				<div class="mk-gi-table-wrap">
					<table class="mk-gi-table mk-gr-detail-table">
						<thead>
							<tr>
								<th scope="col">Filename</th>
								<th scope="col">Type</th>
								<th scope="col" class="mk-gi-table__num">Size</th>
								<th scope="col">Created</th>
								<th scope="col" class="mk-gi-table__actions">Action</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$ATTACHMENTS item=A}
								<tr>
									<td class="mk-gi-table__subject"><span class="mk-gr-detail-cell">{$A.filename|escape:'html'}</span></td>
									<td><span class="mk-gr-detail-cell">{if $A.filetype}{$A.filetype|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
									<td class="mk-gi-table__num"><span class="mk-gr-detail-cell">{$A.filesize|escape:'html'}</span></td>
									<td><span class="mk-gr-detail-cell">{$A.createdtime|escape:'html'}</span></td>
									<td class="mk-gi-table__actions">
										<span class="mk-gr-detail-cell">
											<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost mk-gr-detail-dl-btn" target="_blank" rel="noopener"
											   href="index.php?module=GoodsReceipt&amp;action=DownloadAttachment&amp;attachmentid={$A.attachmentid}&amp;record={$RECORD_DATA.receiptid}&amp;app=INVENTORY">Download</a>
										</span>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			{else}
				<div class="mk-gr-detail-empty mk-gr-detail-empty--attach">
					<span class="mk-gr-detail-empty__icon" aria-hidden="true"></span>
					<p class="mk-gr-detail-empty__title">No attachments yet</p>
					<p class="mk-gr-detail-empty__hint">Upload files when editing this inbound receipt.</p>
				</div>
			{/if}
		</div>
	</section>
</div>
{else}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260325" />
<div class="container-fluid detailview-content inv-modern-page">
	<div class="inv-modern-card">
	<div class="inv-topnav">
		<a class="active" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
		<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
		<a href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<div class="row inv-page-header" style="margin-bottom:12px;">
				<div class="col-sm-8">
					<h3 style="margin-top:0;">{$RECORD_DATA.subject|escape:'html'}</h3>
					<div class="text-muted">Inbound receipt details and supporting documents</div>
					<div class="inv-header-badges" style="margin-top:8px;">
						{if $RECORD_DATA.code}<span class="inv-chip">{$RECORD_DATA.code|escape:'html'}</span>{/if}
					</div>
				</div>
				<div class="col-sm-4 text-right inv-header-actions">
					<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=Edit&record={$RECORD_DATA.receiptid}&app=INVENTORY">Edit</a>
					<a class="btn btn-danger" href="index.php?module=GoodsReceipt&action=Delete&record={$RECORD_DATA.receiptid}&app=INVENTORY" onclick="return confirm('Delete this inbound receipt?');">Delete</a>
					<a class="btn btn-default" href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Back to list</a>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><strong>Inbound Info</strong></div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-3"><strong>Code</strong><div>{if $RECORD_DATA.code}<span class="inv-chip">{$RECORD_DATA.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}</div>
						<div class="col-sm-3"><strong>Subject</strong><div>{$RECORD_DATA.subject|escape:'html'}</div>
						<div class="col-sm-3"><strong>Supplier/Source</strong><div>{$RECORD_DATA.source_name|escape:'html'}</div>
						<div class="col-sm-3"><strong>Received Date</strong><div>{$RECORD_DATA.received_date|escape:'html'}</div>
					</div>
					<div class="row" style="margin-top:10px;">
						<div class="col-sm-12"><strong>Storage Location</strong><div>{$RECORD_DATA.storage_location|escape:'html'}</div>
					</div>
					<div class="row" style="margin-top:10px;">
						<div class="col-sm-12"><strong>Note</strong><div class="well well-sm" style="margin-top:6px;">{$RECORD_DATA.note|escape:'html'|nl2br}</div></div>
					</div>
				</div>
			</div>
			<div class="panel panel-default" style="margin-top:12px;">
				<div class="panel-heading"><strong>Line Items</strong></div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover">
							<thead><tr><th>Product</th><th>Type</th><th>Serial Number</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Line Total</th><th>Description</th><th>Note</th></tr></thead>
							<tbody>
							{foreach from=$ITEMS item=IT}
								<tr>
									<td>{$IT.product_name_display|escape:'html'}</td>
									<td>{$IT.product_type|escape:'html'}</td>
									<td>{$IT.serial_number|escape:'html'}</td>
									<td class="text-right metric-strong">{$IT.quantity_display|escape:'html'}</td>
									<td class="text-right">{$IT.unit_price_display|escape:'html'}</td>
									<td class="text-right metric-strong">{$IT.line_total_display|escape:'html'}</td>
									<td>{$IT.description|escape:'html'}</td>
									<td>{$IT.line_note|escape:'html'}</td>
								</tr>
							{foreachelse}
								<tr><td colspan="8" class="text-center text-muted">No line items.</td></tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="panel panel-default" style="margin-top:12px;">
				<div class="panel-heading"><strong>Attachments</strong></div>
				<div class="panel-body">
					{if $ATTACHMENTS|@count gt 0}
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead><tr><th>Filename</th><th>Type</th><th>Size (bytes)</th><th>Created Time</th><th>Action</th></tr></thead>
								<tbody>
								{foreach from=$ATTACHMENTS item=A}
									<tr>
										<td>{$A.filename|escape:'html'}</td>
										<td>{$A.filetype|escape:'html'}</td>
										<td>{$A.filesize|escape:'html'}</td>
										<td>{$A.createdtime|escape:'html'}</td>
										<td>
											<a class="btn btn-xs btn-default" target="_blank"
											   href="index.php?module=GoodsReceipt&action=DownloadAttachment&attachmentid={$A.attachmentid}&record={$RECORD_DATA.receiptid}&app=INVENTORY">Open/Download</a>
										</td>
									</tr>
								{/foreach}
								</tbody>
							</table>
						</div>
					{else}
						<div class="text-muted">No attachments uploaded.</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
</div>
{/if}
{/strip}
