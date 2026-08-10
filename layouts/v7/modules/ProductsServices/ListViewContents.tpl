{* ProductsServices ListViewContents: Kho / Sales list card shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	{assign var=_mkPsInventory value=((isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY'))}
	<div class="mk-so-page mk-so-list-sales-root mk-ps-page mk-ps-list-v2{if $_mkPsInventory} mk-ps-inventory-page{/if}">
		{include file="partials/ProductsServicesListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-ps-table-card">
			<div id="mk-ps-bulk" class="mk-ps-bulk-bar" hidden></div>
			{capture name=mk_ps_list_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_ps_list_lv}
			<script type="text/javascript">
			(function () {
				var canonical = ['productsservicesname', 'sku', 'product_group', 'item_type', 'price', 'price_tuibao', 'unit'];
				var input = document.querySelector('#listViewContent input[name="list_headers"]');
				if (input) {
					input.value = JSON.stringify(canonical);
				}
				document.body.classList.add('mk-ps-list-v2');
			})();
			</script>
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
