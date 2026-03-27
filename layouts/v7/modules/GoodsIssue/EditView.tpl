{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="editViewPageDiv viewContent content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
				<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a class="active" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
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

			{if !empty($SHOW_VALIDATION)}
				<div class="alert alert-warning inv-alert">Please fill subject and at least one valid line item (qty &gt; 0 and product selected or name filled).</div>
			{/if}
			{if !empty($SHOW_OUT_OF_STOCK)}
				<div class="alert alert-danger inv-alert">Insufficient stock for at least one line item. Nothing was saved.</div>
			{/if}
			{if !empty($SHOW_ERROR_STOCK_MISSING)}
				<div class="alert alert-danger inv-alert">Stock row not found for at least one item identity. Nothing was saved.</div>
			{/if}

			<form id="GoodsIssueEditForm" method="post" action="index.php" class="form-horizontal" enctype="multipart/form-data">
				<input type="hidden" name="module" value="GoodsIssue" />
				<input type="hidden" name="action" value="Save" />
				<input type="hidden" name="app" value="INVENTORY" />
				{if $ISSUE.issueid > 0}<input type="hidden" name="record" value="{$ISSUE.issueid}" />{/if}

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
								<input type="text" name="issued_by" class="form-control" value="{$ISSUE.issued_by|escape:'html'}" />
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

				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Line items</strong></div>
					<div class="panel-body">
						<datalist id="products_list">
							{foreach from=$PRODUCT_OPTIONS item=PO}
								<option value="{$PO.name|escape:'html'}"
									data-stockid="{$PO.stockid|escape:'html'}"
									data-product-key="{$PO.product_key|escape:'html'}"
									data-productid="{$PO.productid|escape:'html'}"
									data-identity="{$PO.identity_type|escape:'html'}"
									data-type="{$PO.type|escape:'html'}"
									data-available="{$PO.available_qty|escape:'html'}"
									data-location="{$PO.stock_location|escape:'html'}"
									data-unit-price="{$PO.unit_price|escape:'html'}"></option>
							{/foreach}
						</datalist>

						<div class="table-responsive">
							<table class="table table-bordered table-hover inv-modern-table" id="GoodsIssueItemsTable">
								<thead>
									<tr>
										<th style="width:32%;">Product</th>
										<th style="width:12%;">Type</th>
										<th style="width:10%;" class="text-right">Qty</th>
										<th style="width:12%;" class="text-right">Unit price</th>
										<th>Line note</th>
										<th style="width:70px;"></th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$ITEMS item=IT name=itloop}
										<tr class="row-item {if empty($IT.is_stock_linked)}is-legacy{/if}">
											<td>
												<input type="hidden" name="item_productid[]" value="{$IT.productid|escape:'html'}" />
												<input type="text" name="item_product_name[]" value="{$IT.product_name|escape:'html'}" class="form-control product-input" list="products_list" placeholder="Start typing product..." />
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
												<input type="number" step="0.0001" min="0" name="item_quantity[]" value="{$IT.quantity|escape:'html'}" class="form-control text-right qty-input" data-available="{$IT.available_qty|escape:'html'}" />
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
											<td><input type="text" name="item_unit_price[]" value="{$IT.unit_price|escape:'html'}" class="form-control text-right" /></td>
											<td><input type="text" name="item_line_note[]" value="{$IT.line_note|escape:'html'}" class="form-control" /></td>
											<td class="text-nowrap">
												<button type="button" class="btn btn-xs btn-danger js-gi-remove">Remove</button>
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>

						<button type="button" class="btn btn-default" id="GoodsIssueAddRow">Add row</button>
					</div>
				</div>

				<div class="form-group inv-form-actions">
					<div class="col-sm-12">
						<button type="submit" class="btn btn-success">Save</button>
						<a class="btn btn-default" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Cancel</a>
					</div>
				</div>
			</form>

			{literal}
			<script type="text/javascript">
				(function() {
					var form = document.getElementById('GoodsIssueEditForm');
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

					document.addEventListener('change', function(e) {
						if (!e.target || !e.target.classList || !e.target.classList.contains('product-input')) return;

						const val = (e.target.value || '').trim();
						const normalizeName = function(v) { return (v || '').trim().toLowerCase(); };
						const key = normalizeName(val);
						const list = document.getElementById('products_list');
						if (!list) return;
						const options = list.options;

						let found = null;
						for (let i = 0; i < options.length; i++) {
							if (normalizeName(options[i].value) === key) {
								found = options[i];
								break;
							}
						}

						const row = e.target.closest('tr');
						if (!row) return;

						const pidEl = row.querySelector('[name="item_productid[]"]');
						const typeEl = row.querySelector('[name="item_product_type[]"]');
						const priceEl = row.querySelector('[name="item_unit_price[]"]');
						const qtyEl = row.querySelector('.qty-input');
						const badge = row.querySelector('.available-badge');
						const meta = row.querySelector('.gi-stock-meta');
						const rowBadge = row.querySelector('.stock-badge');
						const warn = row.querySelector('.qty-warn');

						if (!pidEl || !typeEl || !priceEl || !qtyEl) return;

						if (found) {
							var identityType = (found.dataset.identity || '').trim().toLowerCase();
							var rawPid = (found.dataset.productid || '').trim();
							var isCatalog = identityType === 'catalog' && rawPid !== '' && rawPid !== '0';

							pidEl.value = isCatalog ? rawPid : '';
							typeEl.value = found.dataset.type || typeEl.value;
							priceEl.value = found.dataset.unitPrice || priceEl.value;

							let available = found.dataset.available || '0';
							qtyEl.dataset.available = available;
							if (badge) {
								badge.innerText = isCatalog ? ("Available: " + available) : ("Available: " + available + " (legacy)");
							}

							// Optional: auto-fill storage_location when user hasn't set it yet.
							var headerStorageInput = form.querySelector('input[name="storage_location"]');
							var loc = (found.dataset.location || '').trim();
							if (headerStorageInput && loc && (!headerStorageInput.value || headerStorageInput.value.trim() === '')) {
								headerStorageInput.value = loc;
							}

							// Update helper context under the product input.
							if (meta) {
								let t = (found.dataset.type || '').trim();
								let l = loc || '—';
								meta.innerHTML =
									'<span class="available-text">Available: ' + available + '</span>' +
									'<span class="location-text">Location: ' + l + '</span>' +
									'<span class="type-text">Type: ' + t + '</span>';
							}
							if (rowBadge) {
								rowBadge.className = 'stock-badge ' + (isCatalog ? 'catalog' : 'legacy');
								rowBadge.innerText = isCatalog ? '✓ Catalog' : '⚠ Legacy';
							}
							row.classList.toggle('is-legacy', !isCatalog);
						} else {
							pidEl.value = '';
							qtyEl.dataset.available = '';
							if (badge) badge.innerText = "Available: — (legacy)";
							if (meta) {
								meta.innerHTML =
									'<span class="available-text">Available: —</span>' +
									'<span class="location-text">Location: —</span>' +
									'<span class="type-text">Type: ' + (typeEl.value || 'Other') + '</span>';
							}
							if (rowBadge) {
								rowBadge.className = 'stock-badge legacy';
								rowBadge.innerText = '⚠ Legacy';
							}
							row.classList.add('is-legacy');
						}

						// Visual qty warning
						const av = parseFloat(qtyEl.dataset.available || '');
						const qty = parseFloat(qtyEl.value || '0');
						if (!isNaN(av) && !isNaN(qty) && qty > av) {
							qtyEl.style.borderColor = '#ff4d4d';
							if (warn) warn.style.display = 'inline';
						} else {
							qtyEl.style.borderColor = '';
							if (warn) warn.style.display = 'none';
						}
					});

					// Some browsers trigger only focusout/blur for datalist selection; handle it too.
					document.addEventListener('focusout', function(e) {
						if (!e.target || !e.target.classList || !e.target.classList.contains('product-input')) return;
						// Force the delegated 'change' handler to run (datalist selection can be unreliable across browsers).
						var ev = new Event('change', { bubbles: true });
						e.target.dispatchEvent(ev);
					});

					document.addEventListener('input', function(e) {
						if (!e.target || !e.target.classList || !e.target.classList.contains('qty-input')) return;
						const qtyEl = e.target;
						const row = qtyEl.closest('tr');
						if (!row) return;
						const warn = row.querySelector('.qty-warn');
						const av = parseFloat(qtyEl.dataset.available || '');
						const qty = parseFloat(qtyEl.value || '0');
						if (!isNaN(av) && !isNaN(qty) && qty > av) {
							qtyEl.style.borderColor = '#ff4d4d';
							if (warn) warn.style.display = 'inline';
						} else {
							qtyEl.style.borderColor = '';
							if (warn) warn.style.display = 'none';
						}
					});

					// Initial visual warnings for prefilled rows.
					var initialQtyInputs = document.querySelectorAll('#GoodsIssueItemsTable .qty-input');
					initialQtyInputs.forEach(function(qtyEl) {
						var row = qtyEl.closest('tr');
						if (!row) return;
						var warn = row.querySelector('.qty-warn');
						var av = parseFloat(qtyEl.dataset.available || '');
						var qty = parseFloat(qtyEl.value || '0');
						if (!isNaN(av) && !isNaN(qty) && qty > av) {
							qtyEl.style.borderColor = '#ff4d4d';
							if (warn) warn.style.display = 'inline';
						} else {
							qtyEl.style.borderColor = '';
							if (warn) warn.style.display = 'none';
						}
					});

					document.addEventListener('click', function(e) {
						if (e.target && e.target.classList && e.target.classList.contains('js-gi-remove')) {
							var tr = e.target.closest('tr');
							if (tr) {
								tr.parentNode.removeChild(tr);
							}
						}
					});

					var addBtn = document.getElementById('GoodsIssueAddRow');
					if (addBtn) {
						addBtn.addEventListener('click', function() {
							var tbody = document.querySelector('#GoodsIssueItemsTable tbody');
							if (!tbody) return;
							var tr = document.createElement('tr');
							tr.innerHTML = '' +
								'<td>' +
								'  <input type="hidden" name="item_productid[]" value="" />' +
								'  <input type="text" name="item_product_name[]" value="" class="form-control product-input" list="products_list" placeholder="Start typing product..." />' +
								'  <span class="stock-badge legacy">⚠ Legacy</span>' +
								'  <div class="stock-meta small text-muted gi-stock-meta">' +
								'    <span class="available-text">Available: —</span>' +
								'    <span class="location-text">Location: —</span>' +
								'    <span class="type-text">Type: Other</span>' +
								'  </div>' +
								'  <div class="legacy-warning text-warning small">⚠ This item is not linked to catalog (legacy stock)</div>' +
								'</td>' +
								'<td>' +
								'  <select name="item_product_type[]" class="form-control">' +
								'    <option value="Hardware">Hardware</option>' +
								'    <option value="Software">Software</option>' +
								'    <option value="Service">Service</option>' +
								'    <option value="Other" selected="selected">Other</option>' +
								'  </select>' +
								'</td>' +
								'<td>' +
								'  <input type="number" step="0.0001" min="0" name="item_quantity[]" value="1" class="form-control text-right qty-input" data-available="" />' +
								'  <div style="margin-top:4px;">' +
								'    <span class="available-badge text-muted small">Available: — (legacy)</span>' +
								'  </div>' +
								'  <small class="text-danger qty-warn" style="display:none;">Qty exceeds available</small>' +
								'</td>' +
								'<td><input type="text" name="item_unit_price[]" value="0" class="form-control text-right" /></td>' +
								'<td><input type="text" name="item_line_note[]" value="" class="form-control" /></td>' +
								'<td class="text-nowrap"><button type="button" class="btn btn-xs btn-danger js-gi-remove">Remove</button></td>';
							tr.className = 'row-item is-legacy';
							tbody.appendChild(tr);
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

