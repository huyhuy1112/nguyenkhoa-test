{* Inbound detail actions: Edit / Delete / Back to list *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="detailViewButtoncontainer mk-gr-detail-actions mk-gr-detail-hero__actions">
		<div class="pull-right btn-toolbar mk-gr-detail-actions__toolbar">
			<div class="btn-group mk-gr-detail-actions__group">
				<a class="btn btn-default mk-gr-detail-btn mk-gr-detail-btn--primary" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;record={$RECORD_DATA.receiptid}&amp;app=INVENTORY">
					<span class="mk-gr-detail-btn__ic" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}</span>
					<span class="mk-gr-detail-btn__txt">Edit</span>
				</a>
				<a class="btn btn-default mk-gr-detail-btn mk-gr-detail-btn--danger" href="index.php?module=GoodsReceipt&amp;action=Delete&amp;record={$RECORD_DATA.receiptid}&amp;app=INVENTORY" onclick="return confirm('Delete this inbound receipt? Stock will be reversed.');">
					<span class="mk-gr-detail-btn__ic" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}</span>
					<span class="mk-gr-detail-btn__txt">Delete</span>
				</a>
				<a class="btn btn-default mk-gr-detail-btn mk-gr-detail-btn--ghost" href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">
					<span class="mk-gr-detail-btn__txt">Back to list</span>
				</a>
			</div>
		</div>
	</div>
{/if}
{/strip}
