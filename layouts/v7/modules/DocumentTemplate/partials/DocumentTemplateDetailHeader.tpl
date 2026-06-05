{* DocumentTemplate detail header (TOOLS) *}
{strip}
<div class="mk-dt-detail-header">
	<nav class="mk-dt-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php?module=Home&amp;view=DashBoard&amp;app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
		<span class="mk-dt-breadcrumb__sep">&gt;</span>
		<a href="index.php?module=DocumentTemplate&amp;view=List&amp;app=TOOLS">Templates</a>
		<span class="mk-dt-breadcrumb__sep">&gt;</span>
		<span>{$RECORD_DATA.templatename|escape:'html'}</span>
	</nav>
	<div class="mk-dt-detail-hero">
		<div class="mk-dt-detail-hero__main">
			<div class="mk-dt-detail-hero__identity">
				<div class="mk-dt-detail-hero__icon" aria-hidden="true">
					<i class="fa fa-file-text-o"></i>
				</div>
				<div class="mk-dt-detail-hero__text">
					<h1 class="mk-dt-detail-hero__title">{$RECORD_DATA.templatename|escape:'html'}</h1>
					<div class="mk-dt-detail-hero__meta">
						<span class="mk-dt-feature-pill mk-dt-feature-pill--{$RECORD_DATA.feature|lower|escape:'html'}">{$RECORD_DATA.feature|escape:'html'}</span>
						<span class="mk-dt-detail-hero__version">v{$RECORD_DATA.version}</span>
						{if $RECORD_DATA.isdefault eq 1}
							<span class="mk-dt-default-badge">Default</span>
						{/if}
					</div>
				</div>
			</div>
			<div class="mk-dt-detail-hero__actions">
				<a class="mk-dt-btn mk-dt-btn--ghost" href="index.php?module=DocumentTemplate&amp;view=Edit&amp;copyFrom={$RECORD_DATA.templateid}&amp;app=TOOLS">
					<i class="fa fa-copy" aria-hidden="true"></i><span>Copy</span>
				</a>
				{if $RECORD_DATA.isdefault neq 1}
					<a class="mk-dt-btn mk-dt-btn--primary" href="index.php?module=DocumentTemplate&amp;view=Edit&amp;record={$RECORD_DATA.templateid}&amp;app=TOOLS">
						<i class="fa fa-pencil" aria-hidden="true"></i><span>{vtranslate('LBL_EDIT', 'Vtiger')}</span>
					</a>
					<form method="post" action="index.php" class="mk-dt-delete-form">
						<input type="hidden" name="module" value="DocumentTemplate" />
						<input type="hidden" name="action" value="Delete" />
						<input type="hidden" name="record" value="{$RECORD_DATA.templateid}" />
						<input type="hidden" name="app" value="TOOLS" />
						<button type="submit" class="mk-dt-btn mk-dt-btn--danger" onclick="return confirm('Delete this template?');">
							<i class="fa fa-trash" aria-hidden="true"></i><span>{vtranslate('LBL_DELETE', 'Vtiger')}</span>
						</button>
					</form>
				{else}
					<span class="mk-dt-btn mk-dt-btn--disabled" title="Default template is protected">
						<i class="fa fa-lock" aria-hidden="true"></i><span>Protected</span>
					</span>
				{/if}
			</div>
		</div>
	</div>
</div>
{/strip}
