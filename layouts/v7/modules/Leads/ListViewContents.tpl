{strip}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page">
		{include file="partials/LeadsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div class="mk-leads-filters-card" role="region" aria-label="Filters">
			<div class="mk-leads-filters-row">
				<div class="mk-leads-filter">
					<label class="mk-leads-filter__label">Source:</label>
					<button type="button" class="mk-leads-filter__btn">All <span class="mk-leads-filter__chev">▾</span></button>
				</div>
				<div class="mk-leads-filter">
					<label class="mk-leads-filter__label">Purchase:</label>
					<button type="button" class="mk-leads-filter__btn">All <span class="mk-leads-filter__chev">▾</span></button>
				</div>
				<div class="mk-leads-filter">
					<label class="mk-leads-filter__label">Tier:</label>
					<button type="button" class="mk-leads-filter__btn">All <span class="mk-leads-filter__chev">▾</span></button>
				</div>
				<div class="mk-leads-filter">
					<label class="mk-leads-filter__label">Program:</label>
					<button type="button" class="mk-leads-filter__btn">All <span class="mk-leads-filter__chev">▾</span></button>
				</div>
				<div class="mk-leads-filter">
					<label class="mk-leads-filter__label">Owner:</label>
					<button type="button" class="mk-leads-filter__btn">All <span class="mk-leads-filter__chev">▾</span></button>
				</div>
				<div class="mk-leads-filter mk-leads-filter--right">
					<label class="mk-leads-filter__label mk-leads-filter__label--muted">Stale only</label>
					<button type="button" class="mk-leads-toggle" id="mk-leads-toggle-stale" data-toggle="stale" role="switch" aria-checked="false">
						<span class="mk-leads-toggle__thumb"></span>
					</button>
				</div>
				<div class="mk-leads-filter mk-leads-filter--reset">
					<button type="button" class="mk-leads-reset" id="mk-leads-reset">Reset</button>
				</div>
			</div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card" role="region" aria-label="Leads table">
			<div class="mk-leads-table">
				<div class="mk-leads-tr mk-leads-tr--head">
					<div class="mk-leads-th">Lead</div>
					<div class="mk-leads-th">Type</div>
					<div class="mk-leads-th">Stage</div>
					<div class="mk-leads-th">Tier</div>
					<div class="mk-leads-th">Owner</div>
					<div class="mk-leads-th">Last touch</div>
					<div class="mk-leads-th">Next action</div>
					<div class="mk-leads-th mk-leads-th--right">Value</div>
					<div class="mk-leads-th mk-leads-th--center">Stale</div>
				</div>
				<div id="mk-leads-rows"></div>
			</div>
		</div>
	</div>
{/strip}
