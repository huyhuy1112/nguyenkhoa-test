{* Accounts / Danh sách chủ quán — SC-like Lovable list *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable mk-acc-page mk-acc-page--lovable mk-sc-page mk-sc-page--lovable" data-mk-acc-list="1">
		{include file="partials/AccountsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div id="mk-acc-kpi" class="mk-leads-kpi-grid" aria-label="{vtranslate('LBL_MK_ACC_METRICS', $MODULE)|default:'Chỉ số chủ quán'}"></div>

		<div class="mk-leads-segments-card" role="region" aria-label="{vtranslate('LBL_MK_SEGMENTS', 'ServiceContracts')}">
			<div class="mk-leads-segments-card__label">
				<span class="mk-leads-segments-card__icon" id="mk-acc-segments-icon" aria-hidden="true"></span> {vtranslate('LBL_MK_SEGMENTS', 'ServiceContracts')}
			</div>
			<div id="mk-acc-segments" class="mk-leads-segments"></div>
		</div>

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-acc-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-acc-search" type="search" placeholder="{vtranslate('LBL_MK_ACCOUNTS_SEARCH_PLACEHOLDER', $MODULE)}" autocomplete="off" />
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-acc-filters-toggle" aria-expanded="false">
					<span id="mk-acc-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-acc-filters-chev" aria-hidden="true"></span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-acc-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-acc-filter-summary"></div>
			</div>
			<div id="mk-acc-filters-panel" class="mk-leads-filters-panel" hidden></div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-acc-table-card mk-sc-table-card" role="region" aria-label="{vtranslate('LBL_MK_ACC_TABLE', $MODULE)|default:'Danh sách chủ quán'}">
			<div id="mk-acc-bulk" class="mk-leads-bulk-bar" hidden></div>
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-acc-table">
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-acc-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort mk-sc-th--group-a" scope="col" data-sort="received_date"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_SC_RECEIVED_DATE', 'ServiceContracts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort mk-sc-th--group-a" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_SC_FULL_NAME', 'ServiceContracts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_PHONE', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_BUSINESS_NOTE', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_FRANCHISE_STATUS', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_DATA_SOURCE', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-a" scope="col">{vtranslate('LBL_MK_SC_REFERRER', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_SC_CONTACT_STATUS', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-b" scope="col">{vtranslate('LBL_MK_COL_TAGS', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--group-b mk-leads-th--sort" scope="col" data-sort="last_touch"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_SC_RECENT_TOUCH', 'ServiceContracts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-sc-th--group-c" scope="col">{vtranslate('LBL_MK_SC_INTERACTION_MATERIALS', 'ServiceContracts')}</th>
							<th class="mk-leads-th mk-sc-th--actions" scope="col" style="width:56px;"><span class="sr-only">Thao tác</span></th>
						</tr>
					</thead>
					<tbody id="mk-acc-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-acc-pagination"></div>
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT')}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-org-page">
		<div class="mk-so-pos-layout" id="mk-acc-pos-layout">
			<div class="mk-so-pos-main">
				{assign var=MK_POS_SEARCH_ID value='mk-acc-pos-search'}
				{assign var=MK_POS_SEARCH_CLEAR_ID value='mk-acc-pos-search-clear'}
				{assign var=MK_POS_SEARCH_PLACEHOLDER value='Theo tên Tuibao'}
				{assign var=MK_POS_TITLE value='Danh sách chủ quán'}
				{include file="partials/MkSalesPosListHeader.tpl"|vtemplate_path:'Vtiger'}
				<div class="mk-so-table-card mk-org-table-card">
					{capture name=mk_acc_sales_lv}{include file="partials/MkSalesPosListContents.tpl"|vtemplate_path:'Vtiger'}{/capture}
					{$smarty.capture.mk_acc_sales_lv}
				</div>
			</div>
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-so-page mk-so-list-sales-root mk-org-page">
		{include file="AccountsOrgListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-org-table-card">
			{capture name=mk_acc_mkt_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_acc_mkt_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
