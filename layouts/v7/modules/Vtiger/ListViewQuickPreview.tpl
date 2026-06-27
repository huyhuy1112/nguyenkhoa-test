{*+**********************************************************************************
 * List Quick Preview — modern side panel (all modules)
 ************************************************************************************}
<div class="quickPreview mk-quick-preview">
    <input type="hidden" name="sourceModuleName" id="sourceModuleName" value="{$MODULE_NAME}" />
    <input type="hidden" id="nextRecordId" value="{$NEXT_RECORD_ID}">
    <input type="hidden" id="previousRecordId" value="{$PREVIOUS_RECORD_ID}">

    <div class="quick-preview-modal modal-content mk-quick-preview__shell">
        <div class="modal-body mk-quick-preview__body">
            <div class="mk-quick-preview__topbar">
                <div class="mk-quick-preview__topbar-main">
                    {include file="ListViewQuickPreviewHeaderTitle.tpl"|vtemplate_path:$MODULE_NAME MODULE=$MODULE_NAME MODULE_MODEL=$MODULE_MODEL RECORD=$RECORD}
                </div>
                <button class="close mk-quick-preview__close" aria-hidden="true" data-dismiss="modal" type="button" title="{vtranslate('LBL_CLOSE')}">&times;</button>
            </div>

            <div class="quickPreviewActions mk-quick-preview__actions">
                {assign var=MK_QP_APP value=(isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY) ? $SELECTED_MENU_CATEGORY : ''}
                <button class="btn btn-xs mk-quick-preview__btn-details" type="button" onclick="window.location.href = '{$RECORD->getFullDetailViewUrl()}&app={$MK_QP_APP}'">
                    <i class="fa fa-external-link"></i> {vtranslate('LBL_VIEW_DETAILS', $MODULE_NAME)}
                </button>
                {if $NAVIGATION}
                    <div class="mk-quick-preview__nav">
                        <button class="btn btn-xs mk-quick-preview__nav-btn" id="quickPreviewPreviousRecordButton" data-record="{$PREVIOUS_RECORD_ID}" data-app="{$MK_QP_APP}" {if empty($PREVIOUS_RECORD_ID)} disabled="disabled" {/if} title="{vtranslate('LBL_PREVIOUS', $MODULE_NAME)}">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-xs mk-quick-preview__nav-btn" id="quickPreviewNextRecordButton" data-record="{$NEXT_RECORD_ID}" data-app="{$MK_QP_APP}" {if empty($NEXT_RECORD_ID)} disabled="disabled" {/if} title="{vtranslate('LBL_NEXT', $MODULE_NAME)}">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                {/if}
            </div>

            <div class="quickPreviewSummary mk-quick-preview__card mk-quick-preview__summary">
                <div class="mk-quick-preview__card-head">{vtranslate('LBL_SUMMARY', $MODULE_NAME)}</div>
                <div class="mk-quick-preview__fields">
                    {foreach item=FIELD_MODEL key=FIELD_NAME from=$SUMMARY_RECORD_STRUCTURE['SUMMARY_FIELDS']}
                        {if $FIELD_MODEL->get('name') neq 'modifiedtime' && $FIELD_MODEL->get('name') neq 'createdtime'}
                            <div class="mk-quick-preview__field summaryViewEntries">
                                <div class="mk-quick-preview__field-label fieldLabel">
                                    <label>{vtranslate($FIELD_MODEL->get('label'),$MODULE_NAME)}</label>
                                </div>
                                <div class="mk-quick-preview__field-value fieldValue">
                                    <span class="value textOverflowEllipsis" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'}style="word-wrap: break-word;"{/if}>
                                        {include file=$FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName()|@vtemplate_path:$MODULE_NAME FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}
                                    </span>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            </div>

            <div class="engagementsContainer mk-quick-preview__card mk-quick-preview__section">
                {include file="ListViewQuickPreviewSectionHeader.tpl"|vtemplate_path:$MODULE_NAME TITLE="{vtranslate('LBL_UPDATES',$MODULE_NAME)}"}
                {include file="RecentActivities.tpl"|vtemplate_path:$MODULE_NAME}
            </div>

            {if $MODULE_MODEL->isCommentEnabled()}
                <div class="quickPreviewComments mk-quick-preview__card mk-quick-preview__section">
                    {include file="ListViewQuickPreviewSectionHeader.tpl"|vtemplate_path:$MODULE_NAME TITLE="{vtranslate('LBL_RECENT_COMMENTS',$MODULE_NAME)}"}
                    {include file="QuickViewCommentsList.tpl"|vtemplate_path:$MODULE_NAME}
                </div>
            {/if}
        </div>
    </div>
</div>
