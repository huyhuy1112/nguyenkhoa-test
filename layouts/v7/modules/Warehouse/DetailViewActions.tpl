{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="detailViewButtoncontainer mk-wh-detail-actions mk-wh-detail-hero__actions">
		<div class="pull-right btn-toolbar mk-wh-detail-actions__toolbar">
			<div class="btn-group mk-wh-detail-actions__group">
				<a class="btn btn-default mk-wh-detail-btn mk-wh-detail-btn--primary" href="index.php?module=Warehouse&amp;view=Edit&amp;record={$STOCK.stockid}&amp;app=INVENTORY">
					<span class="mk-wh-detail-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}</span>
					<span class="mk-wh-detail-btn__txt">Edit warehouse</span>
				</a>
				{if $CAN_DELETE}
					<a class="btn btn-default mk-wh-detail-btn mk-wh-detail-btn--danger" href="index.php?module=Warehouse&amp;action=Delete&amp;record={$STOCK.stockid}&amp;app=INVENTORY" onclick="return confirm('Delete this empty storage row?');">
						<span class="mk-wh-detail-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}</span>
						<span class="mk-wh-detail-btn__txt">Delete</span>
					</a>
				{/if}
				<a class="btn btn-default mk-wh-detail-btn mk-wh-detail-btn--ghost" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">
					<span class="mk-wh-detail-btn__txt">Back to list</span>
				</a>
			</div>
		</div>
	</div>
{/if}
{/strip}
