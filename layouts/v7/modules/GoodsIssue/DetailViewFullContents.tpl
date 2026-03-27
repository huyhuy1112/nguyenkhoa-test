{strip}
<div class="main-container clearfix">
	<link rel="stylesheet" href="layouts/v7/modules/Inventory/resources/FlowModern.css?v=20260326" />
	<div class="detailViewPageDiv content-area full-width inv-modern-page" style="margin-left:0;">
		<div class="inv-modern-card">
		<div class="container-fluid">
			<div class="inv-topnav">
				<a href="index.php?module=GoodsReceipt&view=List&app=INVENTORY">Inbound</a>
				<a href="index.php?module=Warehouse&view=List&app=INVENTORY">Storage</a>
				<a class="active" href="index.php?module=GoodsIssue&view=List&app=INVENTORY">Outbound</a>
			</div>
			{if !empty($SHOW_DELETE_BLOCKED)}
				<div class="alert alert-warning inv-alert">Delete blocked: stock row missing for at least one item identity. No changes were made.</div>
			{/if}
			{if !empty($SHOW_DELETE_ERROR)}
				<div class="alert alert-danger inv-alert">Unable to delete outbound issue.</div>
			{/if}

			<div class="row inv-page-header" style="margin-bottom:16px;">
				<div class="col-sm-8">
					<h3 style="margin-top:0;">{$RECORD_DATA.subject|escape:'html'}</h3>
					<p class="text-muted" style="margin-bottom:8px;">Outbound (stock deduction)</p>
					<div class="inv-header-badges">
						<span class="inv-badge inv-badge-strong inv-badge-other">Outbound</span>
						{if $RECORD_DATA.code}<span class="inv-chip">{$RECORD_DATA.code|escape:'html'}</span>{/if}
						{if $RECORD_DATA.destination}<span class="inv-badge inv-badge-muted">{$RECORD_DATA.destination|escape:'html'}</span>{/if}
						{if $RECORD_DATA.issued_by}<span class="inv-badge inv-badge-muted">{$RECORD_DATA.issued_by|escape:'html'}</span>{/if}
						{if $RECORD_DATA.issued_date}<span class="inv-badge inv-badge-muted">{$RECORD_DATA.issued_date|escape:'html'}</span>{/if}
					</div>
				</div>
				<div class="col-sm-4 text-right inv-header-actions">
					<a class="btn btn-primary" href="index.php?module=GoodsIssue&amp;view=Edit&amp;record={$RECORD_DATA.issueid|escape:'html'}&amp;app=INVENTORY">Edit</a>
					<a class="btn btn-danger" href="index.php?module=GoodsIssue&amp;action=Delete&amp;record={$RECORD_DATA.issueid|escape:'html'}&amp;app=INVENTORY" onclick="return confirm('Delete this outbound issue and restore stock?');">Delete</a>
					<a class="btn btn-default" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Back to list</a>
				</div>
			</div>

			<div class="panel panel-default inv-panel">
				<div class="panel-heading"><strong>Outbound info</strong></div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-4"><strong>Issued date</strong><div>{if $RECORD_DATA.issued_date}{$RECORD_DATA.issued_date|escape:'html'}{else}<span class="text-muted">—</span>{/if}</div></div>
						<div class="col-sm-4"><strong>Destination / receiver</strong><div>{if $RECORD_DATA.destination}{$RECORD_DATA.destination|escape:'html'}{else}<span class="text-muted">—</span>{/if}</div></div>
						<div class="col-sm-4"><strong>Storage location</strong><div>{if $RECORD_DATA.storage_location}{$RECORD_DATA.storage_location|escape:'html'}{else}<span class="text-muted">—</span>{/if}</div></div>
					</div>
					<div class="row" style="margin-top:12px;">
						<div class="col-sm-12"><strong>Issuer / người xuất</strong><div class="inv-field-block inv-well" style="margin-top:6px;">{if $RECORD_DATA.issued_by}{$RECORD_DATA.issued_by|escape:'html'}{else}<span class="text-muted">—</span>{/if}</div></div>
					</div>
					<div class="row" style="margin-top:12px;">
						<div class="col-sm-12">
							<strong>Note</strong>
							<div class="inv-field-block inv-well">{if $RECORD_DATA.note}{$RECORD_DATA.note|escape:'html'|nl2br}{else}<span class="text-muted">None.</span>{/if}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="panel panel-default inv-panel" style="margin-top:12px;">
				<div class="panel-heading"><strong>Line items</strong></div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover inv-modern-table">
							<thead>
								<tr>
									<th>Product</th>
									<th>Type</th>
									<th class="text-right">Qty</th>
									<th class="text-right">Unit price</th>
									<th>Line note</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$ITEMS item=IT}
									<tr>
										<td>
											{$IT.product_name|escape:'html'}
											{if $IT.productid > 0}<span class="inv-badge inv-badge-catalog">P:{$IT.productid|escape:'html'}</span>{/if}
										</td>
										<td><span class="inv-chip">{$IT.product_type|escape:'html'}</span></td>
										<td class="text-right metric-strong">{$IT.quantity_display|escape:'html'}</td>
										<td class="text-right">{$IT.unit_price_display|escape:'html'}</td>
										<td>{if $IT.line_note}{$IT.line_note|escape:'html'}{else}<span class="text-muted">—</span>{/if}</td>
									</tr>
								{foreachelse}
									<tr><td colspan="5" class="text-muted text-center">No line items.</td></tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

			{if !empty($ATTACHMENTS)}
				<div class="panel panel-default inv-panel" style="margin-top:12px;">
					<div class="panel-heading"><strong>Attachments</strong></div>
					<div class="panel-body">
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
												<a class="btn btn-xs btn-primary" href="index.php?module=GoodsIssue&amp;action=DownloadAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$RECORD_DATA.issueid|escape:'html'}&amp;app=INVENTORY">Open</a>
												<a class="btn btn-xs btn-danger" href="index.php?module=GoodsIssue&amp;action=DeleteAttachment&amp;attachmentid={$ATT.attachmentid|escape:'html'}&amp;record={$RECORD_DATA.issueid|escape:'html'}&amp;app=INVENTORY" onclick="return confirm('Delete attachment?');" style="margin-left:8px;">Delete</a>
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				</div>
			{/if}
	</div>
	</div>
</div>
{/strip}

