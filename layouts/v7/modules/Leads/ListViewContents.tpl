{strip}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable">
		{include file="partials/LeadsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div id="mk-leads-kpi" class="mk-leads-kpi-grid" aria-label="{vtranslate('LBL_MK_LEAD_METRICS', 'Leads')}"></div>

		<div class="mk-leads-segments-card" role="region" aria-label="{vtranslate('LBL_MK_SEGMENTS', 'Leads')}">
			<div class="mk-leads-segments-card__label">
				<span class="mk-leads-segments-card__icon" id="mk-leads-segments-icon" aria-hidden="true"></span> {vtranslate('LBL_MK_SEGMENTS', 'Leads')}
			</div>
			<div id="mk-leads-segments" class="mk-leads-segments"></div>
			<button type="button" class="mk-leads-segments-save" id="mk-leads-save-segment"><span id="mk-leads-save-segment-ic" aria-hidden="true"></span> {vtranslate('LBL_MK_SAVE_CURRENT', 'Leads')}</button>
		</div>

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-leads-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-leads-search" type="search" placeholder="{vtranslate('LBL_MK_LEADS_SEARCH_PLACEHOLDER', 'Leads')}" autocomplete="off" />
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-leads-filters-toggle" aria-expanded="true">
					<span id="mk-leads-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-leads-filters-chev" aria-hidden="true"></span>
					<span id="mk-leads-filter-count" class="mk-leads-filter-badge" hidden>0</span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-leads-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-leads-filter-summary"></div>
			</div>
			<div id="mk-leads-filters-panel" class="mk-leads-filters-panel"></div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card" role="region" aria-label="{vtranslate('LBL_MK_LEADS_TABLE', 'Leads')}">
			<div id="mk-leads-bulk" class="mk-leads-bulk-bar" hidden></div>
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-leads-table">
					<colgroup>
						<col class="mk-leads-col mk-leads-col--check" />
						<col class="mk-leads-col mk-leads-col--lead" />
						<col class="mk-leads-col mk-leads-col--phone" />
						<col class="mk-leads-col mk-leads-col--area" />
						<col class="mk-leads-col mk-leads-col--source" />
						<col class="mk-leads-col mk-leads-col--ctype" />
						<col class="mk-leads-col mk-leads-col--stage" />
						<col class="mk-leads-col mk-leads-col--tier" />
						<col class="mk-leads-col mk-leads-col--owner" />
						<col class="mk-leads-col mk-leads-col--tags" />
						<col class="mk-leads-col mk-leads-col--touch" />
						<col class="mk-leads-col mk-leads-col--next" />
						<col class="mk-leads-col mk-leads-col--support" />
					</colgroup>
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-leads-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_LEAD', 'Leads')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_PHONE', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_AREA', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_SOURCE', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CUSTOMER_TYPE', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_STAGE', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_TIER', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_OWNER', 'Leads')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_TAGS', 'Leads')}</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="last_touch"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_LAST_TOUCH', 'Leads')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_NEXT_ACTION', 'Leads')}</th>
							<th class="mk-leads-th mk-leads-th--center" scope="col">{vtranslate('LBL_MK_COL_SUPPORT', 'Leads')}</th>
						</tr>
					</thead>
					<tbody id="mk-leads-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-leads-pagination"></div>
		</div>
	</div>
{/strip}
