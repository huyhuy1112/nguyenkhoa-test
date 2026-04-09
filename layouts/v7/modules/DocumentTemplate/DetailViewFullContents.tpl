{strip}
	{if $DT_ERROR_MESSAGE}
		<div class="alert alert-danger" style="margin: 10px 0;">
			<strong>DocumentTemplate error:</strong> {$DT_ERROR_MESSAGE|escape:'html'}
		</div>
	{/if}
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h3 style="margin-bottom:8px;">
					{$RECORD_DATA.templatename|escape:'html'}
					{if $RECORD_DATA.isdefault eq 1}
						<span class="label label-success" style="margin-left:10px;">Default</span>
					{/if}
				</h3>
				<p class="text-muted" style="margin-bottom:0;">
					Feature: <strong>{$RECORD_DATA.feature|escape:'html'}</strong> &nbsp;|&nbsp;
					Version: <strong>{$RECORD_DATA.version}</strong>
				</p>

				{if $DELETE_BLOCKED}
					<div class="alert alert-warning" style="margin-top:15px;">
						Protected default templates cannot be deleted.
					</div>
				{/if}
				{if $READONLY_DEFAULT}
					<div class="alert alert-info" style="margin-top:15px;">
						This is a protected default template. Please use <strong>Copy Template</strong> to create an editable version.
					</div>
				{/if}

				<div style="margin-top:15px;">
					<a class="btn btn-primary" href="index.php?module=DocumentTemplate&view=Edit&copyFrom={$RECORD_DATA.templateid}&app=TOOLS">
						<i class="fa fa-copy"></i>&nbsp; Copy Template
					</a>
					{if $RECORD_DATA.isdefault neq 1}
						<a class="btn btn-default" href="index.php?module=DocumentTemplate&view=Edit&record={$RECORD_DATA.templateid}&app=TOOLS">
							<i class="fa fa-edit"></i>&nbsp; Edit
						</a>
						<form method="post" action="index.php" style="display:inline;" class="dt-delete-form">
							<input type="hidden" name="module" value="DocumentTemplate" />
							<input type="hidden" name="action" value="Delete" />
							<input type="hidden" name="record" value="{$RECORD_DATA.templateid}" />
							<input type="hidden" name="app" value="TOOLS" />
							<button type="submit" class="btn btn-danger" onclick="return confirm('Delete this template?');">
								<i class="fa fa-trash"></i>&nbsp; Delete
							</button>
						</form>
					{/if}
				</div>
				<hr/>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-4">
				<div class="panel panel-default">
					<div class="panel-heading"><strong>Metadata</strong></div>
					<div class="panel-body">
						<div><strong>Description:</strong></div>
						<div style="margin-top:8px;">{$RECORD_DATA.description|escape:'html'|nl2br}</div>

						<hr/>
						<div><strong>Created By:</strong> {$RECORD_DATA.createdby_name|escape:'html'}</div>
						<div style="margin-top:8px;"><strong>Updated By:</strong> {$RECORD_DATA.updatedby_name|escape:'html'}</div>
						<div style="margin-top:8px;"><strong>Updated Time:</strong> {$RECORD_DATA.updatedtime|escape:'html'}</div>
					</div>
				</div>
			</div>

			<div class="col-lg-8">
				<div class="panel panel-default">
					<div class="panel-heading"><strong>Content</strong></div>
					<div class="panel-body">
						<div class="text-muted" style="margin-bottom:10px;">
							Template content is stored as raw HTML/text. Edit the template to update content.
						</div>
						<div style="border:1px solid rgba(0,0,0,0.06); border-radius:8px; padding:12px; background:#fff; min-height:220px;">
							{$RECORD_DATA.content}
						</div>
					</div>
				</div>

				<div class="panel panel-default" style="margin-top:15px;">
					<div class="panel-heading"><strong>History</strong></div>
					<div class="panel-body">
						{if $HISTORY|@count gt 0}
							<table class="table table-bordered" style="background:#fff;">
								<thead>
									<tr>
										<th style="width:110px;">Version</th>
										<th style="width:120px;">Action</th>
										<th style="width:200px;">Time</th>
										<th>Snapshot name</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$HISTORY item=H}
										<tr>
											<td>v{$H.version}</td>
											<td>{$H.action_type|escape:'html'}</td>
											<td>{$H.editedtime|escape:'html'}</td>
											<td>{$H.snapshot_name|escape:'html'}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						{else}
							<div class="text-muted">No history yet.</div>
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}

