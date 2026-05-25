{* Outbound detail actions: Edit / Delete / Back to list *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="detailViewButtoncontainer mk-go-detail-actions mk-go-detail-hero__actions">
		<div class="pull-right btn-toolbar mk-go-detail-actions__toolbar">
			<div class="btn-group mk-go-detail-actions__group">
				<a class="btn btn-default mk-go-detail-btn mk-go-detail-btn--primary" href="index.php?module=GoodsIssue&amp;view=Edit&amp;record={$RECORD_DATA.issueid}&amp;app=INVENTORY">
					<span class="mk-go-detail-btn__ic" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}</span>
					<span class="mk-go-detail-btn__txt">Edit</span>
				</a>
				<a class="btn btn-default mk-go-detail-btn mk-go-detail-btn--danger" href="index.php?module=GoodsIssue&amp;action=Delete&amp;record={$RECORD_DATA.issueid}&amp;app=INVENTORY" onclick="return confirm('Delete this outbound issue and restore stock?');">
					<span class="mk-go-detail-btn__ic" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}</span>
					<span class="mk-go-detail-btn__txt">Delete</span>
				</a>
				<a class="btn btn-default mk-go-detail-btn mk-go-detail-btn--ghost" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">
					<span class="mk-go-detail-btn__txt">Back to list</span>
				</a>
			</div>
		</div>
	</div>
{/if}
{/strip}
