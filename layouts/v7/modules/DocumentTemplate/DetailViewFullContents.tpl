{* DocumentTemplate Detail — premium TOOLS layout *}
{strip}
<div class="mk-dt-detail-page-wrap">
	{include file="partials/DocumentTemplateDetailHeader.tpl"|vtemplate_path:$MODULE}

	{if $DT_ERROR_MESSAGE}
		<div class="mk-dt-alert mk-dt-alert--error">
			<strong>DocumentTemplate error:</strong> {$DT_ERROR_MESSAGE|escape:'html'}
		</div>
	{/if}
	{if $DELETE_BLOCKED}
		<div class="mk-dt-alert mk-dt-alert--warn">
			Protected default templates cannot be deleted.
		</div>
	{/if}
	{if $READONLY_DEFAULT}
		<div class="mk-dt-alert mk-dt-alert--info">
			This is a protected default template. Use <strong>Copy Template</strong> to create an editable version.
		</div>
	{/if}

	<div class="mk-dt-detail-layout">
		<aside class="mk-dt-detail-sidebar">
			<section class="mk-dt-detail-card">
				<header class="mk-dt-detail-card__head">
					<span class="mk-dt-detail-card__icon" aria-hidden="true"><i class="fa fa-info-circle"></i></span>
					<h2 class="mk-dt-detail-card__title">Metadata</h2>
				</header>
				<div class="mk-dt-detail-fields">
					<div class="mk-dt-detail-field mk-dt-detail-field--full">
						<span class="mk-dt-detail-field__label">Description</span>
						<span class="mk-dt-detail-field__value">{$RECORD_DATA.description|escape:'html'|default:'—'}</span>
					</div>
					<div class="mk-dt-detail-field">
						<span class="mk-dt-detail-field__label">Created By</span>
						<span class="mk-dt-detail-field__value">{$RECORD_DATA.createdby_name|escape:'html'}</span>
					</div>
					<div class="mk-dt-detail-field">
						<span class="mk-dt-detail-field__label">Updated By</span>
						<span class="mk-dt-detail-field__value">{$RECORD_DATA.updatedby_name|escape:'html'}</span>
					</div>
					<div class="mk-dt-detail-field mk-dt-detail-field--full">
						<span class="mk-dt-detail-field__label">Updated Time</span>
						<span class="mk-dt-detail-field__value">{$RECORD_DATA.updatedtime|escape:'html'}</span>
					</div>
				</div>
			</section>
		</aside>

		<div class="mk-dt-detail-main">
			<section class="mk-dt-detail-card">
				<header class="mk-dt-detail-card__head">
					<span class="mk-dt-detail-card__icon mk-dt-detail-card__icon--content" aria-hidden="true"><i class="fa fa-code"></i></span>
					<div>
						<h2 class="mk-dt-detail-card__title">Content</h2>
						<p class="mk-dt-detail-card__subtitle">Template content is stored as raw HTML/text. Edit the template to update content.</p>
					</div>
				</header>
				<div class="mk-dt-content-preview">
					<pre class="mk-dt-content-preview__code">{$RECORD_DATA.content|escape:'html'}</pre>
				</div>
			</section>

			<section class="mk-dt-detail-card">
				<header class="mk-dt-detail-card__head">
					<span class="mk-dt-detail-card__icon mk-dt-detail-card__icon--history" aria-hidden="true"><i class="fa fa-history"></i></span>
					<h2 class="mk-dt-detail-card__title">History</h2>
				</header>
				{if $HISTORY|@count gt 0}
					<div class="mk-dt-history-table-wrap">
						<table class="table mk-dt-history-table">
							<thead>
								<tr>
									<th>Version</th>
									<th>Action</th>
									<th>Time</th>
									<th>Snapshot name</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$HISTORY item=H}
									<tr>
										<td><span class="mk-dt-version-tag">v{$H.version}</span></td>
										<td>{$H.action_type|escape:'html'}</td>
										<td>{$H.editedtime|escape:'html'}</td>
										<td>{$H.snapshot_name|escape:'html'}</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				{else}
					<div class="mk-dt-empty">No history yet.</div>
				{/if}
			</section>
		</div>
	</div>
</div>
{/strip}
