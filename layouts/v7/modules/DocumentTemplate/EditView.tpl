{strip}
	<form id="DocumentTemplateEditForm" method="post" action="index.php" class="form-horizontal">
		<input type="hidden" name="module" value="DocumentTemplate" />
		<input type="hidden" name="action" value="Save" />
		<input type="hidden" name="app" value="TOOLS" />
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h3>
					{if $MODE eq 'edit'}Edit Template{elseif $MODE eq 'copy'}Copy Template{else}New Template{/if}
				</h3>
				<p class="text-muted">Manage reusable business feature templates.</p>
				<hr/>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-4">
				<div class="panel panel-default">
					<div class="panel-heading"><strong>Metadata</strong></div>
					<div class="panel-body">
						<input type="hidden" name="record" value="{if $RECORD.templateid}{$RECORD.templateid|escape:'html'}{else}0{/if}" />
						<input type="hidden" name="copyFrom" value="{$COPY_FROM_ID}" />

						<div class="form-group">
							<label>Template Name</label>
							<input type="text" class="form-control" name="templatename" value="{$RECORD.templatename|escape:'html'}" required />
						</div>

						<div class="form-group">
							<label>Feature</label>
							<select name="feature" class="form-control" required>
								{foreach from=$FEATURES item=F}
									<option value="{$F}" {if $RECORD.feature eq $F}selected{/if}>{$F}</option>
								{/foreach}
							</select>
						</div>

						<div class="form-group">
							<label>Description</label>
							<textarea class="form-control" name="description" rows="3">{$RECORD.description|escape:'html'}</textarea>
						</div>

						<div class="checkbox" style="margin-top:10px;">
							<label>
								<input type="hidden" name="isdefault" value="0" />
								{* BA: default templates are system-seeded and protected. Users cannot set default via UI. *}
							</label>
						</div>
					</div>
				</div>
			</div>

			<div class="col-lg-8">
				<div class="panel panel-default">
					<div class="panel-heading"><strong>Content</strong></div>
					<div class="panel-body">
						<div class="form-group">
							<label class="control-label">Template Content</label>
							<textarea id="documenttemplate_content_editor" name="content" class="form-control" rows="16">{$RECORD.content}</textarea>
						</div>
						<div style="margin-top:15px;">
							<button type="submit" class="btn btn-success">Save Template</button>
							<a class="btn btn-default" href="index.php?module=DocumentTemplate&view=List&app=TOOLS">Cancel</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row" style="margin-top:15px;">
			<div class="col-lg-4">
				<div class="panel panel-default">
					<div class="panel-heading"><strong>Version / History</strong></div>
					<div class="panel-body">
						{if $MODE eq 'edit'}
							<div><strong>Current Version:</strong> {$RECORD.version}</div>
							{if $RECORD.updatedtime}
								<div style="margin-top:8px;"><strong>Updated Time:</strong> {$RECORD.updatedtime}</div>
							{/if}
						{else}
							<div><strong>New Template Version:</strong> 1</div>
							<div class="text-muted" style="margin-top:8px;">Saved copies start at version 1.</div>
						{/if}
						<div class="text-muted" style="margin-top:12px;">
							Version history (diffs) can be added later; MVP uses an incrementing version number.
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-8"></div>
		</div>
	</div>
	</form>

	{literal}
	<script type="text/javascript">
		(function() {
			var form = document.getElementById('DocumentTemplateEditForm');
			if (form) {
				form.addEventListener('submit', function() {
					// Ensure CSRF token is posted for custom Tools form submissions.
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
			if (typeof CKEDITOR !== 'undefined' && CKEDITOR.replace) {
				CKEDITOR.replace('documenttemplate_content_editor', {height: 420});
			}
		})();
	</script>
	{/literal}
{/strip}

