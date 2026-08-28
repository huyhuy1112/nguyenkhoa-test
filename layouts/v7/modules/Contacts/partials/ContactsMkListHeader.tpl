{* All Contacts — Lovable layout (Leads / Potentials) *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('Contacts', 'Contacts')}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_CONTACTS', 'Contacts')}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_CONTACTS_SUBTITLE', 'Contacts')}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<a class="mk-leads-btn mk-leads-btn--outline" href="index.php?module=Contacts&amp;view=Import&amp;app=SALES" id="mk-contacts-import-btn">
				<span class="mk-leads-btn__ic" id="mk-contacts-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Contacts&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-contacts-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', 'Contacts')}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
