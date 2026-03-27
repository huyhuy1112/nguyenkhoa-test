{strip}
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
										<td>
											<a href="index.php?module=GoodsIssue&view=Detail&record={$R.issueid}&app=INVENTORY">{$R.subject|escape:'html'}</a>
										</td>
										<td>
											{if $R.code}<span class="inv-chip">{$R.code|escape:'html'}</span>{else}<span class="text-muted">—</span>{/if}
										</td>
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
{/strip}

