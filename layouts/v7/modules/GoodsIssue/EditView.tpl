{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="editViewPageDiv viewContent content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card outbound-section">
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
						<datalist id="products_list"></datalist>

						<div class="table-responsive">
							<table class="table table-bordered table-hover inv-modern-table line-items-table" id="GoodsIssueItemsTable">
								<thead>
									<tr>
										<th style="width:26%;">Product</th>
										<th style="width:10%;">Type</th>
										<th style="width:14%;">SERIAL</th>
										<th style="width:8%;" class="text-right">Qty</th>
										<th style="width:10%;" class="text-right">Unit price</th>
										<th style="width:8%;" class="text-right">Disc. %</th>
										<th style="width:12%;" class="text-right">Line total</th>
										<th>Line note</th>
										<th style="width:70px;"></th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$ITEMS item=IT name=itloop}
										<tr class="row-item {if empty($IT.is_stock_linked)}is-legacy{/if}" data-gi-product-key="{if isset($IT.product_key_hint)}{$IT.product_key_hint|escape:'html'}{/if}" data-gi-product-name="{$IT.product_name|escape:'html'}">
											<td>
												<input type="hidden" name="item_productid[]" value="{$IT.productid|escape:'html'}" />
												<input type="text" name="item_product_name[]" value="{$IT.product_name|escape:'html'}" class="form-control product-input" list="products_list" placeholder="Click for storage list, or type to filter by name…" autocomplete="off" />
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
											<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" value="{$IT.unit_price|escape:'html'}" class="form-control text-right gi-unit-price" /></td>
											<td><input type="number" step="0.0001" min="0" max="100" name="item_discount[]" value="{$IT.discount_percent|default:0|escape:'html'}" class="form-control text-right gi-discount" /></td>
											<td class="text-right gi-line-total-cell">
												<span class="gi-line-total">0</span>
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

					// Prefix search for product name (server: LIKE 'term%'); shared datalist updated via AJAX.
					var giProductSearchTimer = null;
					var giProductSearchDelayMs = 300;

					function goodsIssueBuildDatalistFromOptions(options) {
						var list = document.getElementById('products_list');
						if (!list) return;
						list.innerHTML = '';
						if (!options || !options.length) return;
						for (var i = 0; i < options.length; i++) {
							var o = options[i];
							var el = document.createElement('option');
							el.value = (o.name !== undefined && o.name !== null) ? String(o.name) : '';
							el.setAttribute('data-stockid', o.stockid !== undefined ? String(o.stockid) : '');
							el.setAttribute('data-product-key', o.product_key ? String(o.product_key) : '');
							el.setAttribute('data-productid', o.productid !== undefined ? String(o.productid) : '');
							el.setAttribute('data-identity', o.identity_type ? String(o.identity_type) : '');
							el.setAttribute('data-type', o.type ? String(o.type) : '');
							el.setAttribute('data-available', o.available_qty !== undefined ? String(o.available_qty) : '');
							el.setAttribute('data-location', o.stock_location ? String(o.stock_location) : '');
							el.setAttribute('data-unit-price', o.unit_price !== undefined ? String(o.unit_price) : '');
							list.appendChild(el);
						}
					}

					function goodsIssueRunProductSearch(term) {
						var xhr = new XMLHttpRequest();
						xhr.open('GET', 'index.php?module=GoodsIssue&action=SearchProducts&q=' + encodeURIComponent(term), true);
						xhr.onreadystatechange = function() {
							if (xhr.readyState !== 4 || xhr.status !== 200) return;
							try {
								var data = JSON.parse(xhr.responseText);
								var opts = (data && data.result && data.result.options) ? data.result.options : [];
								goodsIssueBuildDatalistFromOptions(opts);
							} catch (ex) {}
						};
						xhr.send(null);
					}

					document.addEventListener('focus', function(e) {
						if (!e.target || !e.target.classList || !e.target.classList.contains('product-input')) return;
						var term = (e.target.value || '').trim();
						if (term.length < 1) return;
						goodsIssueRunProductSearch(term);
					}, true);

					document.addEventListener('input', function(e) {
						if (!e.target || !e.target.classList || !e.target.classList.contains('product-input')) return;
						var term = (e.target.value || '').trim();
						if (giProductSearchTimer) clearTimeout(giProductSearchTimer);
						if (term.length < 1) {
							var listEl = document.getElementById('products_list');
							if (listEl) listEl.innerHTML = '';
							return;
						}
						giProductSearchTimer = setTimeout(function() {
							goodsIssueRunProductSearch(term);
						}, giProductSearchDelayMs);
					});

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

						if (found) {
							row.dataset.giProductKey = (found.getAttribute('data-product-key') || found.dataset.productKey || '').trim();
						} else {
							row.dataset.giProductKey = val ? ('N:' + val.trim().toLowerCase()) : '';
						}
						row.dataset.giProductName = val;
						loadSerials(row);

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
								'  <input type="text" name="item_product_name[]" value="" class="form-control product-input" list="products_list" placeholder="Click for storage list, or type to filter by name…" autocomplete="off" />' +
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
								'  <select name="serial_number[]" class="form-control serial-select gi-serial-select">' +
								'    <option value="">Select serial</option>' +
								'  </select>' +
								'</td>' +
								'<td>' +
								'  <input type="number" step="0.0001" min="0" name="item_quantity[]" value="1" class="form-control text-right qty-input" data-available="" />' +
								'  <div style="margin-top:4px;">' +
								'    <span class="available-badge text-muted small">Available: — (legacy)</span>' +
								'  </div>' +
								'  <small class="text-danger qty-warn" style="display:none;">Qty exceeds available</small>' +
								'</td>' +
								'<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" value="0" class="form-control text-right gi-unit-price" /></td>' +
								'<td><input type="number" step="0.0001" min="0" max="100" name="item_discount[]" value="0" class="form-control text-right gi-discount" /></td>' +
								'<td class="text-right gi-line-total-cell"><span class="gi-line-total">0</span></td>' +
								'<td><input type="text" name="item_line_note[]" value="" class="form-control" /></td>' +
								'<td class="text-nowrap"><button type="button" class="btn btn-xs btn-danger js-gi-remove">Remove</button></td>';
							tr.className = 'row-item is-legacy';
							tr.dataset.giProductKey = '';
							tr.dataset.giProductName = '';
							tbody.appendChild(tr);
							// Initialize line total for new row
							if (typeof window.GoodsIssueRecalcRow === 'function') {
								window.GoodsIssueRecalcRow(tr);
							}
						});
					}
					
					// Discount & line total calculation
					window.GoodsIssueRecalcRow = function(row) {
						if (!row) return;
						var qtyEl = row.querySelector('.qty-input');
						var priceEl = row.querySelector('.gi-unit-price');
						var discEl = row.querySelector('.gi-discount');
						var totalSpan = row.querySelector('.gi-line-total');
						if (!qtyEl || !priceEl || !discEl || !totalSpan) return;
						var qty = parseFloat(qtyEl.value || '0');
						var price = parseFloat(priceEl.value || '0');
						var disc = parseFloat(discEl.value || '0');
						if (isNaN(qty)) qty = 0;
						if (isNaN(price)) price = 0;
						if (isNaN(disc)) disc = 0;
						if (disc < 0) disc = 0;
						if (disc > 100) disc = 100;
						discEl.value = disc.toFixed(2).replace(/\.00$/, '');
						var lineTotal = qty * price * (1 - (disc / 100));
						if (lineTotal < 0) lineTotal = 0;
						totalSpan.textContent = lineTotal.toFixed(0);
					};

					document.addEventListener('input', function(e) {
						if (!e.target) return;
						if (e.target.classList.contains('qty-input') ||
							e.target.classList.contains('gi-unit-price') ||
							e.target.classList.contains('gi-discount')) {
							var row = e.target.closest('tr');
							if (row && typeof window.GoodsIssueRecalcRow === 'function') {
								window.GoodsIssueRecalcRow(row);
							}
						}
					});

					// Initialize totals on page load
					document.querySelectorAll('#GoodsIssueItemsTable tbody tr').forEach(function(row) {
						if (typeof window.GoodsIssueRecalcRow === 'function') {
							window.GoodsIssueRecalcRow(row);
						}
					});

					function goodsIssueApplySerialOptions(select, data, initial) {
						var list = [];
						if (data && Array.isArray(data.result)) {
							list = data.result;
						} else if (data && data.serials && Array.isArray(data.serials)) {
							data.serials.forEach(function(s) { list.push({ serial: s }); });
						}
						select.innerHTML = '<option value="">Select serial</option>';
						list.forEach(function(item) {
							var s = (item && typeof item === 'object' && item.serial !== undefined) ? String(item.serial) : String(item);
							if (!s) return;
							var opt = document.createElement('option');
							opt.value = s;
							opt.textContent = s;
							select.appendChild(opt);
						});
						if (initial) {
							select.value = initial;
							if (select.value !== initial) {
								var opt2 = document.createElement('option');
								opt2.value = initial;
								opt2.textContent = initial;
								select.appendChild(opt2);
								select.value = initial;
							}
							select.removeAttribute('data-initial-serial');
						}
					}

					function loadSerials(row) {
						var select = row.querySelector('.serial-select');
						if (!select) return;
						var pidEl = row.querySelector('[name="item_productid[]"]');
						var nameEl = row.querySelector('[name="item_product_name[]"]');
						var typeEl = row.querySelector('[name="item_product_type[]"]');
						var pid = pidEl ? parseInt(pidEl.value || '0', 10) : 0;
						if (isNaN(pid)) pid = 0;
						var productName = (nameEl && nameEl.value) ? nameEl.value.trim() : '';
						var productKey = (row.dataset.giProductKey || '').trim();
						var productType = (typeEl && typeEl.value) ? typeEl.value.trim() : '';
						if (pid <= 0 && !productName && !productKey) {
							select.innerHTML = '<option value="">Select serial</option>';
							return;
						}
						var initial = (select.getAttribute('data-initial-serial') || '').trim();
						var parts = ['module=GoodsIssue', 'action=GetSerials'];
						if (pid > 0) parts.push('productid=' + encodeURIComponent(String(pid)));
						if (productName) parts.push('product_name=' + encodeURIComponent(productName));
						if (productKey) parts.push('product_key=' + encodeURIComponent(productKey));
						if (productType) parts.push('product_type=' + encodeURIComponent(productType));
						var url = 'index.php?' + parts.join('&');
						if (typeof fetch === 'function') {
							fetch(url)
								.then(function(res) { return res.json(); })
								.then(function(data) { goodsIssueApplySerialOptions(select, data, initial); })
								.catch(function() { select.innerHTML = '<option value="">Select serial</option>'; });
						} else {
							var xhr = new XMLHttpRequest();
							xhr.open('GET', url, true);
							xhr.onreadystatechange = function() {
								if (xhr.readyState !== 4) return;
								if (xhr.status !== 200) {
									select.innerHTML = '<option value="">Select serial</option>';
									return;
								}
								try {
									goodsIssueApplySerialOptions(select, JSON.parse(xhr.responseText), initial);
								} catch (e) {
									select.innerHTML = '<option value="">Select serial</option>';
								}
							};
							xhr.send(null);
						}
					}

					document.addEventListener('change', function(e) {
						if (!e.target || e.target.name !== 'item_product_type[]') return;
						var row = e.target.closest('tr');
						if (!row || !row.closest('#GoodsIssueItemsTable')) return;
						loadSerials(row);
					});

					document.querySelectorAll('#GoodsIssueItemsTable tbody tr').forEach(function(row) {
						loadSerials(row);
					});
				})();
			</script>
			{/literal}
		</div>
	</div>
	</div>
</div>
{/strip}

