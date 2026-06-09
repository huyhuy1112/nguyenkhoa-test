{strip}
	<div class="modal-footer mk-qc-sales-footer">
		{if $BUTTON_NAME neq null}
			{assign var=BUTTON_LABEL value=$BUTTON_NAME}
		{else}
			{assign var=BUTTON_LABEL value={vtranslate('LBL_SAVE', $MODULE)}}
		{/if}
		{assign var="EDIT_VIEW_URL" value=$MODULE_MODEL->getCreateRecordUrl()}
		<button class="btn btn-default mk-qc-sales-footer__full" id="goToFullForm" data-edit-view-url="{$EDIT_VIEW_URL}" type="button">{vtranslate('LBL_GO_TO_FULL_FORM', $MODULE)}</button>
		<div class="mk-qc-sales-footer__actions">
			<a href="#" class="mk-qc-sales-footer__cancel cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
			<button {if $BUTTON_ID neq null} id="{$BUTTON_ID}" {/if} class="btn btn-success mk-qc-sales-footer__save" type="submit" name="saveButton">{$BUTTON_LABEL}</button>
		</div>
	</div>
{/strip}
