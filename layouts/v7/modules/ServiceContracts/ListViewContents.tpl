{* ServiceContracts ListViewContents: SALES Lovable (Leads-like) + affiliate *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable mk-sc-page mk-sc-page--lovable">
		{include file="partials/ServiceContractsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div id="mk-sc-kpi" class="mk-leads-kpi-grid" aria-label="{vtranslate('LBL_MK_SC_METRICS', $MODULE)}"></div>

		<div class="mk-leads-segments-card" role="region" aria-label="{vtranslate('LBL_MK_SEGMENTS', $MODULE)}">
			<div class="mk-leads-segments-card__label">
				<span class="mk-leads-segments-card__icon" id="mk-sc-segments-icon" aria-hidden="true"></span> {vtranslate('LBL_MK_SEGMENTS', $MODULE)}
			</div>
			<div id="mk-sc-segments" class="mk-leads-segments"></div>
		</div>

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-sc-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-sc-search" type="search" placeholder="{vtranslate('LBL_MK_SC_SEARCH_PLACEHOLDER', $MODULE)}" autocomplete="off" />
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-sc-filters-toggle" aria-expanded="true">
					<span id="mk-sc-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-sc-filters-chev" aria-hidden="true"></span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-sc-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-sc-filter-summary"></div>
			</div>
			<div id="mk-sc-filters-panel" class="mk-leads-filters-panel"></div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-sc-table-card" role="region" aria-label="{vtranslate('LBL_MK_SC_TABLE', $MODULE)}">
			<div id="mk-sc-bulk" class="mk-leads-bulk-bar" hidden></div>
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-sc-table">
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-sc-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort mk-sc-th--group-a" scope="col" data-sort="received_date"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_SC_RECEIVED_DATE', $MODULE)}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort mk-sc-th--group-a" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_SC_FULL_NAME', $MODULE)}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_PHONE', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_BUSINESS_NOTE', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_FRANCHISE_STATUS', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_DATA_SOURCE', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_REFERRER', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_SC_CONTACT_STATUS', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_SC_INTERACTION_1', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_SC_INTERACTION_2', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_SC_INTERACTION_3', $MODULE)}</th>
							<th class="mk-leads-th mk-sc-th--group-c" scope="col">{vtranslate('LBL_MK_SC_INTERACTION_MATERIALS', $MODULE)}</th>
						</tr>
					</thead>
					<tbody id="mk-sc-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-sc-pagination"></div>
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
