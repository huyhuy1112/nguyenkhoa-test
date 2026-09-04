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
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-opps-filters-toggle" aria-expanded="false">
					<span id="mk-opps-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-opps-filters-chev" aria-hidden="true"></span>
					<span id="mk-opps-filter-count" class="mk-leads-filter-badge" hidden>0</span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-opps-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-opps-filter-summary"></div>
			</div>
			<div id="mk-opps-filters-panel" class="mk-leads-filters-panel" hidden></div>
		</div>

		<div class="mk-leads-pipeline-bar" id="mk-opps-pipeline-bar">
			<div class="mk-leads-view-toggle" role="tablist" aria-label="Kiểu xem" data-mode="table" id="mk-opps-view-toggle">
				<span class="mk-leads-view-toggle__glow" aria-hidden="true"></span>
				<span class="mk-leads-view-toggle__thumb" aria-hidden="true"></span>
				<button type="button" class="mk-leads-view-btn is-active" data-opps-view="table" id="mk-opps-view-table" role="tab" aria-selected="true">
					<svg class="mk-leads-view-btn__ic" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M4 6.5h16M4 12h16M4 17.5h16" stroke="currentColor" stroke-width="1.85" stroke-linecap="round"/>
					</svg>
					<span>Bảng</span>
				</button>
				<button type="button" class="mk-leads-view-btn" data-opps-view="kanban" id="mk-opps-view-kanban" role="tab" aria-selected="false" disabled title="Sắp có">
					<svg class="mk-leads-view-btn__ic" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<rect x="3.5" y="4.5" width="5.5" height="15" rx="1.5" stroke="currentColor" stroke-width="1.75"/>
						<rect x="9.25" y="4.5" width="5.5" height="10" rx="1.5" stroke="currentColor" stroke-width="1.75"/>
						<rect x="15" y="4.5" width="5.5" height="13" rx="1.5" stroke="currentColor" stroke-width="1.75"/>
					</svg>
					<span>Kanban</span>
				</button>
			</div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-opps-table-card" role="region" aria-label="{vtranslate('LBL_MK_OPPS_TABLE', 'Potentials')}">
			<div id="mk-opps-bulk" class="mk-leads-bulk-bar" hidden></div>
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-opps-table">
					<colgroup>
						<col class="mk-leads-col mk-leads-col--check" />
						<col class="mk-leads-col mk-leads-col--created" />
						<col class="mk-leads-col mk-leads-col--lead" />
						<col class="mk-leads-col mk-leads-col--phone" />
						<col class="mk-leads-col mk-leads-col--area" />
						<col class="mk-leads-col mk-leads-col--address" />
						<col class="mk-leads-col mk-leads-col--source" />
						<col class="mk-leads-col mk-leads-col--ctype" />
						<col class="mk-leads-col mk-leads-col--biz" />
						<col class="mk-leads-col mk-leads-col--owner" />
						<col class="mk-leads-col mk-leads-col--tags" />
						<col class="mk-leads-col mk-leads-col--touch" />
						<col class="mk-leads-col mk-leads-col--next" />
						<col class="mk-leads-col" style="min-width:120px" />
						<col class="mk-leads-col" style="min-width:140px" />
						<col class="mk-leads-col" style="min-width:120px" />
					</colgroup>
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-opps-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="converted_at"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_CONVERTED_AT', 'Potentials')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('LBL_ACCOUNT_NAME', 'Potentials')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_PHONE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_AREA', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_ADDRESS', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_SOURCE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CUSTOMER_TYPE', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">Mô hình kinh doanh</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_ASSIGNED_TO', 'Potentials')}</th>
							<th class="mk-leads-th mk-leads-col--tags" scope="col">{vtranslate('LBL_MK_COL_TAGS', 'Potentials')}</th>
							<th class="mk-leads-th mk-leads-col--touch" scope="col">Tương tác gần đây</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_NEXT_ACTION', 'Potentials')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_CONFIRM_TAG', 'Potentials')}</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="confirmed_at"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_JOIN_AT', 'Potentials')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_NOTES', 'Potentials')}</th>
						</tr>
					</thead>
					<tbody id="mk-opps-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-opps-pagination"></div>
		</div>
	</div>
{/strip}
