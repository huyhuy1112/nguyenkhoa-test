{* DocumentTemplate ListViewContents *}
{strip}
{assign var=MK_DT_IS_TOOLS value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'TOOLS') || (isset($smarty.get.app) && $smarty.get.app eq 'TOOLS') || (isset($smarty.request.app) && $smarty.request.app eq 'TOOLS')}
	{assign var=MK_DT_IS_TOOLS value=true}
{/if}
{if $MK_DT_IS_TOOLS}
	<div class="mk-dt-page">
		{include file="partials/DocumentTemplateListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-dt-card">
{else}
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default document-template-panel">
					<div class="panel-heading">
						<div class="document-template-title"><strong>Document Templates</strong></div>
						<div class="document-template-subtitle">Template management for business features (Invoice / Quote / Contract)</div>
					</div>
					<div class="panel-body">
{/if}
			{if $DT_ERROR_MESSAGE}
				<div class="alert alert-danger mk-dt-alert">
					<strong>DocumentTemplate error:</strong> {$DT_ERROR_MESSAGE|escape:'html'}
				</div>
			{/if}

			<div class="mk-dt-toolbar">
				<form class="mk-dt-filter-form" method="get" action="index.php">
					<input type="hidden" name="module" value="DocumentTemplate" />
					<input type="hidden" name="view" value="List" />
					<input type="hidden" name="app" value="TOOLS" />
					{if $FILTER_FEATURE}
						<input type="hidden" name="feature" value="{$FILTER_FEATURE|escape:'html'}" />
					{/if}
					<div class="mk-dt-filter-grid">
						<div class="mk-dt-filter-field mk-dt-filter-field--search">
							<label for="dtSearchInput" class="mk-dt-filter-label">Search</label>
							<input id="dtSearchInput" type="text" class="form-control mk-dt-input" name="search" value="{$FILTER_SEARCH|escape:'html'}" placeholder="Search template name..." autocomplete="off" />
						</div>
						{if !$MK_DT_IS_TOOLS}
							<div class="mk-dt-filter-field">
								<label for="dtFeatureSelect" class="mk-dt-filter-label">Feature</label>
								<select id="dtFeatureSelect" name="feature" class="form-control mk-dt-input">
									<option value="">All Features</option>
									{foreach from=$FEATURES item=F}
										<option value="{$F}" {if $FILTER_FEATURE eq $F}selected{/if}>{$F}</option>
									{/foreach}
								</select>
							</div>
						{/if}
						<div class="mk-dt-filter-actions">
							<button type="submit" class="mk-dt-btn mk-dt-btn--primary">Search</button>
							<a class="mk-dt-btn mk-dt-btn--ghost mk-dt-btn--reset" href="index.php?module=DocumentTemplate&amp;view=List&amp;app=TOOLS" role="button">Reset</a>
						</div>
					</div>
				</form>
			</div>

			<div class="mk-dt-summary">
				Showing <strong>{$TOTAL_COUNT|default:0}</strong> template{if $TOTAL_COUNT|default:0 ne 1}s{/if}
				{if $FILTER_FEATURE} in <strong>{$FILTER_FEATURE|escape:'html'}</strong>{/if}
			</div>

			<div class="mk-dt-table-wrap">
				{if $ALL_ROWS|@count gt 0}
					<table class="table mk-dt-table">
						<thead>
							<tr>
								<th class="mk-dt-col-name">Name</th>
								<th class="mk-dt-col-feature">Feature</th>
								<th class="mk-dt-col-version">Version</th>
								<th class="mk-dt-col-desc">Description</th>
								<th class="mk-dt-col-time">Updated Time</th>
								<th class="mk-dt-col-user">Updated By</th>
								<th class="mk-dt-col-default">Default</th>
								<th class="mk-dt-col-actions">Actions</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$ALL_ROWS item=ROW}
								<tr class="mk-dt-row">
									<td class="mk-dt-col-name">
										<a class="mk-dt-name-link" href="index.php?module=DocumentTemplate&amp;view=Detail&amp;record={$ROW.templateid}&amp;app=TOOLS">
											{$ROW.templatename|escape:'html'}
										</a>
									</td>
									<td class="mk-dt-col-feature">
										<span class="mk-dt-feature-pill mk-dt-feature-pill--{$ROW.feature|lower|escape:'html'}">{$ROW.feature|escape:'html'}</span>
									</td>
									<td class="mk-dt-col-version">{$ROW.version}</td>
									<td class="mk-dt-col-desc">{$ROW.description|escape:'html'|truncate:100}</td>
									<td class="mk-dt-col-time">{$ROW.updatedtime|escape:'html'}</td>
									<td class="mk-dt-col-user">{$ROW.updatedby_name|escape:'html'}</td>
									<td class="mk-dt-col-default">
										{if $ROW.isdefault eq 1}
											<span class="mk-dt-default-yes">Yes</span>
										{else}
											<span class="mk-dt-default-no">—</span>
										{/if}
									</td>
									<td class="mk-dt-col-actions">
										<div class="mk-dt-row-actions">
											<a class="mk-dt-icon-btn" title="Copy" href="index.php?module=DocumentTemplate&amp;view=Edit&amp;copyFrom={$ROW.templateid}&amp;app=TOOLS">
												<i class="fa fa-copy"></i>
											</a>
											{if $ROW.isdefault neq 1}
												<a class="mk-dt-icon-btn" title="Edit" href="index.php?module=DocumentTemplate&amp;view=Edit&amp;record={$ROW.templateid}&amp;app=TOOLS">
													<i class="fa fa-pencil"></i>
												</a>
												<form method="post" action="index.php" class="mk-dt-delete-form">
													<input type="hidden" name="module" value="DocumentTemplate" />
													<input type="hidden" name="action" value="Delete" />
													<input type="hidden" name="record" value="{$ROW.templateid}" />
													<input type="hidden" name="app" value="TOOLS" />
													<button type="submit" class="mk-dt-icon-btn mk-dt-icon-btn--danger" title="Delete" onclick="return confirm('Delete template &quot;{$ROW.templatename|escape:'html'}&quot;?');">
														<i class="fa fa-trash"></i>
													</button>
												</form>
											{else}
												<span class="mk-dt-icon-btn mk-dt-icon-btn--disabled" title="Default template is protected">
													<i class="fa fa-lock"></i>
												</span>
											{/if}
										</div>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				{else}
					<div class="mk-dt-empty">
						No templates found for selected filters.
					</div>
				{/if}
			</div>
{if $MK_DT_IS_TOOLS}
		</div>
	</div>
{else}
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}
{/strip}
