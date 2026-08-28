{* All ServiceContracts — Khách chuyển nhượng (Leads-like) *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate($MODULE, $MODULE)}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_SC', $MODULE)}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_SC_SUBTITLE', $MODULE)}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<a class="mk-leads-btn mk-leads-btn--outline" href="index.php?module=ServiceContracts&amp;view=Import&amp;app=SALES" id="mk-sc-import-btn">
				<span class="mk-leads-btn__ic" id="mk-sc-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=ServiceContracts&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-sc-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', $MODULE)}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
