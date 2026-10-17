{* All Contacts — Lovable layout (Leads / Potentials) *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('Contacts', 'Contacts')}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_CONTACTS', 'Contacts')}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_CONTACTS_SUBTITLE', 'Contacts')}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-contacts-edubit-sync-btn" title="{vtranslate('LBL_MK_EDUBIT_SYNC_HINT', 'Contacts')}">
				<span class="mk-leads-btn__ic" id="mk-contacts-edubit-sync-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_MK_EDUBIT_SYNC', 'Contacts')}</span>
			</button>
			<a class="mk-leads-btn mk-leads-btn--outline" href="javascript:void(0)" id="mk-contacts-import-btn" data-mk-quick-import="1" data-module="Contacts" title="Import Excel Khách lẻ / Miutea — chọn file là xong">
				<span class="mk-leads-btn__ic" id="mk-contacts-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<input type="file" id="mk-contacts-import-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hide" tabindex="-1" aria-hidden="true" />
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Contacts&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-contacts-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', 'Contacts')}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
