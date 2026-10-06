{* Accounts / Tuibao tiềm năng — Leads-like header + Google Sheet *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('LBL_MK_ALL_ACCOUNTS', $MODULE)}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_ACCOUNTS', $MODULE)}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_ACCOUNTS_SUBTITLE', $MODULE)}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<a class="mk-leads-btn mk-leads-btn--outline" href="index.php?module=Accounts&amp;view=Import&amp;app=SALES" id="mk-acc-import-btn" data-mk-import="1" data-module="Accounts" data-app="SALES">
				<span class="mk-leads-btn__ic" id="mk-acc-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-acc-sheet-btn" title="Cấu hình Google Sheet (Admin)">
				<span class="mk-leads-btn__txt">Google Sheet</span>
			</button>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Accounts&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-acc-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', $MODULE)}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
