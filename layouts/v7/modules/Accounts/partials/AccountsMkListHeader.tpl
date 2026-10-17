{* Accounts / Danh sách chủ quán — không kết nối Google Sheet *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('LBL_MK_ALL_ACCOUNTS', $MODULE)}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_ACCOUNTS', $MODULE)}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_ACCOUNTS_SUBTITLE', $MODULE)}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<a class="mk-leads-btn mk-leads-btn--outline" href="javascript:void(0)" id="mk-acc-import-btn" data-mk-quick-import="1" data-module="Accounts" title="Import Excel — chọn file là xong">
				<span class="mk-leads-btn__ic" id="mk-acc-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<input type="file" id="mk-acc-import-file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" class="hide" tabindex="-1" aria-hidden="true" />
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Accounts&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-acc-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', $MODULE)}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
