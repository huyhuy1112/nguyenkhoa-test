{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="mk-wh-detail-hero__left">
		<div class="mk-wh-detail-hero__identity">
			<div class="mk-wh-detail-hero__icon" aria-hidden="true">
				{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='STORAGE'}
			</div>
			<div class="mk-wh-detail-hero__text">
				<h1 class="mk-wh-detail-hero__title">
					<span class="recordLabel" title="{$STOCK.product_name_display|escape:'html'}">{$STOCK.product_name_display|escape:'html'}</span>
				</h1>
				<p class="mk-wh-detail-hero__subtitle">Aggregated storage from Inbound. Quantities and last price are derived; edit only warehouse fields on Edit.</p>
				<div class="mk-wh-detail-hero__meta">
					<span class="mk-wh-detail-status-pill">Active stock</span>
					{if $STOCK.code}<span class="mk-gi-chip mk-wh-detail-hero__code">{$STOCK.code|escape:'html'}</span>{/if}
					{if $IS_LEGACY_IDENTITY}
						<span class="mk-gi-chip mk-gi-chip--type">Legacy name-based</span>
					{else}
						<span class="mk-gi-chip mk-gi-chip--type">Catalog linked{if $CATALOG_PRODUCT_ID > 0} · P:{$CATALOG_PRODUCT_ID}{/if}</span>
					{/if}
					<span class="mk-gi-chip mk-gi-chip--type">{$TYPE_LABEL|escape:'html'}</span>
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-8">
		<h3 style="margin-top:0;">{$STOCK.product_name_display|escape:'html'}</h3>
	</div>
{/if}
{/strip}
