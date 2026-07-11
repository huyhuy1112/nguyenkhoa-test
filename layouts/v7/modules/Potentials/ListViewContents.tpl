{strip}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable mk-opps-page">
		{include file="partials/PotentialsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div id="mk-opps-kpi" class="mk-leads-kpi-grid" aria-label="{vtranslate('LBL_MK_OPP_METRICS', 'Potentials')}"></div>

		<div class="mk-leads-segments-card" role="region" aria-label="{vtranslate('LBL_MK_SEGMENTS', 'Potentials')}">
			<div class="mk-leads-segments-card__label">
				<span class="mk-leads-segments-card__icon" id="mk-opps-segments-icon" aria-hidden="true"></span> {vtranslate('LBL_MK_SEGMENTS', 'Potentials')}
			</div>
			<div id="mk-opps-segments" class="mk-leads-segments"></div>
		</div>

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-opps-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-opps-search" type="search" placeholder="{vtranslate('LBL_MK_OPPS_SEARCH_PLACEHOLDER', 'Potentials')}" autocomplete="off" />
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-opps-filters-toggle" aria-expanded="true">
					<span id="mk-opps-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-opps-filters-chev" aria-hidden="true"></span>
					<span id="mk-opps-filter-count" class="mk-leads-filter-badge" hidden>0</span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-opps-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-opps-filter-summary"></div>
			</div>
			<div id="mk-opps-filters-panel" class="mk-leads-filters-panel"></div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-opps-table-card" role="region" aria-label="{vtranslate('LBL_MK_OPPS_TABLE', 'Potentials')}">
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-opps-table">
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-opps-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('LBL_POTENTIAL_NAME', 'Potentials')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_ACCOUNT_NAME', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_ORDER_CATEGORY', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_SALES_STAGE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_AREA', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_SOURCE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CUSTOMER_TYPE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CLASS_TAG', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_MATERIAL_TAG', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_FRANCHISE_TAG', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CONFIRM_TAG', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_AMOUNT', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_ASSIGNED_TO', 'Potentials')}</th>
						</tr>
					</thead>
					<tbody id="mk-opps-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-opps-pagination"></div>
		</div>
	</div>
{/strip}
