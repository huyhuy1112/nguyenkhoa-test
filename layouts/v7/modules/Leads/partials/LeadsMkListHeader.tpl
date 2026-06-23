{* All Leads — matches b-ace.lovable.app/leads *}
{strip}
<div class="mk-leads-header">
	<header class="mk-leads-action-header" role="region" aria-label="{vtranslate('Leads', 'Leads')}">
		<div class="mk-leads-action-header__text">
			<h1 class="mk-leads-action-header__title">{vtranslate('LBL_MK_ALL_LEADS', 'Leads')}</h1>
			<p class="mk-leads-action-header__subtitle">{vtranslate('LBL_MK_LEADS_SUBTITLE', 'Leads')}</p>
		</div>
		<div class="mk-leads-action-header__actions">
			<div class="mk-leads-export-wrap" id="mk-leads-export-wrap">
				<button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-leads-export-btn" aria-haspopup="true" aria-expanded="false">
					<span class="mk-leads-btn__ic" id="mk-leads-export-ic" aria-hidden="true"></span>
					<span class="mk-leads-btn__txt">{vtranslate('LBL_EXPORT', 'Vtiger')}</span>
				</button>
				<div class="mk-leads-export-menu" id="mk-leads-export-menu" hidden>
					<button type="button" data-export="csv">CSV</button>
					<button type="button" data-export="print">PDF (print)</button>
				</div>
			</div>
			<button type="button" class="mk-leads-btn mk-leads-btn--primary" onclick="window.location.href='index.php?module=Leads&amp;view=Edit&amp;app=SALES'">
				<span class="mk-leads-btn__ic" id="mk-leads-create-ic" aria-hidden="true"></span>
				<span class="mk-leads-btn__txt">{vtranslate('LBL_ADD_RECORD', 'Leads')}</span>
			</button>
		</div>
	</header>
</div>
{/strip}
