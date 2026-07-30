{* Contacts ListViewContents: SALES Lovable | MARKETING legacy shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-leads-page mk-leads-page--lovable mk-contacts-page">
		{include file="partials/ContactsMkListHeader.tpl"|vtemplate_path:$MODULE}

		<div id="mk-contacts-kpi" class="mk-leads-kpi-grid" aria-label="{vtranslate('LBL_MK_CONTACT_METRICS', 'Contacts')}"></div>

		<div class="mk-leads-segments-card" role="region" aria-label="{vtranslate('LBL_MK_SEGMENTS', 'Contacts')}">
			<div class="mk-leads-segments-card__label">
				<span class="mk-leads-segments-card__icon" id="mk-contacts-segments-icon" aria-hidden="true"></span> {vtranslate('LBL_MK_SEGMENTS', 'Contacts')}
			</div>
			<div id="mk-contacts-segments" class="mk-leads-segments"></div>
		</div>

		<div class="mk-leads-filters-card" role="region" aria-label="{vtranslate('LBL_FILTERS', 'Vtiger')}">
			<div class="mk-leads-filters-top">
				<div class="mk-leads-search">
					<span class="mk-leads-search__ic" id="mk-contacts-search-ic" aria-hidden="true"></span>
					<input class="mk-leads-search__input" id="mk-contacts-search" type="search" placeholder="{vtranslate('LBL_MK_CONTACTS_SEARCH_PLACEHOLDER', 'Contacts')}" autocomplete="off" />
				</div>
				<button type="button" class="mk-leads-btn mk-leads-btn--outline mk-leads-filters-toggle" id="mk-contacts-filters-toggle" aria-expanded="false">
					<span id="mk-contacts-filters-ic" aria-hidden="true"></span>
					{vtranslate('LBL_FILTERS', 'Vtiger')}
					<span id="mk-contacts-filters-chev" aria-hidden="true"></span>
				</button>
				<button type="button" class="mk-leads-reset" id="mk-contacts-reset" hidden>{vtranslate('LBL_CLEAR', 'Vtiger')}</button>
				<div class="mk-leads-filters-count" id="mk-contacts-filter-summary"></div>
			</div>
			<div id="mk-contacts-filters-panel" class="mk-leads-filters-panel" hidden></div>
		</div>

		<div class="mk-so-table-card mk-leads-table-card mk-contacts-table-card" role="region" aria-label="{vtranslate('LBL_MK_CONTACTS_TABLE', 'Contacts')}">
			<div id="mk-contacts-bulk" class="mk-leads-bulk-bar" hidden></div>
			<div class="mk-leads-table-scroll">
				<table class="mk-leads-table" id="mk-contacts-table">
					<thead>
						<tr>
							<th class="mk-leads-th mk-leads-th--check" scope="col">
								<label class="mk-leads-check">
									<input type="checkbox" id="mk-contacts-check-all" class="mk-leads-check__input" aria-label="{vtranslate('LBL_SELECT_ALL', 'Vtiger')}" />
									<span class="mk-leads-check__ui" aria-hidden="true"></span>
								</label>
							</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="converted_at"><span class="mk-leads-th__inner">Ngày chuyển<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="name"><span class="mk-leads-th__inner">{vtranslate('Contact Name', 'Contacts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('Office Phone', 'Contacts')}</th>
							<th class="mk-leads-th" scope="col">Địa chỉ</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_TAGS', 'Contacts')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('da_cap_bang', 'Contacts')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('da_cap_tai_khoan', 'Contacts')}</th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="thoigian_dangky"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_REG_TIME', 'Contacts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="thoigian_pcth"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_PCTH_TIME', 'Contacts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th mk-leads-th--sort" scope="col" data-sort="thoigian_mqbb"><span class="mk-leads-th__inner">{vtranslate('LBL_MK_COL_MQBB_TIME', 'Contacts')}<span class="mk-leads-sort-ic" aria-hidden="true"></span></span></th>
							<th class="mk-leads-th" scope="col">{vtranslate('Assigned To', 'Vtiger')}</th>
							<th class="mk-leads-th" scope="col">{vtranslate('LBL_MK_COL_NOTES', 'Contacts')}</th>
						</tr>
					</thead>
					<tbody id="mk-contacts-tbody"></tbody>
				</table>
			</div>
			<div class="mk-leads-pagination" id="mk-contacts-pagination"></div>
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-so-page mk-so-list-sales-root mk-contact-page">
		{include file="partials/ContactListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-contact-table-card">
			{capture name=mk_contact_sales_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_contact_sales_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
