{* All Opportunities — same Lovable layout as Leads list *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('Potentials', 'Potentials')}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_OPPORTUNITIES', 'Potentials')}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_OPPS_SUBTITLE', 'Potentials')}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-opps-desk-qr-btn" title="Check-in lớp offline bằng mã QR">
				<span class="mk-leads-btn__ic" id="mk-opps-desk-qr-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">Check-in SĐT</span>
			</button>
			<button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-opps-edubit-sync-btn" title="{vtranslate('LBL_MK_EDUBIT_SYNC_HINT', 'Contacts')}">
				<span class="mk-leads-btn__ic" id="mk-opps-edubit-sync-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_MK_EDUBIT_SYNC', 'Contacts')}</span>
			</button>
			<a class="mk-leads-btn mk-leads-btn--outline" href="index.php?module=Potentials&amp;view=Import&amp;app=SALES" id="mk-opps-import-btn">
				<span class="mk-leads-btn__ic" id="mk-opps-import-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_IMPORT', 'Vtiger')}</span>
			</a>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Potentials&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-opps-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', 'Potentials')}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
