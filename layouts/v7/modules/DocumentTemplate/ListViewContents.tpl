{strip}
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default document-template-panel">
					<div class="panel-heading">
						<div class="document-template-title">
							<strong>{vtranslate('Document Templates', $MODULE)}</strong>
						</div>
						<div class="document-template-subtitle">
							Template management for business features (Invoice / Quote / Contract)
						</div>
					</div>

					<div class="panel-body">
						<form class="form-inline" method="get" action="index.php">
							<input type="hidden" name="module" value="DocumentTemplate" />
							<input type="hidden" name="view" value="List" />
							<input type="hidden" name="app" value="TOOLS" />

							<div class="form-group" style="min-width:320px;">
								<input type="text" class="form-control" name="search" value="{$FILTER_SEARCH|escape:'html'}" placeholder="Search template name..." />
							</div>

							<div class="form-group" style="margin-left:10px;">
								<select name="feature" class="form-control">
									<option value="">All Features</option>
									{foreach from=$FEATURES item=F}
										<option value="{$F}" {if $FILTER_FEATURE eq $F}selected{/if}>{$F}</option>
									{/foreach}
								</select>
							</div>

							<button type="submit" class="btn btn-primary" style="margin-left:10px;">Search</button>
							<a class="btn btn-default" style="margin-left:10px;" href="index.php?module=DocumentTemplate&view=List&app=TOOLS">Reset</a>
						</form>

						<div style="margin-top:15px;">
							<a class="btn btn-success" href="index.php?module=DocumentTemplate&view=Edit&app=TOOLS">
								<i class="fa fa-plus"></i>&nbsp; New Template
							</a>
						</div>

						<hr style="margin:15px 0;"/>

						{if $GROUPS|@count gt 0}
							{foreach from=$GROUPS key=FEAT item=LIST}
								<h4 style="margin-top:18px; margin-bottom:10px;">{$FEAT}</h4>
								<table class="table table-bordered table-striped" style="background:#fff;">
									<thead>
										<tr>
											<th style="width:280px;">Name</th>
											<th style="width:140px;">Version</th>
											<th>Description</th>
											<th style="width:170px;">Updated Time</th>
											<th style="width:180px;">Updated By</th>
											<th style="width:110px;">Default</th>
											<th style="width:190px;">Actions</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$LIST item=ROW}
											<tr>
												<td>
													<a href="index.php?module=DocumentTemplate&view=Detail&record={$ROW.templateid}&app=TOOLS">
														{$ROW.templatename|escape:'html'}
													</a>
												</td>
												<td>{$ROW.version}</td>
												<td>{$ROW.description|escape:'html'|truncate:120}</td>
												<td>{$ROW.updatedtime|escape:'html'}</td>
												<td>{$ROW.updatedby_name|escape:'html'}</td>
												<td>{if $ROW.isdefault eq 1}Yes{else}-{/if}</td>
												<td>
													<div class="btn-group">
														<a class="btn btn-default btn-sm" title="Copy" href="index.php?module=DocumentTemplate&view=Edit&copyFrom={$ROW.templateid}&app=TOOLS">
															<i class="fa fa-copy"></i>
														</a>
														<a class="btn btn-default btn-sm" title="Edit" href="index.php?module=DocumentTemplate&view=Edit&record={$ROW.templateid}&app=TOOLS">
															<i class="fa fa-edit"></i>
														</a>
														<a class="btn btn-danger btn-sm" title="Delete" href="index.php?module=DocumentTemplate&action=Delete&record={$ROW.templateid}&app=TOOLS"
															onclick="return confirm('Delete template \"{$ROW.templatename|escape:'html'}\"? This will be blocked for protected default templates.');">
															<i class="fa fa-trash"></i>
														</a>
													</div>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							{/foreach}
						{else}
							<div class="alert alert-info" style="margin-top:15px;">
								No templates found for selected filters.
							</div>
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}

