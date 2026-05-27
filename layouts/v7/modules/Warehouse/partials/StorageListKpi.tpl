{* Storage KPI strip — real counts from vtiger_warehouse_stock + GoodsIssue outbound *}
{strip}
<div class="mk-wh-kpi-strip" role="group" aria-label="Storage summary">
	<div class="mk-wh-kpi-card">
		<div class="mk-wh-kpi-card__head">
			<span class="mk-wh-kpi-card__icon" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='KPI_SKU'}</span>
			<span class="mk-wh-kpi-card__label">SKU</span>
		</div>
		<p class="mk-wh-kpi-card__value" id="mkWhKpiSku">{$STORAGE_STATS_SKU_DISPLAY|escape:'html'}</p>
		<p class="mk-wh-kpi-card__hint">Mã còn tồn kho</p>
	</div>
	<div class="mk-wh-kpi-card">
		<div class="mk-wh-kpi-card__head">
			<span class="mk-wh-kpi-card__icon" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='KPI_VALUE'}</span>
			<span class="mk-wh-kpi-card__label">Giá trị tồn</span>
		</div>
		<p class="mk-wh-kpi-card__value mk-wh-kpi-card__value--money" id="mkWhKpiValue">{$STORAGE_STATS_VALUE_DISPLAY|escape:'html'}</p>
		<p class="mk-wh-kpi-card__hint">Tổng giá trị hàng đang có</p>
	</div>
	<a class="mk-wh-kpi-card mk-wh-kpi-card--warn" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY&amp;low_stock=1" title="Xem dòng low stock">
		<div class="mk-wh-kpi-card__head">
			<span class="mk-wh-kpi-card__icon" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='KPI_WARNING'}</span>
			<span class="mk-wh-kpi-card__label">Cảnh báo low stock</span>
		</div>
		<p class="mk-wh-kpi-card__value mk-wh-kpi-card__value--warn" id="mkWhKpiLow">{$STORAGE_STATS_LOW_DISPLAY|escape:'html'}</p>
		<p class="mk-wh-kpi-card__hint">Available &lt; {$LOW_STOCK_THRESHOLD|escape:'html'}</p>
	</a>
	<a class="mk-wh-kpi-card" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY" title="Xem Outbound">
		<div class="mk-wh-kpi-card__head">
			<span class="mk-wh-kpi-card__icon" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='KPI_MOVEMENTS'}</span>
			<span class="mk-wh-kpi-card__label">Movements</span>
		</div>
		<p class="mk-wh-kpi-card__value" id="mkWhKpiMovements">{$STORAGE_STATS_MOVEMENTS_DISPLAY|escape:'html'}</p>
		<p class="mk-wh-kpi-card__hint">Phiếu xuất kho (Outbound)</p>
	</a>
</div>
{/strip}
