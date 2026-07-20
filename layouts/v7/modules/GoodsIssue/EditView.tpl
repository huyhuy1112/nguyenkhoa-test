{strip}
{assign var=MK_GI_IS_INV value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	{assign var=MK_GI_IS_INV value=true}
{/if}
{if $MK_GI_IS_INV}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card">
		<div class="mk-wh-page-head mk-go-page-head">
			{include file="partials/OutboundEditHeader.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-inv-flow-bar">
			{assign var=MK_INV_NAV_CLASS value="mk-gi-topnav mk-gi-topnav--pills"}
			{include file="partials/InventoryDetailTopnav.tpl"|@vtemplate_path:'Vtiger'}
		</div>
		<div class="mk-go-edit-content">
{else}
<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
<link rel="stylesheet" href="layouts/v7/modules/Vtiger/resources/MkInventoryOdooEdit.css?v=20260720_wh_line1" />
<link rel="stylesheet" href="layouts/v7/modules/Vtiger/resources/MkWarehouseLineEdit.css?v=20260720_wh_line1" />
<script type="text/javascript" src="layouts/v7/lib/jquery/select2/select2.min.js"></script>
<script type="text/javascript" src="layouts/v7/modules/Vtiger/resources/MkWarehouseLineEdit.js?v=20260720_wh_line1"></script>
<div class="main-container clearfix">
	<div class="editViewPageDiv viewContent content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card outbound-section">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
				<a href="index.php?module=Warehouse&view=List&app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
				<a class="active" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
			</div>
			<style>
				#GoodsIssueItemsTable tbody tr.row-item {
					transition: all 0.2s ease;
				}
				#GoodsIssueItemsTable tbody tr.row-item:hover {
					background: rgba(58, 136, 255, 0.06);
					box-shadow: inset 0 0 0 1px rgba(58, 136, 255, 0.25);
				}
				#GoodsIssueItemsTable .product-input,
				#GoodsIssueItemsTable .qty-input,
				#GoodsIssueItemsTable input[name="item_unit_price[]"] {
					border-radius: 10px;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
				}
				#GoodsIssueItemsTable .stock-badge {
					display: inline-block;
					padding: 3px 9px;
					border-radius: 999px;
					font-size: 11px;
					font-weight: 600;
					margin-left: 8px;
					vertical-align: middle;
				}
				#GoodsIssueItemsTable .stock-badge.catalog {
					background: rgba(46, 184, 92, 0.16);
					color: #1d7f42;
					border: 1px solid rgba(46, 184, 92, 0.35);
				}
				#GoodsIssueItemsTable .stock-badge.legacy {
					background: rgba(255, 165, 0, 0.16);
					color: #b06a00;
					border: 1px solid rgba(255, 165, 0, 0.4);
				}
				#GoodsIssueItemsTable .stock-meta {
					display: flex;
					gap: 12px;
					flex-wrap: wrap;
					margin-top: 6px;
					padding: 6px 8px;
					border-radius: 8px;
					background: rgba(34, 120, 214, 0.07);
					color: #3a5f87;
				}
				#GoodsIssueItemsTable .stock-meta .available-text { color: #2b73c5; }
				#GoodsIssueItemsTable .stock-meta .location-text { color: #5d6d7e; }
				#GoodsIssueItemsTable .stock-meta .type-text { color: #4d6582; }
				#GoodsIssueItemsTable .legacy-warning {
					display: none;
					margin-top: 6px;
					padding: 5px 8px;
					border-left: 3px solid #f0ad4e;
					background: rgba(255, 165, 0, 0.10);
					border-radius: 6px;
					color: #a76608;
				}
				#GoodsIssueItemsTable tr.is-legacy .legacy-warning {
					display: block;
				}
				#GoodsIssueItemsTable tr.is-legacy .product-input {
					border-color: #f0ad4e;
					box-shadow: 0 0 0 2px rgba(240, 173, 78, 0.15);
				}
				.outbound-section {
					background: linear-gradient(135deg, #eef6ff, #ffffff);
					border: 1px solid #cfe3ff;
					border-radius: 12px;
					box-shadow: 0 4px 12px rgba(0, 123, 255, 0.08);
					padding: 16px 8px 20px;
				}
				.outbound-section .inv-suite-head h3 { color: #0b3d6d; font-weight: 700; }
				.outbound-section .panel.inv-panel {
					background: #fff;
					border-color: #cfe3ff;
					box-shadow: 0 2px 8px rgba(0, 86, 179, 0.06);
				}
				.outbound-section .panel-heading {
					background: #007bff;
					color: #fff;
					font-weight: 600;
					border-radius: 4px 4px 0 0;
					border: none;
				}
				.line-items-table tbody tr:hover {
					background: #f2f8ff;
				}
				.serial-select {
					border: 1px solid #007bff !important;
					background: #f8fbff;
					border-radius: 8px;
				}
			</style>
			<div class="inv-suite-head">
				<div>
					<h3 style="margin-top:0;">{if $MODE eq 'edit'}Edit Outbound{else}Create Outbound{/if}</h3>
					<p class="text-muted">Outbound deducts stock from Storage. If stock is missing or insufficient, save will be blocked safely.</p>
				</div>
				<div class="inv-suite-actions">
					<a class="btn btn-default" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Back to list</a>
				</div>
			</div>
{/if}

			{if !empty($SHOW_VALIDATION)}
				{if $MK_GI_IS_INV}
					<div class="mk-gi-alert mk-gi-alert--warn" role="alert">Please fill subject and at least one valid line item (qty &gt; 0 and product selected or name filled).</div>
				{else}
					<div class="alert alert-warning inv-alert">Please fill subject and at least one valid line item (qty &gt; 0 and product selected or name filled).</div>
				{/if}
			{/if}
			{if !empty($SHOW_OUT_OF_STOCK)}
				{if $MK_GI_IS_INV}
					<div class="mk-gi-alert mk-gi-alert--danger" role="alert">Insufficient stock for at least one line item. Nothing was saved.</div>
				{else}
					<div class="alert alert-danger inv-alert">Insufficient stock for at least one line item. Nothing was saved.</div>
				{/if}
			{/if}
			{if !empty($SHOW_ERROR_STOCK_MISSING)}
				{if $MK_GI_IS_INV}
					<div class="mk-gi-alert mk-gi-alert--danger" role="alert">Stock row not found for at least one item identity. Nothing was saved.</div>
				{else}
					<div class="alert alert-danger inv-alert">Stock row not found for at least one item identity. Nothing was saved.</div>
				{/if}
			{/if}

			<form id="GoodsIssueEditForm" method="post" action="index.php" class="{if $MK_GI_IS_INV}mk-go-edit-form{else}form-horizontal{/if}" enctype="multipart/form-data">
				<input type="hidden" name="module" value="GoodsIssue" />
				<input type="hidden" name="action" value="Save" />
				<input type="hidden" name="app" value="INVENTORY" />
				{if $ISSUE.issueid > 0}<input type="hidden" name="record" value="{$ISSUE.issueid}" />{/if}

				{if $MK_GI_IS_INV}
				<section class="mk-go-detail-card" aria-labelledby="mkGoEditInfoTitle">
					<header class="mk-go-detail-card__head">
						<h2 class="mk-go-detail-card__title" id="mkGoEditInfoTitle">Outbound Info</h2>
					</header>
					<div class="mk-go-detail-card__body">
						<div class="mk-go-edit-fields">
							<label class="mk-go-edit-field mk-go-edit-field--wide">
								<span class="mk-go-edit-field__label">Subject</span>
								<input type="text" name="subject" class="mk-go-edit-input" value="{$ISSUE.subject|escape:'html'}" required="required" />
							</label>
							{if $ISSUE.issueid > 0}
							<label class="mk-go-edit-field">
								<span class="mk-go-edit-field__label">Outbound code</span>
								<input type="text" class="mk-go-edit-input" value="{$ISSUE.code|escape:'html'}" readonly="readonly" />
							</label>
							{/if}
							<label class="mk-go-edit-field">
								<span class="mk-go-edit-field__label">Issued date</span>
								<input type="date" name="issued_date" class="mk-go-edit-input" value="{$ISSUE.issued_date|escape:'html'}" />
							</label>
							<label class="mk-go-edit-field">
								<span class="mk-go-edit-field__label">Destination / receiver</span>
								<input type="text" name="destination" class="mk-go-edit-input" value="{$ISSUE.destination|escape:'html'}" />
							</label>
							<label class="mk-go-edit-field mk-go-edit-field--wide">
								<span class="mk-go-edit-field__label">Storage location</span>
								<input type="text" name="storage_location" class="mk-go-edit-input" value="{$ISSUE.storage_location|escape:'html'}" placeholder="Optional reference (does not move stock rows)" />
							</label>
							<label class="mk-go-edit-field">
								<span class="mk-go-edit-field__label">Issuer</span>
								<input type="text" class="mk-go-edit-input" value="{$ISSUE.issued_by|escape:'html'}" readonly="readonly" />
								<input type="hidden" name="issued_by" value="{$ISSUE.issued_by|escape:'html'}" />
								<p class="mk-go-edit-hint">System-controlled (current logged-in user).</p>
							</label>
							<label class="mk-go-edit-field mk-go-edit-field--wide">
								<span class="mk-go-edit-field__label">Note</span>
								<textarea name="note" class="mk-go-edit-input" rows="3">{$ISSUE.note|escape:'html'}</textarea>
							</label>
						</div>
					</div>
				</section>
				{else}
				<div class="panel panel-default inv-panel">
					<div class="panel-heading"><strong>Outbound info</strong></div>
					<div class="panel-body">
						<div class="form-group">
							<label class="col-sm-3 control-label">Subject</label>
							<div class="col-sm-9">
								<input type="text" name="subject" class="form-control" value="{$ISSUE.subject|escape:'html'}" required="required" />
							</div>
						</div>
							{if $ISSUE.issueid > 0}
								<div class="form-group">
									<label class="col-sm-3 control-label">Outbound code</label>
									<div class="col-sm-9">
										<input type="text" class="form-control" value="{$ISSUE.code|escape:'html'}" readonly="readonly" />
									</div>
								</div>
							{/if}
						<div class="form-group">
							<label class="col-sm-3 control-label">Issued date</label>
							<div class="col-sm-3">
								<input type="date" name="issued_date" class="form-control" value="{$ISSUE.issued_date|escape:'html'}" />
							</div>
							<label class="col-sm-3 control-label">Destination / receiver</label>
							<div class="col-sm-3">
								<input type="text" name="destination" class="form-control" value="{$ISSUE.destination|escape:'html'}" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Storage location</label>
							<div class="col-sm-9">
								<input type="text" name="storage_location" class="form-control" value="{$ISSUE.storage_location|escape:'html'}" placeholder="Optional reference (does not move stock rows)" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Issuer / người xuất</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" value="{$ISSUE.issued_by|escape:'html'}" readonly="readonly" />
								<input type="hidden" name="issued_by" value="{$ISSUE.issued_by|escape:'html'}" />
								<p class="text-muted small" style="margin-top:6px;">System-controlled (current logged-in user).</p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Note</label>
							<div class="col-sm-9">
								<textarea name="note" class="form-control" rows="3">{$ISSUE.note|escape:'html'}</textarea>
							</div>
						</div>
					</div>
				</div>
				{/if}

				{if $MK_GI_IS_INV}
				<section class="mk-go-detail-card" aria-labelledby="mkGoEditAttachTitle">
					<header class="mk-go-detail-card__head">
						<h2 class="mk-go-detail-card__title" id="mkGoEditAttachTitle">Attachments</h2>
					</header>
					<div class="mk-go-detail-card__body">
						<label class="mk-go-edit-field mk-go-edit-field--wide">
							<span class="mk-go-edit-field__label">Upload files</span>
							<input type="file" name="attachments[]" class="mk-go-edit-input" multiple="multiple" />
							<p class="mk-go-edit-hint">Allowed: images, PDF, Office docs, CSV/TXT.</p>
						</label>
						{if !empty($ATTACHMENTS)}
							<div class="mk-gi-table-wrap" style="margin-top:12px;">
								<table class="mk-go-edit-attach-table">
									<thead>
										<tr>
											<th scope="col">File</th>
											<th scope="col" class="text-right">Actions</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$ATTACHMENTS item=ATT}
											<tr>
												<td>
													{$ATT.filename|escape:'html'}
													{if $ATT.filetype}<div class="mk-go-edit-hint">{$ATT.filetype|escape:'html'}</div>{/if}
												</td>
												<td class="text-right">
													<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost" href="index.php?module=GoodsIssue&amp;action=DownloadAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$ISSUE.issueid|escape:'html'}&amp;app=INVENTORY">Open</a>
													<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost" href="index.php?module=GoodsIssue&amp;action=DeleteAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$ISSUE.issueid|escape:'html'}&amp;app=INVENTORY" onclick="return confirm('Delete attachment?');" style="margin-left:8px;">Delete</a>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						{/if}
					</div>
				</section>
				{else}
				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Attachments</strong></div>
					<div class="panel-body">
						<div class="form-group">
							<label class="col-sm-3 control-label">Upload files</label>
							<div class="col-sm-9">
								<input type="file" name="attachments[]" class="form-control" multiple="multiple" />
								<p class="text-muted small" style="margin-top:6px;">Allowed: images, PDF, Office docs, CSV/TXT.</p>
							</div>
						</div>
						{if !empty($ATTACHMENTS)}
							<div class="table-responsive">
								<table class="table table-bordered inv-modern-table">
									<thead>
										<tr>
											<th>File</th>
											<th style="width:240px;" class="text-right">Actions</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$ATTACHMENTS item=ATT}
											<tr>
												<td>
													{$ATT.filename|escape:'html'}
													{if $ATT.filetype}<div class="text-muted small">{$ATT.filetype|escape:'html'}</div>{/if}
												</td>
												<td class="text-right">
													<a class="btn btn-xs btn-primary" href="index.php?module=GoodsIssue&amp;action=DownloadAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$ISSUE.issueid|escape:'html'}&amp;app=INVENTORY">Open</a>
													<a class="btn btn-xs btn-danger" href="index.php?module=GoodsIssue&amp;action=DeleteAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$ISSUE.issueid|escape:'html'}&amp;app=INVENTORY" onclick="return confirm('Delete attachment?');" style="margin-left:8px;">Delete</a>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						{/if}
					</div>
				</div>
				{/if}

				{if $MK_GI_IS_INV}
				<section class="mk-go-detail-card mk-go-detail-card--lines" aria-labelledby="mkGoEditLinesTitle">
					<header class="mk-go-detail-card__head">
						<h2 class="mk-go-detail-card__title" id="mkGoEditLinesTitle">Line Items</h2>
					</header>
					<div class="mk-go-detail-card__body mk-go-detail-card__body--flush">
						<div class="mk-wh-line-quick-search" role="search">
							<label class="mk-wh-line-quick-search__label" for="mkGiQuickProductSearch">Tìm hàng hoá</label>
							<select id="mkGiQuickProductSearch" class="mk-wh-quick-product-search" title="Tìm và thêm hàng hoá"></select>
						</div>
						<div class="mk-gi-table-wrap">
							<table class="mk-gi-table mk-go-edit-table" id="GoodsIssueItemsTable">
				{else}
				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Line items</strong></div>
					<div class="panel-body">
						<div class="mk-wh-line-quick-search" role="search">
							<label class="mk-wh-line-quick-search__label" for="mkGiQuickProductSearchLegacy">Tìm hàng hoá</label>
							<select id="mkGiQuickProductSearchLegacy" class="mk-wh-quick-product-search" title="Tìm và thêm hàng hoá"></select>
						</div>
						<div class="table-responsive">
							<table class="table table-bordered table-hover inv-modern-table line-items-table" id="GoodsIssueItemsTable">
				{/if}
								<thead>
									<tr>
										<th style="width:26%;">Product</th>
										<th style="width:10%;">Type</th>
										<th style="width:14%;">SERIAL</th>
										<th style="width:8%;" class="text-right">Qty</th>
										<th style="width:10%;" class="text-right">Unit price</th>
										<th style="width:8%;" class="text-right">Disc. %</th>
										<th style="width:12%;" class="text-right">Line total</th>
										<th style="width:16%;">Description</th>
										<th>Line note</th>
										<th style="width:70px;"></th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$ITEMS item=IT name=itloop}
										<tr class="row-item {if empty($IT.is_stock_linked)}is-legacy{/if}" data-gi-product-key="{if isset($IT.product_key_hint)}{$IT.product_key_hint|escape:'html'}{/if}" data-gi-product-name="{$IT.product_name|escape:'html'}">
											<td>
												<input type="hidden" name="item_productid[]" value="{$IT.productid|escape:'html'}" />
												<input type="hidden" name="item_product_key[]" value="{if isset($IT.product_key_hint)}{$IT.product_key_hint|escape:'html'}{/if}" class="gi-product-key-input" />
												<input type="text" name="item_product_name[]" value="{$IT.product_name|escape:'html'}" class="form-control product-input mk-wh-line-product-name" readonly="readonly" autocomplete="off" />
												{if !empty($IT.is_stock_linked)}
													<span class="stock-badge catalog">✓ Catalog</span>
												{else}
													<span class="stock-badge legacy">⚠ Legacy</span>
												{/if}
												<p class="text-muted small" style="margin:6px 0 0;">Identity uses <code>P:&lt;productid&gt;</code> when selected, else legacy <code>N:&lt;name&gt;</code>.</p>
												<div class="stock-meta small text-muted gi-stock-meta">
													<span class="available-text">Available: {if isset($IT.available_qty) && $IT.available_qty !== null && $IT.available_qty ne ''}{$IT.available_qty|escape:'html'}{else}—{/if}</span>
													<span class="location-text">Location: {if $IT.stock_location}{$IT.stock_location|escape:'html'}{else}—{/if}</span>
													<span class="type-text">Type: {$IT.product_type|escape:'html'}</span>
												</div>
												<div class="legacy-warning text-warning small">⚠ This item is not linked to catalog (legacy stock)</div>
											</td>
											<td>
												<select name="item_product_type[]" class="form-control">
													<option value="Hardware" {if $IT.product_type eq 'Hardware'}selected="selected"{/if}>Hardware</option>
													<option value="Software" {if $IT.product_type eq 'Software'}selected="selected"{/if}>Software</option>
													<option value="Service" {if $IT.product_type eq 'Service'}selected="selected"{/if}>Service</option>
													<option value="Other" {if $IT.product_type eq 'Other'}selected="selected"{/if}>Other</option>
												</select>
											</td>
											<td>
												<select name="serial_number[]" class="form-control serial-select gi-serial-select" data-initial-serial="{$IT.serial_number|escape:'html'}">
													<option value="">Select serial</option>
												</select>
											</td>
											<td>
												<input type="number" step="1" min="0" name="item_quantity[]" value="{$IT.quantity|escape:'html'}" class="form-control text-right qty-input" data-available="{$IT.available_qty|escape:'html'}" />
												<div style="margin-top:4px;">
													<span class="available-badge text-muted small">
														{if isset($IT.available_qty) && $IT.available_qty !== null && $IT.available_qty ne ''}
															Available: {$IT.available_qty|escape:'html'}{if empty($IT.is_stock_linked)} (legacy){/if}
														{else}
															Available: —{if empty($IT.is_stock_linked)} (legacy){/if}
														{/if}
													</span>
												</div>
												<small class="text-danger qty-warn" style="display:none;">Qty exceeds available</small>
											</td>
											<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" value="{$IT.unit_price|escape:'html'}" class="form-control text-right gi-unit-price" /></td>
											<td><input type="number" step="0.0001" min="0" max="100" name="item_discount[]" value="{$IT.discount_percent|default:0|escape:'html'}" class="form-control text-right gi-discount" /></td>
											<td class="text-right gi-line-total-cell">
												<span class="gi-line-total">0</span>
											</td>
											<td>
												<textarea name="description[]" class="form-control gi-line-description" rows="2" placeholder="Inbound hint fills on product pick">{$IT.description|escape:'html'}</textarea>
											</td>
											<td><input type="text" name="item_line_note[]" value="{$IT.line_note|escape:'html'}" class="form-control" /></td>
											<td class="text-nowrap">
												<button type="button" class="btn btn-xs btn-danger js-gi-remove">Remove</button>
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
				{if $MK_GI_IS_INV}
					</div>
				</section>
				<div class="mk-go-edit-actions">
					<button type="submit" class="mk-gi-btn mk-gi-btn--primary">
						<span class="mk-gi-btn__txt">Save</span>
					</button>
					<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">
						<span class="mk-gi-btn__txt">Cancel</span>
					</a>
				</div>
				{else}
					</div>
				</div>

				<div class="form-group inv-form-actions">
					<div class="col-sm-12">
						<button type="submit" class="btn btn-success">Save</button>
						<a class="btn btn-default" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Cancel</a>
					</div>
				</div>
				{/if}
			</form>

{if $MK_GI_IS_INV}
		</div>
	</div>
</div>
{else}
		</div>
	</div>
	</div>
</div>
{/if}
{/strip}

