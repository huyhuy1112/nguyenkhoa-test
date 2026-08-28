{* ProductsServices ListViewContents: BA / KiotViet-style content (filter left + table) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	{assign var=_mkPsInventory value=((isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY'))}
	<div class="mk-so-page mk-so-list-sales-root mk-ps-page mk-ps-list-v2 mk-ps-ba-list{if $_mkPsInventory} mk-ps-inventory-page{/if}">
		{include file="partials/ProductsServicesListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-ps-ba-layout mk-ps-filters-collapsed">
			<aside id="mk-ps-filters" class="mk-ps-filters" aria-label="Bộ lọc hàng hoá" aria-hidden="true">
				<div class="mk-ps-filters__head">
					<span class="mk-ps-filters__title">Bộ lọc</span>
					<button type="button" class="mk-ps-filters__reset" id="mk-ps-filters-reset" hidden>Xóa lọc</button>
				</div>
				<div id="mk-ps-filters-body" class="mk-ps-filters__body"></div>
			</aside>
			<div class="mk-ps-ba-main">
				<div class="mk-so-table-card mk-ps-table-card">
					<div id="mk-ps-bulk" class="mk-ps-bulk-bar" hidden></div>
					{capture name=mk_ps_list_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
					{$smarty.capture.mk_ps_list_lv}
					<script type="text/javascript">
					(function () {
						var canonical = ['sku', 'productsservicesname', 'price_lt_1m', 'price_tuibao'];
						var input = document.querySelector('#listViewContent input[name="list_headers"]');
						if (input) {
							input.value = JSON.stringify(canonical);
						}
						document.body.classList.add('mk-ps-list-v2', 'mk-ps-ba-list');
						document.documentElement.classList.add('mk-ps-ba-list');
					})();
					</script>
				</div>
			</div>
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
