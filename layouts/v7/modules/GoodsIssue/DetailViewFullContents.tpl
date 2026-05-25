{* GoodsIssue Outbound detail — summary cards (Inventory modern UI) *}
{strip}
{assign var=MK_GO_DETAIL_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_GO_DETAIL_INV value=true}
{/if}
{if $MK_GO_DETAIL_INV}
<div class="mk-go-detail-page-content mk-gi-page">
	{include file="partials/InventoryDetailTopnav.tpl"|@vtemplate_path:'Vtiger'}

	{if !empty($SHOW_DELETE_BLOCKED)}
		<div class="mk-go-detail-alert mk-go-detail-alert--warn" role="alert">Delete blocked: stock row missing for at least one item. No changes were made.</div>
	{/if}
	{if !empty($SHOW_DELETE_ERROR)}
		<div class="mk-go-detail-alert mk-go-detail-alert--error" role="alert">Unable to delete this outbound issue.</div>
	{/if}

	<div class="mk-go-detail-stats" role="group" aria-label="Issue summary">
		<div class="mk-go-detail-stat">
			<span class="mk-go-detail-stat__label">Line items</span>
			<strong class="mk-go-detail-stat__value">{$ITEM_COUNT|default:0}</strong>
		</div>
		<div class="mk-go-detail-stat">
			<span class="mk-go-detail-stat__label">Total quantity</span>
			<strong class="mk-go-detail-stat__value">{$TOTAL_QTY_DISPLAY|default:'0.00'}</strong>
		</div>
		<div class="mk-go-detail-stat mk-go-detail-stat--accent">
			<span class="mk-go-detail-stat__label">Issue value</span>
			<strong class="mk-go-detail-stat__value">{$TOTAL_VALUE_DISPLAY|default:'0'}</strong>
		</div>
	</div>

	<section class="mk-go-detail-card mk-go-detail-card--info" aria-labelledby="mkGoOutboundInfoTitle">
		<header class="mk-go-detail-card__head">
			<h2 class="mk-go-detail-card__title" id="mkGoOutboundInfoTitle">Outbound Info</h2>
		</header>
		<div class="mk-go-detail-card__body">
			<div class="mk-go-detail-fields">
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Code</span>
					<span class="mk-go-detail-field__value">{if $RECORD_DATA.code}<span class="mk-gi-chip">{$RECORD_DATA.code|escape:'html'}</span>{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Subject</span>
					<span class="mk-go-detail-field__value">{$RECORD_DATA.subject|escape:'html'}</span>
				</div>
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Issued date</span>
					<span class="mk-go-detail-field__value">{if $RECORD_DATA.issued_date}{$RECORD_DATA.issued_date|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Destination / receiver</span>
					<span class="mk-go-detail-field__value">{if $RECORD_DATA.destination}{$RECORD_DATA.destination|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Storage location</span>
					<span class="mk-go-detail-field__value">{if $RECORD_DATA.storage_location}{$RECORD_DATA.storage_location|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-go-detail-field">
					<span class="mk-go-detail-field__label">Issuer</span>
					<span class="mk-go-detail-field__value">{if $RECORD_DATA.issued_by}{$RECORD_DATA.issued_by|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span>
				</div>
				<div class="mk-go-detail-field mk-go-detail-field--wide">
					<span class="mk-go-detail-field__label">Note</span>
					<span class="mk-go-detail-field__value mk-go-detail-field__value--note">{if $RECORD_DATA.note}{$RECORD_DATA.note|escape:'html'|nl2br}{else}<span class="mk-gi-muted">None.</span>{/if}</span>
				</div>
			</div>
		</div>
	</section>

	<section class="mk-go-detail-card mk-go-detail-card--lines" aria-labelledby="mkGoLineItemsTitle">
		<header class="mk-go-detail-card__head">
			<h2 class="mk-go-detail-card__title" id="mkGoLineItemsTitle">Line Items</h2>
			<span class="mk-go-detail-card__badge">{$ITEM_COUNT|default:0} {if $ITEM_COUNT|default:0 eq 1}item{else}items{/if}</span>
		</header>
		<div class="mk-go-detail-card__body mk-go-detail-card__body--flush">
			<div class="mk-gi-table-wrap">
				<table class="mk-gi-table mk-go-detail-table">
					<thead>
						<tr>
							<th scope="col">Product</th>
							<th scope="col">Type</th>
							<th scope="col">Serial</th>
							<th scope="col" class="mk-gi-table__num">Qty</th>
							<th scope="col" class="mk-gi-table__num">Unit price</th>
							<th scope="col" class="mk-gi-table__num">Disc. %</th>
							<th scope="col" class="mk-gi-table__num">Line total</th>
							<th scope="col">Description</th>
							<th scope="col">Line note</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$ITEMS item=IT}
							<tr>
								<td class="mk-gi-table__subject"><span class="mk-go-detail-cell">{$IT.product_name_display|escape:'html'}</span></td>
								<td><span class="mk-go-detail-cell"><span class="mk-gi-chip mk-gi-chip--type">{$IT.product_type|escape:'html'}</span></span></td>
								<td><span class="mk-go-detail-cell">{if $IT.serial_number ne ''}{$IT.serial_number|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td class="mk-gi-table__num mk-gi-table__qty"><span class="mk-go-detail-cell">{$IT.quantity_display|escape:'html'}</span></td>
								<td class="mk-gi-table__num"><span class="mk-go-detail-cell">{$IT.unit_price_display|escape:'html'}</span></td>
								<td class="mk-gi-table__num"><span class="mk-go-detail-cell">{$IT.discount_percent_display|escape:'html'}</span></td>
								<td class="mk-gi-table__num mk-gi-table__qty"><span class="mk-go-detail-cell">{$IT.line_total_display|escape:'html'}</span></td>
								<td><span class="mk-go-detail-cell">{if $IT.description}{$IT.description|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
								<td><span class="mk-go-detail-cell">{if $IT.line_note}{$IT.line_note|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
							</tr>
						{foreachelse}
							<tr><td colspan="9" class="mk-gi-table__empty">No line items.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="mk-go-detail-card mk-go-detail-card--attach" aria-labelledby="mkGoAttachmentsTitle">
		<header class="mk-go-detail-card__head">
			<h2 class="mk-go-detail-card__title" id="mkGoAttachmentsTitle">Attachments</h2>
			<span class="mk-go-detail-card__badge">{$ATTACHMENTS|@count} file{if $ATTACHMENTS|@count ne 1}s{/if}</span>
		</header>
		{assign var=MK_GO_HAS_ATTACH value=($ATTACHMENTS|@count gt 0)}
		<div class="mk-go-detail-card__body{if $MK_GO_HAS_ATTACH} mk-go-detail-card__body--flush{/if}">
			{if $MK_GO_HAS_ATTACH}
				<div class="mk-gi-table-wrap">
					<table class="mk-gi-table mk-go-detail-table">
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
									<td class="mk-gi-table__subject"><span class="mk-go-detail-cell">{$A.filename|escape:'html'}</span></td>
									<td><span class="mk-go-detail-cell">{if $A.filetype}{$A.filetype|escape:'html'}{else}<span class="mk-gi-muted">—</span>{/if}</span></td>
									<td class="mk-gi-table__num"><span class="mk-go-detail-cell">{$A.filesize|escape:'html'}</span></td>
									<td><span class="mk-go-detail-cell">{$A.createdtime|escape:'html'}</span></td>
									<td class="mk-gi-table__actions">
										<span class="mk-go-detail-cell">
											<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost mk-go-detail-dl-btn" target="_blank" rel="noopener"
											   href="index.php?module=GoodsIssue&amp;action=DownloadAttachment&amp;attachmentid={$A.attachmentid}&amp;record={$RECORD_DATA.issueid}&amp;app=INVENTORY">Download</a>
										</span>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			{else}
				<div class="mk-go-detail-empty mk-go-detail-empty--attach">
					<span class="mk-go-detail-empty__icon" aria-hidden="true"></span>
					<p class="mk-go-detail-empty__title">No attachments yet</p>
					<p class="mk-go-detail-empty__hint">Upload files when editing this outbound issue.</p>
				</div>
			{/if}
		</div>
	</section>
</div>
{else}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
<div class="main-container clearfix">
	<div class="detailViewPageDiv content-area full-width inv-modern-page goodsissue-detail-page" style="margin-left:0;">
		<div class="inv-modern-card">
			<div class="container-fluid">
				<p class="text-muted">Open with <code>app=INVENTORY</code> for the modern outbound detail view.</p>
				<a class="btn btn-default" href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$RECORD_DATA.issueid}&amp;app=INVENTORY">Open Inventory view</a>
			</div>
		</div>
	</div>
</div>
{/if}
{/strip}
